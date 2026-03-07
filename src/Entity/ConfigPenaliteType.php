<?php

namespace App\Entity;

use App\Repository\ConfigPenaliteTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConfigPenaliteTypeRepository::class)]
class ConfigPenaliteType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private ?string $nom = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $montantDefaut = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * @var Collection<int, Penalite>
     */
    #[ORM\OneToMany(targetEntity: Penalite::class, mappedBy: 'penaliteType')]
    private Collection $penalites;

    public function __construct()
    {
        $this->penalites = new ArrayCollection();
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

    public function getMontantDefaut(): ?string
    {
        return $this->montantDefaut;
    }

    public function setMontantDefaut(?string $montantDefaut): static
    {
        $this->montantDefaut = $montantDefaut;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, Penalite>
     */
    public function getPenalites(): Collection
    {
        return $this->penalites;
    }

    public function addPenalite(Penalite $penalite): static
    {
        if (!$this->penalites->contains($penalite)) {
            $this->penalites->add($penalite);
            $penalite->setPenaliteType($this);
        }

        return $this;
    }

    public function removePenalite(Penalite $penalite): static
    {
        if ($this->penalites->removeElement($penalite)) {
            // set the owning side to null (unless already changed)
            if ($penalite->getPenaliteType() === $this) {
                $penalite->setPenaliteType(null);
            }
        }

        return $this;
    }
}
