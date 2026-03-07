<?php

namespace App\Entity;

use App\Repository\AbsencePersonnelRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AbsencePersonnelRepository::class)]
class AbsencePersonnel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'absencePersonnels')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Personel $personnel = null;

    #[ORM\ManyToOne(inversedBy: 'absencePersonnels')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Site $site = null;

    #[ORM\ManyToOne(inversedBy: 'absencePersonnelSaisies')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Personel $saisiePar = null;

    #[ORM\Column]
    private ?float $heureAbsence = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateAbsence = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateSaisie = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPersonnel(): ?Personel
    {
        return $this->personnel;
    }

    public function setPersonnel(?Personel $personnel): static
    {
        $this->personnel = $personnel;

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

    public function getHeureAbsence(): ?float
    {
        return $this->heureAbsence;
    }

    public function setHeureAbsence(float $heureAbsence): static
    {
        $this->heureAbsence = $heureAbsence;

        return $this;
    }

    public function getDateAbsence(): ?\DateTimeInterface
    {
        return $this->dateAbsence;
    }

    public function setDateAbsence(\DateTimeInterface $dateAbsence): static
    {
        $this->dateAbsence = $dateAbsence;

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

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;

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
}
