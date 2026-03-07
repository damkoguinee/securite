<?php

namespace App\Entity;

use App\Repository\FactureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FactureRepository::class)]
class Facture
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // 🧾 Informations de facturation
    #[ORM\Column(length: 50, unique: true)]
    private ?string $reference = null; // ex: FAC-2025-0001

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $dateEmission = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $dateEcheance = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private ?string $montantTotal = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $montantPaye = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $montantHT = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $remisePourcentage = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $remiseMontant = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $baseTVA = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $tauxTVA = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $montantTVA = null;

    #[ORM\Column(length: 20)]
    private ?string $statut = 'en_attente'; // en_attente, payee, partielle, en_retard

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    // 📅 Période facturée
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $periodeDebut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $periodeFin = null;

    #[ORM\ManyToOne(inversedBy: 'factures')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ContratSurveillance $contrat = null;

    

    #[ORM\Column]
    private ?\DateTime $dateSaisie = null;

    #[ORM\ManyToOne(inversedBy: 'factures')]
    private ?Personel $saisiePar = null;

    #[ORM\ManyToOne(inversedBy: 'factures')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Site $site = null;

    /**
     * @var Collection<int, MouvementCollaborateur>
     */
    #[ORM\OneToMany(targetEntity: MouvementCollaborateur::class, mappedBy: 'facturation', orphanRemoval:true, cascade:['persist', 'remove'])]
    private Collection $mouvementCollaborateurs;

   

    #[ORM\ManyToOne(inversedBy: 'factures')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ConfigDevise $devise = null;


    /**
     * @var Collection<int, Paiement>
     */
    #[ORM\ManyToMany(targetEntity: Paiement::class, mappedBy: 'facture')]
    private Collection $paiements;

    /**
     * @var Collection<int, DetailPaiementFacture>
     */
    #[ORM\OneToMany(targetEntity: DetailPaiementFacture::class, mappedBy: 'facture', orphanRemoval:true, cascade:['remove'])]
    private Collection $detailPaiementFactures;

    public function __construct()
    {
        $this->mouvementCollaborateurs = new ArrayCollection();
        $this->paiements = new ArrayCollection();
        $this->detailPaiementFactures = new ArrayCollection();
    }

    // ===============================
    // Getters / Setters
    // ===============================

    public function getId(): ?int
    {
        return $this->id;
    }

    

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;
        return $this;
    }

    public function getDateEmission(): ?\DateTime
    {
        return $this->dateEmission;
    }

    public function setDateEmission(\DateTime $dateEmission): static
    {
        $this->dateEmission = $dateEmission;
        return $this;
    }

    public function getDateEcheance(): ?\DateTime
    {
        return $this->dateEcheance;
    }

    public function setDateEcheance(\DateTime $dateEcheance): static
    {
        $this->dateEcheance = $dateEcheance;
        return $this;
    }

    public function getMontantTotal(): ?string
    {
        return $this->montantTotal;
    }

    public function setMontantTotal(string $montantTotal): static
    {
        $this->montantTotal = $montantTotal;
        return $this;
    }

    public function getMontantPaye(): ?string
    {
        return $this->montantPaye;
    }

    public function setMontantPaye(?string $montantPaye): static
    {
        $this->montantPaye = $montantPaye;
        return $this;
    }

        public function getMontantHT(): ?string
    {
        return $this->montantHT;
    }

    public function setMontantHT(?string $montantHT): static
    {
        $this->montantHT = $montantHT;
        return $this;
    }

    public function getRemisePourcentage(): ?string
    {
        return $this->remisePourcentage;
    }

    public function setRemisePourcentage(?string $remisePourcentage): static
    {
        $this->remisePourcentage = $remisePourcentage;
        return $this;
    }

    public function getRemiseMontant(): ?string
    {
        return $this->remiseMontant;
    }

    public function setRemiseMontant(?string $remiseMontant): static
    {
        $this->remiseMontant = $remiseMontant;
        return $this;
    }

    public function getBaseTVA(): ?string
    {
        return $this->baseTVA;
    }

    public function setBaseTVA(?string $baseTVA): static
    {
        $this->baseTVA = $baseTVA;
        return $this;
    }

    public function getTauxTVA(): ?string
    {
        return $this->tauxTVA;
    }

    public function setTauxTVA(?string $tauxTVA): static
    {
        $this->tauxTVA = $tauxTVA;
        return $this;
    }

    public function getMontantTVA(): ?string
    {
        return $this->montantTVA;
    }

    public function setMontantTVA(?string $montantTVA): static
    {
        $this->montantTVA = $montantTVA;
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

    public function getPeriodeDebut(): ?\DateTime
    {
        return $this->periodeDebut;
    }

    public function setPeriodeDebut(?\DateTime $periodeDebut): static
    {
        $this->periodeDebut = $periodeDebut;
        return $this;
    }

    public function getPeriodeFin(): ?\DateTime
    {
        return $this->periodeFin;
    }

    public function setPeriodeFin(?\DateTime $periodeFin): static
    {
        $this->periodeFin = $periodeFin;
        return $this;
    }

    // 💰 Utilitaires
    public function getMontantRestant(): float
    {
        return (float)$this->montantTotal - (float)($this->montantPaye ?? 0);
    }

    public function getMontantTotalFormatted(): string
    {
        return number_format((float)$this->montantTotal, 0, ',', ' ') . ' GNF';
    }

    public function getMontantPayeFormatted(): string
    {
        return number_format((float)($this->montantPaye ?? 0), 0, ',', ' ') . ' GNF';
    }

    public function getResteFormatted(): string
    {
        return number_format($this->getMontantRestant(), 0, ',', ' ') . ' GNF';
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

    public function getSite(): ?Site
    {
        return $this->site;
    }

    public function setSite(?Site $site): static
    {
        $this->site = $site;

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
            $mouvementCollaborateur->setFacturation($this);
        }

        return $this;
    }

    public function removeMouvementCollaborateur(MouvementCollaborateur $mouvementCollaborateur): static
    {
        if ($this->mouvementCollaborateurs->removeElement($mouvementCollaborateur)) {
            // set the owning side to null (unless already changed)
            if ($mouvementCollaborateur->getFacturation() === $this) {
                $mouvementCollaborateur->setFacturation(null);
            }
        }

        return $this;
    }

    

    public function getDevise(): ?ConfigDevise
    {
        return $this->devise;
    }

    public function setDevise(?ConfigDevise $devise): static
    {
        $this->devise = $devise;

        return $this;
    }

    

    /**
     * @return Collection<int, Paiement>
     */
    public function getPaiements(): Collection
    {
        return $this->paiements;
    }

    public function addPaiement(Paiement $paiement): static
    {
        if (!$this->paiements->contains($paiement)) {
            $this->paiements->add($paiement);
            $paiement->addFacture($this);
        }

        return $this;
    }

    public function removePaiement(Paiement $paiement): static
    {
        if ($this->paiements->removeElement($paiement)) {
            $paiement->removeFacture($this);
        }

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
            $detailPaiementFacture->setFacture($this);
        }

        return $this;
    }

    public function removeDetailPaiementFacture(DetailPaiementFacture $detailPaiementFacture): static
    {
        if ($this->detailPaiementFactures->removeElement($detailPaiementFacture)) {
            // set the owning side to null (unless already changed)
            if ($detailPaiementFacture->getFacture() === $this) {
                $detailPaiementFacture->setFacture(null);
            }
        }

        return $this;
    }
}
