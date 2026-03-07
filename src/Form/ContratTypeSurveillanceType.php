<?php

namespace App\Form;

use App\Entity\ConfigTypeSurveillance;
use App\Entity\ContratTypeSurveillance;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

class ContratTypeSurveillanceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            /* 🌐 Type de surveillance */
            ->add('typeSurveillance', EntityType::class, [
                'class' => ConfigTypeSurveillance::class,
                'choice_label' => 'nom',
                'placeholder' => '— Choisir un type —',
                'label' => 'Type de surveillance *',
                'required' => true,
            ])

            /* 💰 Tarif horaire */
            ->add('tarifHoraire', MoneyType::class, [
                'label' => 'Tarif horaire HT',
                'required' => false,
                'currency' => 'GNF',
                'constraints' => [
                    new PositiveOrZero(),
                ],
                'attr' => [
                    'placeholder' => 'Ex: 25 000',
                ],
            ])

            /* 💰 Tarif mensuel */
            ->add('tarifMensuel', MoneyType::class, [
                'label' => 'Tarif mensuel HT',
                'required' => false,
                'currency' => 'GNF',
                'constraints' => [
                    new PositiveOrZero(),
                ],
                'attr' => [
                    'placeholder' => 'Ex: 1 500 000',
                ],
            ])

            // /* 💰 Forfait */
            // ->add('tarifForfait', MoneyType::class, [
            //     'label' => 'Tarif forfaitaire',
            //     'required' => false,
            //     'currency' => 'GNF',
            //     'constraints' => [
            //         new PositiveOrZero(),
            //     ],
            //     'attr' => [
            //         'placeholder' => 'Ex: 5 000 000',
            //     ],
            // ])

            /* 👮 Agents */
            ->add('nbAgentsJour', NumberType::class, [
                'label' => 'Agents de jour',
                'required' => false,
                'constraints' => [
                    new PositiveOrZero(),
                ],
                'attr' => ['min' => 0],
            ])

            ->add('nbAgentsNuit', NumberType::class, [
                'label' => 'Agents de nuit',
                'required' => false,
                'constraints' => [
                    new PositiveOrZero(),
                ],
                'attr' => ['min' => 0],
            ])

            /* 🕒 Heures */
            ->add('heureParAgent', NumberType::class, [
                'label' => 'Heures / agent',
                'required' => false,
                'scale' => 2,
                'constraints' => [
                    new PositiveOrZero(),
                ],
                'attr' => ['placeholder' => 'Ex: 8'],
            ])

            /* 📝 Commentaire optionnel */
            // ->add('commentaire', TextareaType::class, [
            //     'label' => 'Commentaire',
            //     'required' => false,
            //     'attr' => [
            //         'rows' => 2,
            //         'placeholder' => 'Notes sur ce type de surveillance (optionnel)',
            //     ],
            // ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContratTypeSurveillance::class,
        ]);
    }
}
