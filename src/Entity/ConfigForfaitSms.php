<?php

namespace App\Entity;

use App\Repository\ConfigForfaitSmsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConfigForfaitSmsRepository::class)]
class ConfigForfaitSms
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column]
    private ?int $sms = null;

    #[ORM\Column]
    private ?float $prixFournisseur = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 13, scale: 2)]
    private ?string $marge = null;

    #[ORM\Column(length: 255)]
    private ?string $remarque = null;

    #[ORM\Column(nullable: true)]
    private ?int $validite = null;

    /**
     * @var Collection<int, ForfaitSms>
     */
    #[ORM\OneToMany(targetEntity: ForfaitSms::class, mappedBy: 'forfait')]
    private Collection $forfaitSms;

    public function __construct()
    {
        $this->forfaitSms = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getSms(): ?int
    {
        return $this->sms;
    }

    public function setSms(int $sms): static
    {
        $this->sms = $sms;

        return $this;
    }

    public function getPrixFournisseur(): ?float
    {
        return $this->prixFournisseur;
    }

    public function setPrixFournisseur(float $prixFournisseur): static
    {
        $this->prixFournisseur = $prixFournisseur;

        return $this;
    }

    public function getMarge(): ?string
    {
        return $this->marge;
    }

    public function setMarge(string $marge): static
    {
        $this->marge = $marge;

        return $this;
    }

    public function getRemarque(): ?string
    {
        return $this->remarque;
    }

    public function setRemarque(string $remarque): static
    {
        $this->remarque = $remarque;

        return $this;
    }

    public function getValidite(): ?int
    {
        return $this->validite;
    }

    public function setValidite(?int $validite): static
    {
        $this->validite = $validite;

        return $this;
    }

    /**
     * @return Collection<int, ForfaitSms>
     */
    public function getForfaitSms(): Collection
    {
        return $this->forfaitSms;
    }

    public function addForfaitSms(ForfaitSms $forfaitSms): static
    {
        if (!$this->forfaitSms->contains($forfaitSms)) {
            $this->forfaitSms->add($forfaitSms);
            $forfaitSms->setForfait($this);
        }

        return $this;
    }

    public function removeForfaitSms(ForfaitSms $forfaitSms): static
    {
        if ($this->forfaitSms->removeElement($forfaitSms)) {
            // set the owning side to null (unless already changed)
            if ($forfaitSms->getForfait() === $this) {
                $forfaitSms->setForfait(null);
            }
        }

        return $this;
    }
}
