<?php

namespace App\Repository;

use App\Entity\Personel;
use Doctrine\DBAL\Types\Types;
use App\Entity\AffectationAgent;
use App\Entity\ContratSurveillance;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<AffectationAgent>
 */
class AffectationAgentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AffectationAgent::class);
    }

    //    /**
    //     * @return AffectationAgent[] Returns an array of AffectationAgent objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?AffectationAgent
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * @return Personel[] Returns an array of Personel objects
     */
    public function findAffectation($id = null, $personnel = null, $site = null, $startDate = NULL, $endDate = NULL, $zones = [], $contrat = NULL, $fonctions = [], $statutAffectationDifferent = null): array
    {
        $qb = $this->createQueryBuilder('a');

        if ($id) {
            $qb->andWhere('a.id = :id')
                ->setParameter('id', $id);
        }

        if ($personnel) {
            $qb->andWhere('a.personnel = :personnel')
                ->setParameter('personnel', $personnel);
        }

        // 🎯 FILTRE PAR ZONE DE RATTACHEMENT (hérité de User)
        if (!empty($fonctions)) {
            
            $qb->leftJoin('a.personnel', 'pers')
                ->andWhere('pers.fonction IN (:fonctions)')
                ->setParameter('fonctions', $fonctions);
        }

        if ($contrat) {
            $qb->andWhere('a.contrat = :contrat')
            ->setParameter('contrat', $contrat);
        }

        if ($statutAffectationDifferent) {
            $qb->andWhere('(a.statutAffectation IS NULL OR a.statutAffectation != :statut)')
            ->setParameter('statut', $statutAffectationDifferent);
        }

        if ($site or !empty($zones)) {
            $qb->leftJoin('a.contrat', 'c')
                ->leftJoin('c.bien', 'b');
        }

        if ($site) {
            $qb->andWhere('b.site = :site')
                ->setParameter('site', $site);
        }


        // 🕓 Gestion des dates — accepte string ou objet DateTime
        if ($startDate !== null) {
            if (!$startDate instanceof \DateTimeInterface) {
                $startDate = new \DateTime($startDate);
            }
            $qb->andWhere('a.dateOperation >= :startDate')
                ->setParameter('startDate', $startDate);
        }

        if ($endDate !== null) {
            if (!$endDate instanceof \DateTimeInterface) {
                $endDate = new \DateTime($endDate);
            }
            $qb->andWhere('a.dateOperation <= :endDate')
                ->setParameter('endDate', $endDate);
        }

        // 🎯 FILTRE PAR ZONE DE RATTACHEMENT (hérité de User)
        if (!empty($zones)) {
            $qb->andWhere('b.zoneRattachement IN (:zones)')
                ->setParameter('zones', $zones);
        }
        

        return $qb->getQuery()->getResult();
    }

    public function findAffectationNow($id = null, $personnel = null, $site = null, $date = null): ?AffectationAgent
    {
        $qb = $this->createQueryBuilder('a');

        if ($id) {
            $qb->andWhere('a.id = :id')
                ->setParameter('id', $id);
        }

        if ($personnel) {
            $qb->andWhere('a.personnel = :personnel')
                ->setParameter('personnel', $personnel);
        }

        if ($site) {
            $qb->leftJoin('a.contrat', 'c')
                ->leftJoin('c.bien', 'b')
                ->andWhere('b.site = :site')
                ->setParameter('site', $site);
        }

        // 🕓 Gestion de la date
        if (!$date instanceof \DateTimeInterface && $date !== null) {
            $date = new \DateTime($date);
        } elseif ($date === null) {
            $date = new \DateTime(); // aujourd’hui par défaut
        }

        // 🗓️ On inclut aussi la veille (cas service de nuit)
        $yesterday = (clone $date)->modify('-1 day');

        $qb->andWhere('(a.dateOperation BETWEEN :yesterday AND :today)')
            ->setParameter('yesterday', $yesterday)
            ->setParameter('today', $date);

        // ✅ On ne veut qu'une seule affectation, donc on limite à 1 résultat
        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Récupère les affectations selon la portée temporelle et structurelle
     *
     * @param string $scope single|future|period
     */
    public function findByScopeFromContext(
        string $scope,
        bool $editGroup,
        \DateTimeInterface $dateOperation,
        ?string $startDate,
        ?string $endDate,
        ContratSurveillance $contrat,
        Personel $personnel,
        $groupeAffectation = null
    ): array {

        // ✅ single = sélection directe
        if ($scope === 'single') {
            return [];
            // ⚠️ le controller ajoutera manuellement l’affectation courante
        }

        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.contrat = :contrat')
            ->setParameter('contrat', $contrat);

        /** ===================== GROUPE OU AGENT ===================== */
        if ($editGroup && $groupeAffectation) {
            $qb->andWhere('a.groupeAffectation = :groupe')
            ->setParameter('groupe', $groupeAffectation);
        } else {
            $qb->andWhere('a.personnel = :personnel')
            ->setParameter('personnel', $personnel);
        }

        /** ===================== PORTÉE TEMPORELLE ===================== */
        if ($scope === 'future') {

            $qb->andWhere('a.dateOperation >= :date')
            ->setParameter('date', $dateOperation, Types::DATE_MUTABLE);
        }

        if ($scope === 'period') {

            if (!$startDate || !$endDate) {
                throw new \LogicException('Dates de période manquantes');
            }

            $qb->andWhere('a.dateOperation BETWEEN :start AND :end')
            ->setParameter('start', new \DateTime($startDate), Types::DATE_MUTABLE)
            ->setParameter('end',   new \DateTime($endDate),   Types::DATE_MUTABLE);
        }

        return $qb->getQuery()->getResult();
    }


    /**
    * Récupère les créneaux à venir d’un agent
    * (optionnellement limités à un groupe périodique)
    *
    * @return AffectationAgent[]
    */
    public function findCreneauxAVenir(
        Personel $personnel,
        \DateTimeInterface $dateDebut,
        $contrat = null,
        ?string $groupeAffectation = null
    ): array {

        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.personnel = :personnel')
            ->andWhere('a.dateOperation >= :dateDebut')
            ->setParameter('personnel', $personnel)
            ->setParameter('dateDebut', $dateDebut, Types::DATE_MUTABLE)
            ->orderBy('a.dateOperation', 'ASC');

        // 🔁 Cas périodique : on limite au groupe
        if ($contrat) {
            $qb->andWhere('a.contrat = :contrat')
            ->setParameter('contrat', $contrat);
        }

        // 🔁 Cas périodique : on limite au groupe
        if ($groupeAffectation) {
            $qb->andWhere('a.groupeAffectation = :groupe')
            ->setParameter('groupe', $groupeAffectation);
        }

        return $qb->getQuery()->getResult();
    }


    // public function hasConflitHoraire(
    // Personel $personnel,
    // \DateTimeInterface $dateOperation,
    // ?\DateTimeInterface $heureDebut,
    // ?\DateTimeInterface $heureFin
    // ): bool {

    //     if (!$heureDebut || !$heureFin) {
    //         return false; // Pas d’horaire = pas de conflit
    //     }

    //     $qb = $this->createQueryBuilder('a')
    //         ->andWhere('a.personnel = :personnel')
    //         ->setParameter('personnel', $personnel);

    //     /**
    //      * ===================== CAS 1 : créneau normal (jour)
    //      * =====================
    //      */
    //     if ($heureDebut < $heureFin) {

    //         $qb->andWhere('a.dateOperation = :date')
    //         ->andWhere('
    //                 (
    //                     (a.heureDebut < a.heureFin AND a.heureDebut < :heureFin AND a.heureFin > :heureDebut)
    //                 OR (a.heureDebut > a.heureFin AND (
    //                         :heureDebut < a.heureFin
    //                     OR :heureFin > a.heureDebut
    //                 ))
    //                 )
    //         ')
    //         ->setParameter('date', $dateOperation)
    //         ->setParameter('heureDebut', $heureDebut)
    //         ->setParameter('heureFin', $heureFin);
    //     }

    //     /**
    //      * ===================== CAS 2 : créneau nuit (traverse minuit)
    //      * =====================
    //      */
    //     else {

    //         $dateNext = (clone $dateOperation)->modify('+1 day');

    //         $qb->andWhere('
    //             (
    //                 (a.dateOperation = :date AND a.heureDebut < a.heureFin AND a.heureFin > :heureDebut)
    //             OR (a.dateOperation = :dateNext AND a.heureDebut < a.heureFin AND a.heureDebut < :heureFin)
    //             OR (a.heureDebut > a.heureFin)
    //             )
    //         ')
    //         ->setParameter('date', $dateOperation)
    //         ->setParameter('dateNext', $dateNext)
    //         ->setParameter('heureDebut', $heureDebut)
    //         ->setParameter('heureFin', $heureFin);
    //     }

    //     return (bool) $qb->getQuery()->getOneOrNullResult();
    // }


    public function hasConflitHoraire(
        Personel $personnel,
        \DateTimeInterface $dateOperation,
        ?\DateTimeInterface $heureDebut,
        ?\DateTimeInterface $heureFin
    ): bool {

        // Pas d’horaire = pas de conflit
        if (!$heureDebut || !$heureFin) {
            return false;
        }

        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.personnel = :personnel')
            ->setParameter('personnel', $personnel);

        /**
         * ===================== CAS 1 : créneau jour (heureDebut < heureFin)
         * =====================
         */
        if ($heureDebut < $heureFin) {

            $qb->andWhere('a.dateOperation = :date')
            ->andWhere('
                    (
                        (a.heureDebut < a.heureFin 
                            AND a.heureDebut < :heureFin 
                            AND a.heureFin > :heureDebut
                        )
                        OR
                        (a.heureDebut > a.heureFin 
                            AND (
                                :heureDebut < a.heureFin
                                OR :heureFin > a.heureDebut
                            )
                        )
                    )
            ')
            ->setParameter('date', $dateOperation)
            ->setParameter('heureDebut', $heureDebut)
            ->setParameter('heureFin', $heureFin);
        }

        /**
         * ===================== CAS 2 : créneau nuit (traverse minuit)
         * =====================
         */
        else {

            $dateNext = (clone $dateOperation)->modify('+1 day');

            $qb->andWhere('
                (
                    (a.dateOperation = :date 
                        AND a.heureDebut < a.heureFin 
                        AND a.heureFin > :heureDebut
                    )
                    OR
                    (a.dateOperation = :dateNext 
                        AND a.heureDebut < a.heureFin 
                        AND a.heureDebut < :heureFin
                    )
                    OR
                    (a.heureDebut > a.heureFin)
                )
            ')
            ->setParameter('date', $dateOperation)
            ->setParameter('dateNext', $dateNext)
            ->setParameter('heureDebut', $heureDebut)
            ->setParameter('heureFin', $heureFin);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }


    public function findCountOfAffectationForPersonnel(
    $personnelId,
    $date,
    $statutAffectation = [],
    $typeAffectation = [],
    $presenceConfirme = true
    ): int {

        // 🔐 Sécurisation de la date
        if (!$date instanceof \DateTimeInterface) {
            $date = new \DateTime($date);
        }

        // 📅 Début / fin du mois
        $startDate = (clone $date)->modify('first day of this month')->setTime(0, 0, 0);
        $endDate   = (clone $date)->modify('last day of this month')->setTime(23, 59, 59);

        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.personnel = :personnelId')
            ->andWhere('a.dateOperation BETWEEN :startDate AND :endDate')
            ->setParameter('personnelId', $personnelId)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);

        // 🎯 Filtre par statut d’affectation
        if (!empty($statutAffectation)) {
            $qb->andWhere('a.statutAffectation IN (:statuts)')
            ->setParameter('statuts', $statutAffectation);
        }

        // 🎯 Filtre par type d’affectation
        if (!empty($typeAffectation)) {
            $qb->andWhere('a.typeAffectation IN (:types)')
            ->setParameter('types', $typeAffectation);
        }

        // ✅ Filtre par présence confirmée
        if ($presenceConfirme !== null) {
            $qb->andWhere('a.presenceConfirme = :presence')
            ->setParameter('presence', $presenceConfirme);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }


}
