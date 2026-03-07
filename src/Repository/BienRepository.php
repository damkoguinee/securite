<?php

namespace App\Repository;

use App\Entity\Bien;
use App\Entity\Site;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Bien>
 */
class BienRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bien::class);
    }

    

    //    public function findOneBySomeField($value): ?Bien
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

      /**
     * Recherche de biens avec des filtres dynamiques.
     *
     * @param mixed $site
     * @param array $typeBien
     * @param string|null $statut
     * @param array $modeTransaction
     * @param mixed $gestionnaire
     * @param mixed $proprietaire
     *
     * @return Bien[]
     */
    public function findBiens($site = null, array $typeBien = [], ?string $statut = null, array $modeTransaction = [], $client = null, $zoneRattachement = null): array
    {
        $qb = $this->createQueryBuilder('b')
            ->addOrderBy('b.nom')
            ->addOrderBy('b.statut');

        if ($site) {
            $qb->andWhere('b.site = :site')
                ->setParameter('site', $site);
        }


        if (!empty($zoneRattachement)) {
            $qb->andWhere('b.zoneRattachement IN (:zoneRattachement)')
                ->setParameter('zoneRattachement', $zoneRattachement);
        }
        if (!empty($typeBien)) {
            $qb->andWhere('b.typeBien IN (:typeBien)')
                ->setParameter('typeBien', $typeBien);
        }

        if ($statut) {
            $qb->andWhere('b.statut = :statut')
                ->setParameter('statut', $statut);
        }

        if (!empty($modeTransaction)) {
            $qb->andWhere('b.modeTransaction IN (:modeTransaction)')
                ->setParameter('modeTransaction', $modeTransaction);
        }


        if ($client) {
            $qb->andWhere('b.client = :client')
                ->setParameter('client', $client);
        }

        return $qb->getQuery()->getResult();
    }

    public function findBienBySearch(
    $id = null,
    $site = null,
    $zones = [],
    $search = null,
    $typeBien = null,
    int $pageEnCours = 1,
    int $limit = 10
    ): array {
        $limit = abs($limit);
        $pageEnCours = max(1, $pageEnCours);

        $qb = $this->createQueryBuilder('b');

        if ($id) {
            $qb->andWhere('b.id = :id')
                ->setParameter('id', $id);
        }

        if ($typeBien) {
            $qb->andWhere('b.typeBien IN (:type)')
                ->setParameter('type', $typeBien);
        }

        if ($site) {
            $qb->andWhere('b.site = :site')
                ->setParameter('site', $site);
        }

        // 🎯 FILTRE PAR ZONE DE RATTACHEMENT (hérité de User)
        if (!empty($zones)) {
            $qb->leftJoin('b.zoneRattachement', 'zr')
            ->andWhere('zr.id IN (:zones)')
            ->setParameter('zones', $zones);
        }

        if ($search) {
            $qb->leftJoin('b.client', 'c')
                ->andWhere('
                c.prenom LIKE :val 
                OR c.nom LIKE :val 
                OR c.telephone LIKE :val
                OR c.societe LIKE :val
                OR c.reference LIKE :val
                OR b.nom LIKE :val
            ')
            ->setParameter('val', '%' . $search . '%');
        }

        $qb->orderBy('b.nom', 'ASC');

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


    public function findBiensSansContrat(Site $site)
{
    return $this->createQueryBuilder('b')
        ->leftJoin('b.contratSurveillances', 'c')
        ->andWhere('b.site = :site')
        ->andWhere('c.id IS NULL')
        ->setParameter('site', $site)
        ->orderBy('b.nom', 'ASC')
        ->getQuery()
        ->getResult();
}



}
