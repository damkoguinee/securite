<?php

namespace App\Repository;

use App\Entity\ConfigRegionNaturelle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ConfigRegionNaturelle>
 *
 * @method ConfigRegionNaturelle|null find($id, $lockMode = null, $lockVersion = null)
 * @method ConfigRegionNaturelle|null findOneBy(array $criteria, array $orderBy = null)
 * @method ConfigRegionNaturelle[]    findAll()
 * @method ConfigRegionNaturelle[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConfigRegionNaturelleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConfigRegionNaturelle::class);
    }

//    /**
//     * @return ConfigRegionNaturelle[] Returns an array of ConfigRegionNaturelle objects
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

//    public function findOneBySomeField($value): ?ConfigRegionNaturelle
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
