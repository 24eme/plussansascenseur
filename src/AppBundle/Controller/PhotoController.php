<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\File;

use AppBundle\Document\Photo;
use AppBundle\Document\Ascenseur;
use AppBundle\Document\Thumbnail;
use AppBundle\Type\PhotoType;

class PhotoController extends Controller
{
    /**
     * On upload une photo depuis la page d'accueil
     *
     * @Route("/photo/upload/{ascenseur}",
     *          name="photo_upload",
     *          defaults={"ascenseur": null},
     *          requirements={"ascenseur": "\w{24}"}
     * )
     *
     * @param Request $request La requête
     * @return Response La réponse
     */
    public function photoUploadAction(Request $request)
    {
        if (! $request->isMethod(Request::METHOD_POST)) {
            return $this->redirect($this->generateUrl('homepage'));
        }

        $dm = $this->get('doctrine_mongodb')->getManager();
        $photo = new Photo();

        $ascenseur = $request->get('ascenseur');
        if ($ascenseur) {
            $ascenseur = $dm->getRepository(Ascenseur::class)
                            ->find($ascenseur);
        }

        $uploadPhotoForm = $this->createForm(PhotoType::class, $photo, [
            'method' => 'POST'
        ]);

        $uploadPhotoForm->handleRequest($request);

        if(! $uploadPhotoForm->isValid()) {
            $uploadPhotoForm = $uploadPhotoForm->createView();

            if ($ascenseur) {
                return $this->render(
                    'default/ascenseur_photo.html.twig',
                    compact('ascenseur', 'uploadPhotoForm')
                );
            }
            return $this->render('default/index.html.twig', compact('uploadPhotoForm'));
        }

        $data = $request->request->get('photos');

        $dm->persist($photo);
        $photo->operate();

        $thumbnail = new Thumbnail($photo->getImageFile());
        $thumbnail->thumbnalize();
        $dm->persist($thumbnail);

        $photo->setThumbnail($thumbnail);

        if ($ascenseur) {
            $ascenseur->addPhoto($photo);
        }

        $dm->persist($photo);
        $dm->flush();

        if ($ascenseur) {
            return $this->redirect($this->generateUrl('ascenseur', ['id' => $ascenseur->getId()]));
        }

        return $this->redirect($this->generateUrl('localisation', [
            'photo' => $photo->getId(),
            'coordinates' => $photo->getLocalisation()
        ]));
    }

    /**
     * On affiche la photo
     *
     * @Route("/photo/{id}/{size}", name="photo",
     *                       requirements={
     *                          "id"="\w{24}",
     *                          "size"="original|thumbnail"
     *                       },
     *                       defaults={"size"="thumbnail"}
     * )
     *
     * @param Request $request La requête
     * @param string $id L'id de la Photo
     * @return Response La réponse
     */
    public function photoAction(Request $request, $id)
    {
        $size = $request->get('size');

        $dm = $this->get('doctrine_mongodb')->getManager();
        $response = new Response();

        $photo = $dm->getRepository(Photo::class)->find($id);

        if ($size === 'original') {
            $response->headers->set('Content-Length', $photo->getImageSize());
            $response->headers->set('Content-Type', ($photo->getExt()) ? $photo->getExt() : "image");
            $response->setContent(base64_decode($photo->getBase64()));
        } elseif ($size === 'thumbnail') {
            if (! $photo->getThumbnail()) {
                $tmpfile = tempnam(sys_get_temp_dir(), 'thumb');
                $im = imagecreatefromstring(base64_decode($photo->getBase64()));
                imagejpeg($im, $tmpfile);

                $thumbnail = new Thumbnail(new File($tmpfile));
                $thumbnail->thumbnalize();
                $dm->persist($thumbnail);

                $photo->setThumbnail($thumbnail);
                $dm->persist($photo);

                $dm->flush();
                unlink($tmpfile);
            }

            $response->headers->set('Content-Length', $photo->getThumbnail()->getFile()->getSize());
            $response->headers->set('Content-Type', ($photo->getExt()) ? $photo->getExt() : "image");
            $response->setContent($photo->getThumbnail()->getFile()->getBytes());
        }

        return $response;
    }
}
