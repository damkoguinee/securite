<?php

namespace App\Form;

use App\Entity\Caisse;
use App\Entity\Devise;
use App\Entity\Versement;
use App\Entity\ModePaiement;
use App\Entity\PointDeVente;
use App\Repository\CaisseRepository;
use App\Entity\CategorieDecaissement;
use App\Entity\ConfigDevise;
use App\Entity\ConfigModePaiement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class VersementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $site = $options['site'];
        $type1 = 'client';
        $type2 = 'client-fournisseur';
        $versement = $options["versement"];
        // dd($versement);
        $builder
            ->add('montant', TextType::class, [
                'label' => 'Montant versé*',
                "required"  => true,
                'attr' => [
                    'placeholder' => '1 000 000',
                    'onkeyup' => "formatMontant(this)",
                    'style' => 'font-size: 20px; font-weight: bold; ',
                ]
            ])
            ->add('taux', NumberType::class, [
                'label' => 'Taux',
                'data' => $versement->getTaux() ?? 1,
                'scale' => 2,
                "required"  =>true,
            ])
            ->add('devise', EntityType::class, [
                'class' => ConfigDevise::class,
                'choice_label' => 'nom',
                'label' => "Devise"
            ])
            ->add('modePaie', EntityType::class, [
                'class' => ConfigModePaiement::class,
                'choice_label' => 'designation',
                'label' => 'Mode de paie*',
                'placeholder' => "Sélectionnez",
                'required' => true,
            ])
            ->add('bordereau', null, [
                'label' => 'N°Chèque\Bordereau',
                'required' => false,
                "constraints" => [
                    New Length([
                        "max" => 100,
                        'maxMessage'    => "Le numéro chèque ne doit pas depasser 100 caractères"
                    ])
                ]
            ])
           
            ->add('caisse', EntityType::class, [
                'class' => Caisse::class,
                'choice_label' => 'nom',
                'required' => true,
                'label' => 'Compte de dépôt*',
                'placeholder' => 'Sélectionnez un compte',
                'query_builder' => function (CaisseRepository $er) use ($site) {
                    return $er->createQueryBuilder('c')
                        ->where(':site MEMBER OF c.site')
                        ->setParameter('site', $site);
                },
            ])

            ->add('commentaire', null, [
                'label' => 'Commentaire*',
                'required' => false,
                "constraints" => [
                    New Length([
                        "max" => 255,
                        'maxMessage'    => "Le commentaire ne doit pas depasser 255 caractères"
                    ])
                ]
            ])
            ->add('dateOperation', DateTimeType::class, [
                'label' => 'Date de paiement*',
                'widget' => 'single_text',
                'required' => true,
                'data' => $versement->getId() ? $versement->getDateOperation() : new \DateTime(), // Définir la date et l'heure par défaut sur la date et l'heure actuelles
                'attr' => [
                    'max' => (new \DateTime())->format('Y-m-d\TH:i'), // Limiter la sélection à la date et l'heure actuelles ou antérieures
                ],
                'html5' => true, // Pour activer le support HTML5
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Versement::class,
            'site' => null,
            'versement' => null
        ]);
    }
}
