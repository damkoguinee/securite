<?php

namespace App\Form;

use App\Entity\Penalite;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class PenaliteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            
            ->add('affectationAgent')
            // 🟦 MONTANT PAR DÉFAUT
            ->add('montant', NumberType::class, [
                'label' => 'Montant *',
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
            ->add('commentaire', TextareaType::class, [
                'label' => 'commentaire (facultatif)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Commentaire  concernant ce  pénalité...',
                    'class' => 'form-control shadow-sm',
                    'rows' => 3
                ],
                'constraints' => [
                    new Length([
                        'max' => 500,
                        'maxMessage' => 'La commentaire ne doit pas dépasser 500 caractères.'
                    ]),
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Penalite::class,
        ]);
    }
}
