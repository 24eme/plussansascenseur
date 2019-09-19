<?php

namespace AppBundle\Type;

use AppBundle\Document\Signalement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;

class SignalementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('usage', ChoiceType::class, array('choices' => array_flip(Signalement::$usageList), 'choices_as_values' => true, 'required' => false, 'label' => "Pourquoi comptiez-vous utiliser cet ascenseur :"))
            ->add('etage', IntegerType::class, array('required' => false, 'label' => "Quelle étage souhaitez-vous atteindre :", "attr" => array("placeholder" => "", "type" => "number" )))
            ->add('etageAtteint', ChoiceType::class, array('choices' => $this->getChoicesEtageAtteint(), 'choices_as_values' => true, 'required' => false, 'label' => "Avez-vous pu rejoindre votre étage :"))
            ->add('duree', TextType::class, array('required' => false, 'label' => 'Combien de temps avez-vous mis :', "attr" => array("placeholder" => "")))
            ->add('commentaire', TextareaType::class, array('required' => false, 'label' => "Expliquez la situation :"))
            ->add('pseudo', TextType::class, array('required' => false, 'label' => "Votre nom / pseudo :"))
            ->add('abonnement', CheckboxType::class, array('required' => false, 'label' => "Être tenu-e informé-e"))
            ->add('email', EmailType::class, array('required' => false, 'label' => "Email :"))
            ->add('telephone', TextType::class, array('required' => false, 'label' => "Téléphone :"))
            ->add('intervention', CheckboxType::class, array('required' => false, 'label' => "Demander une intervention du collectif"))
            ->add('nom', TextType::class, array('required' => false, 'label' => "Votre nom :"))
            ->add('prenom', TextType::class, array('required' => false, 'label' => "Votre prénom :"))
            ->add('codeInterphone', TextType::class, array('required' => false, 'label' => "Code / Interphone :"))
            ->add('proprietaire', ChoiceType::class, array('choices' => $this->getChoicesProprietaire(), 'choices_as_values' => true, 'required' => false, 'label' => "Vous êtes :"))
            ->add('complements', ChoiceType::class, array(
                'choices' => array_flip(Signalement::$complementsList),
                'choices_as_values' => true,
                'expanded' => true,
                'multiple' => true,
                'required' => false,
                'empty_data' => null,
                'label' => 'Etes vous les cas suivants ?'
            ))
            ->add('connaissance', ChoiceType::class, array('choices' => array_flip(Signalement::$connaissanceList), 'choices_as_values' => true, 'required' => false, 'label' => "Comment avez vous connu notre collectif :"))
            ->add('datePanne', DateType::class, array('required' => false, 'label' => "Date de panne :", 'widget' => 'single_text', 'html5' => true))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Signalement::class,
        ]);
    }

    public function getChoicesEtageAtteint() {

        return array("Oui, j'ai pu le rejoindre" => true,
                     "Non, je n'ai pas pu le rejoindre" => false);
    }

    public function getChoicesProprietaire() {

        return array("Proprietaire" => true,
                     "Locataire" => false);
    }
}
