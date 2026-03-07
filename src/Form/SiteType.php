<?php

namespace App\Form;

use App\Entity\Client;
use App\Entity\ConfigQuartier;
use App\Entity\Entreprise;
use App\Entity\Personel;
use App\Entity\Site;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SiteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('nom', TextType::class, [
            'label' => 'Nom du site *',
            'required' => true,
            'attr' => ['maxlength' => 100],
        ])
        
        ->add('email', EmailType::class, [
            'label' => 'Email *',
            'required' => true,
            'attr' => ['maxlength' => 50],
        ])
        ->add('dateOuverture', DateType::class, [
            'label' => 'Date d\'ouverture',
            'required' => false,
            'widget' => 'single_text',
        ])
        ->add('telephone', TelType::class, [
            'label' => 'Téléphone *',
            'required' => true,
            'attr' => ['maxlength' => 20],
        ])
        ->add('initial', TextType::class, [
            'label' => 'Initiales',
            'required' => false,
            'attr' => ['maxlength' => 50],
        ])
        ->add('adresse', EntityType::class, [
            'class' => ConfigQuartier::class,
            'choice_label' => 'nom', // Vous pouvez ajuster ici en fonction des propriétés disponibles dans ConfigQuartier
            'label' => 'Adresse *',
            'required' => true,
        ])
        ->add('complementAdresse', TextType::class, [
            'label' => 'Complément d\'adresse',
            'required' => false,
            'attr' => ['maxlength' => 100],
        ])

        ->add('description', TextType::class, [
            'label' => 'Description',
            'required' => false,
        ])
        ->add('entreprise', EntityType::class, [
            'class' => Entreprise::class,
            'choice_label' => 'nom', // Comme précédemment, vous pouvez ajuster pour afficher un champ lisible
            'label' => 'Entreprise *',
            'required' => true,
        ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Site::class,
        ]);
    }
}
