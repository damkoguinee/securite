<?php

namespace App\Repository;

use App\Entity\Penalite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Penalite>
 */
class PenaliteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Penalite::class);
    }

    //    /**
    //     * @return Penalite[] Returns an array of Penalite objects
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

    //    public function findOneBySomeField($value): ?Penalite
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

      /**
     * @return int|null Returns the sum of hours for the specified personnel
     */
    public function findSumOfPenaliteForPersonnel($personnelId, $date): ?int
    {
        // Extraire le mois et l'année de la date fournie
        $year = substr($date, 0, 4);
        $month = substr($date, 5, 2);

        // Créer la date de début et de fin pour le mois donné
        $startDate = new \DateTime("{$year}-{$month}-01");
        $endDate = clone $startDate;
        $endDate->modify('last day of this month');

        return $this->createQueryBuilder('p')
            ->leftJoin('p.affectationAgent', 'a')
            ->select('SUM(p.montant) as totalMontant')
            ->andWhere('a.personnel = :personnelId')
            ->andWhere('p.periode BETWEEN :startDate AND :endDate')
            ->setParameter('personnelId', $personnelId)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate' , $endDate)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
