<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Caisse;
use App\Entity\Devise;
use App\Entity\LieuxVentes;
use App\Entity\ConfigDevise;
use App\Entity\PointDeVente;
use App\Entity\TransfertFond;
use App\Entity\ConfigModePaiement;
use App\Repository\CaisseRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

class TransfertFondType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $site = $options['site'];
        $transfert = $options["data"];
        $builder
            ->add('montant', TextType::class, [
                'label' => 'Montant à transférer*',
                "required"  =>false,
                    'attr' => [
                        'onkeyup' => "formatMontant(this)",
                        'style' => 'font-size: 20px; font-weight: bold; ',
                    ]
            ])
            ->add('devise', EntityType::class, [
                'class' => ConfigDevise::class,
                'choice_label' => 'nom',
                'label' => "Devise*"
            ])

            ->add('modePaie', EntityType::class, [
                'class' => ConfigModePaiement::class,
                'choice_label' => 'designation',
                'label' => 'Mode de paie*',
                'placeholder' => "Sélectionnez",
                'required' => true,
            ])
            ->add('caisse', EntityType::class, [
                'class' => Caisse::class,
                'choice_label' => 'nom',
                'required' => false,
                'label' => 'Caisse de départ',
                'placeholder' => 'Sélectionnez un compte',
                'query_builder' => function (CaisseRepository $er) use ($site) {
                    return $er->createQueryBuilder('c')
                        ->where(':site MEMBER OF c.site')
                        ->setParameter('site', $site);
                },
            ])
            ->add('caisseReception', EntityType::class, [
                'class' => Caisse::class,
                'choice_label' => 'nom',
                'required' => false,
                'label' => 'Caisse de réception',
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
                'label' => 'Date opération*',
                'widget' => 'single_text',
                'required' => true,
                'data' => new \DateTime(),
                'attr' => [
                    'max' => (new \DateTime())->format('Y-m-d\TH:i'), 
                ],
                'html5' => true,
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
            'data_class' => TransfertFond::class,
            'site' => null,
        ]);
    }
}
