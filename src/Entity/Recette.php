<?php

namespace App\Entity;

use App\Repository\RecetteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RecetteRepository::class)]
class Recette extends MouvementCaisse
{

    #[ORM\ManyToOne(inversedBy: 'recettes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CategorieRecette $categorie = null;

    #[ORM\Column(nullable: true)]
    private ?float $tva = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $document = null;

    public function getCategorie(): ?CategorieRecette
    {
        return $this->categorie;
    }

    public function setCategorie(?CategorieRecette $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getTva(): ?float
    {
        return $this->tva;
    }

    public function setTva(?float $tva): static
    {
        $this->tva = $tva;

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
