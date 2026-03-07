<?php

namespace App\Form;

use App\Entity\Client;
use App\Entity\ConfigQuartier;
use App\Entity\Site;
use App\Repository\SiteRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ClientEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['data'];
        $builder

        ->add('societe', null, [
            "constraints"   =>  [
                new Length([
                    "max"           =>  100,
                    "maxMessage"    =>  "La société ne doit pas contenir plus de 100 caractères",
                    
                ]),
                new NotBlank(["message" => "la société ne peut pas être vide !"])
            ],
            "required"  =>true,
            "label"     =>"Société"
        ])

        
        ->add('nom', null, [
            "constraints"   =>  [
                new Length([
                    "max"           =>  50,
                    "maxMessage"    =>  "Le nom ne doit pas contenir plus de 50 caractères",
                    
                ]),
                new NotBlank(["message" => "le nom ne peut pas être vide !"])
            ],
            "required"  =>true,
            "label"     =>"Nom *"
        ])
        ->add('prenom',null,[
            "constraints"   =>  [
                new Length([
                    "max"           =>  100,
                    "maxMessage"    =>  "Le prénom ne doit pas contenir plus de 100 caractères"
                ]),
                new NotBlank(["message" => "le prénom ne peut pas être vide !"])
            ],
            "required"  =>true,
            "label"     =>"Prénom *"
        ])

        ->add('telephone', TelType::class, [
            "constraints"   =>  [
                new Length([
                    "min"           =>  9,
                    "minMessage"    =>  "Le téléphone ne doit pas contenir moins de 9 ",
                    
                ]),
                new NotBlank(["message" => "le numéro téléphone ne peut pas être vide !"])
            ],
            "required"  =>true,
            "label"     =>"Numéro de téléphone *"
        ])
        ->add('email', EmailType::class, [
            "constraints"   =>  [
                new Length([
                    "max"           =>  150,
                    "maxMessage"    =>  "L'émail ne doit pas contenir plus de 150 caractères",
                    
                ])
            ],
            "required"  =>false,
            "label"     =>"Adresse Email"
        ])

        ->add('document', FileType::class, [
            'label' => 'Document',
            'required' => false, // Document est optionnel
            'mapped' => false,  // Le fichier n'est pas directement lié à l'entité
            'allow_file_upload' => true,
            'attr' => ['accept' => '.pdf,.doc,.docx,.jpeg,.png'],
        ])
        ->add('complementAdresse', TextType::class, [
            'label' => 'Complément d\'adresse',
            'required' => false,
            'attr' => ['maxlength' => 255],
        ])
        
        ->add('site', EntityType::class, [
            'class' => Site::class,
            'multiple' => true,        // Permet la sélection multiple
            'expanded' => true,
            'choice_label' => 'nom',
            "label"     =>"Site de rattachement *"

        ])

        ->add('sexe', ChoiceType::class, [
            'choices'  => [
                'Homme' => 'homme',
                'Femme' => 'femme'
            ],
            'placeholder' => 'Sélectionnez le sexe', // Ajoute une option vide
            'required' => false, // Rends le champ nullable
            'label' => 'Sexe',
            'data' => 'homme',
        ])

        ->add('dateNaissance', DateType::class, [
            'widget' => 'single_text', // Permet un champ HTML5 (format yyyy-mm-dd)
            'required' => false,       // Rends le champ nullable
            'label' => 'Date de naissance',
            'html5' => true,
        ])

        ->add('photo', FileType::class, [
            'label' => 'Photo',
            'required' => false, // Photo est optionnelle
            'mapped' => false,  // Le fichier n'est pas directement lié à l'entité
            'constraints' => [
                new File([
                    'maxSize' => '2M',
                    'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif'],
                    'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG, PNG, GIF)',
                ]),
            ], 
        ])

        ->add('username', null, [
            "constraints"   =>  [
                new Length([
                    "max"           =>  180,
                    "maxMessage"    =>  "Le pseudo ne doit pas contenir plus de 180 caractères",
                    
                ]),
                new NotBlank(["message" => "le pseudo ne peut pas être vide !"])
            ],
            "required"  =>true,
            "label"     =>"Identifiant*"
        ])
        ->add('password', null, [
            "mapped"        =>false,
            "required"      => $user->getId() ? false : true,
            "label"     =>"Mot de passe*"

        ])
         ;

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Client::class,
            'sites' => [],
        ]);
    }
}
