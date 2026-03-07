<?php

namespace App\Entity;

use App\Repository\AffectationAgentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AffectationAgentRepository::class)]
class AffectationAgent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'affectationAgents')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ContratSurveillance $contrat = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $dateOperation = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTime $heureDebut = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTime $heureFin = null;

    #[ORM\Column]
    private ?bool $presenceConfirme = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\Column(length: 50)]
    private ?string $poste = null;

    #[ORM\ManyToOne(inversedBy: 'affectationAgents')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Personel $saisirPar = null;

    #[ORM\Column]
    private ?\DateTime $dateSaisie = null;

    #[ORM\ManyToOne(inversedBy: 'affectationAgentPersonnels')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Personel $personnel = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $groupeAffectation = null;

    #[ORM\Column(nullable: true)]
    private ?float $duree = null;

    /**
     * @var Collection<int, Presence>
     */
    #[ORM\OneToMany(targetEntity: Presence::class, mappedBy: 'affectationAgent', orphanRemoval: true, cascade:['remove'])]
    private Collection $presences;

    /**
     * @var Collection<int, Penalite>
     */
    #[ORM\OneToMany(targetEntity: Penalite::class, mappedBy: 'affectationAgent')]
    private Collection $penalites;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $typeAffectation = null;

    #[ORM\ManyToOne(inversedBy: 'affectationAgentRemplace')]
    private ?Personel $agentInitial = null;

    #[ORM\ManyToOne(inversedBy: 'affectationAgentContratInitial')]
    private ?ContratSurveillance $contratInitial = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $statutAffectation = null;

    public function __construct()
    {
        $this->presences = new ArrayCollection();
        $this->penalites = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContrat(): ?ContratSurveillance
    {
        return $this->contrat;
    }

    public function setContrat(?ContratSurveillance $contrat): static
    {
        $this->contrat = $contrat;

        return $this;
    }

    public function getDateOperation(): ?\DateTime
    {
        return $this->dateOperation;
    }

    public function setDateOperation(\DateTime $dateOperation): static
    {
        $this->dateOperation = $dateOperation;

        return $this;
    }

    public function getHeureDebut(): ?\DateTime
    {
        return $this->heureDebut;
    }

    public function setHeureDebut(?\DateTime $heureDebut): static
    {
        $this->heureDebut = $heureDebut;

        return $this;
    }

    public function getHeureFin(): ?\DateTime
    {
        return $this->heureFin;
    }

    public function setHeureFin(?\DateTime $heureFin): static
    {
        $this->heureFin = $heureFin;

        return $this;
    }

    public function isPresenceConfirme(): ?bool
    {
        return $this->presenceConfirme;
    }

    public function setPresenceConfirme(bool $presenceConfirme): static
    {
        $this->presenceConfirme = $presenceConfirme;

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

    public function getPoste(): ?string
    {
        return $this->poste;
    }

    public function setPoste(string $poste): static
    {
        $this->poste = $poste;

        return $this;
    }

    public function getSaisirPar(): ?Personel
    {
        return $this->saisirPar;
    }

    public function setSaisirPar(?Personel $saisirPar): static
    {
        $this->saisirPar = $saisirPar;

        return $this;
    }

    public function getDateSaisie(): ?\DateTime
    {
        return $this->dateSaisie;
    }

    public function setDateSaisie(\DateTime $dateSaisie): static
    {
        $this->dateSaisie = $dateSaisie;

        return $this;
    }

    public function getPersonnel(): ?Personel
    {
        return $this->personnel;
    }

    public function setPersonnel(?Personel $personnel): static
    {
        $this->personnel = $personnel;

        return $this;
    }

    public function getGroupeAffectation(): ?string
    {
        return $this->groupeAffectation;
    }

    public function setGroupeAffectation(?string $groupeAffectation): static
    {
        $this->groupeAffectation = $groupeAffectation;

        return $this;
    }

    public function getDuree(): ?float
    {
        return $this->duree;
    }

    public function setDuree(?float $duree): static
    {
        $this->duree = $duree;

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
            $presence->setAffectationAgent($this);
        }

        return $this;
    }

    public function removePresence(Presence $presence): static
    {
        if ($this->presences->removeElement($presence)) {
            // set the owning side to null (unless already changed)
            if ($presence->getAffectationAgent() === $this) {
                $presence->setAffectationAgent(null);
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
            $penalite->setAffectationAgent($this);
        }

        return $this;
    }

    public function removePenalite(Penalite $penalite): static
    {
        if ($this->penalites->removeElement($penalite)) {
            // set the owning side to null (unless already changed)
            if ($penalite->getAffectationAgent() === $this) {
                $penalite->setAffectationAgent(null);
            }
        }

        return $this;
    }

    public function getTypeAffectation(): ?string
    {
        return $this->typeAffectation;
    }

    public function setTypeAffectation(?string $typeAffectation): static
    {
        $this->typeAffectation = $typeAffectation;

        return $this;
    }

    public function getAgentInitial(): ?Personel
    {
        return $this->agentInitial;
    }

    public function setAgentInitial(?Personel $agentInitial): static
    {
        $this->agentInitial = $agentInitial;

        return $this;
    }

    public function getContratInitial(): ?ContratSurveillance
    {
        return $this->contratInitial;
    }

    public function setContratInitial(?ContratSurveillance $contratInitial): static
    {
        $this->contratInitial = $contratInitial;

        return $this;
    }

    public function getStatutAffectation(): ?string
    {
        return $this->statutAffectation;
    }

    public function setStatutAffectation(?string $statutAffectation): static
    {
        $this->statutAffectation = $statutAffectation;

        return $this;
    }
}
