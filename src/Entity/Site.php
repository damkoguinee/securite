<?php

namespace App\Entity;

use App\Repository\SiteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SiteRepository::class)]
class Site
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    #[ORM\ManyToOne(inversedBy: 'sites')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ConfigQuartier $adresse = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $complementAdresse = null;

    #[ORM\Column(length: 50)]
    private ?string $email = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateOuverture = null;

    #[ORM\Column(length: 20)]
    private ?string $telephone = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $initial = null;


    #[ORM\ManyToOne(inversedBy: 'sites')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Entreprise $entreprise = null;

    /**
     * @var Collection<int, HistoriqueChangement>
     */
    #[ORM\OneToMany(targetEntity: HistoriqueChangement::class, mappedBy: 'site')]
    private Collection $historiqueChangements;

    /**
     * @var Collection<int, Site>
     */
    #[ORM\OneToMany(targetEntity: Site::class, mappedBy: 'site')]
    private Collection $sites;

     /**
     * @var Collection<int, AvanceSalaire>
     */
    #[ORM\OneToMany(targetEntity: AvanceSalaire::class, mappedBy: 'site')]
    private Collection $avanceSalaires;

    /**
     * @var Collection<int, AbsencePersonnel>
     */
    #[ORM\OneToMany(targetEntity: AbsencePersonnel::class, mappedBy: 'site')]
    private Collection $absencePersonnels;


    /**
     * @var Collection<int, PaiementSalairePersonnel>
     */
    #[ORM\OneToMany(targetEntity: PaiementSalairePersonnel::class, mappedBy: 'site')]
    private Collection $paiementSalairePersonnels;

    /**
     * @var Collection<int, PrimePersonnel>
     */
    #[ORM\OneToMany(targetEntity: PrimePersonnel::class, mappedBy: 'site')]
    private Collection $primePersonnels;

    /**
     * @var Collection<int, MouvementCaisse>
     */
    #[ORM\OneToMany(targetEntity: MouvementCaisse::class, mappedBy: 'site')]
    private Collection $mouvementCaisses;

    /**
     * @var Collection<int, Facture>
     */
    #[ORM\OneToMany(targetEntity: Facture::class, mappedBy: 'site')]
    private Collection $factures;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * @var Collection<int, ConfigSalaire>
     */
    #[ORM\OneToMany(targetEntity: ConfigSalaire::class, mappedBy: 'site')]
    private Collection $configSalaires;

   


    public function __construct()
    {
        $this->historiqueChangements = new ArrayCollection();
        $this->sites = new ArrayCollection();
        $this->avanceSalaires = new ArrayCollection();
        $this->absencePersonnels = new ArrayCollection();
        $this->paiementSalairePersonnels = new ArrayCollection();
        $this->primePersonnels = new ArrayCollection();
        $this->mouvementCaisses = new ArrayCollection();
        $this->factures = new ArrayCollection();
        $this->configSalaires = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getAdresse(): ?ConfigQuartier
    {
        return $this->adresse;
    }

    public function setAdresse(?ConfigQuartier $adresse): static
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getComplementAdresse(): ?string
    {
        return $this->complementAdresse;
    }

    public function setComplementAdresse(?string $complementAdresse): static
    {
        $this->complementAdresse = $complementAdresse;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getDateOuverture(): ?\DateTimeInterface
    {
        return $this->dateOuverture;
    }

    public function setDateOuverture(?\DateTimeInterface $dateOuverture): static
    {
        $this->dateOuverture = $dateOuverture;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getInitial(): ?string
    {
        return $this->initial;
    }

    public function setInitial(?string $initial): static
    {
        $this->initial = $initial;

        return $this;
    }


    

    public function getEntreprise(): ?Entreprise
    {
        return $this->entreprise;
    }

    public function setEntreprise(?Entreprise $entreprise): static
    {
        $this->entreprise = $entreprise;

        return $this;
    }

   
    
    /**
     * @return Collection<int, HistoriqueChangement>
     */
    public function getHistoriqueChangements(): Collection
    {
        return $this->historiqueChangements;
    }

    public function addHistoriqueChangement(HistoriqueChangement $historiqueChangement): static
    {
        if (!$this->historiqueChangements->contains($historiqueChangement)) {
            $this->historiqueChangements->add($historiqueChangement);
            $historiqueChangement->setSite($this);
        }

        return $this;
    }

    public function removeHistoriqueChangement(HistoriqueChangement $historiqueChangement): static
    {
        if ($this->historiqueChangements->removeElement($historiqueChangement)) {
            // set the owning side to null (unless already changed)
            if ($historiqueChangement->getSite() === $this) {
                $historiqueChangement->setSite(null);
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
            $avanceSalaire->setSite($this);
        }

        return $this;
    }

    public function removeAvanceSalaire(AvanceSalaire $avanceSalaire): static
    {
        if ($this->avanceSalaires->removeElement($avanceSalaire)) {
            // set the owning side to null (unless already changed)
            if ($avanceSalaire->getSite() === $this) {
                $avanceSalaire->setSite(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AbsencePersonnel>
     */
    public function getAbsencePersonnels(): Collection
    {
        return $this->absencePersonnels;
    }

    public function addAbsencePersonnel(AbsencePersonnel $absencePersonnel): static
    {
        if (!$this->absencePersonnels->contains($absencePersonnel)) {
            $this->absencePersonnels->add($absencePersonnel);
            $absencePersonnel->setSite($this);
        }

        return $this;
    }

    public function removeAbsencePersonnel(AbsencePersonnel $absencePersonnel): static
    {
        if ($this->absencePersonnels->removeElement($absencePersonnel)) {
            // set the owning side to null (unless already changed)
            if ($absencePersonnel->getSite() === $this) {
                $absencePersonnel->setSite(null);
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
            $paiementSalairePersonnel->setSite($this);
        }

        return $this;
    }

    public function removePaiementSalairePersonnel(PaiementSalairePersonnel $paiementSalairePersonnel): static
    {
        if ($this->paiementSalairePersonnels->removeElement($paiementSalairePersonnel)) {
            // set the owning side to null (unless already changed)
            if ($paiementSalairePersonnel->getSite() === $this) {
                $paiementSalairePersonnel->setSite(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PrimePersonnel>
     */
    public function getPrimePersonnels(): Collection
    {
        return $this->primePersonnels;
    }

    public function addPrimePersonnel(PrimePersonnel $primePersonnel): static
    {
        if (!$this->primePersonnels->contains($primePersonnel)) {
            $this->primePersonnels->add($primePersonnel);
            $primePersonnel->setSite($this);
        }

        return $this;
    }

    public function removePrimePersonnel(PrimePersonnel $primePersonnel): static
    {
        if ($this->primePersonnels->removeElement($primePersonnel)) {
            // set the owning side to null (unless already changed)
            if ($primePersonnel->getSite() === $this) {
                $primePersonnel->setSite(null);
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
            $mouvementCaiss->setSite($this);
        }

        return $this;
    }

    public function removeMouvementCaiss(MouvementCaisse $mouvementCaiss): static
    {
        if ($this->mouvementCaisses->removeElement($mouvementCaiss)) {
            // set the owning side to null (unless already changed)
            if ($mouvementCaiss->getSite() === $this) {
                $mouvementCaiss->setSite(null);
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
            $facture->setSite($this);
        }

        return $this;
    }

    public function removeFacture(Facture $facture): static
    {
        if ($this->factures->removeElement($facture)) {
            // set the owning side to null (unless already changed)
            if ($facture->getSite() === $this) {
                $facture->setSite(null);
            }
        }

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, ConfigSalaire>
     */
    public function getConfigSalaires(): Collection
    {
        return $this->configSalaires;
    }

    public function addConfigSalaire(ConfigSalaire $configSalaire): static
    {
        if (!$this->configSalaires->contains($configSalaire)) {
            $this->configSalaires->add($configSalaire);
            $configSalaire->setSite($this);
        }

        return $this;
    }

    public function removeConfigSalaire(ConfigSalaire $configSalaire): static
    {
        if ($this->configSalaires->removeElement($configSalaire)) {
            // set the owning side to null (unless already changed)
            if ($configSalaire->getSite() === $this) {
                $configSalaire->setSite(null);
            }
        }

        return $this;
    }

    

}
