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
        $mainTypes = $this->calculateMainTypes(
            $contrat,
            $periodeDebut,
            $periodeFin,
            $tauxProrata,
            $nbJoursActifs
        );

        $complementaires = $this->calculateComplementaires(
            $contrat,
            $periodeDebut,
            $periodeFin,
            $nbJoursMois
        );

        $montantHTInitial = $mainTypes['totalHT'] + $complementaires['totalHT'];

        $taxes = $this->calculateTaxes($contrat, $montantHTInitial);

        return [
            'lignes' => array_merge($mainTypes['lignes'], $complementaires['lignes']),
            'htInitial' => $montantHTInitial,
            'ht' => $taxes['ht'],
            'tva' => $taxes['tva'],
            'ttc' => $taxes['ttc'],
            'remisePourcentage' => $taxes['remisePourcentage'],
            'remiseMontant' => $taxes['remiseMontant'],
            'tauxTVA' => $taxes['tauxTVA'],
        ];
    }

    private function calculateMainTypes(
        ContratSurveillance $contrat,
        \DateTime $periodeDebut,
        \DateTime $periodeFin,
        float $tauxProrata,
        int $nbJoursActifs
    ): array {
        $totalHT = 0;
        $lignes = [];

        $remisePourcentage = $contrat->getRemise() ?? 0;
        $tauxTVA = $contrat->getTva() ?? 0;

        foreach ($contrat->getTypesSurveillance() as $type) {
            $nbJour = $type->getNbAgentsJour() ?? 0;
            $nbNuit = $type->getNbAgentsNuit() ?? 0;
            $nbTotal = $nbJour + $nbNuit;

            $tarifMensuel = $type->getTarifMensuel() ?? 0;
            $tarifJournalier = $type->getTarifHoraire();

            $montantHT = 0;

            if ($contrat->getModeFacturation() === 'mensuel') {
                if ($tarifJournalier && $tauxProrata != 1) {
                    $montantHT = $tarifJournalier * $nbJoursActifs;
                } else {
                    $montantHT = $tarifMensuel;
                }
            }

            if ($contrat->getModeFacturation() === 'mensuel_agent') {
                if ($tarifJournalier && $tauxProrata != 1) {
                    $montantHT = ($tarifMensuel * $nbTotal * $nbJoursActifs) / 30;
                } else {
                    $montantHT = $tarifMensuel * $nbTotal * $tauxProrata;
                }
            }

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

                $montantHT = $totalHeures * $tarifHoraire;
            }

            $montantRemise = $remisePourcentage > 0 ? $montantHT * ($remisePourcentage / 100) : 0;
            $montantApresRemise = $montantHT - $montantRemise;
            $montantTVA = $tauxTVA > 0 ? $montantApresRemise * ($tauxTVA / 100) : 0;
            $montantTTC = $montantApresRemise + $montantTVA;

            $totalHT += $montantHT;

            $lignes[] = [
                'categorie' => 'principal',
                'type' => $type->getTypeSurveillance()?->getNom(),
                'agentsJour' => $nbJour,
                'agentsNuit' => $nbNuit,
                'agentsTotal' => $nbTotal,
                'tarif' => $tarifMensuel,
                'montantHT' => $montantHT,
                'remise' => $montantRemise,
                'tva' => $montantTVA,
                'ttc' => $montantTTC,
            ];
        }

        return [
            'lignes' => $lignes,
            'totalHT' => $totalHT,
        ];
    }

    private function calculateComplementaires(
        ContratSurveillance $contrat,
        \DateTime $periodeDebut,
        \DateTime $periodeFin,
        int $nbJoursMois
    ): array {
        $totalHT = 0;
        $lignes = [];

        $remisePourcentage = $contrat->getRemise() ?? 0;
        $tauxTVA = $contrat->getTva() ?? 0;

        foreach ($contrat->getContratComplementaires() as $cc) {
            if ($cc->getDateDebut() > $periodeFin) {
                continue;
            }

            if ($cc->getDateFin() && $cc->getDateFin() < $periodeDebut) {
                continue;
            }

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
                $nbAgents = $cts->getNbAgent() ?? 0;
                $tarif = $cts->getTarif() ?? 0;
                $tarifJournalier = $tarif / 30;

                $montantHT = 0;

                if ($contrat->getModeFacturation() === 'mensuel') {
                    if ($tarifJournalier && $tauxProrataCC != 1) {
                        $montantHT = $tarifJournalier * $nbJoursActifsCC;
                    } else {
                        $montantHT = $tarif * $tauxProrataCC;
                    }
                }

                if ($contrat->getModeFacturation() === 'mensuel_agent') {
                    if ($tarifJournalier && $tauxProrataCC != 1) {
                        $montantHT = ($tarifJournalier * $nbJoursActifsCC) * $nbAgents;
                    } else {
                        $montantHT = ($tarif * $nbAgents) * $tauxProrataCC;
                    }
                }

                $montantRemise = $remisePourcentage > 0 ? $montantHT * ($remisePourcentage / 100) : 0;
                $montantApresRemise = $montantHT - $montantRemise;
                $montantTVA = $tauxTVA > 0 ? $montantApresRemise * ($tauxTVA / 100) : 0;
                $montantTTC = $montantApresRemise + $montantTVA;

                $totalHT += $montantHT;

                $lignes[] = [
                    'categorie' => 'complementaire',
                    'contratComplementaireId' => $cc->getId(),
                    'motif' => $cc->getMotif(),
                    'dateDebut' => $cc->getDateDebut(),
                    'dateFin' => $cc->getDateFin(),
                    'type' => $cts->getTypeSurveillance()?->getNom(),
                    'agentsJour' => null,
                    'agentsNuit' => null,
                    'agentsTotal' => $nbAgents,
                    'tarif' => $tarif,
                    'montantHT' => $montantHT,
                    'remise' => $montantRemise,
                    'tva' => $montantTVA,
                    'ttc' => $montantTTC,
                ];
            }
        }

        return [
            'lignes' => $lignes,
            'totalHT' => $totalHT,
        ];
    }

    private function calculateTaxes(ContratSurveillance $contrat, float $montantHTInitial): array
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
            'ht' => $montantHT,
            'tva' => $montantTVA,
            'ttc' => $montantTTC,
            'remisePourcentage' => $remisePourcentage,
            'remiseMontant' => $remiseMontant,
            'tauxTVA' => $tauxTVA,
        ];
    }
}