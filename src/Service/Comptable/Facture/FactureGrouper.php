<?php
namespace App\Service\Comptable\Facture;

use App\Repository\MouvementCollaborateurRepository;

class FactureGrouper
{
    private MouvementCollaborateurRepository $mouvementCollabRepo;
    
    public function __construct(MouvementCollaborateurRepository $mouvementCollabRepo)
    {
        $this->mouvementCollabRepo = $mouvementCollabRepo;
    }

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

    public function groupFactureForPDF(array $factures): array
    {
        $facturesGroup = [];

        foreach ($factures as $facture) {

            $contrat = $facture->getContrat();
            $bien    = $contrat->getBien();
            $client  = $bien->getClient();

            $groupeFacturation = $bien->getGroupeFacturation();
                    // 🔑 clé de regroupement
            if ($groupeFacturation) {
                $key = 'CLIENT_'.$client->getId().'_GROUPE_'.$groupeFacturation->getId();
                $libelle = $groupeFacturation->getNom();
            } else {
                $key = 'CLIENT_'.$client->getId().'_BIEN_'.$bien->getId();
                $libelle = $bien->getNom();
            }


            if (!isset($facturesGroup[$key])) {

                $dateOp = $facture->getDateEmission();

                $facturesGroup[$key] = [
                    'client'        => $client,
                    'groupe'        => $groupeFacturation,
                    'libelle'       => $libelle,
                    'biens'         => [],
                    'factures'      => [],
                    'solde_actuel'  => $this->mouvementCollabRepo->findSoldeCollaborateur($client),
                    'ancien_solde'  => $this->mouvementCollabRepo->findAncienSoldeCollaborateur($client, $dateOp),
                    'mode'            => $facture->getContrat()->getModeFacturation(),
                    'modeFacturation'            => $client->getModeFacturation(),
                ];
            }

            

            $facturesGroup[$key]['factures'][] = $facture;
            $facturesGroup[$key]['biens'][$bien->getId()] = $bien;

        }
            
        return $facturesGroup;

    }
}