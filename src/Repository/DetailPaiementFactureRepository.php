<?php

namespace App\Repository;

use App\Entity\DetailPaiementFacture;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DetailPaiementFacture>
 */
class DetailPaiementFactureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DetailPaiementFacture::class);
    }

    //    /**
    //     * @return DetailPaiementFacture[] Returns an array of DetailPaiementFacture objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('d.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?DetailPaiementFacture
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
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
    public function findDetailPaiement($site = null, $client = null, $contrat = null): array
    {

        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.facture', 'f')
            ->leftJoin('f.contrat', 'c')
            ->leftJoin('c.bien', 'b')
            ->leftJoin('b.client', 'cl');




        if ($site) {
            $qb->andWhere('f.site = :site')
            ->setParameter('site', $site);
        }

        if ($client) {
            $qb->andWhere('b.client = :client')
            ->setParameter('client', $client);
        }

        if ($contrat) {
            $qb->andWhere('f.contrat = :contrat')
            ->setParameter('contrat', $contrat);
        }

        return $qb->getQuery()->getResult();
    }
}
