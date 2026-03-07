<?php

namespace App\Entity;

use App\Repository\PersonelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PersonelRepository::class)]
class Personel extends User
{

    #[ORM\Column(length: 100)]
    private ?string $fonction = null;

    #[ORM\Column(nullable: true)]
    private ?float $tauxHoraire = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $salaireBase = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateEmbauche = null;


    #[ORM\Column(length: 255, nullable: true)]
    private ?string $signature = null;

    

    /**
     * @var Collection<int, Bien>
     */
    #[ORM\OneToMany(targetEntity: Bien::class, mappedBy: 'gestionnaire')]
    private Collection $biens;

    /**
     * @var Collection<int, MouvementCaisse>
     */
    #[ORM\OneToMany(targetEntity: MouvementCaisse::class, mappedBy: 'saisiePar')]
    private Collection $mouvementCaisses;

    /**
     * @var Collection<int, PaiementSalairePersonnel>
     */
    #[ORM\OneToMany(targetEntity: PaiementSalairePersonnel::class, mappedBy: 'personnel')]
    private Collection $paiementSalairePersonnels;

    /**
     * @var Collection<int, AffectationAgent>
     */
    #[ORM\OneToMany(targetEntity: AffectationAgent::class, mappedBy: 'saisirPar')]
    private Collection $affectationAgents;

    /**
     * @var Collection<int, AffectationAgent>
     */
    #[ORM\OneToMany(targetEntity: AffectationAgent::class, mappedBy: 'personnel')]
    private Collection $affectationAgentPersonnels;

    /**
     * @var Collection<int, Presence>
     */
    #[ORM\OneToMany(targetEntity: Presence::class, mappedBy: 'saisiePar')]
    private Collection $presences;

    

    /**
     * @var Collection<int, Facture>
     */
    #[ORM\OneToMany(targetEntity: Facture::class, mappedBy: 'saisiePar')]
    private Collection $factures;

    /**
     * @var Collection<int, Penalite>
     */
    #[ORM\OneToMany(targetEntity: Penalite::class, mappedBy: 'saisiePar')]
    private Collection $penalites;

    /**
     * @var Collection<int, ContratComplementaire>
     */
    #[ORM\OneToMany(targetEntity: ContratComplementaire::class, mappedBy: 'saisiePar')]
    private Collection $contratComplementaires;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $typePersonnel = null;

    #[ORM\ManyToOne(inversedBy: 'personels')]
    private ?Bien $bienAffecte = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $dateFinContrat = null;

    /**
     * @var Collection<int, AffectationAgent>
     */
    #[ORM\OneToMany(targetEntity: AffectationAgent::class, mappedBy: 'agentInitial')]
    private Collection $affectationAgentRemplace;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $statutPlanning = null;


   


   

    public function __construct()
    {
        parent::__construct();
       
        $this->biens = new ArrayCollection();
        $this->mouvementCaisses = new ArrayCollection();
        $this->paiementSalairePersonnels = new ArrayCollection();
        $this->affectationAgents = new ArrayCollection();
        $this->affectationAgentPersonnels = new ArrayCollection();
        $this->presences = new ArrayCollection();
        $this->factures = new ArrayCollection();
        $this->penalites = new ArrayCollection();
        $this->contratComplementaires = new ArrayCollection();
        $this->affectationAgentRemplace = new ArrayCollection();
    }

    


    public function getFonction(): ?string
    {
        return $this->fonction;
    }

    public function setFonction(string $fonction): static
    {
        $this->fonction = $fonction;

        return $this;
    }

    public function getTauxHoraire(): ?float
    {
        return $this->tauxHoraire;
    }

    public function setTauxHoraire(?float $tauxHoraire): static
    {
        $this->tauxHoraire = $tauxHoraire;

        return $this;
    }

    public function getSalaireBase(): ?string
    {
        return $this->salaireBase;
    }

    public function setSalaireBase(?string $salaireBase): static
    {
        $this->salaireBase = $salaireBase;

        return $this;
    }

    public function getDateEmbauche(): ?\DateTimeInterface
    {
        return $this->dateEmbauche;
    }

