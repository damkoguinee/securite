<?php

namespace App\Entity;

use App\Repository\ConfigSousPrefectureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConfigSousPrefectureRepository::class)]
class ConfigSousPrefecture
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $code = null;

    #[ORM\Column(length: 150)]
    private ?string $nom = null;

    #[ORM\ManyToOne(inversedBy: 'configSousPrefectures')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ConfigPrefecture $prefecture = null;

    #[ORM\OneToMany(mappedBy: 'sousPrefecture', targetEntity: ConfigCommune::class)]
    private Collection $configCommunes;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $longitude = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $latitude = null;

    public function __construct()
    {
        $this->configCommunes = new ArrayCollection();
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

    public function getPrefecture(): ?ConfigPrefecture
    {
        return $this->prefecture;
    }

    public function setPrefecture(?ConfigPrefecture $prefecture): static
    {
        $this->prefecture = $prefecture;

        return $this;
    }

    /**
     * @return Collection<int, ConfigCommune>
     */
    public function getConfigCommunes(): Collection
    {
        return $this->configCommunes;
    }

    public function addConfigCommune(ConfigCommune $configCommune): static
    {
        if (!$this->configCommunes->contains($configCommune)) {
            $this->configCommunes->add($configCommune);
            $configCommune->setSousPrefecture($this);
        }

        return $this;
    }

    public function removeConfigCommune(ConfigCommune $configCommune): static
    {
        if ($this->configCommunes->removeElement($configCommune)) {
            // set the owning side to null (unless already changed)
            if ($configCommune->getSousPrefecture() === $this) {
                $configCommune->setSousPrefecture(null);
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
