<?php

namespace App\Form;

use App\Entity\Bien;
use App\Entity\Site;
use App\Entity\Client;
use App\Entity\Adresse;
use App\Entity\Personel;
use App\Entity\Partenaire;
use App\Entity\Utilisateur;
use App\Entity\ConfigQuartier;
use App\Entity\ConfigTypeBien;
use App\Entity\ConfigZoneRattachement;
use App\Entity\GroupeFacturation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class BienType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('site', EntityType::class, [
                'class' => Site::class,
                'choice_label' => 'nom',
                'label' => 'Site *',
                'placeholder' => 'Choisir un site',
                'required' => true,
                'constraints' => [
                    new Assert\NotNull(['message' => 'Le site est obligatoire']),
                ],
            ])

            ->add('zoneRattachement', EntityType::class, [
                'class' => ConfigZoneRattachement::class,
                'choice_label' => 'nom ',
                'label' => 'Zone de rattachement',
                'placeholder' => "Sélectionnez une zone",
                'required' => false,
            ])

            ->add('adresse', EntityType::class, [
                'class' => ConfigQuartier::class,
                'choice_label' => 'nom',
                'label' => 'Adresse *',
                'placeholder' => 'Choisir une adresse',
                'required' => true,
                'constraints' => [
                    new Assert\NotNull(['message' => "L'adresse est obligatoire"]),
                ],
            ])
            // ->add('gestionnaire', EntityType::class, [
            //     'class' => Personel::class,
            //     'choice_label' => function (Personel $personel) {
            //         return $personel->getNom() . ' ' . $personel->getPrenom();
            //     },
            //     'label' => 'Gestionnaire *',
            //     'placeholder' => 'Choisir un gestionnaire',
            //     'required' => true,
            //     'constraints' => [
            //         new Assert\NotNull(['message' => 'Le gestionnaire est obligatoire']),
            //     ],
            // ])
            ->add('client', EntityType::class, [
                'class' => Client::class,
                'choice_label' => function (Client $client) {
                    return $client->getNomComplet();
                },
                'label' => 'Propriétaire *',
                'placeholder' => 'Choisir un propriétaire',
                'required' => true,
                'constraints' => [
                    new Assert\NotNull(['message' => 'Le propriétaire est obligatoire']),
                ],
            ])

            
            ->add('nom', TextType::class, [
                'label' => 'Nom du bien *',
                'required' => true,
                'attr' => ['maxlength' => 100],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nom est obligatoire']),
                    new Assert\Length([
                        'max' => 100,
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut *',
                'choices' => [
                    'Actif' => 'actif',
                    'Inactif' => 'inactif',
                ],
                'required' => true,
                'placeholder' => 'Choisir un statut',
                'attr' => ['maxlength' => 30],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le statut est obligatoire']),
                    new Assert\Length([
                        'max' => 30,
                        'maxMessage' => 'Le statut ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('typeBien', EntityType::class, [
                'class' => ConfigTypeBien::class,
                'choice_label' => function (ConfigTypeBien $type) {
                    return $type->getNom();
                },
                'label' => 'Type de bien *',
                'placeholder' => 'Choisir un type',
                'required' => true,
                'constraints' => [
                    new Assert\NotNull(['message' => 'Le type de bien est obligatoire']),
                ],
            ])
            // ->add('type_bien', ChoiceType::class, [
            //     'label' => 'Type de bien *',
            //     'choices' => [
            //         'Appartement' => 'appartement',
            //         'Hôtel' => 'hotel',
            //         'Terrain' => 'terrain',
            //         'Immeuble' => 'immeuble',
            //         'Villa' => 'villa',
            //         'Résidence' => 'residence',
            //         'Magasin' => 'magasin',
            //         'Société' => 'societe',
            //         'Superette' => 'superette',
            //         'Entrepôt' => 'entrepot',
            //         'Autres' => 'autres',
            //     ],
            //     'required' => true,
            //     'placeholder' => 'Choisir un type de bien',
            //     'attr' => ['maxlength' => 50],
            //     'constraints' => [
            //         new Assert\NotBlank(['message' => 'Le type de bien est obligatoire']),
            //         new Assert\Length([
            //             'max' => 50,
            //             'maxMessage' => 'Le type de bien ne peut pas dépasser {{ limit }} caractères',
            //         ]),
            //     ],
            // ])

            ->add('longitude', TextType::class, [
                'label' => 'Longitude',
                'required' => false,
                'attr' => [
                    'maxlength' => 255,
                    'placeholder' => 'Ex: -1.5637',
                ],
                'constraints' => [
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => 'La longitude ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('latitude', TextType::class, [
                'label' => 'Latitude',
                'required' => false,
                'attr' => [
                    'maxlength' => 255,
                    'placeholder' => 'Ex: 12.3490',
                ],
                'constraints' => [
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => 'La latitude ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])

            
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Bien::class,
        ]);
    }
}
