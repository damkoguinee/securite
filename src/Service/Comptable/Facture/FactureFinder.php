<?php

namespace App\Service\Comptable\Facture;

use App\Entity\Site;
use App\Repository\FactureRepository;
use Symfony\Component\HttpFoundation\Request;

class FactureFinder
{
    private FactureRepository $factureRepository;

    public function __construct(FactureRepository $factureRepository)
    {
        $this->factureRepository = $factureRepository;
    }

    public function findFactures(Site $site, Request $request): array
    {
        $periode = $request->query->get('periode');

        if ($periode) {

            $periode = new \DateTime($periode);

            $date1 = (clone $periode)->modify('first day of this month')->setTime(0,0,0);
            $date2 = (clone $periode)->modify('last day of this month')->setTime(23,59,59);

            return $this->factureRepository->findFacture(
                site: $site,
                startDate: $date1,
                endDate: $date2,
                zones: $request->get('zone') ?? null
            );
        }

        /**
         * FACTURE UNIQUE OU GROUPE
         */
        if ($request->get('facture')) {

            $facture = $this->factureRepository->find($request->get('facture'));

            if (!$facture) {
                return [];
            }

            $bien = $facture->getContrat()->getBien();
            $groupe = $bien->getGroupeFacturation();
            $client = $bien->getClient();

            $periode = $facture->getPeriodeDebut();

            $date1 = (clone $periode)->modify('first day of this month')->setTime(0,0,0);
            $date2 = (clone $periode)->modify('last day of this month')->setTime(23,59,59);

            /**
             * Si le bien appartient à un groupe → récupérer toutes les factures du groupe
             */
            if ($groupe) {

                return $this->factureRepository->findFacture(
                        site: $site, 
                        client: $client,
                        startDate: $date1,
                        endDate: $date2,
                );
            }

            /**
             * Sinon retourner la facture seule
             */
            return [$facture];
        }

        return [];
    }
}