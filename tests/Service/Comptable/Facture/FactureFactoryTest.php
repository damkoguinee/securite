<?php
namespace App\Tests\Service\Comptable\Facture;

use App\Entity\ContratSurveillance;
use App\Entity\Facture;
use App\Entity\Personel;
use App\Entity\Site;
use App\Entity\User;
use App\Repository\ConfigDeviseRepository;
use App\Repository\FactureRepository;
use App\Service\Comptable\Facture\FactureFactory;
use PHPUnit\Framework\TestCase;

class FactureFactoryTest extends TestCase
{
    public function testCreateFacture(): void 
    {
        $deviseRepo = $this->createMock(ConfigDeviseRepository::class);
        $factureRepo = $this->createMock(FactureRepository::class);

        $devise = $this->createMock(\App\Entity\ConfigDevise::class);

        $deviseRepo->method('findOneBy')
            ->willReturn($devise);

        $factureRepo->method('generateReference')
            ->willReturn('FAC-2024-001');

        $factureFactory = new FactureFactory($deviseRepo, $factureRepo);

        $contrat = $this->createMock(ContratSurveillance::class);
        $site = $this->createMock(Site::class);
        $user = $this->createMock(Personel::class);

        $bien = $this->createMock(\App\Entity\Bien::class);
        $client = $this->createMock(\App\Entity\Client::class);

        $contrat->method('getBien')->willReturn($bien);
        $bien->method('getClient')->willReturn($client);

        $periodeDebut = new \DateTime('2024-01-01');
        $periodeFin = new \DateTime('2024-01-31');

        $montant = [
            'htInitial' => 1000,
            'remisePourcentage' => 0,
            'remiseMontant' => 0,
            'ht' => 1000,
            'tauxTVA' => 18,
            'tva' => 180,
            'ttc' => 1180
        ];

        $facture = $factureFactory->create(
            $contrat,
            $site,
            $user,
            $periodeDebut,
            $periodeFin,
            $montant
        );

        $this->assertInstanceOf(Facture::class, $facture);
        $this->assertEquals('FAC-2024-001', $facture->getReference());
        $this->assertEquals(1180, $facture->getMontantTotal());
    }
}