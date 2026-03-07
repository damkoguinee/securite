<?php

namespace App\Repository;

use App\Entity\AvanceSalaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AvanceSalaire>
 */
class AvanceSalaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AvanceSalaire::class);
    }

    //    /**
    //     * @return AvanceSalaire[] Returns an array of AvanceSalaire objects
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

    //    public function findOneBySomeField($value): ?AvanceSalaire
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

      /**
     * @return int|null Returns the sum of avances for the specified personnel
     */
    public function findSumOfAvanceForPersonnel($personnelId, $date, $contrat = null): ?int
    {
        $year  = substr($date, 0, 4);
        $month = substr($date, 5, 2);

        $startDate = new \DateTime("{$year}-{$month}-01");
        $endDate   = (clone $startDate)->modify('last day of this month');

        $qb = $this->createQueryBuilder('a')
            ->select('COALESCE(ABS(SUM(a.montant)),0) as totalMontant')
            ->andWhere('a.personnel = :personnelId')
            ->andWhere('a.periode BETWEEN :startDate AND :endDate')
            ->setParameter('personnelId', $personnelId)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);

        // 🎯 Filtre contrat
        if ($contrat) {
            $qb->andWhere('a.contrat = :contrat')
            ->setParameter('contrat', $contrat);
        } else {
            $qb->andWhere('a.contrat IS NULL');
        }

        return $qb->getQuery()->getSingleScalarResult();
    }
}
