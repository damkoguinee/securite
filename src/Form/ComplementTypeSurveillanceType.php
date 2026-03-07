<?php

namespace App\Form;

use App\Entity\ConfigTypeSurveillance;
use App\Entity\ContratTypeSurveillance;
use Symfony\Component\Form\AbstractType;
use App\Entity\ComplementTypeSurveillance;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class ComplementTypeSurveillanceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('typeSurveillance', EntityType::class, [
                'class' => ConfigTypeSurveillance::class,
                'choice_label' => 'nom',
                'placeholder' => '— Choisir un type —',
                'label' => 'Type de surveillance *',
                'required' => true,
            ])

            ->add('tarif', NumberType::class, [
                'label' => 'Tarif appliqué HT',
                'required' => false,
                'scale' => 2,
            ])
            ->add('nbAgent', NumberType::class, [
                'label' => 'Nombre d\'agents',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ComplementTypeSurveillance::class,
        ]);
    }
}
