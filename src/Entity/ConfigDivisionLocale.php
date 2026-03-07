<?php

namespace App\Entity;

use App\Repository\ConfigDivisionLocaleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConfigDivisionLocaleRepository::class)]
class ConfigDivisionLocale
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    private ?string $type = null;

    #[ORM\ManyToOne(inversedBy: 'configDivisionLocales')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ConfigRegionAdministrative $region = null;

    #[ORM\OneToMany(mappedBy: 'divisionLocale', targetEntity: ConfigQuartier::class)]
    private Collection $configQuartiers;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $longitude = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $latitude = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $code = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'configDivisionLocales')]
    private ?self $parent = null;

    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    private Collection $configDivisionLocales;

    public function __construct()
    {
        $this->configQuartiers = new ArrayCollection();
        $this->configDivisionLocales = new ArrayCollection();
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getRegion(): ?ConfigRegionAdministrative
    {
        return $this->region;
    }

    public function setRegion(?ConfigRegionAdministrative $region): static
    {
        $this->region = $region;

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
            $configQuartier->setDivisionLocale($this);
        }

        return $this;
    }

    public function removeConfigQuartier(ConfigQuartier $configQuartier): static
    {
        if ($this->configQuartiers->removeElement($configQuartier)) {
            // set the owning side to null (unless already changed)
            if ($configQuartier->getDivisionLocale() === $this) {
                $configQuartier->setDivisionLocale(null);
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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getConfigDivisionLocales(): Collection
    {
        return $this->configDivisionLocales;
    }

    public function addConfigDivisionLocale(self $configDivisionLocale): static
    {
        if (!$this->configDivisionLocales->contains($configDivisionLocale)) {
            $this->configDivisionLocales->add($configDivisionLocale);
            $configDivisionLocale->setParent($this);
        }

        return $this;
    }

    public function removeConfigDivisionLocale(self $configDivisionLocale): static
    {
        if ($this->configDivisionLocales->removeElement($configDivisionLocale)) {
            // set the owning side to null (unless already changed)
            if ($configDivisionLocale->getParent() === $this) {
                $configDivisionLocale->setParent(null);
            }
        }

        return $this;
    }
}
