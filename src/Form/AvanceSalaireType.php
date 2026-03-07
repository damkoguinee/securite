<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Caisse;
use App\Entity\Devise;
use App\Entity\Personel;
use App\Entity\ComptesDepot;
use App\Entity\ConfigDevise;
use App\Entity\ModePaiement;
use App\Entity\PointDeVente;
use App\Entity\AvanceSalaire;
use App\Entity\TypesPaiements;
use App\Entity\ConfigModePaiement;
use App\Entity\ContratSurveillance;
use App\Repository\UserRepository;
use App\Repository\CaisseRepository;
use App\Repository\PersonelRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\MonthType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class AvanceSalaireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $site = $options['site'];
        $builder
            ->add('personnel', EntityType::class, [
                'class' => Personel::class,
                'choice_label' => function (Personel $a) {
                    return ucwords($a->getPrenom())." ".strtoupper($a->getNom());
                },
                'placeholder' => 'Sélectionner un personnel',
                'required' => true,
                'label' => 'Personnel*',
                'query_builder' => function (PersonelRepository $er) use( $site) {
                    return $er->createQueryBuilder('p')
                        ->andWhere(':site MEMBER OF p.site')
                        ->setParameter('site', $site)
                        ->orderBy('p.prenom', 'ASC') 
                        ->addOrderBy('p.nom', 'ASC'); 
                },
            ])
            ->add('periode', DateType::class, [
                'label' => 'Période*',
                'widget' => 'single_text',
                'required' => true,
                'attr' => [
                    'max' => (new \DateTime())->format('Y-m-d'),
                ],
            ])
            ->add('montant', TextType::class, [
                'label' => 'Montant décaissé*',
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
            
            ->add('commentaire', null, [
                "constraints"   =>  [
                    new Length([
                        "max"           =>  255,
                        "maxMessage"    =>  "Ce champs ne doit pas contenir plus de 255 caractères",
                        
                    ])
                ],
                "attr"  =>["placeholder" =>"saisir le bordereau/N° chèque "],
                "required"  => false,
                "label"     =>"Détails paiement"
            ])

            ->add('modePaie', EntityType::class, [
                "class"             => ConfigModePaiement::class,
                "choice_label"  =>  function(ConfigModePaiement $a){
                    return $a->getDesignation();
                },
                // "choice_label"      =>"valeurDimension",
                "placeholder"       =>"Selectionner le mode de paiement",
                "required"  =>true,
                "label"     =>"Mode de paiement*"
            ])
            ->add('caisse', EntityType::class, [
                'class' => Caisse::class,
                'choice_label' => 'nom',
                'required' => true,
                'label' => 'Caisse décaissée*',
                'placeholder' => 'Sélectionnez un compte',
                'query_builder' => function (CaisseRepository $er) use ($site) {
                    return $er->createQueryBuilder('c')
                        ->where(':site MEMBER OF c.site')
                        ->setParameter('site', $site);
                },
            ])

            ->add('contrat', EntityType::class, [
                'class' => ContratSurveillance::class,
                'choice_label' => fn(ContratSurveillance $contrat) =>
                    $contrat->getBien()->getClient()->getNomCompletUser(),
                'label' => "Site de l'agent " , 
                'required' => false,
                'placeholder' => '- Sélectionnez un site -'
            ])

        ;
    }

    
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AvanceSalaire::class,
            'selected_user' => null,
            'site' => null,
        ]);
    }
}
