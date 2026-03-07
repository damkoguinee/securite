<?php

namespace App\Repository;

use App\Entity\ConfigCommune;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ConfigCommune>
 *
 * @method ConfigCommune|null find($id, $lockMode = null, $lockVersion = null)
 * @method ConfigCommune|null findOneBy(array $criteria, array $orderBy = null)
 * @method ConfigCommune[]    findAll()
 * @method ConfigCommune[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConfigCommuneRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConfigCommune::class);
    }

//    /**
//     * @return ConfigCommune[] Returns an array of ConfigCommune objects
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

//    public function findOneBySomeField($value): ?ConfigCommune
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
