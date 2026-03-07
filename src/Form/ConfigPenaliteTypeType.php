<?php

namespace App\Form;

use App\Entity\ConfigPenaliteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigPenaliteTypeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            // 🟦 NOM DE LA PÉNALITÉ
            ->add('nom', TextType::class, [
                'label' => 'Nom de la pénalité *',
                'attr' => [
                    'placeholder' => 'Ex : Absence, Sommeil, Retard...',
                    'class' => 'form-control shadow-sm'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le nom est obligatoire.']),
                    new Length([
                        'max' => 150,
                        'maxMessage' => 'Le nom ne doit pas dépasser 150 caractères.'
                    ]),
                ]
            ])

            // 🟦 MONTANT PAR DÉFAUT
            ->add('montantDefaut', NumberType::class, [
                'label' => 'Montant par défaut *',
                'scale' => 0,
                'attr' => [
                    'placeholder' => 'Ex : 50000',
                    'class' => 'form-control shadow-sm',
                    'min' => 0
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le montant est obligatoire.']),
                    new Positive(['message' => 'Le montant doit être supérieur à 0.'])
                ]
            ])

            // 🟦 DESCRIPTION
            ->add('description', TextareaType::class, [
                'label' => 'Description (facultatif)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Commentaire ou règles concernant ce type de pénalité...',
                    'class' => 'form-control shadow-sm',
                    'rows' => 3
                ],
                'constraints' => [
                    new Length([
                        'max' => 500,
                        'maxMessage' => 'La description ne doit pas dépasser 500 caractères.'
                    ]),
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ConfigPenaliteType::class,
        ]);
    }
}
