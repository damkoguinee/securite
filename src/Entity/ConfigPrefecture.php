<?php

namespace App\Entity;

use App\Repository\ConfigPrefectureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConfigPrefectureRepository::class)]
class ConfigPrefecture
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $code = null;

    #[ORM\Column(length: 150)]
    private ?string $nom = null;

    #[ORM\ManyToOne(inversedBy: 'configPrefectures')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ConfigRegionAdministrative $regionAdministrative = null;

    #[ORM\OneToMany(mappedBy: 'prefecture', targetEntity: ConfigSousPrefecture::class)]
    private Collection $configSousPrefectures;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $longitude = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $latitude = null;

    public function __construct()
    {
        $this->configSousPrefectures = new ArrayCollection();
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

    public function getRegionAdministrative(): ?ConfigRegionAdministrative
    {
        return $this->regionAdministrative;
    }

    public function setRegionAdministrative(?ConfigRegionAdministrative $regionAdministrative): static
    {
        $this->regionAdministrative = $regionAdministrative;

        return $this;
    }

    /**
     * @return Collection<int, ConfigSousPrefecture>
     */
    public function getConfigSousPrefectures(): Collection
    {
        return $this->configSousPrefectures;
    }

    public function addConfigSousPrefecture(ConfigSousPrefecture $configSousPrefecture): static
    {
        if (!$this->configSousPrefectures->contains($configSousPrefecture)) {
            $this->configSousPrefectures->add($configSousPrefecture);
            $configSousPrefecture->setPrefecture($this);
        }

        return $this;
    }

    public function removeConfigSousPrefecture(ConfigSousPrefecture $configSousPrefecture): static
    {
        if ($this->configSousPrefectures->removeElement($configSousPrefecture)) {
            // set the owning side to null (unless already changed)
            if ($configSousPrefecture->getPrefecture() === $this) {
                $configSousPrefecture->setPrefecture(null);
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
