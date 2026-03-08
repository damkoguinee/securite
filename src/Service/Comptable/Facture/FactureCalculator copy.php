<?php

namespace App\Service\Comptable\Facture;

use App\Entity\ContratSurveillance;

class FactureCalculator
{
    public function calculate(
        ContratSurveillance $contrat,
        \DateTime $periodeDebut,
        \DateTime $periodeFin,
        float $tauxProrata,
        int $nbJoursActifs,
        int $nbJoursMois = 30
    ): array {

        $montantHTInitial = 0;

        // 1️⃣ TYPE PRINCIPAL
        $montantHTInitial += $this->calculateMainTypes(
            $contrat,
            $periodeDebut,
            $periodeFin,
            $tauxProrata,
            $nbJoursActifs
        );

        // 2️⃣ CONTRATS COMPLEMENTAIRES
        $montantHTInitial += $this->calculateComplementaires(
            $contrat,
            $periodeDebut,
            $periodeFin,
            $nbJoursMois
        );

        // 3️⃣ REMISE + TVA
        return $this->calculateTaxes($contrat, $montantHTInitial);
    }


    private function calculateMainTypes(
        ContratSurveillance $contrat,
        \DateTime $periodeDebut,
        \DateTime $periodeFin,
        float $tauxProrata,
        int $nbJoursActifs
    ): float {

        $montant = 0;

        foreach ($contrat->getTypesSurveillance() as $type) {

            $tarifJournalier = $type->getTarifHoraire();
            $tarifMensuel = $type->getTarifMensuel();

            /* ====== 1️⃣ FACTURATION MENSUELLE ====== */
            if ($contrat->getModeFacturation() === 'mensuel') {

                if ($tarifJournalier && $tauxProrata != 1) {
                    $montant += $tarifJournalier * $nbJoursActifs;
                } else {
                    $montant += $tarifMensuel ?? 0;
                }

                continue;
            }

            /* ====== 2️⃣ FACTURATION PAR AGENT ====== */
            if ($contrat->getModeFacturation() === 'mensuel_agent') {

                $nbTotal =
                    ($type->getNbAgentsJour() ?? 0) +
                    ($type->getNbAgentsNuit() ?? 0);

                if ($nbTotal > 0) {

                    if ($tarifJournalier && $tauxProrata != 1) {
                        $montant += round(($tarifMensuel * $nbTotal * $nbJoursActifs) / 30);
                    } else {
                        $montant += ($tarifMensuel * $nbTotal) * $tauxProrata;
                    }
                }

                continue;
            }

            /* ====== 3️⃣ FACTURATION HORAIRE ====== */
            if ($contrat->getModeFacturation() === 'horaire') {

                $tarifHoraire = $type->getTarifHoraire() ?? 0;
                $totalHeures = 0;

                foreach ($contrat->getAffectationAgents() as $aff) {

                    $dateOp = $aff->getDateOperation();

                    if ($dateOp < $periodeDebut || $dateOp > $periodeFin) {
                        continue;
                    }

                    if (!$aff->isPresenceConfirme()) {
                        continue;
                    }

                    $debut = $aff->getHeureDebut();
                    $fin = $aff->getHeureFin();

                    if ($debut && $fin) {

                        $diff = $fin->getTimestamp() - $debut->getTimestamp();
                        $totalHeures += $diff / 3600;
                    }
                }

                $montant += $totalHeures * $tarifHoraire;
            }
        }

        return $montant;
    }


    private function calculateComplementaires(
        ContratSurveillance $contrat,
        \DateTime $periodeDebut,
        \DateTime $periodeFin,
        int $nbJoursMois
    ): float {

        $montant = 0;

        foreach ($contrat->getContratComplementaires() as $cc) {

            if ($cc->getDateDebut() > $periodeFin) {
                continue;
            }

            if ($cc->getDateFin() && $cc->getDateFin() < $periodeDebut) {
                continue;
            }

             /* ============================
            🔁 CALCUL PRORATA DU CC
            ============================ */
            
            $dateDebutCC = max($cc->getDateDebut(), $periodeDebut);
            $dateFinCC = $cc->getDateFin()
                ? min($cc->getDateFin(), $periodeFin)
                : $periodeFin;

            if ($dateFinCC < $dateDebutCC) {
                continue;
            }

            $nbJoursActifsCC = $dateDebutCC->diff($dateFinCC)->days;
            $tauxProrataCC = $nbJoursActifsCC / $nbJoursMois;

            foreach ($cc->getComplementTypeSurveillances() as $cts) {

                $tarif = $cts->getTarif() ?? 0;
                $tarifJournalier = $tarif / 30;

                if ($contrat->getModeFacturation() === 'mensuel') {

                    if ($tarifJournalier && $tauxProrataCC != 1) {
                        $montant += $tarifJournalier * $nbJoursActifsCC;
                    } else {
                        $montant += $tarif * $tauxProrataCC;
                    }

                    continue;
                }

                if ($contrat->getModeFacturation() === 'mensuel_agent') {

                    $nbAgents = $cts->getNbAgent() ?? 0;

                    if ($tarifJournalier && $tauxProrataCC != 1) {
                        $montant += ($tarifJournalier * $nbJoursActifsCC) * $nbAgents;
                    } else {
                        $montant += ($tarif * $nbAgents) * $tauxProrataCC;
                    }
                }
            }
        }

        return $montant;
    }


    private function calculateTaxes($contrat, float $montantHTInitial): array
    {
        $montantHT = $montantHTInitial;

        $remisePourcentage = $contrat->getRemise() ?? 0;
        $remiseMontant = 0;

        if ($remisePourcentage > 0) {

            $remiseMontant = $montantHT * ($remisePourcentage / 100);
            $montantHT -= $remiseMontant;
        }

        $tauxTVA = $contrat->getTva() ?? 0;
        $montantTVA = 0;

        if ($tauxTVA > 0) {
            $montantTVA = $montantHT * ($tauxTVA / 100);
        }

        $montantTTC = round($montantHT + $montantTVA, 2);

        return [
            'htInitial' => $montantHTInitial,
            'ht' => $montantHT,
            'tva' => $montantTVA,
            'ttc' => $montantTTC,
            'remisePourcentage' => $remisePourcentage,
            'remiseMontant' => $remiseMontant,
            'tauxTVA' => $tauxTVA
        ];
    }
}