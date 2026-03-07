<?php

namespace App\Repository;

use App\Entity\ConfigSousPrefecture;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ConfigSousPrefecture>
 *
 * @method ConfigSousPrefecture|null find($id, $lockMode = null, $lockVersion = null)
 * @method ConfigSousPrefecture|null findOneBy(array $criteria, array $orderBy = null)
 * @method ConfigSousPrefecture[]    findAll()
 * @method ConfigSousPrefecture[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConfigSousPrefectureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConfigSousPrefecture::class);
    }

//    /**
//     * @return ConfigSousPrefecture[] Returns an array of ConfigSousPrefecture objects
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

//    public function findOneBySomeField($value): ?ConfigSousPrefecture
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
