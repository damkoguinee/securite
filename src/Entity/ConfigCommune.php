<?php

namespace App\Entity;

use App\Repository\ConfigCommuneRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConfigCommuneRepository::class)]
class ConfigCommune
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $code = null;

    #[ORM\Column(length: 150)]
    private ?string $nom = null;

    #[ORM\ManyToOne(inversedBy: 'configCommunes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ConfigSousPrefecture $sousPrefecture = null;

    

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $longitude = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $latitude = null;

    

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

    public function getSousPrefecture(): ?ConfigSousPrefecture
    {
        return $this->sousPrefecture;
    }

    public function setSousPrefecture(?ConfigSousPrefecture $sousPrefecture): static
    {
        $this->sousPrefecture = $sousPrefecture;

        return $this;
    }

    /**
     * @return Collection<int, ConfigQuartier>
     */
    public function getConfigQuartiers(): Collection
    {
        return $this->configQuartiers;
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
