<?php

namespace App\Entity;

use App\Repository\MouvementCaisseRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MouvementCaisseRepository::class)]
#[ORM\InheritanceType("JOINED")]
#[ORM\DiscriminatorColumn(name:"type", type:"string")]
#[ORM\DiscriminatorMap([
    "paiement" => Paiement::class,
    "depense" => Depense::class,
    "salaire" => PaiementSalairePersonnel::class,
    "transfert" => TransfertFond::class,
    "versement" => Versement::class,
    "decaissement" => Decaissement::class,
    "avance" => AvanceSalaire::class,
    "recette" => Recette::class
])]
abstract  class MouvementCaisse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


    #[ORM\ManyToOne(inversedBy: 'mouvementCaisses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Caisse $caisse = null;

    #[ORM\ManyToOne(inversedBy: 'mouvementCaisses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ConfigDevise $devise = null;

    #[ORM\ManyToOne(inversedBy: 'mouvementCaisses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ConfigModePaiement $modePaie = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateSaisie = null;

    #[ORM\Column(length: 100)]
    private ?string $typeMouvement = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateOperation = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 13, scale: 2)]
    private ?string $montant = null;

    #[ORM\Column]
    private ?float $taux = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $bordereau = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reference = null;

    #[ORM\ManyToOne(inversedBy: 'mouvementCaisses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Site $site = null;

    #[ORM\ManyToOne(inversedBy: 'mouvementCaisses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Personel $saisiePar = null;

    public function __construct()
    {
        $this->taux = 1.0;
        $this->dateSaisie = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    
    public function getCaisse(): ?Caisse
    {
        return $this->caisse;
    }

    public function setCaisse(?Caisse $caisse): static
    {
        $this->caisse = $caisse;

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

    

    public function getModePaie(): ?ConfigModePaiement
    {
        return $this->modePaie;
    }

    public function setModePaie(?ConfigModePaiement $modePaie): static
    {
        $this->modePaie = $modePaie;

        return $this;
    }

    

    public function getDateSaisie(): ?\DateTimeInterface
    {
        return $this->dateSaisie;
    }

    public function setDateSaisie(\DateTimeInterface $dateSaisie): static
    {
        $this->dateSaisie = $dateSaisie;

        return $this;
    }

    public function getTypeMouvement(): ?string
    {
        return $this->typeMouvement;
    }

    public function setTypeMouvement(string $typeMouvement): static
    {
        $this->typeMouvement = $typeMouvement;

        return $this;
    }

    public function getDateOperation(): ?\DateTimeInterface
    {
        return $this->dateOperation;
    }

    public function setDateOperation(\DateTimeInterface $dateOperation): static
    {
        $this->dateOperation = $dateOperation;

        return $this;
    }

    public function getMontant(): ?string
    {
        return $this->montant;
    }

    public function setMontant(string $montant): static
    {
        $this->montant = $montant;

        return $this;
    }

    public function getTaux(): ?float
    {
        return $this->taux;
    }

    public function setTaux(float $taux): static
    {
        $this->taux = $taux;

        return $this;
    }

    public function getBordereau(): ?string
    {
        return $this->bordereau;
    }

    public function setBordereau(?string $bordereau): static
    {
        $this->bordereau = $bordereau;

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

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;

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

    public function getSaisiePar(): ?Personel
    {
        return $this->saisiePar;
    }

    public function setSaisiePar(?Personel $saisiePar): static
    {
        $this->saisiePar = $saisiePar;

        return $this;
    }

    
}
