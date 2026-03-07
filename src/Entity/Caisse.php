<?php

namespace App\Entity;

use App\Repository\CaisseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CaisseRepository::class)]
class Caisse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var Collection<int, Site>
     */
    #[ORM\ManyToMany(targetEntity: Site::class)]
    private Collection $site;

    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    private ?string $type = null;

    #[ORM\Column(length: 100, nullable:true)]
    private ?string $numero = null;


    /**
     * @var Collection<int, MouvementCaisse>
     */
    #[ORM\OneToMany(targetEntity: MouvementCaisse::class, mappedBy: 'caisse')]
    private Collection $mouvementCaisses;

    /**
     * @var Collection<int, TransfertFond>
     */
    #[ORM\OneToMany(targetEntity: TransfertFond::class, mappedBy: 'caisseReception')]
    private Collection $transfertFonds;

    /**
     * @var Collection<int, Decaissement>
     */
    #[ORM\OneToMany(mappedBy: 'caisse', targetEntity: Decaissement::class, orphanRemoval: false)]
    private Collection $decaissements;


    public function __construct()
    {
        $this->mouvementCaisses = new ArrayCollection();
        $this->transfertFonds = new ArrayCollection();
         $this->decaissements = new ArrayCollection();
         $this->site = new ArrayCollection(); 
    }

    public function getId(): ?int
    {
        return $this->id;
    }

     /**
     * @return Collection<int, Site>
     */
    public function getSite(): Collection
    {
        return $this->site;
    }

    public function addSite(Site $site): static
    {
        if (!$this->site->contains($site)) {
            $this->site->add($site);
        }

        return $this;
    }

    public function removeSite(Site $site): static
    {
        $this->site->removeElement($site);

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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(string $numero): static
    {
        $this->numero = $numero;

        return $this;
    }

    

    /**
     * @return Collection<int, MouvementCaisse>
     */
    public function getMouvementCaisses(): Collection
    {
        return $this->mouvementCaisses;
    }

    public function addMouvementCaiss(MouvementCaisse $mouvementCaiss): static
    {
        if (!$this->mouvementCaisses->contains($mouvementCaiss)) {
            $this->mouvementCaisses->add($mouvementCaiss);
            $mouvementCaiss->setCaisse($this);
        }

        return $this;
    }

    public function removeMouvementCaiss(MouvementCaisse $mouvementCaiss): static
    {
        if ($this->mouvementCaisses->removeElement($mouvementCaiss)) {
            // set the owning side to null (unless already changed)
            if ($mouvementCaiss->getCaisse() === $this) {
                $mouvementCaiss->setCaisse(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TransfertFond>
     */
    public function getTransfertFonds(): Collection
    {
        return $this->transfertFonds;
    }

    public function addTransfertFond(TransfertFond $transfertFond): static
    {
        if (!$this->transfertFonds->contains($transfertFond)) {
            $this->transfertFonds->add($transfertFond);
            $transfertFond->setCaisseReception($this);
        }

        return $this;
    }

    public function removeTransfertFond(TransfertFond $transfertFond): static
    {
        if ($this->transfertFonds->removeElement($transfertFond)) {
            // set the owning side to null (unless already changed)
            if ($transfertFond->getCaisseReception() === $this) {
                $transfertFond->setCaisseReception(null);
            }
        }

        return $this;
    }

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
            $decaissement->setCaisse($this);
        }

        return $this;
    }

    public function removeDecaissement(Decaissement $decaissement): static
    {
        if ($this->decaissements->removeElement($decaissement)) {
            // unset owning side
            if ($decaissement->getCaisse() === $this) {
                $decaissement->setCaisse(null);
            }
        }

        return $this;
    }


    
}
