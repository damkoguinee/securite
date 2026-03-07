<?php

namespace App\Service\Comptable\Contrat;

use App\Entity\Site;
use App\Repository\ClientRepository;
use App\Repository\ContratSurveillanceRepository;

class ContratService
{
    private $contratRepo;
    private $clientRepo;

    public function __construct(ContratSurveillanceRepository $contratRepo, ClientRepository $clientRepo)
    {
        $this->contratRepo = $contratRepo;
        $this->clientRepo = $clientRepo;
    }

    public function getContrat(
        ?int $id = null,
        ?Site $site = null,
        ?int $clientId = null,
        ?string $search = null,
        ?string $modeFacturation = null,
        array $statut = ['actif']
    )
    {
        if ($clientId) {
            $client = $this->clientRepo->find($clientId);
        }

        return $this->contratRepo->findContrat(
            id: $id,
            site: $site,
            client: $clientId ? $client : null,
            search: $search,
            modeFacturation: $modeFacturation,
            statut: $statut
        );
    }
}