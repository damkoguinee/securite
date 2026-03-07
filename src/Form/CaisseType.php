<?php

namespace App\Form;

use App\Entity\Site;
use App\Entity\Caisse;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CaisseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'required' => true,
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type *',
                'required' => true,
                'choices'  => [
                    'Caisse' => 'caisse',
                    'Banque' => 'banque',
                ],
                'placeholder' => 'Choisissez un type',
            ])
            ->add('numero', TextType::class, [
                'label' => 'Numéro',
                'required' => false,
            ])
            ->add('site', EntityType::class, [
                'class' => Site::class,
                'choice_label' => 'nom',   // ou n'importe quel champ (ex: "name")
                'label' => 'Sites',
                'required' => false,       // ou true selon ton besoin
                'multiple' => true,        // <--- important pour ManyToMany
                'expanded' => true,       // false => liste déroulante multiple, true => cases à cocher
            ])
        ;

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Caisse::class,
        ]);
    }
}
