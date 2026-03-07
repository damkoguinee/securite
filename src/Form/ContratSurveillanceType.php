<?php

namespace App\Form;

use App\Entity\Bien;
use App\Entity\ContratSurveillance;
use Symfony\Component\Form\AbstractType;
use App\Form\ContratTypeSurveillanceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class ContratSurveillanceType extends AbstractType
{
    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $biensDisponibles = $options['biens_disponibles'];
        $builder
            /* 🗓️ Dates */
            ->add('dateDebut', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de début *',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'La date de début est obligatoire.']),
                ],
            ])
            ->add('dateFin', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de fin',
                'required' => false,
            ])

            /* 💳 Mode de facturation */
            ->add('modeFacturation', ChoiceType::class, [
                'label' => 'Mode de facturation *',
                'choices' => [
                    'Mensuel/Agent' => 'mensuel_agent',
                    'Mensuel' => 'mensuel',
                    'Horaire' => 'horaire',
                    'Forfait' => 'forfait',
                ],
                'placeholder' => '— Sélectionnez un mode —',
                'required' => true,
            ])

            /* ⚙️ Statut */
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut du contrat *',
                'choices' => [
                    'Actif' => 'actif',
                    'Inactif' => 'inactif',
                    'Suspendu' => 'suspendu',
                    'Résilié' => 'resilie',
                ],
                'placeholder' => '— Sélectionnez un statut —',
                'required' => true,
            ])

            /* 📝 Commentaire global */
            ->add('commentaire', TextareaType::class, [
                'label' => 'Commentaire (optionnel)',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                ],
            ])

            ->add('tva', NumberType::class, [
                'label' => 'TVA (%)',
                'scale' => 2,               // nombre de décimales
                'html5' => true,
                'attr' => [
                    'min' => 0,
                    'max' => 100,
                    'step' => '0.01',
                ],
                'required' => false,
            ])

            ->add('remise', NumberType::class, [
                'label' => 'Remise (%)',
                'scale' => 2,
                'html5' => true,
                'attr' => [
                    'min' => 0,
                    'max' => 100,
                    'step' => '0.01',
                ],
                'required' => false,

            ])


            /* 🏠 Bien concerné */
            ->add('bien', EntityType::class, [
                'class' => Bien::class,
                'label' => 'Site concerné *',
                'choices' => $biensDisponibles,     // 👈 Liste filtrée
                'choice_label' => 'nom',
                'placeholder' => '— Sélectionnez un site —',
                'required' => true,
            ])

            ->add('typesSurveillance', CollectionType::class, [
                'entry_type' => ContratTypeSurveillanceType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'prototype' => true,
                'prototype_options' => [
                    'required' => false,   // 👈 le prototype n'est pas required
                ],
                'by_reference' => false,   // OBLIGATOIRE pour add/remove
                'label' => false,
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContratSurveillance::class,
            'biens_disponibles' => [] // valeur par défaut
        ]);
    }
}
