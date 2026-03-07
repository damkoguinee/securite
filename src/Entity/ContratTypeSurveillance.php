<?php

namespace App\Entity;

use App\Repository\ContratTypeSurveillanceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContratTypeSurveillanceRepository::class)]
class ContratTypeSurveillance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // --- Lien vers le contrat ---
    #[ORM\ManyToOne(inversedBy: 'typesSurveillance')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ContratSurveillance $contrat = null;

    // --- Lien vers le type de surveillance configuré ---
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?ConfigTypeSurveillance $typeSurveillance = null;

    // --- Tarifs du contrat ---
    #[ORM\Column(type: 'decimal', precision: 13, scale: 2, nullable: true)]
    private ?string $tarifHoraire = null;

    #[ORM\Column(type: 'decimal', precision: 13, scale: 2, nullable: true)]
    private ?string $tarifMensuel = null;

    // --- Effectifs ---
    #[ORM\Column(nullable: true)]
    private ?int $nbAgentsJour = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbAgentsNuit = null;

    // --- Horaires ---
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $heureParAgent = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContrat(): ?ContratSurveillance
    {
        return $this->contrat;
    }

    public function setContrat(?ContratSurveillance $contrat): static
    {
        $this->contrat = $contrat;
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

    public function getTarifHoraire(): ?string
    {
        return $this->tarifHoraire;
    }

    public function setTarifHoraire(?string $tarifHoraire): static
    {
        $this->tarifHoraire = $tarifHoraire;
        return $this;
    }

    public function getTarifMensuel(): ?string
    {
        return $this->tarifMensuel;
    }

    public function setTarifMensuel(?string $tarifMensuel): static
    {
        $this->tarifMensuel = $tarifMensuel;
        return $this;
    }

    public function getNbAgentsJour(): ?int
    {
        return $this->nbAgentsJour;
    }

    public function setNbAgentsJour(?int $nbAgentsJour): static
    {
        $this->nbAgentsJour = $nbAgentsJour;
        return $this;
    }

    public function getNbAgentsNuit(): ?int
    {
        return $this->nbAgentsNuit;
    }

    public function setNbAgentsNuit(?int $nbAgentsNuit): static
    {
        $this->nbAgentsNuit = $nbAgentsNuit;
        return $this;
    }

    public function getHeureParAgent(): ?float
    {
        return $this->heureParAgent;
    }

    public function setHeureParAgent(?float $heureParAgent): static
    {
        $this->heureParAgent = $heureParAgent;
        return $this;
    }
}
