<?php

namespace App\Repository;

use App\Entity\Depense;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Depense>
 */
class DepenseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Depense::class);
    }

//    /**
//     * @return Depense[] Returns an array of Depense objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('v')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('d.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Depense
//    {
//        return $this->createQueryBuilder('v')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }



    public function findDepenseSearch(
        $site = null,
        $startDate = null,
        $endDate = null,
        $categorie = null,
        int $pageEnCours = 1,
        int $limit = 10
    ): array {
        $limit = abs($limit);
        $pageEnCours = max(1, $pageEnCours);

        $qb = $this->createQueryBuilder('d');

        if ($startDate !== null && $endDate !== null) {
            $endDate = (new \DateTime($endDate))->modify('+1 day');
            $qb->andWhere('d.dateOperation BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);
        }

        if ($site) {
            $qb->andWhere('d.site = :site')
            ->setParameter('site', $site);
        }

        if ($categorie !== null) {
            $qb->andWhere('d.categorieDepense = :cat')
            ->setParameter('cat', $categorie);
        }

        // Appliquer la pagination
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


     /**
    * @return array
    */
    public function totalDepenses(
        $site = null,
        $categorie = null,
        $devise = null,
        $startDate = null,
        $endDate = null,
        bool $alwaysGroupByDevise = false
    ): array {
        $qb = $this->createQueryBuilder('d')
            ->select('SUM(d.montant) as montantTotal', 'dev.nom', 'dev.id as id_devise')
            ->leftJoin('d.devise', 'dev');

        if ($site !== null) {
            $qb->andWhere('d.site = :site')
            ->setParameter('site', $site);
        }

        if ($categorie !== null) {
            $qb->andWhere('d.categorieDepense = :categorie')
            ->setParameter('categorie', $categorie);
        }

        if ($devise !== null) {
            $qb->andWhere('d.devise = :devise')
            ->setParameter('devise', $devise);
        }

        if ($startDate !== null) {
            $start = new \DateTime($startDate);
            $end = (new \DateTime($endDate))->modify('+1 day');
            $qb->andWhere('d.dateOperation BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $start)
            ->setParameter('endDate', $end);
        }

        // Group by devise si nécessaire
        if ($devise === null || $alwaysGroupByDevise) {
            $qb->groupBy('d.devise');
        }

        return $qb->getQuery()->getResult();
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
