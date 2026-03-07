<?php

namespace App\Form;

use App\Entity\ConfigQuartier;
use App\Entity\Entreprise;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class EntrepriseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'required' => true,
                'attr' => ['maxlength' => 100],
            ])
            ->add('identifiant', TextType::class, [
                'label' => 'Identifiant',
                'required' => false,
                'attr' => ['maxlength' => 100],
            ])
            ->add('numeroAgrement', TextType::class, [
                'label' => 'Numéro d\'agrément',
                'required' => false,
                'attr' => ['maxlength' => 100],
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Téléphone',
                'required' => true,
                'attr' => ['maxlength' => 20],
            ])
            ->add('logo', FileType::class, [
                'label' => 'Logo (image)',
                'required' => false,
                'mapped' => false, // Nous ne mappons pas directement à l'entité ici
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif'],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG, PNG, GIF)',
                    ]),
                ],
            ])
            ->add('complementAdresse', TextType::class, [
                'label' => 'Complément d\'adresse',
                'required' => false,
                'attr' => ['maxlength' => 255],
            ])
            ->add('adresse', EntityType::class, [
                'class' => ConfigQuartier::class,
                'choice_label' => 'nom',  // Afficher la colonne "nom" de ConfigQuartier
                'label' => 'Adresse',
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Entreprise::class,
        ]);
    }
}
