<?php

namespace App\Entity;

use App\Repository\BienRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BienRepository::class)]
class Bien
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'biens')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Site $site = null;

    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 30)]
    private ?string $statut = null;

    // #[ORM\Column(length: 50)]
    // private ?string $typeBien = null;

    

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;


    #[ORM\ManyToOne(inversedBy: 'biens')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ConfigQuartier $adresse = null;

    #[ORM\ManyToOne(inversedBy: 'biens')]
    private ?Personel $gestionnaire = null;


    

    /**
     * @var Collection<int, Caisse>
     */
    #[ORM\OneToMany(targetEntity: Caisse::class, mappedBy: 'bien')]
    private Collection $caisses;

    #[ORM\ManyToOne(inversedBy: 'biens')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $latitude = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $longitude = null;

    /**
     * @var Collection<int, ContratSurveillance>
     */
    #[ORM\OneToMany(targetEntity: ContratSurveillance::class, mappedBy: 'bien')]
    private Collection $contratSurveillances;

    #[ORM\ManyToOne(inversedBy: 'biens')]
    private ?ConfigZoneRattachement $zoneRattachement = null;

    #[ORM\ManyToOne(inversedBy: 'biens')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ConfigTypeBien $typeBien = null;

    /**
     * @var Collection<int, Personel>
     */
    #[ORM\OneToMany(targetEntity: Personel::class, mappedBy: 'bienAffecte')]
    private Collection $personels;

    #[ORM\ManyToOne(inversedBy: 'biens')]
    private ?GroupeFacturation $groupeFacturation = null;

    

    public function __construct()
    {
        $this->caisses = new ArrayCollection();
        $this->contratSurveillances = new ArrayCollection();
        $this->personels = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

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

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    // public function getTypeBien(): ?string
    // {
    //     return $this->typeBien;
    // }

    // public function setTypeBien(string $typeBien): static
    // {
    //     $this->typeBien = $typeBien;

    //     return $this;
    // }

    
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

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

    public function getGestionnaire(): ?Personel
    {
        return $this->gestionnaire;
    }

    public function setGestionnaire(?Personel $gestionnaire): static
    {
        $this->gestionnaire = $gestionnaire;

        return $this;
    }

   
    /**
     * @return Collection<int, Caisse>
     */
    public function getCaisses(): Collection
    {
        return $this->caisses;
    }

    

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        $this->client = $client;

        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(?string $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(?string $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * @return Collection<int, ContratSurveillance>
     */
    public function getContratSurveillances(): Collection
    {
        return $this->contratSurveillances;
    }

    public function addContratSurveillance(ContratSurveillance $contratSurveillance): static
    {
        if (!$this->contratSurveillances->contains($contratSurveillance)) {
            $this->contratSurveillances->add($contratSurveillance);
            $contratSurveillance->setBien($this);
        }

        return $this;
    }

    public function removeContratSurveillance(ContratSurveillance $contratSurveillance): static
    {
        if ($this->contratSurveillances->removeElement($contratSurveillance)) {
            // set the owning side to null (unless already changed)
            if ($contratSurveillance->getBien() === $this) {
                $contratSurveillance->setBien(null);
            }
        }

        return $this;
    }

    public function getZoneRattachement(): ?ConfigZoneRattachement
    {
        return $this->zoneRattachement;
    }

    public function setZoneRattachement(?ConfigZoneRattachement $zoneRattachement): static
    {
        $this->zoneRattachement = $zoneRattachement;

        return $this;
    }

    public function getTypeBien(): ?ConfigTypeBien
    {
        return $this->typeBien;
    }

    public function setTypeBien(?ConfigTypeBien $typeBien): static
    {
        $this->typeBien = $typeBien;

        return $this;
    }

    /**
     * @return Collection<int, Personel>
     */
    public function getPersonels(): Collection
    {
        return $this->personels;
    }

    public function addPersonel(Personel $personel): static
    {
        if (!$this->personels->contains($personel)) {
            $this->personels->add($personel);
            $personel->setBienAffecte($this);
        }

        return $this;
    }

    public function removePersonel(Personel $personel): static
    {
        if ($this->personels->removeElement($personel)) {
            // set the owning side to null (unless already changed)
            if ($personel->getBienAffecte() === $this) {
                $personel->setBienAffecte(null);
            }
        }

        return $this;
    }

    public function getGroupeFacturation(): ?GroupeFacturation
    {
        return $this->groupeFacturation;
    }

    public function setGroupeFacturation(?GroupeFacturation $groupeFacturation): static
    {
        $this->groupeFacturation = $groupeFacturation;

        return $this;
    }

    

    
}
