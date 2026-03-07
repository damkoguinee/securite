<?php

namespace App\Repository;

use App\Entity\Recette;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Recette>
 */
class RecetteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Recette::class);
    }

//    /**
//     * @return Recette[] Returns an array of Recette objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('v')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('r.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Recette
//    {
//        return $this->createQueryBuilder('v')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }



    public function findRecetteSearch(
        $site = null,
        $startDate = null,
        $endDate = null,
        $categorie = null,
        int $pageEnCours = 1,
        int $limit = 10
    ): array {
        $limit = abs($limit);
        $pageEnCours = max(1, $pageEnCours);

        $qb = $this->createQueryBuilder('r');

        if ($startDate !== null && $endDate !== null) {
            $endDate = (new \DateTime($endDate))->modify('+1 day');
            $qb->andWhere('r.dateOperation BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);
        }

        if ($site) {
            $qb->andWhere('r.site = :site')
            ->setParameter('site', $site);
        }

        if ($categorie !== null) {
            $qb->andWhere('r.categorieRecette = :cat')
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
    public function totalRecettes(
        $site = null,
        $categorie = null,
        $devise = null,
        $startDate = null,
        $endDate = null,
        bool $alwaysGroupByDevise = false
    ): array {
        $qb = $this->createQueryBuilder('r')
            ->select('SUM(r.montant) as montantTotal', 'dev.nom', 'dev.id as id_devise')
            ->leftJoin('r.devise', 'dev');

        if ($site !== null) {
            $qb->andWhere('r.site = :site')
            ->setParameter('site', $site);
        }

        if ($categorie !== null) {
            $qb->andWhere('r.categorieRecette = :categorie')
            ->setParameter('categorie', $categorie);
        }

        if ($devise !== null) {
            $qb->andWhere('r.devise = :devise')
            ->setParameter('devise', $devise);
        }

        if ($startDate !== null) {
            $start = new \DateTime($startDate);
            $end = (new \DateTime($endDate))->modify('+1 day');
            $qb->andWhere('r.dateOperation BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $start)
            ->setParameter('endDate', $end);
        }

        // Group by devise si nécessaire
        if ($devise === null || $alwaysGroupByDevise) {
            $qb->groupBy('r.devise');
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
