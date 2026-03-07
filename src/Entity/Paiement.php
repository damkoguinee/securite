<?php

namespace App\Entity;

use App\Repository\PaiementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaiementRepository::class)]
class Paiement extends MouvementCaisse
{
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $document = null;

    /**
     * @var Collection<int, MouvementCollaborateur>
     */
    #[ORM\OneToMany(targetEntity: MouvementCollaborateur::class, mappedBy: 'paiement', orphanRemoval:true, cascade:['persist', 'remove'])]
    private Collection $mouvementPaiements;

    /**
     * @var Collection<int, Facture>
     */
    #[ORM\ManyToMany(targetEntity: Facture::class, inversedBy: 'paiements')]
    private Collection $facture;

    /**
     * @var Collection<int, DetailPaiementFacture>
     */
    #[ORM\OneToMany(targetEntity: DetailPaiementFacture::class, mappedBy: 'paiement', orphanRemoval: true, cascade:['persist', 'remove'])]
    private Collection $detailPaiementFactures;


    public function __construct()
    {
        parent::__construct();
        $this->mouvementPaiements = new ArrayCollection();
        $this->facture = new ArrayCollection();
        $this->detailPaiementFactures = new ArrayCollection();
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


    /**
     * @return Collection<int, MouvementCollaborateur>
     */
    public function getMouvementPaiements(): Collection
    {
        return $this->mouvementPaiements;
    }

    public function addMouvementPaiement(MouvementCollaborateur $mouvementPaiement): static
    {
        if (!$this->mouvementPaiements->contains($mouvementPaiement)) {
            $this->mouvementPaiements->add($mouvementPaiement);
            $mouvementPaiement->setPaiement($this);
        }

        return $this;
    }

    public function removeMouvementPaiement(MouvementCollaborateur $mouvementPaiement): static
    {
        if ($this->mouvementPaiements->removeElement($mouvementPaiement)) {
            // set the owning side to null (unless already changed)
            if ($mouvementPaiement->getPaiement() === $this) {
                $mouvementPaiement->setPaiement(null);
            }
        }

        return $this;
    }

   

    /**
     * @return Collection<int, Facture>
     */
    public function getFacture(): Collection
    {
        return $this->facture;
    }

    public function addFacture(Facture $facture): static
    {
        if (!$this->facture->contains($facture)) {
            $this->facture->add($facture);
        }

        return $this;
    }

    public function removeFacture(Facture $facture): static
    {
        $this->facture->removeElement($facture);

        return $this;
    }

    /**
     * @return Collection<int, DetailPaiementFacture>
     */
    public function getDetailPaiementFactures(): Collection
    {
        return $this->detailPaiementFactures;
    }

    public function addDetailPaiementFacture(DetailPaiementFacture $detailPaiementFacture): static
    {
        if (!$this->detailPaiementFactures->contains($detailPaiementFacture)) {
            $this->detailPaiementFactures->add($detailPaiementFacture);
            $detailPaiementFacture->setPaiement($this);
        }

        return $this;
    }

    public function removeDetailPaiementFacture(DetailPaiementFacture $detailPaiementFacture): static
    {
        if ($this->detailPaiementFactures->removeElement($detailPaiementFacture)) {
            // set the owning side to null (unless already changed)
            if ($detailPaiementFacture->getPaiement() === $this) {
                $detailPaiementFacture->setPaiement(null);
            }
        }

        return $this;
    }

    
}
