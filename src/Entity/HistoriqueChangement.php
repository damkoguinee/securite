<?php

namespace App\Entity;

use App\Repository\HistoriqueChangementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HistoriqueChangementRepository::class)]
class HistoriqueChangement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'historiqueChangements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $saisiePar = null;

    #[ORM\Column]
    private ?\DateTime $dateSaisie = null;

    #[ORM\ManyToOne(inversedBy: 'historiqueChangements')]
    private ?User $user = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $motif = null;

    #[ORM\Column(length: 100)]
    private ?string $type = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $information = null;

    #[ORM\ManyToOne(inversedBy: 'historiqueChangements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Site $site = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSaisiePar(): ?User
    {
        return $this->saisiePar;
    }

    public function setSaisiePar(?User $saisiePar): static
    {
        $this->saisiePar = $saisiePar;

        return $this;
    }

    public function getDateSaisie(): ?\DateTime
    {
        return $this->dateSaisie;
    }

    public function setDateSaisie(\DateTime $dateSaisie): static
    {
        $this->dateSaisie = $dateSaisie;

        return $this;
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

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(string $motif): static
    {
        $this->motif = $motif;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getInformation(): ?string
    {
        return $this->information;
    }

    public function setInformation(?string $information): static
    {
        $this->information = $information;

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
}
