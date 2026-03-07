<?php

namespace App\Entity;

use App\Repository\VersementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VersementRepository::class)]
class Versement extends MouvementCaisse
{
    
    #[ORM\ManyToOne(inversedBy: 'versements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $collaborateur = null;

    /**
     * @var Collection<int, MouvementCollaborateur>
     */
    #[ORM\OneToMany(targetEntity: MouvementCollaborateur::class, mappedBy: 'versement', orphanRemoval:true, cascade:['persist', 'remove'])]
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
            $mouvementCollaborateur->setVersement($this);
        }

        return $this;
    }

    public function removeMouvementCollaborateur(MouvementCollaborateur $mouvementCollaborateur): static
    {
        if ($this->mouvementCollaborateurs->removeElement($mouvementCollaborateur)) {
            // set the owning side to null (unless already changed)
            if ($mouvementCollaborateur->getVersement() === $this) {
                $mouvementCollaborateur->setVersement(null);
            }
        }

        return $this;
    }
}
