<?php

namespace App\Form;

use App\Entity\ConfigSalaire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class ConfigSalaireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            // 🔖 Code interne (ex: CNSS, PRIME_REPOS, CHARGE_PATRONALE)

            ->add('code', ChoiceType::class, [
                'label' => 'Code',
                'required' => true,
                'choices' => [
                    'Repos travaillé'        => 'REPOS_TRAVAILLE',
                    'Journée entière (24h agent)'        => 'JOURNEE_ENTIERE',
                    'Prime'                  => 'PRIME',
                    'Pénalité'               => 'PENALITE',
                    'Charge salariale'       => 'CHARGE_SALARIALE',
                    'Charge patronale'       => 'CHARGE_PATRONALE',
                    'CNSS salarié'           => 'CNSS_SALARIE',
                    'CNSS employeur'         => 'CNSS_EMPLOYEUR',
                    'Impôt / Taxe'           => 'TAXE',
                ],
                'placeholder' => '— Sélectionner un code —',
                'help' => 'Code interne utilisé dans les calculs de salaire',
                'attr' => [
                    'class' => 'form-select',
                ],
            ])


            // 📝 Libellé affiché
            ->add('libelle', TextType::class, [
                'label' => 'Libellé',
                'required' => true,
                'attr' => [
                    'maxlength' => 150,
                    'placeholder' => 'Ex: Cotisation CNSS salarié',
                ],
            ])

            // 📊 Taux (%)
            ->add('taux', NumberType::class, [
                'label' => 'Taux (%)',
                'required' => false,
                'scale' => 2,
                'attr' => [
                    'placeholder' => 'Ex: 5.5',
                ],
                'help' => 'Laisser vide si montant fixe',
            ])

            // 🧮 Type de calcul
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'required' => false,
                'choices' => [
                    'Charge salariale'   => 'salarial',
                    'Charge patronale'  => 'patronal',
                    'Prime'              => 'prime',
                    'Déduction'          => 'deduction',
                    'Repos travaille'          => 'REPOS_TRAVAILLE',
                ],
                'placeholder' => '— Sélectionner un type —',
            ])

            

            ->add('montant', NumberType::class, [
                'label' => 'Montant',
                'required' => false,
                'scale' => 2,
                'attr' => [
                    'placeholder' => 'Ex: 50',
                    'min' => 0,
                    'step' => 0.01,
                ],
                'help' => 'Laisser vide si non applicable',
            ])
            // ✅ Actif / inactif
            ->add('actif', CheckboxType::class, [
                'label' => 'Configuration active',
                'required' => false,
                'data' => true, // ✔️ actif par défaut
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ConfigSalaire::class,
        ]);
    }
}
