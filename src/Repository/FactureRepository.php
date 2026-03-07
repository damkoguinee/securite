<?php

namespace App\Repository;

use App\Entity\Client;
use App\Entity\ContratSurveillance;
use App\Entity\Facture;
use App\Entity\Site;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Facture>
 */
class FactureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Facture::class);
    }

    //    /**
    //     * @return Facture[] Returns an array of Facture objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('f.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Facture
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }


    /**
     * @return Facture[]
     */
    public function findFacture(
        ?Site $site = null,
        ?int $id = null,
        ?ContratSurveillance $contrat = null,
        ?Client $client = null,
        array $statut = [],
        ?string $search = null,
        \DateTimeInterface|string|null $startDate = null,
        \DateTimeInterface|string|null $endDate = null,
        array $zones = []
    ): array {

        $qb = $this->createQueryBuilder('f')
            ->leftJoin('f.contrat', 'c')->addSelect('c')
            ->leftJoin('c.bien', 'b')->addSelect('b')
            ->leftJoin('b.client', 'cl')->addSelect('cl');

        // 🎯 Gestion des dates
        if ($startDate !== null && $endDate !== null) {

            if (!$startDate instanceof \DateTimeInterface) {
                $startDate = new \DateTime($startDate);
            }

            if (!$endDate instanceof \DateTimeInterface) {
                $endDate = new \DateTime($endDate);
            }

            $endDate = (clone $endDate)->modify('last day of this month');

            $qb->andWhere('f.periodeDebut >= :startDate')
            ->andWhere('f.periodeFin <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);
        }

        // 🎯 Filtre ID
        if ($id !== null) {
            $qb->andWhere('f.id = :id')
            ->setParameter('id', $id);
        }

        // 🎯 Filtre contrat
        if ($contrat !== null) {
            $qb->andWhere('f.contrat = :contrat')
            ->setParameter('contrat', $contrat);
        }

        // 🎯 Filtre client
        if ($client !== null) {
            $qb->andWhere('b.client = :client')
            ->setParameter('client', $client);
        }

        // 🎯 Filtre site
        if ($site !== null) {
            $qb->andWhere('f.site = :site')
            ->setParameter('site', $site);
        }

        // 🎯 Filtre zones
        if (!empty($zones)) {
            $qb->andWhere('b.zoneRattachement IN (:zones)')
            ->setParameter('zones', $zones);
        }

        // 🎯 Filtre statut
        if (!empty($statut)) {
            $qb->andWhere('f.statut IN (:statut)')
            ->setParameter('statut', $statut);
        }

        // 🎯 Recherche texte
        if ($search !== null && $search !== '') {

            $qb->andWhere('
                (
                    cl.prenom LIKE :search
                    OR cl.nom LIKE :search
                    OR cl.telephone LIKE :search
                    OR cl.reference LIKE :search
                    OR b.description LIKE :search
                )
            ')
            ->setParameter('search', '%' . $search . '%');
        }

        $qb->orderBy('cl.prenom', 'ASC');

        return $qb->getQuery()->getResult();
    }


    public function findFactureSearch(
        ?Site $site = null,
        ?ContratSurveillance $contrat = null,
        ?string $search = null,
        \DateTimeInterface|string|null $startDate = null,
        \DateTimeInterface|string|null $endDate = null,
        int $pageEnCours = 1,
        int $limit = 10,
        array $zones = []
    ): array {
        $limit = abs($limit);
        $pageEnCours = max(1, $pageEnCours);

        $qb = $this->createQueryBuilder('f')
            ->leftJoin('f.contrat', 'c')
            ->addSelect('c')
            ->leftJoin('c.bien', 'b')
            ->addSelect('b')
            ->leftJoin('b.client', 'cl')
            ->addSelect('cl');
        if ($startDate !== null && $endDate !== null) {
            if (!$startDate instanceof \DateTimeInterface) {
                $startDate = new \DateTime($startDate);
            }

            if (!$endDate instanceof \DateTimeInterface) {
                $endDate = new \DateTime($endDate);
            }

            $endDate = (clone $endDate)->modify('last day of this month');
        

            $qb->andWhere('f.periodeDebut >= :startDate')
                ->andWhere('f.periodeFin <= :endDate')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate);
        }
        

        if ($site) {
            $qb->andWhere('f.site = :site')
                ->setParameter('site', $site);
        }
        // 🎯 Filtre contrat
        if ($contrat !== null) {
            $qb->andWhere('f.contrat = :contrat')
            ->setParameter('contrat', $contrat);
        }

        if (!empty($zones)) {
            $qb->andWhere('b.zoneRattachement IN (:zones)')
                ->setParameter('zones', $zones);
        }

        if ($search) {
            $qb->andWhere(
                'f.reference LIKE :val
                OR cl.prenom LIKE :val
                OR cl.nom LIKE :val
                OR cl.telephone LIKE :val
                OR cl.reference LIKE :val
                OR b.description LIKE :val'
            )
            ->setParameter('val', '%' . $search . '%');
        }

        $qb->setFirstResult(($pageEnCours - 1) * $limit)
            ->setMaxResults($limit);

        $query = $qb->getQuery();
        $paginator = new Paginator($query);

        return [
            'data' => iterator_to_array($paginator),
            'nbrePages' => (int) ceil(count($paginator) / $limit),
            'pageEnCours' => $pageEnCours,
            'limit' => $limit,
            'total' => count($paginator),
        ];
    }

    public function findFactureForContratAndPeriod(
        ?ContratSurveillance $contrat,
        \DateTime $periodeDebut, 
        \DateTime $periodeFin
    ): ?Facture
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.contrat = :contrat')
            ->andWhere('f.periodeDebut = :deb')
            ->andWhere('f.periodeFin = :fin')
            ->setParameter('contrat', $contrat)
            ->setParameter('deb', $periodeDebut)
            ->setParameter('fin', $periodeFin)
            ->getQuery()
            ->getOneOrNullResult();
    }


    public function findMaxId(): ?int
    {
        $result = $this->createQueryBuilder('u')
            ->select('MAX(u.id)')
            ->getQuery()
            ->getSingleScalarResult();
        return $result;
    }

    public function generateReference(\DateTimeInterface $periodeDebut): string
    {
        do {
            // ID max actuel (pas fiable à 100%, mais utilisé dans la référence)
            $maxId = $this->findMaxId();
            $nextId = ($maxId ?? 0) + 1;

            // Mois / Année
            $month = $periodeDebut->format('m');
            $year = $periodeDebut->format('Y');

            // Code unique sécurisé
            $shortUniq = strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));

            // Générer une référence candidate
            $reference = sprintf("FAC-%s-%s-%s-%s", $month, $year, $nextId, $shortUniq);

            // Vérifier si elle existe déjà
            $exists = $this->createQueryBuilder('f')
                ->select('COUNT(f.id)')
                ->andWhere('f.reference = :ref')
                ->setParameter('ref', $reference)
                ->getQuery()
                ->getSingleScalarResult();

        } while ($exists > 0); // 🔁 tant qu'il existe un doublon, régénérer

        return $reference;
    }


    public function findFactureGroup(
        ?Site $site = null, 
        \DateTimeInterface|string|null $startDate = null,
        \DateTimeInterface|string|null $endDate = null,
    ): array
    {
        $qb = $this->createQueryBuilder('f')
            ->select(
                'd.nom AS nomDevise',
                'SUM(f.montantHT) AS totalHT',
                'SUM(f.remiseMontant) AS totalRemise',
                'SUM(f.montantTVA) AS totalTVA',
                'SUM(f.montantTotal) AS totalTTC',
                'SUM(f.montantTotal - f.montantPaye) AS creance',
                'COUNT(f.id) AS nombreFactures'
            )
            ->join('f.devise', 'd')
            ->groupBy('d.nom')
        ;

        /* ----- FILTRE PAR DATE ----- */
        if ($startDate !== null && $endDate !== null) {
            if (!$startDate instanceof \DateTimeInterface) {
                $startDate = new \DateTime($startDate);
            }

            if (!$endDate instanceof \DateTimeInterface) {
                $endDate = new \DateTime($endDate);
            }

            // Fin du mois
            $endDate = (clone $endDate)->modify('last day of this month');

            $qb->andWhere('f.periodeDebut >= :start')
            ->andWhere('f.periodeFin <= :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate);
        }

        /* ----- FILTRE SITE ----- */
        if ($site !== null) {
            $qb->andWhere('f.site = :site')
            ->setParameter('site', $site);
        }

        return $qb->getQuery()->getArrayResult();
    }


}
