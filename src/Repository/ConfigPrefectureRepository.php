<?php

namespace App\Repository;

use App\Entity\ConfigPrefecture;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ConfigPrefecture>
 *
 * @method ConfigPrefecture|null find($id, $lockMode = null, $lockVersion = null)
 * @method ConfigPrefecture|null findOneBy(array $criteria, array $orderBy = null)
 * @method ConfigPrefecture[]    findAll()
 * @method ConfigPrefecture[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConfigPrefectureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConfigPrefecture::class);
    }

//    /**
//     * @return ConfigPrefecture[] Returns an array of ConfigPrefecture objects
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

//    public function findOneBySomeField($value): ?ConfigPrefecture
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
