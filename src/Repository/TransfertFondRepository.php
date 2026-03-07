<?php

namespace App\Repository;

use App\Entity\TransfertFond;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<TransfertFond>
 */
class TransfertFondRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TransfertFond::class);
    }

    //    /**
    //     * @return TransfertFond[] Returns an array of TransfertFond objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('t.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?TransfertFond
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }


    public function findTransfertSearch(
        $site = null,
        $caisseReception = null,
        $caisseDepart = null,
        $startDate = null,
        $endDate = null,
        int $pageEnCours = 1,
        int $limit = 10
    ): array {
        $limit = abs($limit);
        $pageEnCours = max(1, $pageEnCours);

        $qb = $this->createQueryBuilder('t');

        if ($startDate !== null && $endDate !== null) {
            $endDate = (new \DateTime($endDate))->modify('+1 day');
            $qb->andWhere('t.dateOperation BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);
        }

        if ($site) {
            $qb->andWhere('t.site = :site')
            ->setParameter('site', $site);
        }

        if ($caisseReception) {
            $qb->andWhere('t.caisseReception = :caisseReception')
            ->setParameter('caisseReception', $caisseReception);
        }

        if ($caisseDepart) {
            $qb->andWhere('t.caisse = :caisseDepart')
            ->setParameter('caisseDepart', $caisseDepart);
        }

        // Ajout de la pagination ici
        $qb->setFirstResult(($pageEnCours - 1) * $limit)
        ->setMaxResults($limit);

        $paginator = new Paginator($qb->getQuery());

        return [
            'data' => iterator_to_array($paginator),
            'nbrePages' => ceil(count($paginator) / $limit),
            'pageEnCours' => $pageEnCours,
            'limit' => $limit,
            'total' => count($paginator),
        ];
    }


    

    public function findMaxId(): ?int
    {
        $result = $this->createQueryBuilder('u')
            ->select('MAX(u.id)')
            ->getQuery()
            ->getSingleScalarResult();
        return $result;
    }
}
