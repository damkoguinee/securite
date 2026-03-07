<?php

namespace App\Repository;

use App\Entity\ConfigQuartier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ConfigQuartier>
 *
 * @method ConfigQuartier|null find($id, $lockMode = null, $lockVersion = null)
 * @method ConfigQuartier|null findOneBy(array $criteria, array $orderBy = null)
 * @method ConfigQuartier[]    findAll()
 * @method ConfigQuartier[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConfigQuartierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConfigQuartier::class);
    }

//    /**
//     * @return ConfigQuartier[] Returns an array of ConfigQuartier objects
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

//    public function findOneBySomeField($value): ?ConfigQuartier
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    public function rechercheQuartier($value): array
    {
        
        return $this->createQueryBuilder('c')
            ->leftJoin('c.divisionLocale', 'd')
            ->leftJoin('d.region', 'r')
            ->andWhere('c.nom LIKE :val Or d.nom LIKE :val Or r.nom LIKE :val')
            ->setParameter('val', '%' .$value . '%')
            // ->setParameter('val', '%' . $value . '%')
            ->setMaxResults(100)
            ->getQuery()
            ->getResult();
    }
}
