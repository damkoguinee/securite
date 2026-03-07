<?php
namespace App\Service\Comptable\Facture;

use App\Entity\Site;
use App\Entity\User;
use App\Repository\ConfigDeviseRepository;
use App\Repository\FactureRepository;
use App\Service\Comptable\Contrat\ContratService;
use Doctrine\ORM\EntityManagerInterface;

class FactureGenerator
{
   
    private EntityManagerInterface $entityManager;
    private FactureRepository $factureRepo;
    private ContratService $contratService;
    private FactureEligibilityChecker $factureEligibility;
    private FactureCalculator $factureCalculator;
    private ProrataCalculator $prorataCalculator;
    private FactureFactory $factureFactory;

    public function __construct(
        FactureRepository $factureRepo,
        ConfigDeviseRepository $deviseRepo,
        ContratService $contratService,
        FactureEligibilityChecker $factureEligibility,
        ProrataCalculator $prorataCalculator,
        FactureCalculator $factureCalculator,
        FactureFactory $factureFactory,
        EntityManagerInterface $entityManager,
    )
    {
        $this->factureRepo = $factureRepo;
        $this->contratService = $contratService;
        $this->factureEligibility = $factureEligibility;
        $this->prorataCalculator = $prorataCalculator;
        $this->factureCalculator = $factureCalculator;
        $this->factureFactory = $factureFactory;
        $this->entityManager = $entityManager;
    }

    public function generateFactures(
        Site $site, 
        User $user,
        int $mois, 
        int $annee, 
        ?int $clientId = null
    ): array
    {
        /* ================================
        2️⃣ CALCUL PÉRIODE
        ================================= */
        $periodeDebut = new \DateTime("$annee-$mois-01");
        $periodeFin   = (clone $periodeDebut)->modify('last day of this month');
        
        /* ================================
        3️⃣ CONTRATS ACTIFS
        ================================= */
        $contrats = $this->contratService->getContrat(site: $site, clientId: $clientId);
      
        $nbFactures = 0;

        $this->entityManager->beginTransaction();
        try {

            foreach ($contrats as $contrat) {
                
                /* ================================
                4️⃣ CONDITIONS D’ÉLIGIBILITÉ
                ================================= */

                if (!$this->factureEligibility->isEligible($contrat, $periodeDebut, $periodeFin)) {
                    continue;
                }

                $factureExistante = $this->factureRepo->findFactureForContratAndPeriod(
                    $contrat,
                    $periodeDebut,
                    $periodeFin
                );

                if ($factureExistante) continue;


                /* ================================================
                5️⃣ CALCUL PRORATA DU CONTRAT (correct)
                ================================================= */

                $prorata = $this->prorataCalculator->calculate(
                    $contrat,
                    $periodeDebut,
                    $periodeFin
                );

                $tauxProrata = $prorata['taux'];
                $nbJoursActifs = $prorata['joursActifs'];

                /* ================================================
                6️⃣ CALCUL HT (TYPE PRINCIPAL)
                ================================================= */
                $montant = $this->factureCalculator->calculate(
                    $contrat,
                    $periodeDebut,
                    $periodeFin,
                    $tauxProrata,
                    $nbJoursActifs
                );


                /* ================================================
                9️⃣ CRÉATION DE LA FACTURE
                ================================================= */

                $facture = $this->factureFactory->create(
                    $contrat,
                    $site,
                    $user,
                    $periodeDebut,
                    $periodeFin,
                    $montant
                );


                $this->entityManager->persist($facture);
                $nbFactures++;
            }

            $this->entityManager->flush();
            $this->entityManager->commit();

        } catch (\Throwable $e) {

            $this->entityManager->rollback();
            throw $e;
        }

        return [
            'count' => $nbFactures,
            'periode' => $periodeDebut
        ];
    }
}