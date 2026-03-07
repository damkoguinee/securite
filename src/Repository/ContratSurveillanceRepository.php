<?php

namespace App\Repository;

use App\Entity\ContratSurveillance;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<ContratSurveillance>
 */
class ContratSurveillanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContratSurveillance::class);
    }

    //    /**
    //     * @return ContratSurveillance[] Returns an array of ContratSurveillance objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?ContratSurveillance
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function findContrat(
    $id = null,
    $site = null,
    $client = null,
    $search = null,
    $modeFacturation = null,
    $statut = ['actif'],
    ): array {
        // ✅ Alias principaux
        $qb = $this->createQueryBuilder('cs')
            ->leftJoin('cs.bien', 'b')
            ->leftJoin('b.client', 'cl') // alias "cl" pour éviter le conflit
            ->addSelect('b', 'cl');

        // 🎯 Filtre par ID
        if ($id) {
            $qb->andWhere('cs.id = :id')
            ->setParameter('id', $id);
        }

        // 🎯 Filtre par mode de facturation
        if ($modeFacturation) {
            if (is_array($modeFacturation)) {
                $qb->andWhere('cs.modeFacturation IN (:mode)')
                ->setParameter('mode', $modeFacturation);
            } else {
                $qb->andWhere('cs.modeFacturation = :mode')
                ->setParameter('mode', $modeFacturation);
            }
        }

        // 🎯 Filtre par site
        if ($site) {
            $qb->andWhere('b.site = :site')
            ->setParameter('site', $site);
        }

        if ($client) {
            $qb->andWhere('b.client = :client')
            ->setParameter('client', $client);
        }

        // 🎯 Filtre par statut
        if ($statut) {
            $qb->andWhere('b.statut IN (:statut)')
            ->setParameter('statut', $statut);
        }

        // 🔍 Filtre de recherche libre (optionnel)
        if ($search) {
            $qb->andWhere('
                b.nom LIKE :search 
                OR cl.nom LIKE :search
                OR cl.prenom LIKE :search
                OR cl.telephone LIKE :search
                OR cl.reference LIKE :search
                OR cs.modeFacturation LIKE :search
            ')
            ->setParameter('search', '%' . $search . '%');
        }

        // 📋 Tri
        $qb->orderBy('b.nom', 'ASC');

        // ✅ Retour correct : getQuery() avant getResult()
        return $qb->getQuery()->getResult();
    }


    public function findContratBySearch(
    $id = null,
    $site = null,
    $zones = [],
    $search = null,
    $modeFacturation = null,
    $statut = null,
    int $pageEnCours = 1,
    int $limit = 10
    ): array {
        $limit = abs($limit);
        $pageEnCours = max(1, $pageEnCours);

        // ✅ Alias principaux
        $qb = $this->createQueryBuilder('cs')
            ->leftJoin('cs.bien', 'b')
            ->leftJoin('b.client', 'cl') // alias "cl" pour éviter le conflit
            ->addSelect('b', 'cl');

        // 🎯 Filtre par ID
        if ($id) {
            $qb->andWhere('cs.id = :id')
            ->setParameter('id', $id);
        }

        // 🎯 Filtre par mode de facturation
        if ($modeFacturation) {
            $qb->andWhere('cs.modeFacturation IN (:mode)')
            ->setParameter('mode', $modeFacturation);
        }

         // 🎯 Filtre par statut
        if ($statut) {
            $qb->andWhere('cs.statut IN (:mode)')
            ->setParameter('mode', $statut);
        }

        // 🎯 Filtre par site
        if ($site) {
            $qb->andWhere('b.site = :site')
            ->setParameter('site', $site);
        }

        // 🎯 FILTRE PAR ZONE DE RATTACHEMENT (hérité de User)
        if (!empty($zones)) {
            $qb->leftJoin('b.zoneRattachement', 'zr')
            ->andWhere('zr.id IN (:zones)')
            ->setParameter('zones', $zones);
        }

        // 🎯 Recherche libre
        if ($search) {
            $qb->andWhere('
                cl.prenom LIKE :val 
                OR cl.nom LIKE :val 
                OR cl.telephone LIKE :val
                OR cl.societe LIKE :val
                OR cl.reference LIKE :val
                OR b.nom LIKE :val
            ')
            ->setParameter('val', '%' . $search . '%');
        }

        // 📋 Tri
        $qb->orderBy('b.nom', 'ASC');

        // 📄 Pagination
        $qb->setFirstResult(($pageEnCours - 1) * $limit)
        ->setMaxResults($limit);

        $query = $qb->getQuery();
        $paginator = new Paginator($query, true);
        $total = count($paginator);

        return [
            'data' => iterator_to_array($paginator),
            'nbrePages' => ceil($total / $limit),
            'pageEnCours' => $pageEnCours,
            'limit' => $limit,
            'total' => $total,
        ];
    }

}
