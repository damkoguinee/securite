<?php
namespace App\Service\Comptable\Facture;


class FactureGrouper
{
    public function groupClientAndMonth(array $factures): array
    {
        $facturesGroup = [];
        foreach ($factures as $facture) {
            $client = $facture->getContrat()->getBien()->getClient();
            $mois = $facture->getPeriodeDebut()->format('Y-m');

            $key = $client->getId().'_'.$mois;

            if (!isset($facturesGroup[$key])) {
                $facturesGroup[$key] = [
                    'client' => $client,
                    'mois' => $mois,
                    'facturations' => [],
                    'total' => 0
                ];
            }

            $facturesGroup[$key]['facturations'][] = $facture;
            $facturesGroup[$key]['total'] += $facture->getMontantTotal();
        }

        return $facturesGroup;
    }
}