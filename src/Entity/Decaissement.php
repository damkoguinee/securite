<?php

namespace App\Entity;

use App\Repository\DecaissementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DecaissementRepository::class)]
class Decaissement extends MouvementCaisse
{

    #[ORM\ManyToOne(inversedBy: 'decaissements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $collaborateur = null;

    #[ORM\ManyToOne(inversedBy: 'decaissements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CategorieDecaissement $categorie = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $document = null;

    /**
     * @var Collection<int, MouvementCollaborateur>
     */
    #[ORM\OneToMany(targetEntity: MouvementCollaborateur::class, mappedBy: 'decaissement', orphanRemoval:true, cascade:['persist', 'remove'])]
    private Collection $mouvementCollaborateurs;

    public function __construct()
    {
        parent::__construct();
        $this->mouvementCollaborateurs = new ArrayCollection();
    }


    public function getCollaborateur(): ?User
    {
        return $this->collaborateur;
    }

    public function setCollaborateur(?User $collaborateur): static
    {
        $this->collaborateur = $collaborateur;

        return $this;
    }

    public function getDocument(): ?string
    {
        return $this->document;
    }

    public function setDocument(?string $document): static
    {
        $this->document = $document;

        return $this;
    }

    public function getCategorie(): ?CategorieDecaissement
    {
        return $this->categorie;
    }

    public function setCategorie(?CategorieDecaissement $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }

    /**
     * @return Collection<int, MouvementCollaborateur>
     */
    public function getMouvementCollaborateurs(): Collection
    {
        return $this->mouvementCollaborateurs;
    }

    public function addMouvementCollaborateur(MouvementCollaborateur $mouvementCollaborateur): static
    {
        if (!$this->mouvementCollaborateurs->contains($mouvementCollaborateur)) {
            $this->mouvementCollaborateurs->add($mouvementCollaborateur);
            $mouvementCollaborateur->setDecaissement($this);
        }

        return $this;
    }

    public function removeMouvementCollaborateur(MouvementCollaborateur $mouvementCollaborateur): static
    {
        if ($this->mouvementCollaborateurs->removeElement($mouvementCollaborateur)) {
            // set the owning side to null (unless already changed)
            if ($mouvementCollaborateur->getDecaissement() === $this) {
                $mouvementCollaborateur->setDecaissement(null);
            }
        }

        return $this;
    }
}
