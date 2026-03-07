<?php

namespace App\Entity;

use App\Repository\ClientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
class Client extends User
{

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $societe = null;


    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $dateCreation = null;

    /**
     * @var Collection<int, Bien>
     */
    #[ORM\OneToMany(targetEntity: Bien::class, mappedBy: 'client')]
    private Collection $biens;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $modeFacturation = null;

    /**
     * @var Collection<int, GroupeFacturation>
     */
    #[ORM\OneToMany(targetEntity: GroupeFacturation::class, mappedBy: 'client')]
    private Collection $groupeFacturations;

   

    public function __construct()
    {
        parent::__construct();
        $this->biens = new ArrayCollection();
        $this->groupeFacturations = new ArrayCollection();
    }

    public function getSociete(): ?string
    {
        return $this->societe;
    }

    public function setSociete(?string $societe): static
    {
        $this->societe = $societe;

        return $this;
    }

    public function getDateCreation(): ?\DateTime
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTime $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

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
            $bien->setClient($this);
        }

        return $this;
    }

    public function removeBien(Bien $bien): static
    {
        if ($this->biens->removeElement($bien)) {
            // set the owning side to null (unless already changed)
            if ($bien->getClient() === $this) {
                $bien->setClient(null);
            }
        }

        return $this;
    }

   public function getNomComplet(): ?string
    {

        $societe = strtoupper($this->societe ?? '');

        return trim($societe . ' ' .$this->getNomCompletUser());
    }

   public function getModeFacturation(): ?string
   {
       return $this->modeFacturation;
   }

   public function setModeFacturation(?string $modeFacturation): static
   {
       $this->modeFacturation = $modeFacturation;

       return $this;
   }

   /**
    * @return Collection<int, GroupeFacturation>
    */
   public function getGroupeFacturations(): Collection
   {
       return $this->groupeFacturations;
   }

   public function addGroupeFacturation(GroupeFacturation $groupeFacturation): static
   {
       if (!$this->groupeFacturations->contains($groupeFacturation)) {
           $this->groupeFacturations->add($groupeFacturation);
           $groupeFacturation->setClient($this);
       }

       return $this;
   }

   public function removeGroupeFacturation(GroupeFacturation $groupeFacturation): static
   {
       if ($this->groupeFacturations->removeElement($groupeFacturation)) {
           // set the owning side to null (unless already changed)
           if ($groupeFacturation->getClient() === $this) {
               $groupeFacturation->setClient(null);
           }
       }

       return $this;
   }

    
    
}
