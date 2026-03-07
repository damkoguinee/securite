<?php

namespace App\Entity;

use App\Repository\ConfigRegionNaturelleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConfigRegionNaturelleRepository::class)]
class ConfigRegionNaturelle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $code = null;

    #[ORM\Column(length: 150)]
    private ?string $nom = null;

    #[ORM\OneToMany(mappedBy: 'regionNaturelle', targetEntity: ConfigRegionAdministrative::class)]
    private Collection $configRegionAdministratives;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $longitude = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $latitude = null;

    public function __construct()
    {
        $this->configRegionAdministratives = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

        return $this;
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

    /**
     * @return Collection<int, ConfigRegionAdministrative>
     */
    public function getConfigRegionAdministratives(): Collection
    {
        return $this->configRegionAdministratives;
    }

    public function addConfigRegionAdministrative(ConfigRegionAdministrative $configRegionAdministrative): static
    {
        if (!$this->configRegionAdministratives->contains($configRegionAdministrative)) {
            $this->configRegionAdministratives->add($configRegionAdministrative);
            $configRegionAdministrative->setRegionNaturelle($this);
        }

        return $this;
    }

    public function removeConfigRegionAdministrative(ConfigRegionAdministrative $configRegionAdministrative): static
    {
        if ($this->configRegionAdministratives->removeElement($configRegionAdministrative)) {
            // set the owning side to null (unless already changed)
            if ($configRegionAdministrative->getRegionNaturelle() === $this) {
                $configRegionAdministrative->setRegionNaturelle(null);
            }
        }

        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(?string $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(?string $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }
}
