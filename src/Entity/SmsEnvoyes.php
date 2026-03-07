<?php

namespace App\Entity;

use App\Repository\SmsEnvoyesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SmsEnvoyesRepository::class)]
class SmsEnvoyes
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'smsEnvoyes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ForfaitSms $forfait = null;

    #[ORM\Column(length: 20)]
    private ?string $destinataire = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $message = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\Column]
    private ?\DateTime $dateEnvoie = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getForfait(): ?ForfaitSms
    {
        return $this->forfait;
    }

    public function setForfait(?ForfaitSms $forfait): static
    {
        $this->forfait = $forfait;

        return $this;
    }

    public function getDestinataire(): ?string
    {
        return $this->destinataire;
    }

    public function setDestinataire(string $destinataire): static
    {
        $this->destinataire = $destinataire;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): static
    {
        $this->message = $message;

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

    public function getDateEnvoie(): ?\DateTime
    {
        return $this->dateEnvoie;
    }

    public function setDateEnvoie(\DateTime $dateEnvoie): static
    {
        $this->dateEnvoie = $dateEnvoie;

        return $this;
    }
}
