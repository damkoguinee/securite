<?php

namespace App\Repository;

use App\Entity\Versement;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Versement>
 */
class VersementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Versement::class);
    }

//    /**
//     * @return Versement[] Returns an array of Versement objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('v')
//            ->andWhere('v.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('v.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Versement
//    {
//        return $this->createQueryBuilder('v')
//            ->andWhere('v.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }


        /**
     * @param string $value
     * @param array|null $typeUser
     * @return array
     */
    public function findVersementSearch($site = null, $search = null, $startDate = null, $endDate = null, int $pageEnCours = 1, int $limit = 10): array
    {
        $limit = abs($limit);
        $pageEnCours = max(1, $pageEnCours);

        $qb = $this->createQueryBuilder('v');

        if ($startDate !== null && $endDate !== null) {
            $endDate = (new \DateTime($endDate))->modify('+1 day');
            $qb->andWhere('v.dateOperation BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);
        }


        if ($site) {
            $qb->andWhere('v.site = :site')
            ->setParameter('site', $site);
        }
        if ($search) {
            $qb->andWhere('v.collaborateur = :collaborateur')
            ->setParameter('collaborateur', $search);
        }

         $qb->setFirstResult(($pageEnCours - 1) * $limit)
        ->setMaxResults($limit);

        $query = $qb->getQuery();
        $paginator = new Paginator($query);

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
