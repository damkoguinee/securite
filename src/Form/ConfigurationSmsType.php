<?php

namespace App\Form;

use App\Entity\ConfigurationSms;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigurationSmsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add('etat', ChoiceType::class, [
                'choices' => [
                    'Actif' => 'actif',
                    'Inactif' => 'inactif',
                ],
                'label' => 'État*',
                'required' => true, // Rend le champ obligatoire
                'placeholder' => 'Sélectionnez un état', // Optionnel : ajoute une option vide par défaut
            ])
            ->add('frequence', ChoiceType::class, [
                'choices' => [
                    'Jour-1' => '1',
                    'Jour-2' => '2',
                    'Jour-3' => '3',
                    'Jour-4' => '4',
                    'Jour-5' => '5',
                ],
                'label' => 'Fréquence',
                'required' => false, // Rend le champ obligatoire
                'placeholder' => 'Sélectionnez une fréquence', // Optionnel
            ])
            ->add('message')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ConfigurationSms::class,
        ]);
    }
}
