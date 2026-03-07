<?php

namespace App\Entity;

use App\Repository\ContratComplementaireRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContratComplementaireRepository::class)]
class ContratComplementaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'contratComplementaires')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ContratSurveillance $contrat = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $dateDebut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $dateFin = null;

    #[ORM\Column(length: 150)]
    private ?string $motif = null;

    #[ORM\Column]
    private ?\DateTime $dateSaisie = null;

    #[ORM\ManyToOne(inversedBy: 'contratComplementaires')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Personel $saisiePar = null;

    /**
     * @var Collection<int, ComplementTypeSurveillance>
     */
    #[ORM\OneToMany(targetEntity: ComplementTypeSurveillance::class, mappedBy: 'contratComplementaire', orphanRemoval: true, cascade:['persist', 'remove'])]
    private Collection $complementTypeSurveillances;

    public function __construct()
    {
        $this->complementTypeSurveillances = new ArrayCollection();
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

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(string $motif): static
    {
        $this->motif = $motif;

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

    public function getSaisiePar(): ?Personel
    {
        return $this->saisiePar;
    }

    public function setSaisiePar(?Personel $saisiePar): static
    {
        $this->saisiePar = $saisiePar;

        return $this;
    }

    /**
     * @return Collection<int, ComplementTypeSurveillance>
     */
    public function getComplementTypeSurveillances(): Collection
    {
        return $this->complementTypeSurveillances;
    }

    public function addComplementTypeSurveillance(ComplementTypeSurveillance $complementTypeSurveillance): static
    {
        if (!$this->complementTypeSurveillances->contains($complementTypeSurveillance)) {
            $this->complementTypeSurveillances->add($complementTypeSurveillance);
            $complementTypeSurveillance->setContratComplementaire($this);
        }

        return $this;
    }

    public function removeComplementTypeSurveillance(ComplementTypeSurveillance $complementTypeSurveillance): static
    {
        if ($this->complementTypeSurveillances->removeElement($complementTypeSurveillance)) {
            // set the owning side to null (unless already changed)
            if ($complementTypeSurveillance->getContratComplementaire() === $this) {
                $complementTypeSurveillance->setContratComplementaire(null);
            }
        }

        return $this;
    }
}
