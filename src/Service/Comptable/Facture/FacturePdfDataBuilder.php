<?php

namespace App\Service\Comptable\Facture;

use App\Entity\Facture;

class FacturePdfDataBuilder
{
    public function __construct(
        private readonly ProrataCalculator $prorataCalculator,
        private readonly FactureCalculator $factureCalculator,
        private readonly FactureEligibilityChecker $eligibilityChecker
    ) {}

    public function build(Facture $facture): array
    {
        $contrat = $facture->getContrat();

        $periodeDebut = $facture->getPeriodeDebut();
        $periodeFin = $facture->getPeriodeFin();

        if (!$this->eligibilityChecker->isEligible($contrat, $periodeDebut, $periodeFin)) {
            return [];
        }

        $prorata = $this->prorataCalculator->calculate(
            $contrat,
            $periodeDebut,
            $periodeFin
        );

        $calcul = $this->factureCalculator->calculate(
            $contrat,
            $periodeDebut,
            $periodeFin,
            $prorata['taux'],
            $prorata['joursActifs']
        );

        return [
            'prorata' => $prorata,
            'calcul' => $calcul
        ];
    }
}