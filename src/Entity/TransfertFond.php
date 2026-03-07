<?php

namespace App\Entity;

use App\Repository\TransfertFondRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransfertFondRepository::class)]
class TransfertFond extends MouvementCaisse
{
    #[ORM\ManyToOne(inversedBy: 'transfertFonds')]
    private ?Caisse $caisseReception = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $document = null;

    public function getCaisseReception(): ?Caisse
    {
        return $this->caisseReception;
    }

    public function setCaisseReception(?Caisse $caisseReception): static
    {
        $this->caisseReception = $caisseReception;

        return $this;
    }

    public function getDocument(): ?string
    {
        return $this->document;
    }

    public function setDocument(?string $document): static
    {
        $this->document = $document;

        return $this;
    }
}
