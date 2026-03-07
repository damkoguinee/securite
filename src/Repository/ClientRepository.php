<?php

namespace App\Repository;

use App\Entity\Client;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Client>
 */
class ClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Client::class);
    }

    //    /**
    //     * @return Client[] Returns an array of Client objects
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

    //    public function findOneBySomeField($value): ?Client
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }


    public function findClientBySearch(
    $id = null,
    $site = null,
    $zones = [],
    $search = null,
    $statut = null,
    $typeUser = null,
    int $pageEnCours = 1,
    int $limit = 10
    ): array {
        $limit = abs($limit);
        $pageEnCours = max(1, $pageEnCours);

        $qb = $this->createQueryBuilder('c');

        if ($id) {
            $qb->andWhere('c.id = :id')
                ->setParameter('id', $id);
        }

        if ($typeUser) {
            $qb->andWhere('c.typeUser IN (:type)')
                ->setParameter('type', $typeUser);
        }

        if ($statut) {
            $qb->andWhere('c.statut IN (:statut)')
                ->setParameter('statut', $statut);
        }

        if ($site) {
            $qb->andWhere(':site MEMBER OF c.site')
                ->setParameter('site', $site);
        }

        // 🎯 FILTRE PAR ZONE DE RATTACHEMENT (hérité de User)
        if (!empty($zones)) {
            $qb->leftJoin('c.zoneRattachement', 'zr')
            ->andWhere('zr.id IN (:zones)')
            ->setParameter('zones', $zones);
        }

        if ($search) {
            $qb->andWhere('
                c.prenom LIKE :val 
                OR c.nom LIKE :val 
                OR c.telephone LIKE :val
                OR c.societe LIKE :val
                OR c.reference LIKE :val
            ')
            ->setParameter('val', '%' . $search . '%');
        }

        $qb->orderBy('c.prenom', 'ASC');

        // ⚙️ Appliquer la pagination ici
        $qb->setFirstResult(($pageEnCours - 1) * $limit)
        ->setMaxResults($limit);

        $query = $qb->getQuery();

        $paginator = new Paginator($query, true);
        $total = count($paginator);

        return [
            'data' => iterator_to_array($paginator),
            'nbrePages' => ceil($total / $limit),
            'pageEnCours' => $pageEnCours,
            'limit' => $limit,
            'total' => $total,
        ];
    }


    public function findMaxId(): ?int
    {
        $result = $this->createQueryBuilder('c')
            ->select('MAX(c.id)')
            ->getQuery()
            ->getSingleScalarResult();
        return $result;
    }
}
