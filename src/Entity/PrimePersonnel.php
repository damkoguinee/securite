<?php

namespace App\Entity;

use App\Repository\PrimePersonnelRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PrimePersonnelRepository::class)]
class PrimePersonnel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'primePersonnels')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Personel $personnel = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 13, scale: 2)]
    private ?string $montant = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $periode = null;

    

    #[ORM\Column]
    private ?\DateTime $dateSaisie = null;

    #[ORM\ManyToOne(inversedBy: 'primePersonnelSaisies')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Personel $saisiePar = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\ManyToOne(inversedBy: 'primePersonnels')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Site $site = null;

    

    public function getId(): ?int
    {
        return $this->id;
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

    public function getMontant(): ?string
    {
        return $this->montant;
    }

    public function setMontant(string $montant): static
    {
        $this->montant = $montant;

        return $this;
    }

    public function getPeriode(): ?\DateTime
    {
        return $this->periode;
    }

    public function setPeriode(\DateTime $periode): static
    {
        $this->periode = $periode;

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

    public function getSaisiePar(): ?Personel
    {
        return $this->saisiePar;
    }

    public function setSaisiePar(?Personel $saisiePar): static
    {
        $this->saisiePar = $saisiePar;

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

    public function getSite(): ?Site
    {
        return $this->site;
    }

    public function setSite(?Site $site): static
    {
        $this->site = $site;

        return $this;
    }

   
}
