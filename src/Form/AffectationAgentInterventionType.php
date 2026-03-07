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

class AffectationAgentInterventionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $site = $options['site'];
        $builder
            // 📅 Date de l’opération — obligatoire
            

            // 🧱 Poste (Jour, Nuit, Chef Poste…)
            ->add('poste', ChoiceType::class, [
                'label' => 'Poste',
                'required' => true,
                'choices' => [
                    'Jour' => 'Jour',
                    'Nuit' => 'Nuit',
                ],
                'placeholder' => '— Sélectionner un poste —',
            ])

            // ⏰ Heure début / fin
            ->add('heureDebut', TimeType::class, [
                'widget' => 'single_text',
                'label' => 'Heure de début',
                'required' => false,
            ])
            ->add('heureFin', TimeType::class, [
                'widget' => 'single_text',
                'label' => 'Heure de fin',
                'required' => false,
            ])

            ->add('dateFinIntervention', DateType::class, [
                'mapped' => false,                 // ❌ pas en base
                'widget' => 'single_text',
                'label' => 'Fin de l’intervention',
                'required' => true,
                'data' => $options['dateOperation'] ?? new \DateTime(),
            ])

            ->add('joursIntervention', ChoiceType::class, [
                'label' => 'Jours',
                'mapped' => false,              // ❌ pas en base
                'required' => true,
                'expanded' => true,             // ✅ cases à cocher
                'multiple' => true,
                'choices' => [
                    'Tous les jours' => 'ALL',
                    'Lundi'    => 'Mon',
                    'Mardi'    => 'Tue',
                    'Mercredi' => 'Wed',
                    'Jeudi'    => 'Thu',
                    'Vendredi' => 'Fri',
                    'Samedi'   => 'Sat',
                    'Dimanche' => 'Sun',
                ],
                'data' => ['ALL'],               // ✅ par défaut
            ])



            ->add('commentaire', TextareaType::class, [
                'label' => 'Commentaire / Observation',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Notes ou remarques éventuelles...',
                ],
            ])

            // 📜 Contrat associé → on affiche bien le nom du bien et la description
            ->add('contrat', EntityType::class, [
                'class' => ContratSurveillance::class,
                'choice_label' => function (ContratSurveillance $c) {
                    $bien = $c->getBien() ? $c->getBien()->getNom() : 'N/A';
                    return sprintf('%s — %s', $bien, $c->getBien()->getDescription() ?: 'Sans description');
                },
                'label' => 'Contrat de surveillance',
                'placeholder' => '— Sélectionner un contrat —',
                'required' => true,
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
