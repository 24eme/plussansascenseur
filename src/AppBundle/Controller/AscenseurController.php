<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;

use AppBundle\Document\Ascenseur;
use AppBundle\Document\Photo;
use AppBundle\Document\Thumbnail;
use AppBundle\Document\Signalement;

use AppBundle\Type\AscenseurType;
use AppBundle\Type\PhotoType;
use AppBundle\Type\FollowerType;
use AppBundle\Type\SignalementType;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use AppBundle\Lib\AdresseDataGouvApi;

class AscenseurController extends Controller
{
    /** @var int $perpage Le nombre par page */
    const PERPAGE = 20;

    /**
     * On liste les derniers ascenseurs mis à jours
     *
     * @Route("/listing", name="listing")
     *
     * @param Request $request La requête
     * @return Response La réponse
     */
    public function listingAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $limit = (int) $request->query->get('perpage', self::PERPAGE);
        $page = (int) $request->query->get('page', 1);

        $validator = new Assert\Type('int');

        $errors = $this->get('validator')->validate(
            $page,
            $validator
        );

        if (0 !== count($errors) || $page < 1) {
            $page = 1;
        }

        $skip = ($page - 1) * $limit;

        $ascenseurs = $dm->getRepository(Ascenseur::class)
                         ->paginate($limit, $skip);

        $pages = ceil($dm->getRepository(Ascenseur::class)->count() / $limit);

        $query_string = ($limit === self::PERPAGE) ? '' : "perpage=$limit&";

