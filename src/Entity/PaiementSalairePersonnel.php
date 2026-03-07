<?php

namespace App\Entity;

use App\Repository\PaiementSalairePersonnelRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaiementSalairePersonnelRepository::class)]
class PaiementSalairePersonnel extends MouvementCaisse
{

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $periode = null;


    #[ORM\Column(type: Types::DECIMAL, precision: 13, scale: 2)]
    private ?string $salaireBrut = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 13, scale: 2, nullable: true)]
    private ?string $avanceSalaire = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 13, scale: 2, nullable: true)]
    private ?string $prime = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 13, scale: 2, nullable: true)]
    private ?string $cotisation = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $heures = null;

    #[ORM\Column(nullable: true)]
    private ?float $tauxHoraire = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $compteBancaire = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $banqueVirement = null;

    #[ORM\ManyToOne(inversedBy: 'paiementSalairePersonnels')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Personel $personnel = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 13, scale: 2, nullable: true)]
    private ?string $penalite = null;

    #[ORM\Column(nullable: true)]
    private ?int $jourTravaille = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 13, scale: 2, nullable: true)]
    private ?string $reposTravaille = null;

    #[ORM\Column(nullable: true)]
    private ?float $journeeEntiere = null;

    #[ORM\ManyToOne(inversedBy: 'paiementSalairePersonnels')]
    private ?ContratSurveillance $contrat = null;

    
    public function getPeriode(): ?\DateTime
    {
        return $this->periode;
    }

    public function setPeriode(\DateTime $periode): static
    {
        $this->periode = $periode;

        return $this;
    }

   
    public function getSalaireBrut(): ?string
    {
        return $this->salaireBrut;
    }

    public function setSalaireBrut(string $salaireBrut): static
    {
        $this->salaireBrut = $salaireBrut;

        return $this;
    }

    public function getAvanceSalaire(): ?string
    {
        return $this->avanceSalaire;
    }

    public function setAvanceSalaire(?string $avanceSalaire): static
    {
        $this->avanceSalaire = $avanceSalaire;

        return $this;
    }

    public function getPrime(): ?string
    {
        return $this->prime;
    }

    public function setPrime(?string $prime): static
    {
        $this->prime = $prime;

        return $this;
    }

    public function getCotisation(): ?string
    {
        return $this->cotisation;
    }

    public function setCotisation(?string $cotisation): static
    {
        $this->cotisation = $cotisation;

        return $this;
    }

    public function getHeures(): ?string
    {
        return $this->heures;
    }

    public function setHeures(?string $heures): static
    {
        $this->heures = $heures;

        return $this;
    }

    public function getTauxHoraire(): ?float
    {
        return $this->tauxHoraire;
    }

    public function setTauxHoraire(?float $tauxHoraire): static
    {
        $this->tauxHoraire = $tauxHoraire;

        return $this;
    }

    public function getCompteBancaire(): ?string
    {
        return $this->compteBancaire;
    }

    public function setCompteBancaire(?string $compteBancaire): static
    {
        $this->compteBancaire = $compteBancaire;

        return $this;
    }

    public function getBanqueVirement(): ?string
    {
        return $this->banqueVirement;
    }

    public function setBanqueVirement(?string $banqueVirement): static
    {
        $this->banqueVirement = $banqueVirement;

        return $this;
    }

    public function getPersonnel(): ?Personel
    {
        return $this->personnel;
    }

    public function setPersonnel(?Personel $personnel): static
    {
        $this->personnel = $personnel;

        return $this;
    }

    public function getPenalite(): ?string
    {
        return $this->penalite;
    }

    public function setPenalite(?string $penalite): static
    {
        $this->penalite = $penalite;

        return $this;
    }

    public function getJourTravaille(): ?int
    {
        return $this->jourTravaille;
    }

    public function setJourTravaille(?int $jourTravaille): static
    {
        $this->jourTravaille = $jourTravaille;

        return $this;
    }

    public function getReposTravaille(): ?string
    {
        return $this->reposTravaille;
    }

    public function setReposTravaille(?string $reposTravaille): static
    {
        $this->reposTravaille = $reposTravaille;

        return $this;
    }

    public function getJourneeEntiere(): ?float
    {
        return $this->journeeEntiere;
    }

    public function setJourneeEntiere(?float $journeeEntiere): static
    {
        $this->journeeEntiere = $journeeEntiere;

        return $this;
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
}
