<?php

namespace App\Entity;

use App\Repository\MouvementCollaborateurRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MouvementCollaborateurRepository::class)]
class MouvementCollaborateur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'mouvementCollaborateurs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $collaborateur = null;

    #[ORM\ManyToOne(inversedBy: 'mouvementCollaborateurs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ConfigDevise $devise = null;

    #[ORM\ManyToOne(inversedBy: 'mouvementCollaborateurs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?site $site = null;

    #[ORM\Column(length: 100)]
    private ?string $origine = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 13, scale: 2, nullable: true)]
    private ?string $montant = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateOperation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateSaisie = null;

    #[ORM\ManyToOne(inversedBy: 'mouvementCollaborateurs')]
    private ?Decaissement $decaissement = null;

    #[ORM\ManyToOne(inversedBy: 'mouvementCollaborateurs')]
    private ?Versement $versement = null;

    #[ORM\ManyToOne(inversedBy: 'mouvementCollaborateurs')]
    private ?Facture $facturation = null;

    #[ORM\ManyToOne(inversedBy: 'mouvementPaiements')]
    private ?Paiement $paiement = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCollaborateur(): ?User
    {
        return $this->collaborateur;
    }

    public function setCollaborateur(?User $collaborateur): static
    {
        $this->collaborateur = $collaborateur;

        return $this;
    }

    public function getDevise(): ?ConfigDevise
    {
        return $this->devise;
    }

    public function setDevise(?ConfigDevise $devise): static
    {
        $this->devise = $devise;

        return $this;
    }

    public function getSite(): ?site
    {
        return $this->site;
    }

    public function setSite(?site $site): static
    {
        $this->site = $site;

        return $this;
    }

    public function getOrigine(): ?string
    {
        return $this->origine;
    }

    public function setOrigine(string $origine): static
    {
        $this->origine = $origine;

        return $this;
    }

    public function getMontant(): ?string
    {
        return $this->montant;
    }

    public function setMontant(?string $montant): static
    {
        $this->montant = $montant;

        return $this;
    }

    public function getDateOperation(): ?\DateTimeInterface
    {
        return $this->dateOperation;
    }

    public function setDateOperation(\DateTimeInterface $dateOperation): static
    {
        $this->dateOperation = $dateOperation;

        return $this;
    }

    public function getDateSaisie(): ?\DateTimeInterface
    {
        return $this->dateSaisie;
    }

    public function setDateSaisie(\DateTimeInterface $dateSaisie): static
    {
        $this->dateSaisie = $dateSaisie;

        return $this;
    }

    public function getDecaissement(): ?Decaissement
    {
        return $this->decaissement;
    }

    public function setDecaissement(?Decaissement $decaissement): static
    {
        $this->decaissement = $decaissement;

        return $this;
    }

    public function getVersement(): ?Versement
    {
        return $this->versement;
    }

    public function setVersement(?Versement $versement): static
    {
        $this->versement = $versement;

        return $this;
    }

    public function getFacturation(): ?Facture
    {
        return $this->facturation;
    }

    public function setFacturation(?Facture $facturation): static
    {
        $this->facturation = $facturation;

        return $this;
    }

    public function getPaiement(): ?Paiement
    {
        return $this->paiement;
    }

    public function setPaiement(?Paiement $paiement): static
    {
        $this->paiement = $paiement;

        return $this;
    }

    

    
}
