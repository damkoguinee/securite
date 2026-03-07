<?php

namespace App\Entity;

use App\Repository\AvanceSalaireRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AvanceSalaireRepository::class)]
class AvanceSalaire extends MouvementCaisse
{

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $periode = null;

    #[ORM\ManyToOne(inversedBy: 'avanceSalaires')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Personel $personnel = null;

    #[ORM\Column(length: 10)]
    private ?string $mois = null;

    #[ORM\ManyToOne(inversedBy: 'avanceSalaires')]
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

    public function getPersonnel(): ?Personel
    {
        return $this->personnel;
    }

    public function setPersonnel(?Personel $personnel): static
    {
        $this->personnel = $personnel;

        return $this;
    }

    public function getMois(): ?string
    {
        return $this->mois;
    }

    public function setMois(string $mois): static
    {
        $this->mois = $mois;

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
