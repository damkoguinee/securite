<?php

namespace App\Repository;

use App\Entity\Paiement;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Paiement>
 */
class PaiementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Paiement::class);
    }

//    /**
//     * @return Paiement[] Returns an array of Paiement objects
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

//    public function findOneBySomeField($value): ?Paiement
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
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
    public function findPaiement($site = null, $client = null, $startDate = null, $endDate = null, int $pageEnCours = 1, int $limit = 10): array
    {
        $limit = abs($limit);
        $pageEnCours = max(1, $pageEnCours);

        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.facture', 'f')
            ->leftJoin('f.contrat', 'c')
            ->leftJoin('c.bien', 'b')
            ->leftJoin('b.client', 'cl')
            ->orderBy('p.dateOperation', 'DESC');

        if ($startDate !== null && $endDate !== null) {

            // Conversion automatique en DateTime si ce sont des strings
            if (!$startDate instanceof \DateTimeInterface) {
                $startDate = new \DateTime($startDate);
            }

            if (!$endDate instanceof \DateTimeInterface) {
                $endDate = new \DateTime($endDate);
            }
            $endDate = (clone $endDate)->modify('+1 day');
            $qb->andWhere('p.dateOperation BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);
        }




        if ($site) {
            $qb->andWhere('f.site = :site')
            ->setParameter('site', $site);
        }

        if ($client) {
            $qb->andWhere('b.client = :client')
            ->setParameter('client', $client);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param string $value
     * @param array|null $typeUser
     * @return array
     */
    public function findPaiementSearch($site = null, $search = null, $startDate = null, $endDate = null, int $pageEnCours = 1, int $limit = 10): array
    {
        $limit = abs($limit);
        $pageEnCours = max(1, $pageEnCours);

        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.facture', 'f')
            ->leftJoin('f.contrat', 'c')
            ->leftJoin('c.bien', 'b')
            ->leftJoin('b.client', 'cl')
            ->orderBy('p.dateOperation', 'DESC');

        if ($startDate !== null && $endDate !== null) {

            // Conversion automatique en DateTime si ce sont des strings
            if (!$startDate instanceof \DateTimeInterface) {
                $startDate = new \DateTime($startDate);
            }

            if (!$endDate instanceof \DateTimeInterface) {
                $endDate = new \DateTime($endDate);
            }
            $endDate = (clone $endDate)->modify('+1 day');
            $qb->andWhere('p.dateOperation BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);
        }




        if ($site) {
            $qb->andWhere('f.site = :site')
            ->setParameter('site', $site);
        }

        if ($search) {
            $qb->andWhere('p.reference LIKE :val  or cl.prenom LIKE :val OR cl.nom LIKE :val OR cl.telephone LIKE :val or cl.reference LIKE :val or b.description LIKE :val ')
            ->setParameter('val', '%' . $search . '%')
            ->setMaxResults(100);
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

    public function generateReference(): string
    {
        // Max ID existant
        $maxId = $this->findMaxId();
        $nextId = ($maxId ?? 0) + 1;

        // Jour - Mois - Année
        $day = (new \DateTime())->format('d');
        $month = (new \DateTime())->format('m');
        $year = (new \DateTime())->format('Y');

        // Petit identifiant unique
        $shortUniq = substr(strtoupper(uniqid()), -4);

        return sprintf("REC-%s-%s-%s-%s-%s", $day,$month, $year, $nextId, $shortUniq);
    }
}
