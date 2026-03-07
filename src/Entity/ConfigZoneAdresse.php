<?php

namespace App\Entity;

use App\Repository\ConfigZoneAdresseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConfigZoneAdresseRepository::class)]
class ConfigZoneAdresse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $nom = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $code = null;

    #[ORM\OneToMany(mappedBy: 'zone', targetEntity: ConfigQuartier::class)]
    private Collection $configQuartiers;



    public function __construct()
    {
        $this->configQuartiers = new ArrayCollection();
        $this->personels = new ArrayCollection();
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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return Collection<int, ConfigQuartier>
     */
    public function getConfigQuartiers(): Collection
    {
        return $this->configQuartiers;
    }

    public function addConfigQuartier(ConfigQuartier $configQuartier): static
    {
        if (!$this->configQuartiers->contains($configQuartier)) {
            $this->configQuartiers->add($configQuartier);
            $configQuartier->setZone($this);
        }

        return $this;
    }

    public function removeConfigQuartier(ConfigQuartier $configQuartier): static
    {
        if ($this->configQuartiers->removeElement($configQuartier)) {
            // set the owning side to null (unless already changed)
            if ($configQuartier->getZone() === $this) {
                $configQuartier->setZone(null);
            }
        }

        return $this;
    }

    
    
}