    public function setDateEmbauche(\DateTimeInterface $dateEmbauche): static
    {
        $this->dateEmbauche = $dateEmbauche;

        return $this;
    }

    

    public function getSignature(): ?string
    {
        return $this->signature;
    }

    public function setSignature(?string $signature): static
    {
        $this->signature = $signature;

        return $this;
    }

    

    /**
     * @return Collection<int, Bien>
     */
    public function getBiens(): Collection
    {
        return $this->biens;
    }

    public function addBien(Bien $bien): static
    {
        if (!$this->biens->contains($bien)) {
            $this->biens->add($bien);
            $bien->setGestionnaire($this);
        }

        return $this;
    }

    public function removeBien(Bien $bien): static
    {
        if ($this->biens->removeElement($bien)) {
            // set the owning side to null (unless already changed)
            if ($bien->getGestionnaire() === $this) {
                $bien->setGestionnaire(null);
            }
        }

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
            $mouvementCaiss->setSaisiePar($this);
        }

        return $this;
    }

    public function removeMouvementCaiss(MouvementCaisse $mouvementCaiss): static
    {
        if ($this->mouvementCaisses->removeElement($mouvementCaiss)) {
            // set the owning side to null (unless already changed)
            if ($mouvementCaiss->getSaisiePar() === $this) {
                $mouvementCaiss->setSaisiePar(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PaiementSalairePersonnel>
     */
    public function getPaiementSalairePersonnels(): Collection
    {
        return $this->paiementSalairePersonnels;
    }

    public function addPaiementSalairePersonnel(PaiementSalairePersonnel $paiementSalairePersonnel): static
    {
        if (!$this->paiementSalairePersonnels->contains($paiementSalairePersonnel)) {
            $this->paiementSalairePersonnels->add($paiementSalairePersonnel);
            $paiementSalairePersonnel->setPersonnel($this);
        }

        return $this;
    }

    public function removePaiementSalairePersonnel(PaiementSalairePersonnel $paiementSalairePersonnel): static
    {
        if ($this->paiementSalairePersonnels->removeElement($paiementSalairePersonnel)) {
            // set the owning side to null (unless already changed)
            if ($paiementSalairePersonnel->getPersonnel() === $this) {
                $paiementSalairePersonnel->setPersonnel(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AffectationAgent>
     */
    public function getAffectationAgents(): Collection
    {
        return $this->affectationAgents;
    }

    public function addAffectationAgent(AffectationAgent $affectationAgent): static
    {
        if (!$this->affectationAgents->contains($affectationAgent)) {
            $this->affectationAgents->add($affectationAgent);
            $affectationAgent->setSaisirPar($this);
        }

        return $this;
    }

    public function removeAffectationAgent(AffectationAgent $affectationAgent): static
    {
        if ($this->affectationAgents->removeElement($affectationAgent)) {
            // set the owning side to null (unless already changed)
            if ($affectationAgent->getSaisirPar() === $this) {
                $affectationAgent->setSaisirPar(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AffectationAgent>
     */
    public function getAffectationAgentPersonnels(): Collection
    {
        return $this->affectationAgentPersonnels;
    }

    public function addAffectationAgentPersonnel(AffectationAgent $affectationAgentPersonnel): static
    {
        if (!$this->affectationAgentPersonnels->contains($affectationAgentPersonnel)) {
            $this->affectationAgentPersonnels->add($affectationAgentPersonnel);
            $affectationAgentPersonnel->setPersonnel($this);
        }

        return $this;
    }

    public function removeAffectationAgentPersonnel(AffectationAgent $affectationAgentPersonnel): static
    {
        if ($this->affectationAgentPersonnels->removeElement($affectationAgentPersonnel)) {
            // set the owning side to null (unless already changed)
            if ($affectationAgentPersonnel->getPersonnel() === $this) {
                $affectationAgentPersonnel->setPersonnel(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Presence>
     */
    public function getPresences(): Collection
    {
        return $this->presences;
    }

    public function addPresence(Presence $presence): static
    {
        if (!$this->presences->contains($presence)) {
            $this->presences->add($presence);
            $presence->setSaisiePar($this);
        }

        return $this;
    }

    public function removePresence(Presence $presence): static
    {
        if ($this->presences->removeElement($presence)) {
            // set the owning side to null (unless already changed)
            if ($presence->getSaisiePar() === $this) {
                $presence->setSaisiePar(null);
            }
        }

        return $this;
    }

   

    /**
     * @return Collection<int, Facture>
     */
    public function getFactures(): Collection
    {
        return $this->factures;
    }

    public function addFacture(Facture $facture): static
    {
        if (!$this->factures->contains($facture)) {
            $this->factures->add($facture);
            $facture->setSaisiePar($this);
        }

        return $this;
    }

    public function removeFacture(Facture $facture): static
    {
        if ($this->factures->removeElement($facture)) {
            // set the owning side to null (unless already changed)
            if ($facture->getSaisiePar() === $this) {
                $facture->setSaisiePar(null);
            }
        }

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
            $penalite->setSaisiePar($this);
        }

        return $this;
    }

    public function removePenalite(Penalite $penalite): static
    {
        if ($this->penalites->removeElement($penalite)) {
            // set the owning side to null (unless already changed)
            if ($penalite->getSaisiePar() === $this) {
                $penalite->setSaisiePar(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ContratComplementaire>
     */
    public function getContratComplementaires(): Collection
    {
        return $this->contratComplementaires;
    }

    public function addContratComplementaire(ContratComplementaire $contratComplementaire): static
    {
        if (!$this->contratComplementaires->contains($contratComplementaire)) {
            $this->contratComplementaires->add($contratComplementaire);
            $contratComplementaire->setSaisiePar($this);
        }

        return $this;
    }

    public function removeContratComplementaire(ContratComplementaire $contratComplementaire): static
    {
        if ($this->contratComplementaires->removeElement($contratComplementaire)) {
            // set the owning side to null (unless already changed)
            if ($contratComplementaire->getSaisiePar() === $this) {
                $contratComplementaire->setSaisiePar(null);
            }
        }

        return $this;
    }

    public function getTypePersonnel(): ?string
    {
        return $this->typePersonnel;
    }

    public function setTypePersonnel(?string $typePersonnel): static
    {
        $this->typePersonnel = $typePersonnel;

        return $this;
    }

    public function getBienAffecte(): ?Bien
    {
        return $this->bienAffecte;
    }

    public function setBienAffecte(?Bien $bienAffecte): static
    {
        $this->bienAffecte = $bienAffecte;

        return $this;
    }

    public function getDateFinContrat(): ?\DateTime
    {
        return $this->dateFinContrat;
    }

    public function setDateFinContrat(?\DateTime $dateFinContrat): static
    {
        $this->dateFinContrat = $dateFinContrat;

        return $this;
    }

    /**
     * @return Collection<int, AffectationAgent>
     */
    public function getAffectationAgentRemplace(): Collection
    {
        return $this->affectationAgentRemplace;
    }

    public function addAffectationAgentRemplace(AffectationAgent $affectationAgentRemplace): static
    {
        if (!$this->affectationAgentRemplace->contains($affectationAgentRemplace)) {
            $this->affectationAgentRemplace->add($affectationAgentRemplace);
            $affectationAgentRemplace->setAgentInitial($this);
        }

        return $this;
    }

    public function removeAffectationAgentRemplace(AffectationAgent $affectationAgentRemplace): static
    {
        if ($this->affectationAgentRemplace->removeElement($affectationAgentRemplace)) {
            // set the owning side to null (unless already changed)
            if ($affectationAgentRemplace->getAgentInitial() === $this) {
                $affectationAgentRemplace->setAgentInitial(null);
            }
        }

        return $this;
    }

    public function getStatutPlanning(): ?string
    {
        return $this->statutPlanning;
    }

    public function setStatutPlanning(?string $statutPlanning): static
    {
        $this->statutPlanning = $statutPlanning;

        return $this;
    }
}
