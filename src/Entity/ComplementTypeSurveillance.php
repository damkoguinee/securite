<?php

namespace App\Entity;

use App\Repository\ComplementTypeSurveillanceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ComplementTypeSurveillanceRepository::class)]
class ComplementTypeSurveillance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'complementTypeSurveillances')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ContratComplementaire $contratComplementaire = null;

    #[ORM\ManyToOne(inversedBy: 'complementTypeSurveillances')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ConfigTypeSurveillance $typeSurveillance = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 13, scale: 2, nullable: true)]
    private ?string $tarif = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbAgent = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContratComplementaire(): ?ContratComplementaire
    {
        return $this->contratComplementaire;
    }

    public function setContratComplementaire(?ContratComplementaire $contratComplementaire): static
    {
        $this->contratComplementaire = $contratComplementaire;

        return $this;
    }

    public function getTypeSurveillance(): ?ConfigTypeSurveillance
    {
        return $this->typeSurveillance;
    }

    public function setTypeSurveillance(?ConfigTypeSurveillance $typeSurveillance): static
    {
        $this->typeSurveillance = $typeSurveillance;

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

    public function getNbAgent(): ?int
    {
        return $this->nbAgent;
    }

    public function setNbAgent(?int $nbAgent): static
    {
        $this->nbAgent = $nbAgent;

        return $this;
    }
}
