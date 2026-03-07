<?php

namespace App\Entity;

use App\Repository\ConfigTypeSurveillanceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConfigTypeSurveillanceRepository::class)]
class ConfigTypeSurveillance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 13, scale: 2, nullable: true)]
    private ?string $tarifHoraire = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 13, scale: 2, nullable: true)]
    private ?string $tarifMensuel = null;

    /**
     * @var Collection<int, ContratTypeSurveillance>
     */
    #[ORM\OneToMany(
        targetEntity: ContratTypeSurveillance::class,
        mappedBy: 'typeSurveillance',
        cascade: ['persist', 'remove']
    )]
    private Collection $contratTypeSurveillances;

    /**
     * @var Collection<int, ComplementTypeSurveillance>
     */
    #[ORM\OneToMany(targetEntity: ComplementTypeSurveillance::class, mappedBy: 'typeSurveillance')]
    private Collection $complementTypeSurveillances;

    public function __construct()
    {
        $this->contratTypeSurveillances = new ArrayCollection();
        $this->complementTypeSurveillances = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getTarifHoraire(): ?string
    {
        return $this->tarifHoraire;
    }

    public function setTarifHoraire(?string $tarifHoraire): static
    {
        $this->tarifHoraire = $tarifHoraire;
        return $this;
    }

    public function getTarifMensuel(): ?string
    {
        return $this->tarifMensuel;
    }

    public function setTarifMensuel(?string $tarifMensuel): static
    {
        $this->tarifMensuel = $tarifMensuel;
        return $this;
    }

    /**
     * @return Collection<int, ContratTypeSurveillance>
     */
    public function getContratTypeSurveillances(): Collection
    {
        return $this->contratTypeSurveillances;
    }

    public function addContratTypeSurveillance(ContratTypeSurveillance $cts): static
    {
        if (!$this->contratTypeSurveillances->contains($cts)) {
            $this->contratTypeSurveillances->add($cts);
            $cts->setTypeSurveillance($this);
        }

        return $this;
    }

    public function removeContratTypeSurveillance(ContratTypeSurveillance $cts): static
    {
        if ($this->contratTypeSurveillances->removeElement($cts)) {
            if ($cts->getTypeSurveillance() === $this) {
                $cts->setTypeSurveillance(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ComplementTypeSurveillance>
     */
    public function getComplementTypeSurveillances(): Collection
    {
        return $this->complementTypeSurveillances;
    }

    public function addComplementTypeSurveillance(ComplementTypeSurveillance $complementTypeSurveillance): static
    {
        if (!$this->complementTypeSurveillances->contains($complementTypeSurveillance)) {
            $this->complementTypeSurveillances->add($complementTypeSurveillance);
            $complementTypeSurveillance->setTypeSurveillance($this);
        }

        return $this;
    }

    public function removeComplementTypeSurveillance(ComplementTypeSurveillance $complementTypeSurveillance): static
    {
        if ($this->complementTypeSurveillances->removeElement($complementTypeSurveillance)) {
            // set the owning side to null (unless already changed)
            if ($complementTypeSurveillance->getTypeSurveillance() === $this) {
                $complementTypeSurveillance->setTypeSurveillance(null);
            }
        }

        return $this;
    }
}
