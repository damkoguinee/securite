<?php

namespace App\Form;

use App\Entity\Bien;
use App\Entity\Site;
use App\Entity\Personel;
use App\Entity\ConfigQuartier;
use App\Entity\ConfigZoneAdresse;
use App\Entity\ConfigZoneRattachement;
use App\Form\DocumentUserType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class PersonelType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $userId = $options['data']->getId();

        $builder
            ->add('username', null, [
                "constraints" => [
                    new Length([
                        "max" => 180,
                        "maxMessage" => "Le pseudo ne doit pas contenir plus de 180 caractères",
                    ])
                ],
                "required" => false,
                "label" => "Identifiant"
            ])

            ->add('roles', ChoiceType::class, [
                "choices" => [
                    "Agent" => "ROLE_AGENT",
                    "Comptable" => "ROLE_COMPTABLE",
                    "Gestionnaire" => "ROLE_GESTIONNAIRE",
                    "RH" => "ROLE_RH",
                    "RH" => "ROLE_RH",
                    "Responsable" => "ROLE_RESPONSABLE",
                    "Administrateur" => "ROLE_ADMIN",
                    "Suppression" => "ROLE_SUPPRESSION",
                    "Modification" => "ROLE_MODIFICATION",
                ],
                "multiple" => true,
                "expanded" => true,
                "label" => "Niveau d'accès*"
            ])

            ->add('password', null, [
                "mapped" => false,
                "required" => false,
                "label" => "Mot de passe"
            ])

            ->add('nom', null, [
                "constraints" => [
                    new Length(["max" => 50]),
                    new NotBlank(["message" => "Le nom ne peut pas être vide !"])
                ],
                "label" => "Nom *"
            ])

            ->add('reference', null, [
                "constraints" => [
                    new Length(["max" => 50]),
                    new NotBlank(["message" => "Le matricule ne peut pas être vide !"])
                ],
                "label" => "Matricule "
            ])

            ->add('prenom', null, [
                "constraints" => [
                    new Length(["max" => 100]),
                    new NotBlank(["message" => "Le prénom ne peut pas être vide !"])
                ],
                "label" => "Prénom *"
            ])

            ->add('statut', ChoiceType::class, [
                'label' => 'Statut *',
                'choices' => [
                    'actif' => 'actif',
                    'inactif' => 'inactif',
                    'resilie' => 'resilie',
                ]
            ])

            ->add('telephone', TelType::class, [
                "constraints" => [
                    new Length(["min" => 9]),
                    new NotBlank(["message" => "Le numéro de téléphone ne peut pas être vide !"])
                ],
                "label" => "N° Téléphone *"
            ])

            ->add('email', EmailType::class, [
                "constraints" => [
                    new Length(["max" => 150])
                ],
                "required" => false,
                "label" => "Adresse Email"
            ])

            ->add('typePersonnel', ChoiceType::class, [
                "choices" => [
                    '' => '',
                    "Personnel de direction" => "personnel",
                    "Agent" => "agent",
                ],
                'label' => 'Type de Personnel *',
            ])

            ->add('fonction', ChoiceType::class, [
                "choices" => [
                    '' => '',
                    "Agent" => "agent",
                    "Commercial" => "commercial",
                    "Rh" => "RH",
                    "Comptable" => "comptable",
                    "Gestionnaire" => "gestionnaire",
                    "Responsable" => "responsable",
                    "Assistante de direction" => "assistante de direction",
                    "Controleur" => "contrôleur",
                    "Chef des opérations" => "chef des opérations",
                    "Coordinateur" => "coordinateur",
                    "Responsable Planifiaction" => "responsable planifiaction",
                    "Receptionniste" => "Receptionniste",
                    "Caissière" => "Caissière",
                    "Contrôleur relève" => "Contrôleur relève",
                    "Agent relève" => "agent releve",
                    "Administrateur" => "admin",
                ],
                'label' => 'Fonction *',
            ])

            ->add('statutPlanning', ChoiceType::class, [
                "choices" => [
                    '' => '',
                    "Inactif" => "inactif",
                    "traite" => "traite",
                ],
                'label' => 'Statut Planning',
                'required' => false,
            ])

            ->add('tauxHoraire', TextType::class, [
                'label' => 'Taux horaire *',
                'required' => true,
            ])

            ->add('salaireBase', TextType::class, [
                'label' => 'Salaire de base *',
                'required' => true,
            ])

            ->add('dateEmbauche', DateType::class, [
                'label' => 'Date d\'embauche ',
                'widget' => 'single_text',
            ])

            ->add('dateFinContrat', DateType::class, [
                'label' => 'Date de résiliation ',
                'widget' => 'single_text',
                'required' => false,
            ])

            ->add('photo', FileType::class, [
                'label' => 'Photo',
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif'],
                    ]),
                ],
            ])

            // 🔹 FILIATION
            ->add('nomPere', TextType::class, [
                'label' => 'Nom du père',
                'required' => false,
                'attr' => ['maxlength' => 100]
            ])

            ->add('nomMere', TextType::class, [
                'label' => 'Nom de la mère',
                'required' => false,
                'attr' => ['maxlength' => 100]
            ])

            ->add('telephoneParent', TelType::class, [
                'label' => 'Téléphone du parent/tuteur',
                'required' => false,
                'attr' => ['maxlength' => 13]
            ])

            // 🔹 DOCUMENTS MULTIPLES
            ->add('documentUsers', CollectionType::class, [
                'entry_type' => DocumentUserType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => 'Documents joints',
                'prototype' => true,
                'attr' => ['class' => 'document-collection']
            ])

            ->add('signature', FileType::class, [
                'label' => 'Signature',
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif'],
                    ]),
                ],
            ])

            ->add('complementAdresse', TextType::class, [
                'label' => 'Complément d\'adresse',
                'required' => false
            ])

            ->add('adresse', EntityType::class, [
                'class' => ConfigQuartier::class,
                'choice_label' => 'nom',
                'label' => 'Adresse',
            ])

            ->add('zoneRattachement', EntityType::class, [
                'class' => ConfigZoneRattachement::class,
                'choice_label' => 'nom',
                'label' => 'Rattachement',
                'placeholder' => "Sélectionnez une ou plusieurs zones",
                'multiple' => true,
                'expanded' => true, // mettre true si tu veux des cases à cocher
                'required' => false,
            ])

            ->add('bienAffecte', EntityType::class, [
                'class' => Bien::class,
                'choice_label' => fn (Bien $bien) =>
                    $bien->getClient()->getNomComplet(). ' — ' . $bien->getNom()  ,
                'label' => 'Bien',
                'placeholder' => '— Sélectionner un bien —',
                'multiple' => false,     // ✅ UN SEUL
                'expanded' => false,     // ✅ select HTML
                'required' => false,
                'attr' => [
                    'class' => 'form-select',
                ],
            ])


          

            ->add('site', EntityType::class, [
                'class' => Site::class,
                'multiple' => true,
                'expanded' => true,
                'choice_label' => 'nom',
                "label" => "Rattachement *"
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Personel::class,
        ]);
    }
}
