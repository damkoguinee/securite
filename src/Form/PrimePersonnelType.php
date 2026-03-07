<?php

namespace App\Form;

use App\Entity\Personel;
use App\Entity\PrimePersonnel;
use App\Repository\PersonelRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class PrimePersonnelType extends AbstractType
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
             ->add('montant', TextType::class, [
                'label' => 'Montant décaissé*',
                "required"  => true,
                'attr' => [
                    'placeholder' => '1 000 000',
                    'onkeyup' => "formatMontant(this)",
                    'style' => 'font-size: 20px; font-weight: bold; ',
                ]
            ])
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
            
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PrimePersonnel::class,
            'site' => null,

        ]);
    }
}
