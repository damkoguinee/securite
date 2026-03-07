<?php

namespace App\Repository;

use App\Entity\ConfigZoneAdresse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ConfigZoneAdresse>
 *
 * @method ConfigZoneAdresse|null find($id, $lockMode = null, $lockVersion = null)
 * @method ConfigZoneAdresse|null findOneBy(array $criteria, array $orderBy = null)
 * @method ConfigZoneAdresse[]    findAll()
 * @method ConfigZoneAdresse[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConfigZoneAdresseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConfigZoneAdresse::class);
    }

//    /**
//     * @return ConfigZoneAdresse[] Returns an array of ConfigZoneAdresse objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('z')
//            ->andWhere('z.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('z.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?ConfigZoneAdresse
//    {
//        return $this->createQueryBuilder('z')
//            ->andWhere('z.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