        return $this->render('default/listing.html.twig', compact(
            'ascenseurs', 'page', 'pages', 'query_string'
        ));
    }

    /**
     * Signale un nouvel ascenseur en panne.
     *
     * Paramètre GET:
     * * coordinates: Des coordonnées GPS
     * * photo: Un id de photo (optionnel)
     *
     * @Route("/ascenseur/new", name="signalement")
     *
     * @return Response La response
     */
    public function newAction (Request $request)
    {
        $ascenseur = new Ascenseur();
        $signalement = new Signalement($ascenseur);

        $form = $this->createForm(SignalementType::class, $signalement, [
            'method' => Request::METHOD_POST
        ]);

        if (! $request->isMethod(Request::METHOD_POST)) {
            return $this->render('default/signalement.html.twig', ["form" => $form->createView()]);
        }

        $form->handleRequest($request);

        if (! $form->isSubmitted() || ! $form->isValid()) {
            return $this->render('default/signalement.html.twig', ["form" => $form->createView()]);
        }

        $dm = $this->get('doctrine_mongodb')->getManager();
        $coordinates = $request->get('coordinates', null);
        $photoid = $request->get('photo', null);

        $coordinatesArr = ($coordinates)
            ? explode(',', urldecode($coordinates))
            : null;

        if ($coordinatesArr && count($coordinatesArr) === 2) {
            $ascenseur->setLatLon($coordinatesArr[1], $coordinatesArr[0]);
            $adresse = AdresseDataGouvApi::getAddrByCoordinates(urldecode($coordinates));
            $ascenseur->setAdresse($adresse['name']);
            $ascenseur->setCodePostal($adresse['postcode']);
            $ascenseur->setCommune($adresse['city']);
        }

        if ($photoid) {
            $photo = $dm->getRepository(Photo::class)->find($photoid);
            $ascenseur->addPhoto($photo);
        }

        $dm->persist($ascenseur);
        $dm->getRepository(Ascenseur::class)
           ->saveVersion(
               $ascenseur,
               new \DateTime(),
               "Création de l'ascenseur",
               $signalement->getPseudo()
           );
        $dm->persist($signalement);
        $dm->flush();

        return $this->redirect($this->generateUrl('ascenseur', ['id' => $ascenseur->getId()]));
    }

    /**
     * On affiche les informations d'un ascenseur
     *
     * @Route("/ascenseur/{id}", name="ascenseur", requirements={"id"="\w{24}"})
     *
     * @param Request $request La requête
     * @param string $id L'id de l'ascenseur
     * @return Response La réponse
     */
    public function ascenseurAction(Request $request, $id)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $ascenseur = $dm->getRepository(Ascenseur::class)->find($id);

        $geojson = $this->buildGeoJson($ascenseur);

        return $this->render('default/ascenseur.html.twig', compact(
            'ascenseur', 'geojson'
        ));
    }

    /**
     * On édite les informations de l'ascenseur
     * GET: On affiche le formulaire
     * POST: On enregistre les informations
     *
     * @Route("/ascenseur/{id}/edition", name="ascenseur_edition", requirements={"id"="\w{24}"})
     *
     * @param Request $request La requête
     * @param string $id L'id de l'ascenseur
     * @return Response La réponse
     */
    public function ascenseurEditionAction(Request $request, $id)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $ascenseur = $dm->getRepository(Ascenseur::class)->find($id);

        $form = $this->createForm(AscenseurType::class, $ascenseur, [
            'method' => Request::METHOD_POST
        ]);

        if(! $request->isMethod(Request::METHOD_POST)) {
            $form = $form->createView();
            return $this->render('default/ascenseur_edition.html.twig', compact('form', 'ascenseur'));
        }

        $form->handleRequest($request);

        if(! $form->isSubmitted() || ! $form->isValid()) {
            $form = $form->createView();
            return $this->render('default/ascenseur_edition.html.twig', compact('form', 'ascenseur'));
        }

        $dm->flush();

        $dm->getRepository(Ascenseur::class)
           ->saveVersion($ascenseur, new \DateTime(), "Des informations sur l'ascenseur ont été complétées", null);

        return $this->redirect($this->generateUrl('ascenseur', ['id' => $ascenseur->getId()]));
    }

    /**
     * Ajoute une photo dans un ascenceur
     *
     * @Route("/ascenseur/{id}/ajout-photo", name="ascenseur_photo", requirements={"id"="\w{24}"})
     *
     * @param Request $request L'objet request
     * @param string $id L'id de l'ascenseur
     * @return Response La réponse
     */
    public function ascenseurAjoutPhotoAction(Request $request, $id)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $ascenseur = $dm->getRepository(Ascenseur::class)->find($id);
        $photo = new Photo();

        $uploadPhotoForm = $this->createForm(PhotoType::class, $photo, [
            'method' => 'POST',
            'action' => $this->generateUrl('photo_upload', ['ascenseur' => $id])
        ]);

        $uploadPhotoForm = $uploadPhotoForm->createView();
        return $this->render('default/ascenseur_photo.html.twig', compact('ascenseur', 'uploadPhotoForm'));
    }

    /**
     * Signale un changment de status de l'ascenseur
     *
     * @Route("/ascenseur/{id}/changement", name="switch_status", requirements={"id"="\w{24}"})
     *
     * @param Request $request La requête
     * @param string $id L'id de l'ascenseur
     * @return Response La réponse
     */
    public function switchStatusAction(Request $request, $id)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $ascenseur = $dm->getRepository(Ascenseur::class)->find($id);

        $form = $this->createFormBuilder()
                     ->add('confirm', SubmitType::class, [
                         'label' => 'Confirmer',
                         'attr' => ['class' => 'btn btn-block btn-success']
                     ])
                     ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            switch ($ascenseur->getStatut()) {
                case Ascenseur::STATUT_ENPANNE:
                    $new_statut = Ascenseur::STATUT_FONCTIONNEL;
                    break;
                case Ascenseur::STATUT_FONCTIONNEL:
                    $new_statut = Ascenseur::STATUT_ENPANNE;
                    break;
                default:
                    $new_statut = Ascenseur::STATUT_ENPANNE;
                    break;
            }

            $ascenseur->setStatut($new_statut);
            $dm->persist($ascenseur);

            $dm->getRepository(Ascenseur::class)->saveVersion(
                $ascenseur,
                new \DateTime(),
                "Le statut a été mis à jour → $new_statut",
                null
            );

            $dm->flush();

            return $this->redirect($this->generateUrl('ascenseur', ['id' => $ascenseur->getId()]));
        }

        $form = $form->createView();
        return $this->render('default/ascenseur_change.html.twig', compact('ascenseur', 'form'));
    }

    /**
     * Un nouveau follower suit l'ascenseur
     * GET: On affiche un formulaire d'informations à remplir
     * POST: On enregistre les infos
     *
     * @Route("/ascenseur/{id}/join", name="ascenseur_follower", requirements={"id"="\w{24}"})
     */
    public function followerAction(Request $request, $id)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $ascenseur = $dm->getRepository(Ascenseur::class)->find($id);
        $signalement = new Signalement($ascenseur);

        $form = $this->createForm(FollowerType::class, $signalement, [
            'method' => Request::METHOD_POST
        ]);

        if(! $request->isMethod(Request::METHOD_POST)) {
            $form = $form->createView();
            return $this->render('default/follower.html.twig', compact('form', 'ascenseur'));
        }

        $form->handleRequest($request);

        if(! $form->isSubmitted() || ! $form->isValid()) {
            $form = $form->createView();
            return $this->render('default/follower.html.twig', compact('form', 'ascenseur'));
        }

        if ($signalement->getPseudo() || $signalement->getEmail() || $signalement->getTelephone()) {
            $signalement->createFollower();
            $dm->persist($signalement);

            $dm->flush();
        }

        return $this->redirect($this->generateUrl('ascenseur', ['id' => $ascenseur->getId()]));

    }

    /**
     * Retourne les informations de localisation de l'ascenseur
     *
     * @param Ascenseur $ascenseur L'ascenseur
     * @return stdClass Les données geojson
     */
    private function buildGeoJson(Ascenseur $ascenseur)
    {
        $geojson = new \stdClass();
        $geojson->type = "FeatureCollection";
        $geojson->features = [];

        $feature = new \stdClass();
        $feature->type = "Feature";
        $feature->properties = new \stdClass();
        $feature->properties->_id = $ascenseur->getId();
        $feature->properties->icon = 'ascenseur';

        $feature->geometry = new \stdClass();
        $feature->geometry->type = "Point";
        $feature->geometry->coordinates = [$ascenseur->getLon(), $ascenseur->getLat()];

        $geojson->features[] = $feature;
        return $geojson;
    }
}
