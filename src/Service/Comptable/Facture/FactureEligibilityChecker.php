<?php

namespace App\Service\Comptable\Facture;

class FactureEligibilityChecker
{
    public function isEligible($contrat, $periodeDebut, $periodeFin): bool
    {
        if ($contrat->getDateDebut() > $periodeFin) {
            return false;
        }

        if ($contrat->getDateFin() !== null && $contrat->getDateFin() < $periodeDebut) {
            return false;
        }

        if (!in_array($contrat->getModeFacturation(), ['mensuel', 'mensuel_agent', 'horaire'])) {
            return false;
        }

        return true;
    }
}