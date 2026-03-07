<?php

namespace App\Entity;

use App\Repository\ConfigRegionAdministrativeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConfigRegionAdministrativeRepository::class)]
class ConfigRegionAdministrative
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $code = null;

    #[ORM\Column(length: 150)]
    private ?string $nom = null;

    #[ORM\ManyToOne(inversedBy: 'configRegionAdministratives')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ConfigRegionNaturelle $regionNaturelle = null;

    #[ORM\OneToMany(mappedBy: 'regionAdministrative', targetEntity: ConfigPrefecture::class)]
    private Collection $configPrefectures;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $longitude = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $latitude = null;

    #[ORM\OneToMany(mappedBy: 'region', targetEntity: ConfigDivisionLocale::class)]
    private Collection $configDivisionLocales;

    public function __construct()
    {
        $this->configPrefectures = new ArrayCollection();
        $this->configDivisionLocales = new ArrayCollection();
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

    public function getRegionNaturelle(): ?ConfigRegionNaturelle
    {
        return $this->regionNaturelle;
    }

    public function setRegionNaturelle(?ConfigRegionNaturelle $regionNaturelle): static
    {
        $this->regionNaturelle = $regionNaturelle;

        return $this;
    }

    /**
     * @return Collection<int, ConfigPrefecture>
     */
    public function getConfigPrefectures(): Collection
    {
        return $this->configPrefectures;
    }

    public function addConfigPrefecture(ConfigPrefecture $configPrefecture): static
    {
        if (!$this->configPrefectures->contains($configPrefecture)) {
            $this->configPrefectures->add($configPrefecture);
            $configPrefecture->setRegionAdministrative($this);
        }

        return $this;
    }

    public function removeConfigPrefecture(ConfigPrefecture $configPrefecture): static
    {
        if ($this->configPrefectures->removeElement($configPrefecture)) {
            // set the owning side to null (unless already changed)
            if ($configPrefecture->getRegionAdministrative() === $this) {
                $configPrefecture->setRegionAdministrative(null);
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

    /**
     * @return Collection<int, ConfigDivisionLocale>
     */
    public function getConfigDivisionLocales(): Collection
    {
        return $this->configDivisionLocales;
    }

    public function addConfigDivisionLocale(ConfigDivisionLocale $configDivisionLocale): static
    {
        if (!$this->configDivisionLocales->contains($configDivisionLocale)) {
            $this->configDivisionLocales->add($configDivisionLocale);
            $configDivisionLocale->setRegion($this);
        }

        return $this;
    }

    public function removeConfigDivisionLocale(ConfigDivisionLocale $configDivisionLocale): static
    {
        if ($this->configDivisionLocales->removeElement($configDivisionLocale)) {
            // set the owning side to null (unless already changed)
            if ($configDivisionLocale->getRegion() === $this) {
                $configDivisionLocale->setRegion(null);
            }
        }

        return $this;
    }
}
