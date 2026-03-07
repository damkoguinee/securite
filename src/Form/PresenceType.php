<?php

namespace App\Form;

use App\Entity\Presence;
use App\Entity\AffectationAgent;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PresenceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // 📅 Date et heure du pointage
            ->add('datePointage', DateTimeType::class, [
                'label' => 'Date et heure du pointage',
                'widget' => 'single_text',
                'required' => true,
                'html5' => true,
            ])

            // 🔁 Type du pointage
            ->add('typePointage', ChoiceType::class, [
                'label' => 'Type de pointage',
                'choices' => [
                    'Entrée' => 'entree',
                    'Sortie' => 'sortie',
                ],
                'placeholder' => 'Sélectionnez...',
                'required' => true,
            ])

            // ⚙️ Mode de pointage
            ->add('mode', ChoiceType::class, [
                'label' => 'Mode d’enregistrement',
                'choices' => [
                    'Manuel' => 'manuel',
                    'QR Code' => 'qr',
                    'Badge' => 'badge',
                    'Mobile' => 'mobile',
                ],
                'placeholder' => 'Choisissez le mode',
                'required' => true,
            ])

            // 🚦 Statut du pointage
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Présent' => 'present',
                    'Retard' => 'retard',
                    'Absent' => 'absent',
                    'Remplacé' => 'remplacé',
                    'Mission' => 'mission',
                ],
                'placeholder' => '— Aucun —',
                'required' => false,
            ])

            // 💬 Commentaire libre
            ->add('commentaire', TextareaType::class, [
                'label' => 'Commentaire',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Ex : présence confirmée par le superviseur...',
                ],
            ])

            // 🔗 Affectation liée
            ->add('affectationAgent', EntityType::class, [
                'class' => AffectationAgent::class,
                'label' => 'Affectation concernée',
                'choice_label' => function (AffectationAgent $a) {
                    $nom = $a->getPersonnel()?->getNomComplet() ?? '—';
                    $poste = $a->getPoste() ?? 'Poste';
                    $date = $a->getDateOperation()?->format('d/m/Y');
                    return sprintf('%s — %s (%s)', $nom, $poste, $date);
                },
                'placeholder' => 'Sélectionnez une affectation',
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Presence::class,
        ]);
    }
}
