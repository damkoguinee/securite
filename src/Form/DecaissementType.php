<?php

namespace App\Form;

use App\Entity\Caisse;
use App\Entity\Devise;
use App\Entity\ConfigDevise;
use App\Entity\Decaissement;
use App\Entity\ModePaiement;
use App\Entity\PointDeVente;
use App\Entity\ConfigModePaiement;
use App\Repository\CaisseRepository;
use App\Entity\CategorieDecaissement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

class DecaissementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $site = $options['site'];
        $type1 = 'client';
        $type2 = 'client-fournisseur';
        $decaissement = $options["decaissement"];
        // dd($decaissement);
        $builder
            ->add('montant', TextType::class, [
                'label' => 'Montant décaissé*',
                "required"  => true,
                'attr' => [
                    'placeholder' => '1 000 000',
                    'onkeyup' => "formatMontant(this)",
                    'style' => 'font-size: 20px; font-weight: bold; ',
                ]
            ])
            // ->add('taux', NumberType::class, [
            //     'label' => 'Taux',
            //     'data' => $decaissement->getTaux() ?? 1,
            //     'scale' => 2,
            //     "required"  =>true,
            // ])
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
                'label' => 'Compte décaissé*',
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
                'data' => $decaissement->getId() ? $decaissement->getDateOperation() : new \DateTime(), // Définir la date et l'heure par défaut sur la date et l'heure actuelles
                'attr' => [
                    'max' => (new \DateTime())->format('Y-m-d\TH:i'), // Limiter la sélection à la date et l'heure actuelles ou antérieures
                ],
                'html5' => true, // Pour activer le support HTML5
            ])

            ->add('categorie', EntityType::class, [
                'class' => CategorieDecaissement::class,
                'choice_label' => 'designation',
            ])

            ->add('document', FileType::class, [
                "mapped"        =>  false,
                "required"      => false,
                "constraints"   => [
                    new File([
                        "mimeTypes" => [ "application/pdf", "image/jpeg", "image/gif", "image/png" ],
                        "mimeTypesMessage" => "Format accepté : PDF, gif, jpg, png",
                        "maxSize" => "5048k",
                        "maxSizeMessage" => "Taille maximale du fichier : 2 Mo"
                    ])
                ],
                'label' =>"Joindre un document",
                "help" => "Formats autorisés : PDF, gif, jpg, png"
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Decaissement::class,
            'site' => null,
            'decaissement' => null
        ]);
    }
}
