<?php

namespace App\Repository;

use App\Entity\Caisse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Caisse>
 */
class CaisseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Caisse::class);
    }

    //    /**
    //     * @return Caisse[] Returns an array of Caisse objects
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

    //    public function findOneBySomeField($value): ?Caisse
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * @return Caisse[] Returns an array of Caisse objects
     */
    public function findCaisse($site = null, array $type = []): array
    {
        $qb = $this->createQueryBuilder('c')
            ->orderBy('c.id', 'ASC');

        if ($site !== null) {
            $qb->andWhere(':site MEMBER OF c.site')
            ->setParameter('site', $site);
        }

        if (!empty($type)) {
            $qb->andWhere('c.type IN (:type)')
            ->setParameter('type', $type);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Retourne la caisse par défaut selon la site et une liste de types
     *
     * @param mixed $site
     * @param array $type
     * @return Caisse|null
     */
    public function findDefaultCaisse($site = null, array $type = ['caisse']): ?Caisse
    {
        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.type IN (:type)')
            ->setParameter('type', $type)
            ->setMaxResults(1);

        if ($site !== null) {
            $qb->andWhere(':site MEMBER OF c.site')
            ->setParameter('site', $site);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }


    


}
