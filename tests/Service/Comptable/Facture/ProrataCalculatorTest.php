<?php

namespace App\Tests\Service\Comptable\Facture;

use PHPUnit\Framework\TestCase;
use App\Service\Comptable\Facture\ProrataCalculator;
use App\Entity\ContratSurveillance;

class ProrataCalculatorTest extends TestCase
{
    public function testFullMonth(): void
    {
        $calculator = new ProrataCalculator();

        $contrat = $this->createMock(ContratSurveillance::class);

        $contrat->method('getDateDebut')
            ->willReturn(new \DateTime('2024-01-01'));

        $contrat->method('getDateFin')
            ->willReturn(null);

        $periodeDebut = new \DateTime('2024-01-01');
        $periodeFin = new \DateTime('2024-01-31');

        $result = $calculator->calculate(
            $contrat,
            $periodeDebut,
            $periodeFin
        );

        $this->assertEquals(1, $result['taux']);
        $this->assertEquals(30, $result['joursActifs']);
    }

    public function testPartialMonth(): void
    {
        $calculator = new ProrataCalculator();

        $contrat = $this->createMock(ContratSurveillance::class);

        $contrat->method('getDateDebut')
            ->willReturn(new \DateTime('2024-01-10'));

        $contrat->method('getDateFin')
            ->willReturn(null);

        $periodeDebut = new \DateTime('2024-01-01');
        $periodeFin = new \DateTime('2024-01-31');

        $result = $calculator->calculate(
            $contrat,
            $periodeDebut,
            $periodeFin
        );

        $this->assertEquals(21, $result['joursActifs']);
        $this->assertEquals(21 / 30, $result['taux']);
    }
}