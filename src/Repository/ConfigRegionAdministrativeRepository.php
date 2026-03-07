<?php

namespace App\Repository;

use App\Entity\ConfigRegionAdministrative;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ConfigRegionAdministrative>
 *
 * @method ConfigRegionAdministrative|null find($id, $lockMode = null, $lockVersion = null)
 * @method ConfigRegionAdministrative|null findOneBy(array $criteria, array $orderBy = null)
 * @method ConfigRegionAdministrative[]    findAll()
 * @method ConfigRegionAdministrative[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConfigRegionAdministrativeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConfigRegionAdministrative::class);
    }

//    /**
//     * @return ConfigRegionAdministrative[] Returns an array of ConfigRegionAdministrative objects
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

//    public function findOneBySomeField($value): ?ConfigRegionAdministrative
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
