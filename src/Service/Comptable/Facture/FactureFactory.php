<?php
namespace App\Service\Comptable\Facture;

use App\Entity\Facture;
use App\Entity\MouvementCollaborateur;
use App\Entity\Site;
use App\Entity\User;
use App\Entity\ContratSurveillance;
use App\Repository\ConfigDeviseRepository;
use App\Repository\FactureRepository;

class FactureFactory
{
    private ConfigDeviseRepository $deviseRepo;
    private FactureRepository $factureRepo;

    public function __construct(
        ConfigDeviseRepository $deviseRepo,
        FactureRepository $factureRepo
    ) {
        $this->deviseRepo = $deviseRepo;
        $this->factureRepo = $factureRepo;
    }

    public function create(
        ContratSurveillance $contrat,
        Site $site,
        User $user,
        \DateTime $periodeDebut,
        \DateTime $periodeFin,
        array $montant
    ): Facture {

        $facture = new Facture();

        $facture->setContrat($contrat)
            ->setSite($site)
            ->setPeriodeDebut($periodeDebut)
            ->setPeriodeFin($periodeFin)
            ->setDateEmission(new \DateTime())
            ->setDateEcheance((new \DateTime())->modify('+10 days'))
            ->setStatut("en_attente")
            ->setDevise($this->deviseRepo->findOneBy([]))
            ->setDateSaisie(new \DateTime())
            ->setSaisiePar($user);

        $reference = $this->factureRepo->generateReference(
            periodeDebut: $periodeDebut
        );

        $facture->setReference($reference);

        $facture->setMontantHT($montant['htInitial'])
            ->setRemisePourcentage($montant['remisePourcentage'])
            ->setRemiseMontant(round($montant['remiseMontant'], 2))
            ->setBaseTVA($montant['ht'])
            ->setTauxTVA($montant['tauxTVA'])
            ->setMontantTVA(round($montant['tva'], 2))
            ->setMontantTotal(round($montant['ttc']))
            ->setMontantPaye(0);

        // mouvement collaborateur

        $mouv = new MouvementCollaborateur();

        $mouv->setCollaborateur($contrat->getBien()->getClient())
            ->setOrigine("facturation")
            ->setMontant(-$montant['ttc'])
            ->setDevise($this->deviseRepo->findOneBy([]))
            ->setSite($site)
            ->setDateOperation(new \DateTime())
            ->setDateSaisie(new \DateTime());

        $facture->addMouvementCollaborateur($mouv);

        return $facture;
    }
}