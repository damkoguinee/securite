<?php

namespace App\Form;

use App\Entity\Personel;
use App\Entity\AffectationAgent;
use App\Entity\ContratSurveillance;
use App\Repository\PersonelRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class AffectationAgentPermutationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $site = $options['site'];
        $builder
        

            ->add('dateDebutPermutation', DateType::class, [
                'mapped' => false,                 // ❌ pas en base
                'widget' => 'single_text',
                'label' => 'Début Permut',
                'required' => true,
                'data' => $options['dateOperation'] ?? new \DateTime(),
            ])

            ->add('dateFinPermutation', DateType::class, [
                'mapped' => false,                 // ❌ pas en base
                'widget' => 'single_text',
                'label' => 'Fin Permut',
                'required' => true,
                'data' => $options['dateOperation'] ?? new \DateTime(),
            ])

            



            ->add('commentaire', TextareaType::class, [
                'label' => 'Commentaire / Observation',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Notes ou remarques éventuelles...',
                ],
            ])

            // 👮‍♂️ Personnel concerné
            ->add('personnel', EntityType::class, [
                'class' => Personel::class,
                'choice_label' => function (Personel $p) {
                    return strtoupper($p->getNomCompletUser());
                },
                'label' => 'Agent concerné',
                'placeholder' => '— Sélectionner un agent —',
                'required' => true,

                'query_builder' => function (PersonelRepository $er) use ($site) {
                    return $er->createQueryBuilder('p')
                        ->where(':site MEMBER OF p.site')
                        ->andWhere('p.fonction = :fonction')
                        ->setParameter('site', $site)
                        ->setParameter('fonction', 'agent');

                },
            ]);
           
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AffectationAgent::class,
            'site' => NULL,
            'dateOperation' => null,
        ]);
    }
}
