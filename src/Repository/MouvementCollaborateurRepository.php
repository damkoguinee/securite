<?php

namespace App\Repository;

use App\Entity\MouvementCollaborateur;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<MouvementCollaborateur>
 */
class MouvementCollaborateurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MouvementCollaborateur::class);
    }

    //    /**
    //     * @return MouvementCollaborateur[] Returns an array of MouvementCollaborateur objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('m.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?MouvementCollaborateur
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }


    public function findSoldeCollaborateur($collaborateur, $devise = null): array
    {
        $query = $this->createQueryBuilder('m')
            ->select('sum(m.montant) as montant', 'c.nom as devise')
            ->leftJoin('m.devise', 'c')
            ->andWhere('m.collaborateur = :colab')
            ->setParameter('colab', $collaborateur);

        if ($devise) {
            $query->andWhere('m.devise = :devise')
                ->setParameter('devise', $devise);
        }

        return $query->groupBy('m.devise')
            ->getQuery()
            ->getResult();
    }

    public function findSoldeCompteCollaborateur($collaborateur, $devises, $site = null): array
    {
        // Construire le QueryBuilder
        $query = $this->createQueryBuilder('m')
            ->select(
                'SUM(m.montant) AS montant',
                'd.nom AS devise',
                'd.id AS id_devise',
                'MAX(m.dateOperation) AS derniereOperation'
            )
            ->leftJoin('m.devise', 'd')
            ->andWhere('m.collaborateur = :colab')
            ->setParameter('colab', $collaborateur);

        // Ajouter le filtre sur le site si nécessaire
        if (!empty($site)) {
            $query->andWhere('m.site = :site')
                ->setParameter('site', $site);
            
        }

        // Ajouter le groupement et exécuter la requête
        $results = $query
            ->groupBy('d.nom')
            ->getQuery()
            ->getResult();

        // Créer un tableau pour stocker les résultats finaux
        $finalResults = [];
        foreach ($devises as $devise) {
            $trouve = false;
            foreach ($results as $resultat) {
                if ($resultat['devise'] === $devise->getNom()) {
                    $finalResults[] = $resultat;
                    $trouve = true;
                    break;
                }
            }
            if (!$trouve) {
                // Ajouter une devise non trouvée avec un montant de 0.00
                $finalResults[] = [
                    'montant' => '0.00',
                    'devise' => $devise->getNom(),
                    'id_devise' => $devise->getId(),
                ];
            }
        }

        return $finalResults;
    }

    /**
     * @return array
     */
    public function findSoldeDetailByCollaborateur(
        $collaborateur = null, 
        $devise = null, 
        $startDate = null, 
        $endDate = null, 
        $site = null, 
        int $pageEnCours = 1, 
        int $limit = 50
    ): array {
        $limit = abs($limit);
        $result = [];

        $query = $this->createQueryBuilder('m');

        // Filtre collaborateur
        if ($collaborateur !== null) {
            $query->andWhere('m.collaborateur = :collab')
                ->setParameter('collab', $collaborateur);
        }

        // Filtre site
        if ($site !== null) {
            $query->andWhere('m.site = :site')
                ->setParameter('site', $site);
        }

        // Filtre devise
        if ($devise !== null) {
            $query->andWhere('m.devise = :devise')
                ->setParameter('devise', $devise);
        }

        // Filtre sur les dates
        if ($startDate !== null && $endDate !== null) {
            $endDateObj = (new \DateTime($endDate))->modify('+1 day');
            $query->andWhere('m.dateSaisie BETWEEN :startDate AND :endDate')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDateObj);
        }

        // Filtre sur le site
        if ($site !== null) {
            $query->andWhere('m.site = :site')
                ->setParameter('site', $site);
        }

        // Tri
        $query->addOrderBy('m.dateOperation', 'ASC')
            ->addOrderBy('m.id', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult(($pageEnCours - 1) * $limit);

        // Pagination
        $paginator = new Paginator($query);
        $data = $paginator->getQuery()->getResult();
        $nbrePages = ceil($paginator->count() / $limit);

        $result['data'] = $data;
        $result['nbrePages'] = $nbrePages;
        $result['pageEncours'] = $pageEnCours;
        $result['limit'] = $limit;

        return $result;
    }

    /**
     * @return int|null
     */
    public function findSumMontantBeforeStartDate(
        $collaborateur = null, 
        $devise = null, 
        $startDate = null, 
        $site = null
    ): ?int {
        $query = $this->createQueryBuilder('m')
            ->select('SUM(m.montant) as totalMontant');

        // Filtre collaborateur
        if ($collaborateur !== null) {
            $query->andWhere('m.collaborateur = :collab')
                ->setParameter('collab', $collaborateur);
        }

        // Filtre devise
        if ($devise !== null) {
            $query->andWhere('m.devise = :devise')
                ->setParameter('devise', $devise);
        }

        // Filtre date
        if ($startDate !== null) {
            $query->andWhere('m.dateSaisie < :startDate')
                ->setParameter('startDate', $startDate);
        }

        // Filtre site
        if ($site !== null) {
            $query->andWhere('m.site = :site')
                ->setParameter('site', $site);
        }

        // Exécution
        $result = $query->getQuery()->getSingleScalarResult();

        return $result !== null ? (int) $result : null;
    }



    public function findAncienSoldeCollaborateur($collaborateur, $dateOp): array
    {
        return $this->createQueryBuilder('m')
            ->select('sum(m.montant) as montant' , 'c.nom as devise')
            ->leftJoin('m.devise', 'c')
            ->andWhere('m.collaborateur = :colab')
            ->andWhere('m.dateOperation < :dateOp')
            ->setParameter('colab', $collaborateur)
            ->setParameter('dateOp', $dateOp)
            ->addGroupBy('m.devise')
            ->orderBy('m.devise')
            ->getQuery()
            ->getResult()

        ;
    }


    public function verifMouvement($collaborateur): array
    {
        return $this->createQueryBuilder('m')
            ->select('m.id')
            ->andWhere('m.collaborateur = :colab')
            // ->andWhere('m.montant != :montant')
            ->setParameter('colab', $collaborateur)
            // ->setParameter('montant', 0)
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        ;
    }
}
