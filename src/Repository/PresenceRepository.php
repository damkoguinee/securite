<?php

namespace App\Repository;

use App\Entity\Presence;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Presence>
 */
class PresenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Presence::class);
    }

    //    /**
    //     * @return Presence[] Returns an array of Presence objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Presence
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }


     /**
     * @return array Retourne les données paginées avec info pages
     */
    public function acces($statut = [],  $fonction = [], $personnel = null, \DateTime $startDate = null, int $page = 1, int $limit = 50): array
    {
        $qb = $this->createQueryBuilder('p')
                ->leftJoin('p.affectationAgent', 'a')
                ->leftJoin('a.personnel', 'u');
        
        // if ($personnel !== null) {
        //     $qb->andWhere('a.personnel = :personnel')
        //     ->setParameter('personnel', $personnel);
        // }

        if (!empty($statut)) {
            $qb->andWhere('p.statut IN (:statut)')
            ->setParameter('statut', $statut);
        }

        if (!empty($fonction)) {
            $qb->andWhere('p.fonction IN (:fonction)')
            ->setParameter('fonction', $fonction);
        }

        

        if ($startDate !== null) {
            $startDate->setTime(0, 0, 0);
            $endDate = (clone $startDate)->setTime(23, 59, 59);

            $qb->andWhere('p.datePointage BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);
        }

        $qb->orderBy('u.prenom', 'ASC')
        ->addOrderBy('u.nom', 'ASC')
        ->addOrderBy('u.reference', 'ASC')
        ->setFirstResult(($page - 1) * $limit)
        ->setMaxResults($limit);

        $paginator = new Paginator($qb->getQuery());
        $data = iterator_to_array($paginator); // transforme en array

        $nbrePages = ceil($paginator->count() / $limit);

        return [
            'data' => $data,
            'nbrePages' => $nbrePages,
            'pageEncours' => $page,
            'limit' => $limit,
        ];
    }
}
