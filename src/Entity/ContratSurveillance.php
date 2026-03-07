<?php

namespace App\Entity;

use App\Repository\ContratSurveillanceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContratSurveillanceRepository::class)]
class ContratSurveillance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'contratSurveillances')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Bien $bien = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $dateDebut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $dateFin = null;

    #[ORM\Column(length: 50)]
    private ?string $modeFacturation = null;

    #[ORM\Column(length: 20)]
    private ?string $statut = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    /**
     * @var Collection<int, ContratTypeSurveillance>
     */
    #[ORM\OneToMany(
        targetEntity: ContratTypeSurveillance::class,
        mappedBy: 'contrat',
        cascade: ['persist', 'remove'],
        orphanRemoval: false
    )]
    private Collection $typesSurveillance;

    /**
     * @var Collection<int, Facture>
     */
    #[ORM\OneToMany(targetEntity: Facture::class, mappedBy: 'contrat')]
    private Collection $factures;

    /**
     * @var Collection<int, AffectationAgent>
     */
    #[ORM\OneToMany(targetEntity: AffectationAgent::class, mappedBy: 'contrat')]
    private Collection $affectationAgents;

    /**
     * @var Collection<int, ContratComplementaire>
     */
    #[ORM\OneToMany(targetEntity: ContratComplementaire::class, mappedBy: 'contrat')]
    private Collection $contratComplementaires;

    #[ORM\Column(nullable: true)]
    private ?float $remise = null;

    #[ORM\Column(nullable: true)]
    private ?float $tva = null;

    /**
     * @var Collection<int, AffectationAgent>
     */
    #[ORM\OneToMany(targetEntity: AffectationAgent::class, mappedBy: 'contratInitial')]
    private Collection $affectationAgentContratInitial;

    /**
     * @var Collection<int, PaiementSalairePersonnel>
     */
    #[ORM\OneToMany(targetEntity: PaiementSalairePersonnel::class, mappedBy: 'contrat')]
    private Collection $paiementSalairePersonnels;

    /**
     * @var Collection<int, AvanceSalaire>
     */
    #[ORM\OneToMany(targetEntity: AvanceSalaire::class, mappedBy: 'contrat')]
    private Collection $avanceSalaires;

    public function __construct()
    {
        $this->typesSurveillance = new ArrayCollection();
        $this->factures = new ArrayCollection();
        $this->affectationAgents = new ArrayCollection();
        $this->contratComplementaires = new ArrayCollection();
        $this->affectationAgentContratInitial = new ArrayCollection();
        $this->paiementSalairePersonnels = new ArrayCollection();
        $this->avanceSalaires = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBien(): ?Bien
    {
        return $this->bien;
    }

    public function setBien(?Bien $bien): static
    {
        $this->bien = $bien;
        return $this;
    }

    public function getDateDebut(): ?\DateTime
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTime $dateDebut): static
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTime
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTime $dateFin): static
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    public function getModeFacturation(): ?string
    {
        return $this->modeFacturation;
    }

    public function setModeFacturation(string $modeFacturation): static
    {
        $this->modeFacturation = $modeFacturation;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;
        return $this;
    }

    /**
     * @return Collection<int, ContratTypeSurveillance>
     */
    public function getTypesSurveillance(): Collection
    {
        return $this->typesSurveillance;
    }

    public function setTypesSurveillance(iterable $items): self
    {
        $this->typesSurveillance = new ArrayCollection();

        foreach ($items as $item) {
            $this->addTypeSurveillance($item);
        }

        return $this;
    }

    public function addTypeSurveillance(ContratTypeSurveillance $type): static
    {
        if (!$this->typesSurveillance->contains($type)) {
            $this->typesSurveillance->add($type);
            $type->setContrat($this);
        }
        return $this;
    }

    public function removeTypeSurveillance(ContratTypeSurveillance $type): static
    {
        if ($this->typesSurveillance->removeElement($type)) {
            if ($type->getContrat() === $this) {
                $type->setContrat(null);
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
            $facture->setContrat($this);
        }
        return $this;
    }

    public function removeFacture(Facture $facture): static
    {
        if ($this->factures->removeElement($facture)) {
            if ($facture->getContrat() === $this) {
                $facture->setContrat(null);
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
            $affectationAgent->setContrat($this);
        }
        return $this;
    }

    public function removeAffectationAgent(AffectationAgent $affectationAgent): static
    {
        if ($this->affectationAgents->removeElement($affectationAgent)) {
            if ($affectationAgent->getContrat() === $this) {
                $affectationAgent->setContrat(null);
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
            $contratComplementaire->setContrat($this);
        }

        return $this;
    }

    public function removeContratComplementaire(ContratComplementaire $contratComplementaire): static
    {
        if ($this->contratComplementaires->removeElement($contratComplementaire)) {
            // set the owning side to null (unless already changed)
            if ($contratComplementaire->getContrat() === $this) {
                $contratComplementaire->setContrat(null);
            }
        }

        return $this;
    }

    public function getRemise(): ?float
    {
        return $this->remise;
    }

    public function setRemise(?float $remise): static
    {
        $this->remise = $remise;

        return $this;
    }

    public function getTva(): ?float
    {
        return $this->tva;
    }

    public function setTva(?float $tva): static
    {
        $this->tva = $tva;

        return $this;
    }

    /**
     * @return Collection<int, AffectationAgent>
     */
    public function getAffectationAgentContratInitial(): Collection
    {
        return $this->affectationAgentContratInitial;
    }

    public function addAffectationAgentContratInitial(AffectationAgent $affectationAgentContratInitial): static
    {
        if (!$this->affectationAgentContratInitial->contains($affectationAgentContratInitial)) {
            $this->affectationAgentContratInitial->add($affectationAgentContratInitial);
            $affectationAgentContratInitial->setContratInitial($this);
        }

        return $this;
    }

    public function removeAffectationAgentContratInitial(AffectationAgent $affectationAgentContratInitial): static
    {
        if ($this->affectationAgentContratInitial->removeElement($affectationAgentContratInitial)) {
            // set the owning side to null (unless already changed)
            if ($affectationAgentContratInitial->getContratInitial() === $this) {
                $affectationAgentContratInitial->setContratInitial(null);
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
            $paiementSalairePersonnel->setContrat($this);
        }

        return $this;
    }

    public function removePaiementSalairePersonnel(PaiementSalairePersonnel $paiementSalairePersonnel): static
    {
        if ($this->paiementSalairePersonnels->removeElement($paiementSalairePersonnel)) {
            // set the owning side to null (unless already changed)
            if ($paiementSalairePersonnel->getContrat() === $this) {
                $paiementSalairePersonnel->setContrat(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AvanceSalaire>
     */
    public function getAvanceSalaires(): Collection
    {
        return $this->avanceSalaires;
    }

    public function addAvanceSalaire(AvanceSalaire $avanceSalaire): static
    {
        if (!$this->avanceSalaires->contains($avanceSalaire)) {
            $this->avanceSalaires->add($avanceSalaire);
            $avanceSalaire->setContrat($this);
        }

        return $this;
    }

    public function removeAvanceSalaire(AvanceSalaire $avanceSalaire): static
    {
        if ($this->avanceSalaires->removeElement($avanceSalaire)) {
            // set the owning side to null (unless already changed)
            if ($avanceSalaire->getContrat() === $this) {
                $avanceSalaire->setContrat(null);
            }
        }

        return $this;
    }
}
