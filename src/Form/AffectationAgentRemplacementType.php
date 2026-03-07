<?php

namespace App\Form;

use App\Entity\AffectationAgent;
use App\Entity\ContratSurveillance;
use App\Entity\Personel;
use App\Repository\PersonelRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AffectationAgentRemplacementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $site = $options['site'];
        $builder
            // 📅 Date de l’opération — obligatoire
            

            // 🧱 Poste (Jour, Nuit, Chef Poste…)
            ->add('poste', TextType::class, [
                'label' => 'Poste',
                'required' => true,
                'attr' => [
                    'maxlength' => 50,
                    'placeholder' => 'Ex: Jour, Nuit, Chef poste…',
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
        ]);
    }
}
