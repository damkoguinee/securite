<?php

namespace App\Entity;

use App\Repository\DocumentUserRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DocumentUserRepository::class)]
class DocumentUser
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'documentUsers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    // Titre du document : CNI, Contrat, Diplôme, ...
    #[ORM\Column(length: 100)]
    private ?string $titre = null;

    // Nom du fichier sauvegardé sur le serveur
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fichier = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(?string $titre): static
    {
        $this->titre = $titre;
        return $this;
    }

    public function getFichier(): ?string
    {
        return $this->fichier;
    }

    public function setFichier(?string $fichier): static
    {
        $this->fichier = $fichier;
        return $this;
    }
}
