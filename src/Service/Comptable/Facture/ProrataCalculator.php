<?php

namespace App\Service\Comptable\Facture;

use App\Entity\ContratSurveillance;

class ProrataCalculator
{
    public function calculate(
        ContratSurveillance $contrat,
        \DateTime $periodeDebut,
        \DateTime $periodeFin
    ): array {

        $dateDebutContrat = max($contrat->getDateDebut(), $periodeDebut);

        $dateFinContrat = $contrat->getDateFin()
            ? min($contrat->getDateFin(), $periodeFin)
            : $periodeFin;

        // Détection premier mois
        $estPremierMois =
            $contrat->getDateDebut()->format('Y-m') === $periodeDebut->format('Y-m')
            && $contrat->getDateDebut()->format('d') !== '01';

        // Détection dernier mois
        $estDernierMois =
            $contrat->getDateFin() !== null
            && $contrat->getDateFin()->format('Y-m') === $periodeDebut->format('Y-m')
            && $contrat->getDateFin()->format('d') !== $periodeFin->format('d');

        // Mois complet
        if (!$estPremierMois && !$estDernierMois) {

            return [
                'taux' => 1,
                'joursActifs' => 30
            ];
        }

        // règle mois commercial
        $jourDebut = min(30, (int) $dateDebutContrat->format('d'));
        $jourFin   = (int) $dateFinContrat->format('d');

        // si dernier jour réel du mois
        if ($dateFinContrat->format('d') == $dateFinContrat->format('t')) {
            $jourFin = 30;
        }

        $jourFin = min(30, $jourFin);

        $nbJoursActifs = ($jourFin - $jourDebut) + 1;

        $tauxProrata = $nbJoursActifs / 30;

        return [
            'taux' => $tauxProrata,
            'joursActifs' => $nbJoursActifs
        ];
    }
}