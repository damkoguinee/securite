<?php

namespace App\Repository;

use App\Entity\ConfigDivisionLocale;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ConfigDivisionLocale>
 *
 * @method ConfigDivisionLocale|null find($id, $lockMode = null, $lockVersion = null)
 * @method ConfigDivisionLocale|null findOneBy(array $criteria, array $orderBy = null)
 * @method ConfigDivisionLocale[]    findAll()
 * @method ConfigDivisionLocale[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConfigDivisionLocaleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConfigDivisionLocale::class);
    }

//    /**
//     * @return ConfigDivisionLocale[] Returns an array of ConfigDivisionLocale objects
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

//    public function findOneBySomeField($value): ?ConfigDivisionLocale
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

   /**
    * @return ConfigDivisionLocale[] Returns an array of ConfigDivisionLocale objects
    */
   public function listeDivisionParType($types): array
   {
       return $this->createQueryBuilder('c')
           ->andWhere('c.type IN (:types)')
           ->setParameter('types', $types)
           ->orderBy('c.nom', 'ASC')
           ->getQuery()
           ->getResult()
       ;
   }

   public function rechercheDivisionParTypes($value, $types): array
    {
        
        return $this->createQueryBuilder('d')
            ->leftJoin('d.region', 'r')
            ->andWhere('d.type IN (:types)')
            ->andWhere('d.nom LIKE :val Or r.nom LIKE :val')
            ->setParameter('val', $value . '%')
            ->setParameter('types', $types)
            ->setMaxResults(100)
            ->getQuery()
            ->getResult();
    }

    public function divisionsGroupedByRegion(): array
    {
        return $this->createQueryBuilder('d')
            ->join('d.region', 'r')
            ->addSelect('r')
            ->orderBy('r.nom', 'ASC')
            ->addOrderBy('d.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

}
