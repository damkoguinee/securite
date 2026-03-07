<?php

namespace App\Entity;

use App\Repository\ForfaitSmsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ForfaitSmsRepository::class)]
class ForfaitSms
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'forfaitSms')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ConfigForfaitSms $forfait = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 13, scale: 2)]
    private ?string $prix = null;

    #[ORM\Column(length: 10)]
    private ?string $etat = null;

    #[ORM\Column]
    private ?\DateTime $dateSouscription = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $identifiant = null;

    /**
     * @var Collection<int, SmsEnvoyes>
     */
    #[ORM\OneToMany(targetEntity: SmsEnvoyes::class, mappedBy: 'forfait')]
    private Collection $smsEnvoyes;

    public function __construct()
    {
        $this->smsEnvoyes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getForfait(): ?ConfigForfaitSms
    {
        return $this->forfait;
    }

    public function setForfait(?ConfigForfaitSms $forfait): static
    {
        $this->forfait = $forfait;

        return $this;
    }

    public function getPrix(): ?string
    {
        return $this->prix;
    }

    public function setPrix(string $prix): static
    {
        $this->prix = $prix;

        return $this;
    }

    public function getEtat(): ?string
    {
        return $this->etat;
    }

    public function setEtat(string $etat): static
    {
        $this->etat = $etat;

        return $this;
    }

    public function getDateSouscription(): ?\DateTime
    {
        return $this->dateSouscription;
    }

    public function setDateSouscription(\DateTime $dateSouscription): static
    {
        $this->dateSouscription = $dateSouscription;

        return $this;
    }

    public function getIdentifiant(): ?string
    {
        return $this->identifiant;
    }

    public function setIdentifiant(?string $identifiant): static
    {
        $this->identifiant = $identifiant;

        return $this;
    }

    /**
     * @return Collection<int, SmsEnvoyes>
     */
    public function getSmsEnvoyes(): Collection
    {
        return $this->smsEnvoyes;
    }

    public function addSmsEnvoye(SmsEnvoyes $smsEnvoye): static
    {
        if (!$this->smsEnvoyes->contains($smsEnvoye)) {
            $this->smsEnvoyes->add($smsEnvoye);
            $smsEnvoye->setForfait($this);
        }

        return $this;
    }

    public function removeSmsEnvoye(SmsEnvoyes $smsEnvoye): static
    {
        if ($this->smsEnvoyes->removeElement($smsEnvoye)) {
            // set the owning side to null (unless already changed)
            if ($smsEnvoye->getForfait() === $this) {
                $smsEnvoye->setForfait(null);
            }
        }

        return $this;
    }
}
