<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;


#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_USERNAME', fields: ['username'])]
#[ORM\UniqueConstraint(
    name: 'UNIQ_IDENTIFIER_REFERENCE',
    fields: ['reference']
)]
#[UniqueEntity(fields: ['username'], message: 'There is already an account with this username')]
#[UniqueEntity(fields: ['reference'], message: 'There is already an account with this reference')]
#[ORM\InheritanceType("JOINED")]
#[ORM\DiscriminatorColumn(name:"type", type:"string")]
#[ORM\DiscriminatorMap([
    "personnel" => Personel::class,
    "client" => Client::class,
    "developpeur" => Developpeur::class,
])]
abstract class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $username = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    private ?string $prenom = null;

    #[ORM\Column(length: 20)]
    private ?string $telephone = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 50)]
    private ?string $reference = null;

    #[ORM\Column(length: 20)]
    private ?string $typeUser = null;


    #[ORM\Column(length: 255, nullable: true)]
    private ?string $complementAdresse = null;

    #[ORM\ManyToOne(inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ConfigQuartier $adresse = null;

    #[ORM\Column(length: 20)]
    private ?string $statut = null;
    #[ORM\Column(length: 15, nullable: true)]
    private ?string $sexe = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $dateNaissance = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    /**
     * @var Collection<int, Site>
     */
    #[ORM\ManyToMany(targetEntity: Site::class)]
    private Collection $site;

    /**
     * @var Collection<int, Decaissement>
     */
    #[ORM\OneToMany(mappedBy: 'collaborateur', targetEntity: Decaissement::class)]
    private Collection $decaissements;

    /**
     * @var Collection<int, Versement>
     */
    #[ORM\OneToMany(mappedBy: 'collaborateur', targetEntity: Versement::class)]
    private Collection $versements;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $nomMere = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $nomPere = null;

    #[ORM\Column(length: 13, nullable: true)]
    private ?string $telephoneParent = null;

    /**
     * @var Collection<int, DocumentUser>
     */
    #[ORM\OneToMany(targetEntity: DocumentUser::class, mappedBy: 'user', orphanRemoval:true, cascade:['persist', 'remove'])]
    private Collection $documentUsers;

    /**
     * @var Collection<int, MouvementCollaborateur>
     */
    #[ORM\OneToMany(mappedBy: 'collaborateur', targetEntity: MouvementCollaborateur::class)]
    private Collection $mouvementCollaborateurs;

    /**
     * @var Collection<int, ConfigZoneRattachement>
     */
    #[ORM\ManyToMany(targetEntity: ConfigZoneRattachement::class, inversedBy: 'users')]
    private Collection $zoneRattachement;



    public function __construct()
    {
        $this->site = new ArrayCollection();
        $this->decaissements = new ArrayCollection();
        $this->versements = new ArrayCollection();
        $this->documentUsers = new ArrayCollection();
        $this->mouvementCollaborateurs = new ArrayCollection();
        $this->zoneRattachement = new ArrayCollection();

    }



    public function getId(): ?int
    {
        return $this->id;
    }

    /**
 * @return Collection<int, MouvementCollaborateur>
 */
public function getMouvementCollaborateurs(): Collection
{
    return $this->mouvementCollaborateurs;
}

public function addMouvementCollaborateur(MouvementCollaborateur $mouvement): static
{
    if (!$this->mouvementCollaborateurs->contains($mouvement)) {
        $this->mouvementCollaborateurs->add($mouvement);
        $mouvement->setCollaborateur($this);
    }

    return $this;
}

public function removeMouvementCollaborateur(MouvementCollaborateur $mouvement): static
{
    if ($this->mouvementCollaborateurs->removeElement($mouvement)) {
        if ($mouvement->getCollaborateur() === $this) {
            $mouvement->setCollaborateur(null);
        }
    }

    return $this;
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

    /**
     * @return Collection<int, Versement>
     */
    public function getVersements(): Collection
    {
        return $this->versements;
    }

    public function addVersement(Versement $versement): static
    {
        if (!$this->versements->contains($versement)) {
            $this->versements->add($versement);
            $versement->setCollaborateur($this);
        }

        return $this;
    }

    public function removeVersement(Versement $versement): static
    {
        if ($this->versements->removeElement($versement)) {
            if ($versement->getCollaborateur() === $this) {
                $versement->setCollaborateur(null);
            }
        }

        return $this;
    }


    

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
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

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
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

    public function getTypeUser(): ?string
    {
        return $this->typeUser;
    }

    public function setTypeUser(string $typeUser): static
    {
        $this->typeUser = $typeUser;

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

    public function getAdresse(): ?ConfigQuartier
    {
        return $this->adresse;
    }

    public function setAdresse(?ConfigQuartier $adresse): static
    {
        $this->adresse = $adresse;

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

    

    

    

    public function getSexe(): ?string
    {
        return $this->sexe;
    }

    public function setSexe(?string $sexe): static
    {
        $this->sexe = $sexe;

        return $this;
    }

    public function getDateNaissance(): ?\DateTime
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(?\DateTime $dateNaissance): static
    {
        $this->dateNaissance = $dateNaissance;

        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;

        return $this;
    }


    public function getNomCompletUser(): ?string
    {
        $nom = strtoupper($this->nom ?? '');
        $prenom = ucfirst(strtolower($this->prenom ?? ''));
        $reference = strtoupper(strtolower($this->reference ?? ''));

        return trim($prenom . ' ' . $nom.' '.$reference);
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
            $decaissement->setCollaborateur($this);
        }

        return $this;
    }

    public function removeDecaissement(Decaissement $decaissement): static
    {
        if ($this->decaissements->removeElement($decaissement)) {
            if ($decaissement->getCollaborateur() === $this) {
                $decaissement->setCollaborateur(null);
            }
        }

        return $this;
    }

    public function getNomMere(): ?string
    {
        return $this->nomMere;
    }

    public function setNomMere(?string $nomMere): static
    {
        $this->nomMere = $nomMere;

        return $this;
    }

    public function getNomPere(): ?string
    {
        return $this->nomPere;
    }

    public function setNomPere(?string $nomPere): static
    {
        $this->nomPere = $nomPere;

        return $this;
    }

    public function getTelephoneParent(): ?string
    {
        return $this->telephoneParent;
    }

    public function setTelephoneParent(?string $telephoneParent): static
    {
        $this->telephoneParent = $telephoneParent;

        return $this;
    }

    /**
     * @return Collection<int, DocumentUser>
     */
    public function getDocumentUsers(): Collection
    {
        return $this->documentUsers;
    }

    public function addDocumentUser(DocumentUser $documentUser): static
    {
        if (!$this->documentUsers->contains($documentUser)) {
            $this->documentUsers->add($documentUser);
            $documentUser->setUser($this);
        }

        return $this;
    }

    public function removeDocumentUser(DocumentUser $documentUser): static
    {
        if ($this->documentUsers->removeElement($documentUser)) {
            // set the owning side to null (unless already changed)
            if ($documentUser->getUser() === $this) {
                $documentUser->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ConfigZoneRattachement>
     */
    public function getZoneRattachement(): Collection
    {
        return $this->zoneRattachement;
    }

    public function addZoneRattachement(ConfigZoneRattachement $zoneRattachement): static
    {
        if (!$this->zoneRattachement->contains($zoneRattachement)) {
            $this->zoneRattachement->add($zoneRattachement);
        }

        return $this;
    }

    public function removeZoneRattachement(ConfigZoneRattachement $zoneRattachement): static
    {
        $this->zoneRattachement->removeElement($zoneRattachement);

        return $this;
    }


    

}
