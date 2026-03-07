<?php

namespace App\Repository;

use App\Entity\Personel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Personel>
 */
class PersonelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Personel::class);
    }

    //    /**
    //     * @return Personel[] Returns an array of Personel objects
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

    //    public function findOneBySomeField($value): ?Personel
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    //    /**
    //     * @return Personel[] Returns an array of Personel objects
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


    /**
     * @return Personel[] Returns an array of Personel objects
     */
    public function findPersonnelBySite($id = null, $site = null, array $fonction = [], $typePersonnel = [], $bienAffecte = [], $zones = [], $statutPlanning = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->orderBy('p.prenom', 'ASC');

        if ($id) {
            $qb->andWhere('p.id = :id')
                ->setParameter('id', $id);
        }

        if ($site) {
            $qb->andWhere(':site MEMBER OF p.site')
                ->setParameter('site', $site);
        }
        // 🎯 Filtrer par fonction si au moins une est passée
        if (!empty($fonction)) {
            $qb->andWhere('p.fonction IN (:fonctions)')
            ->setParameter('fonctions', $fonction);
        }

        if (!empty($bienAffecte)) {
            $qb->andWhere('p.bienAffecte IN (:bienAffectes)')
            ->setParameter('bienAffectes', $bienAffecte);
        }

        if (!empty($statutPlanning)) {
            $qb->andWhere('p.statutPlanning IN (:statutPlannings)')
            ->setParameter('statutPlannings', $statutPlanning);
        }

        // 🎯 Filtrer par typePersonnel si au moins une est passée
        if (!empty($typePersonnel)) {
            $qb->andWhere('p.typePersonnel IN (:typePersonnels)')
            ->setParameter('typePersonnels', $typePersonnel);
        }

        // 🎯 FILTRE PAR ZONE DE RATTACHEMENT (hérité de User)
        if (!empty($zones)) {
            $qb->leftJoin('p.zoneRattachement', 'zr')
            ->andWhere('zr.id IN (:zones)')
            ->setParameter('zones', $zones);
        }

        return $qb->getQuery()->getResult();
    }


       /**
     * @param string $search
     * @return array
     */
    public function findUserBySearch($search = null, $site = null, $id = null, $region = null, array $fonction = [], $typePersonnel = [], $statutPlanning = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.adresse', 'a')
            ->leftJoin('a.divisionLocale', 'd');

        if ($site) {
            $qb->andWhere(':site MEMBER OF p.site')
            ->setParameter('site', $site);
        }
        if ($id) {
            $qb->andWhere('p.id = :id')
            ->setParameter('id', $id);
        }

        if ($region) {
            $qb->andWhere('d.region = :region')
            ->setParameter('region', $region);
        }

        if (!empty($fonction)) {
            $qb->andWhere('p.fonction IN (:fonctions)')
            ->setParameter('fonctions', $fonction);
        }

        if (!empty($statutPlanning)) {
            $qb->andWhere('p.statutPlanning IN (:statutPlannings)')
            ->setParameter('statutPlannings', $statutPlanning);
        }

        // 🎯 Filtrer par typePersonnel si au moins une est passée
        if (!empty($typePersonnel)) {
            $qb->andWhere('p.typePersonnel IN (:typePersonnels)')
            ->setParameter('typePersonnels', $typePersonnel);
        }

        if ($search) {
            $qb->andWhere('p.prenom LIKE :val OR p.nom LIKE :val OR p.telephone LIKE :val or p.reference LIKE :val')
            ->setParameter('val', '%' . $search . '%')
            ->setMaxResults(100);
        }

        return $qb->getQuery()->getResult();
    }

    public function findResponsablesOrAdmins($site = null): array
    {
        $qb = $this->createQueryBuilder('p');

        $qb->andWhere('p.roles LIKE :admin')
        ->orWhere('p.roles LIKE :responsable')
        ->setParameter('admin', '%ROLE_ADMIN%')
        ->setParameter('responsable', '%ROLE_RESPONSABLE%');

        if ($site !== null) {
            $qb->andWhere(':site MEMBER OF p.site')
            ->setParameter('site', $site);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les personnels qui n'ont pas encore de paiement pour une période donnée
     *
     * @param string $date (format YYYY-MM-DD)
     * @param site|null $site
     * @return array
     */
    public function findPersonnelsNotInPaiementsForPeriod(
        $date,
        $site = null,
        $search = null,
        $zones = [],
        $fonctions = [],
        $contrat = null
    ): array
    {
        $year  = substr($date, 0, 4);
        $month = substr($date, 5, 2);

        $startDate = new \DateTime("{$year}-{$month}-01");
        $endDate   = (clone $startDate)->modify('last day of this month');

        $qb = $this->createQueryBuilder('p');

        // 🔗 Jointure paiements sur la période + filtre contrat (si fourni)
        if ($contrat) {
            $qb->leftJoin(
                'p.paiementSalairePersonnels',
                's',
                'WITH',
                's.periode BETWEEN :date1 AND :date2 AND s.contrat = :contrat'
            )
            ->setParameter('contrat', $contrat);
        } else {
            // personnel "normal" : paiements sans contrat
            $qb->leftJoin(
                'p.paiementSalairePersonnels',
                's',
                'WITH',
                's.periode BETWEEN :date1 AND :date2 AND s.contrat IS NULL'
            );
        }

        $qb->andWhere('s.id IS NULL')
            ->setParameter('date1', $startDate)
            ->setParameter('date2', $endDate);

        // Site
        if ($site) {
            $qb->andWhere(':site MEMBER OF p.site')
            ->setParameter('site', $site);
        }

        // Zones
        if (!empty($zones)) {
            $qb->leftJoin('p.zoneRattachement', 'zr')
            ->andWhere('zr.id IN (:zones)')
            ->setParameter('zones', $zones);
        }

        // Fonctions
        if (!empty($fonctions)) {
            $qb->andWhere('p.fonction IN (:fonctions)')
            ->setParameter('fonctions', $fonctions);
        }

        // Search
        if ($search) {
            $qb->andWhere('
                p.prenom LIKE :search
                OR p.reference LIKE :search
                OR p.telephone LIKE :search
                OR p.typeUser LIKE :search
            ')
            ->setParameter('search', '%' . $search . '%');
        }

        $qb->addOrderBy('p.prenom', 'ASC')
        ->addOrderBy('p.nom', 'ASC');

        return $qb->getQuery()->getResult();
    }

}
