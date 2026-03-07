<?php

namespace App\Form;

use App\Entity\ConfigTypeSurveillance;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Validator\Constraints as Assert;

class ConfigTypeSurveillanceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // 🔹 Nom du type de surveillance
            ->add('nom', TextType::class, [
                'label' => 'Nom du type de surveillance *',
                'attr' => [
                    'maxlength' => 100,
                    'placeholder' => 'Ex : Gardiennage, Ronde, Télésurveillance'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nom du type de surveillance est obligatoire.']),
                    new Assert\Length([
                        'max' => 100,
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])

            // 🔹 Description
            ->add('description', TextareaType::class, [
                'label' => 'Description (facultatif)',
                'required' => false,
                'attr' => [
                    'maxlength' => 255,
                    'rows' => 3,
                    'placeholder' => 'Description ou détails du service'
                ],
                'constraints' => [
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => 'La description ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])

            // 🔹 Tarif horaire
            ->add('tarifHoraire', NumberType::class, [
                'label' => 'Tarif horaire (GNF)',
                'required' => false,
                'scale' => 2, // deux décimales (pour .00)
                'attr' => [
                    'min' => 0,
                    'step' => 0.01,
                    'placeholder' => 'Ex : 25000.00',
                ],
                'constraints' => [
                    new Assert\PositiveOrZero(['message' => 'Le tarif horaire doit être un nombre positif.']),
                ],
            ])

            // 🔹 Tarif mensuel
            ->add('tarifMensuel', NumberType::class, [
                'label' => 'Tarif mensuel (GNF)',
                'required' => false,
                'scale' => 2,
                'attr' => [
                    'min' => 0,
                    'step' => 0.01,
                    'placeholder' => 'Ex : 1500000.00',
                ],
                'constraints' => [
                    new Assert\PositiveOrZero(['message' => 'Le tarif mensuel doit être un nombre positif.']),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ConfigTypeSurveillance::class,
        ]);
    }
}
