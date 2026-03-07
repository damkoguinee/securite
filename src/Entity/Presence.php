<?php

namespace App\Entity;

use App\Repository\PresenceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PresenceRepository::class)]
class Presence
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'presences')]
    private ?AffectationAgent $affectationAgent = null;

    #[ORM\Column]
    private ?\DateTime $datePointage = null;

    #[ORM\Column(length: 50)]
    private ?string $typePointage = null;

    #[ORM\Column(length: 20)]
    private ?string $mode = null;

    #[ORM\Column(nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(nullable: true)]
    private ?float $longitude = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\ManyToOne(inversedBy: 'presences')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Personel $saisiePar = null;

    #[ORM\Column]
    private ?\DateTime $dateSaisie = null;

    #[ORM\Column(length: 20)]
    private ?string $statut = null;

    #[ORM\Column(nullable: true)]
    private ?float $ecart = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAffectationAgent(): ?AffectationAgent
    {
        return $this->affectationAgent;
    }

    public function setAffectationAgent(?AffectationAgent $affectationAgent): static
    {
        $this->affectationAgent = $affectationAgent;

        return $this;
    }

    public function getDatePointage(): ?\DateTime
    {
        return $this->datePointage;
    }

    public function setDatePointage(\DateTime $datePointage): static
    {
        $this->datePointage = $datePointage;

        return $this;
    }

    public function getTypePointage(): ?string
    {
        return $this->typePointage;
    }

    public function setTypePointage(string $typePointage): static
    {
        $this->typePointage = $typePointage;

        return $this;
    }

    public function getMode(): ?string
    {
        return $this->mode;
    }

    public function setMode(string $mode): static
    {
        $this->mode = $mode;

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;

        return $this;
    }

    public function getSaisiePar(): ?Personel
    {
        return $this->saisiePar;
    }

    public function setSaisiePar(?Personel $saisiePar): static
    {
        $this->saisiePar = $saisiePar;

        return $this;
    }

    public function getDateSaisie(): ?\DateTime
    {
        return $this->dateSaisie;
    }

    public function setDateSaisie(\DateTime $dateSaisie): static
    {
        $this->dateSaisie = $dateSaisie;

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

    public function getEcart(): ?float
    {
        return $this->ecart;
    }

    public function setEcart(?float $ecart): static
    {
        $this->ecart = $ecart;

        return $this;
    }
}
