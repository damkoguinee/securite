<?php

namespace App\Repository;

use App\Entity\AbsencePersonnel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AbsencePersonnel>
 */
class AbsencePersonnelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AbsencePersonnel::class);
    }

    //    /**
    //     * @return AbsencePersonnel[] Returns an array of AbsencePersonnel objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?AbsencePersonnel
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * @return int|null Returns the sum of hours for the specified personnel
     */
    public function findSumOfHoursForPersonnel($personnelId, $date): ?int
    {
        // Extraire le mois et l'année de la date fournie
        $year = substr($date, 0, 4);
        $month = substr($date, 5, 2);

        // Créer la date de début et de fin pour le mois donné
        $startDate = new \DateTime("{$year}-{$month}-01");
        $endDate = clone $startDate;
        $endDate->modify('last day of this month');

        return $this->createQueryBuilder('a')
            ->select('SUM(a.heureAbsence) as totalHours')
            ->andWhere('a.personnel = :personnelId')
            ->andWhere('a.dateAbsence BETWEEN :startDate AND :endDate')
            ->setParameter('personnelId', $personnelId)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate' , $endDate)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
