<?php

namespace App\Repository;

use App\Entity\PaiementSalairePersonnel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PaiementSalairePersonnel>
 */
class PaiementSalairePersonnelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaiementSalairePersonnel::class);
    }

    //    /**
    //     * @return PaiementSalairePersonnel[] Returns an array of PaiementSalairePersonnel objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?PaiementSalairePersonnel
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * Trouve les personnels qui n'ont pas encore de paiement pour une période donnée
     *
     * @param string $date (format YYYY-MM-DD)
     * @param site|null $site
     * @return array
     */
    public function findSalaireSearch($date, $site = null, $search = null, $zones = [], $fonctions = [], $contrat = null ): array
    {
        // Extraire le mois et l'année
        $year = substr($date, 0, 4);
        $month = substr($date, 5, 2);

        

        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.personnel', 'p');

        if ($date !== null) {
            // Début et fin du mois
            $startDate = new \DateTime("{$year}-{$month}-01");
            $endDate   = (clone $startDate)->modify('last day of this month');
            $qb->andWhere('s.periode BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);
        }

        // Si on filtre par site
        if ($site) {
            $qb->andWhere(':site MEMBER OF p.site')
            ->setParameter('site', $site);
        }

        if ($contrat) {
            $qb->andWhere('p.contrat = :contrat')
            ->setParameter('contrat', $contrat);
        }

        // 🎯 FILTRE PAR ZONE DE RATTACHEMENT (hérité de User)
        if (!empty($zones)) {
            $qb->leftJoin('p.zoneRattachement', 'zr')
            ->andWhere('zr.id IN (:zones)')
            ->setParameter('zones', $zones);
        }

        // 🎯 FILTRE PAR fonction
        if (!empty($fonctions)) {
            
            $qb->andWhere('p.fonction IN (:fonctions)')
                ->setParameter('fonctions', $fonctions);
        }
            // 🔍 Filtre de recherche libre (optionnel)
        if ($search) {
            $qb->andWhere('
                p.prenom LIKE :search 
                OR p.reference LIKE :search
                OR p.telephone LIKE :search
                OR p.typeUser LIKE :search
            ')
            ->setParameter('search', '%' . $search . '%');
        }
        $qb->addOrderBy('s.periode', 'ASC')
            ->addOrderBy('p.prenom', 'ASC')
            ->addOrderBy('p.nom', 'ASC');
        return $qb->getQuery()->getResult();
    }
}
