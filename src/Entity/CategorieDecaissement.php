<?php

namespace App\Entity;

use App\Repository\CategorieDecaissementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategorieDecaissementRepository::class)]
class CategorieDecaissement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $designation = null;

    /**
     * @var Collection<int, Versement>
     */
    #[ORM\OneToMany(targetEntity: Versement::class, mappedBy: 'categorie')]
    private Collection $versements;

    /**
     * @var Collection<int, Decaissement>
     */
    #[ORM\OneToMany(targetEntity: Decaissement::class, mappedBy: 'categorie')]
    private Collection $decaissements;

    public function __construct()
    {
        $this->versements = new ArrayCollection();
        $this->decaissements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDesignation(): ?string
    {
        return $this->designation;
    }

    public function setDesignation(string $designation): static
    {
        $this->designation = $designation;

        return $this;
    }

    /**
     * @return Collection<int, Versement>
     */
    public function getVersements(): Collection
    {
        return $this->versements;
    }

    // public function addVersement(Versement $versement): static
    // {
    //     if (!$this->versements->contains($versement)) {
    //         $this->versements->add($versement);
    //         $versement->setCategorie($this);
    //     }

    //     return $this;
    // }

    // public function removeVersement(Versement $versement): static
    // {
    //     if ($this->versements->removeElement($versement)) {
    //         // set the owning side to null (unless already changed)
    //         if ($versement->getCategorie() === $this) {
    //             $versement->setCategorie(null);
    //         }
    //     }

    //     return $this;
    // }

    /**
     * @return Collection<int, Decaissement>
     */
    public function getDecaissements(): Collection
    {
        return $this->decaissements;
    }

    public function addDecaissement(Decaissement $decaissement): static
    {
        if (!$this->decaissements->contains($decaissement)) {
            $this->decaissements->add($decaissement);
            $decaissement->setCategorie($this);
        }

        return $this;
    }

    public function removeDecaissement(Decaissement $decaissement): static
    {
        if ($this->decaissements->removeElement($decaissement)) {
            // set the owning side to null (unless already changed)
            if ($decaissement->getCategorie() === $this) {
                $decaissement->setCategorie(null);
            }
        }

        return $this;
    }
}
