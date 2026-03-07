<?php

namespace App\Entity;

use App\Repository\LicenceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LicenceRepository::class)]
class Licence
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $numeroLicence = null;

    #[ORM\ManyToOne(inversedBy: 'licences')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Entreprise $entreprise = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $datefin = null;

    #[ORM\Column(length: 15)]
    private ?string $statut = null;

    #[ORM\Column(length: 15)]
    private ?string $statutSiteWeb = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 13, scale: 2, nullable: true)]
    private ?string $tarif = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $typeLicence = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lienPaiement = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumeroLicence(): ?string
    {
        return $this->numeroLicence;
    }

    public function setNumeroLicence(string $numeroLicence): static
    {
        $this->numeroLicence = $numeroLicence;

        return $this;
    }

    public function getEntreprise(): ?Entreprise
    {
        return $this->entreprise;
    }

    public function setEntreprise(?Entreprise $entreprise): static
    {
        $this->entreprise = $entreprise;

        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDatefin(): ?\DateTimeInterface
    {
        return $this->datefin;
    }

    public function setDatefin(\DateTimeInterface $datefin): static
    {
        $this->datefin = $datefin;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getStatutSiteWeb(): ?string
    {
        return $this->statutSiteWeb;
    }

    public function setStatutSiteWeb(string $statutSiteWeb): static
    {
        $this->statutSiteWeb = $statutSiteWeb;

        return $this;
    }

    public function getTarif(): ?string
    {
        return $this->tarif;
    }

    public function setTarif(?string $tarif): static
    {
        $this->tarif = $tarif;

        return $this;
    }

    public function getTypeLicence(): ?string
    {
        return $this->typeLicence;
    }

    public function setTypeLicence(?string $typeLicence): static
    {
        $this->typeLicence = $typeLicence;

        return $this;
    }

    public function getLienPaiement(): ?string
    {
        return $this->lienPaiement;
    }

    public function setLienPaiement(?string $lienPaiement): static
    {
        $this->lienPaiement = $lienPaiement;

        return $this;
    }
}
