<?php

namespace App\Form;

use App\Entity\Caisse;
use App\Entity\Facture;
use App\Entity\Paiement;
use App\Entity\ConfigDevise;
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
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

class PaiementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $site = $options['site'];
        $paiement = $options["paiement"];
        // dd($paiement);
        $builder

            ->add('facture', EntityType::class, [
                'class' => Facture::class,

                'choice_label' => function (Facture $f) {

                    $montantTotal = $f->getMontantTotal();
                    $totalPaiement = 0;
                    foreach ($f->getDetailPaiementFactures() as  $paiement) {
                        $totalPaiement = $totalPaiement + $paiement->getMontant();
                    }

                    $reste = number_format($montantTotal - $totalPaiement, 0, '', ' ');

                    return sprintf(
                        '%s | %s GNF → Reste %s GNF | %s au %s',
                        $f->getReference(),
                        number_format($montantTotal, 0, ' ', ' '),
                        $reste,
                        $f->getPeriodeDebut()->format('d/m/Y'),
                        $f->getPeriodeFin()->format('d/m/Y')
                    );
                },

                'choice_attr' => function (Facture $f) {

                    $montantTotal = $f->getMontantTotal();
                    $totalPaiement = 0;
                    foreach ($f->getDetailPaiementFactures() as  $paiement) {
                        $totalPaiement = $totalPaiement + $paiement->getMontant();
                    }

                    $reste = $montantTotal - $totalPaiement;

                    return [
                        'data-montant' => $reste,
                    ];
                },

                'multiple' => true,
                'choices' => $paiement->getFacture()->toArray(),
                'required' => true,
                'label' => 'Factures concernées*',
            ])

            
            ->add('montant', TextType::class, [
                'label' => 'Montant Payé*',
                "required"  => true,
                'attr' => [
                    'placeholder' => '1 000 000',
                    'onkeyup' => "formatMontant(this)",
                    'style' => 'font-size: 20px; font-weight: bold; ',
                ]
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
            
            ->add('caisse', EntityType::class, [
                'class' => Caisse::class,
                'choice_label' => 'nom',
                'required' => true,
                'label' => 'Caisse*',
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
                'data' => $paiement->getId() ? $paiement->getDateOperation() : new \DateTime(), // Définir la date et l'heure par défaut sur la date et l'heure actuelles
                'attr' => [
                    'max' => (new \DateTime())->format('Y-m-d\TH:i'), // Limiter la sélection à la date et l'heure actuelles ou antérieures
                ],
                'html5' => true, // Pour activer le support HTML5
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
            'data_class' => Paiement::class,
            'site' => null,
            'paiement' => null
        ]);
    }
}
