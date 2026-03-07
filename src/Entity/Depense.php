<?php

namespace App\Entity;

use App\Repository\DepenseRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DepenseRepository::class)]
class Depense extends MouvementCaisse
{

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $document = null;

    #[ORM\ManyToOne(inversedBy: 'depenses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CategorieDepense $categorieDepense = null;


    
    public function getDocument(): ?string
    {
        return $this->document;
    }

    public function setDocument(?string $document): static
    {
        $this->document = $document;

        return $this;
    }

    public function getCategorieDepense(): ?CategorieDepense
    {
        return $this->categorieDepense;
    }

    public function setCategorieDepense(?CategorieDepense $categorieDepense): static
    {
        $this->categorieDepense = $categorieDepense;

        return $this;
    }
}
