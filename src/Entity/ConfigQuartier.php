<?php

namespace App\Entity;

use App\Repository\ConfigQuartierRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConfigQuartierRepository::class)]
class ConfigQuartier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $code = null;

    #[ORM\Column(length: 150)]
    private ?string $nom = null;

    


    #[ORM\Column(length: 255, nullable: true)]
    private ?string $longitude = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $latitude = null;

    #[ORM\ManyToOne(inversedBy: 'configQuartiers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ConfigDivisionLocale $divisionLocale = null;


    #[ORM\ManyToOne(inversedBy: 'configQuartiers')]
    #[ORM\JoinColumn(nullable: true)]
    private ?ConfigZoneAdresse $zone = null;

    /**
     * @var Collection<int, Entreprise>
     */
    #[ORM\OneToMany(targetEntity: Entreprise::class, mappedBy: 'adresse')]
    private Collection $entreprises;

    /**
     * @var Collection<int, Site>
     */
    #[ORM\OneToMany(targetEntity: Site::class, mappedBy: 'adresse')]
    private Collection $sites;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'adresse')]
    private Collection $users;

    /**
     * @var Collection<int, Bien>
     */
    #[ORM\OneToMany(targetEntity: Bien::class, mappedBy: 'adresse')]
    private Collection $biens;




    public function __construct()
    {
        $this->entreprises = new ArrayCollection();
        $this->sites = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->biens = new ArrayCollection();
    }

      
    

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

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

    

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(?string $longitude): static
    {
        $this->longitude = $longitude;

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

    public function getDivisionLocale(): ?ConfigDivisionLocale
    {
        return $this->divisionLocale;
    }

    public function setDivisionLocale(?ConfigDivisionLocale $divisionLocale): static
    {
        $this->divisionLocale = $divisionLocale;

        return $this;
    }


    public function getZone(): ?ConfigZoneAdresse
    {
        return $this->zone;
    }

    public function setZone(?ConfigZoneAdresse $zone): static
    {
        $this->zone = $zone;

        return $this;
    }

    /**
     * @return Collection<int, Entreprise>
     */
    public function getEntreprises(): Collection
    {
        return $this->entreprises;
    }

    public function addEntreprise(Entreprise $entreprise): static
    {
        if (!$this->entreprises->contains($entreprise)) {
            $this->entreprises->add($entreprise);
            $entreprise->setAdresse($this);
        }

        return $this;
    }

    public function removeEntreprise(Entreprise $entreprise): static
    {
        if ($this->entreprises->removeElement($entreprise)) {
            // set the owning side to null (unless already changed)
            if ($entreprise->getAdresse() === $this) {
                $entreprise->setAdresse(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Site>
     */
    public function getsites(): Collection
    {
        return $this->sites;
    }

    public function addSite(Site $site): static
    {
        if (!$this->sites->contains($site)) {
            $this->sites->add($site);
            $site->setAdresse($this);
        }

        return $this;
    }

    public function removeSite(Site $site): static
    {
        if ($this->sites->removeElement($site)) {
            // set the owning side to null (unless already changed)
            if ($site->getAdresse() === $this) {
                $site->setAdresse(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setAdresse($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getAdresse() === $this) {
                $user->setAdresse(null);
            }
        }

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
            $bien->setAdresse($this);
        }

        return $this;
    }

    public function removeBien(Bien $bien): static
    {
        if ($this->biens->removeElement($bien)) {
            // set the owning side to null (unless already changed)
            if ($bien->getAdresse() === $this) {
                $bien->setAdresse(null);
            }
        }

        return $this;
    }
    
}
