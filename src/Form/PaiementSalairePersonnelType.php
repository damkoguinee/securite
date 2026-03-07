<?php

namespace App\Form;

use App\Entity\Caisse;
use App\Entity\ConfigModePaiement;
use App\Entity\ContratSurveillance;
use App\Entity\PaiementSalairePersonnel;
use App\Entity\Personel;
use App\Repository\CaisseRepository;
use App\Repository\PersonelRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class PaiementSalairePersonnelType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $site = $options['site'];
        $builder
            ->add('periode', DateType::class, [
                'label' => 'Période*',
                'widget' => 'single_text',
                'required' => true,
                'attr' => [
                    'max' => (new \DateTime())->format('Y-m-d'),
                ],
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
            // ->add('salaireBrut')
            // ->add('prime')
            // ->add('avanceSalaire')
            // ->add('cotisation')
            // ->add('salaireNet')
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
            
            ->add('contrat', EntityType::class, [
                'class' => ContratSurveillance::class,
                'choice_label' => fn(ContratSurveillance $contrat) => 
                        $contrat->getBien()->getNom()." ".$contrat->getBien()->getClient()->getNomCompletUser(),
                'label' => "Site de l'agent " , 
                'required' => false,
                'placeholder' => '- Sélectionnez un site -'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PaiementSalairePersonnel::class,
            'site' => null,
        ]);
    }
}
