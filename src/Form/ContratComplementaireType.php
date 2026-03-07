<?php

namespace App\Form;

use App\Entity\Bien;
use App\Entity\ContratComplementaire;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ContratComplementaireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
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
            /* 📝 Commentaire global */
            ->add('motif', TextType::class, [
                'label' => 'Motif de la demande *',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex : Renfort week-end, événement spécial…',
                ],
                'constraints' => [
                    new NotBlank([
                        "message" => "Veuillez saisir le motif."
                    ]),
                    new Length([
                        "max" => 150,
                        "maxMessage" => "Le motif ne doit pas dépasser 150 caractères."
                    ])
                ]
            ])


            ->add('complementTypeSurveillances', CollectionType::class, [
                'entry_type' => ComplementTypeSurveillanceType::class,
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
            'data_class' => ContratComplementaire::class,
        ]);
    }
}
