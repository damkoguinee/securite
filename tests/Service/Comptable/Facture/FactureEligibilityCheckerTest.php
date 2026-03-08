<?php

namespace App\Tests\Service\Comptable\Facture;

use PHPUnit\Framework\TestCase;
use App\Service\Comptable\Facture\FactureEligibilityChecker;
use App\Entity\ContratSurveillance;

class FactureEligibilityCheckerTest extends TestCase
{
    public function testEligibleContract(): void
    {
        $checker = new FactureEligibilityChecker();

        $contrat = $this->createMock(ContratSurveillance::class);

        $contrat->method('getDateDebut')
            ->willReturn(new \DateTime('2024-01-01'));

        $contrat->method('getDateFin')
            ->willReturn(null);

        $contrat->method('getModeFacturation')
            ->willReturn('mensuel');

        $periodeDebut = new \DateTime('2024-01-01');
        $periodeFin = new \DateTime('2024-01-31');

        $result = $checker->isEligible($contrat, $periodeDebut, $periodeFin);

        $this->assertTrue($result);
    }

    public function testContractStartsAfterPeriod(): void
    {
        $checker = new FactureEligibilityChecker();

        $contrat = $this->createMock(ContratSurveillance::class);

        $contrat->method('getDateDebut')
            ->willReturn(new \DateTime('2024-02-01'));

        $contrat->method('getDateFin')
            ->willReturn(null);

        $contrat->method('getModeFacturation')
            ->willReturn('mensuel');

        $periodeDebut = new \DateTime('2024-01-01');
        $periodeFin = new \DateTime('2024-01-31');

        $result = $checker->isEligible($contrat, $periodeDebut, $periodeFin);

        $this->assertFalse($result);
    }

    public function testInvalidModeFacturation(): void
    {
        $checker = new FactureEligibilityChecker();

        $contrat = $this->createMock(ContratSurveillance::class);

        $contrat->method('getDateDebut')
            ->willReturn(new \DateTime('2024-01-01'));

        $contrat->method('getDateFin')
            ->willReturn(null);

        $contrat->method('getModeFacturation')
            ->willReturn('annuel');

        $periodeDebut = new \DateTime('2024-01-01');
        $periodeFin = new \DateTime('2024-01-31');

        $result = $checker->isEligible($contrat, $periodeDebut, $periodeFin);

        $this->assertFalse($result);
    }
}