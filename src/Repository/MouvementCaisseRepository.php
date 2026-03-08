<?php

namespace App\Repository;

use App\Entity\MouvementCaisse;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<MouvementCaisse>
 */
class MouvementCaisseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MouvementCaisse::class);
    }

//    /**
//     * @return MouvementCaisse[] Returns an array of MouvementCaisse objects
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

//    public function findOneBySomeField($value): ?MouvementCaisse
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }


    /**
     * @return array
     */
    public function mouvementCaisseParPeriodeParSite($startDate, $endDate, $site): array
    {
        // Modifie la date de fin pour inclure toute la journée
        $endDate = (new \DateTime($endDate))->modify('+1 day');

        // Initialisation de la requête
        $query = $this->createQueryBuilder('m')
            ->leftJoin('m.devise', 'd')
            ->where('m.site = :site')
            ->andWhere('m.dateOperation BETWEEN :startDate AND :endDate')
            ->setParameter('site', $site)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getResult();

        return $query;
    }


    
    /**
    * @return array
    */
    public function soldeCaisseParPeriodeParVendeurParSiteFlexible(
        $vendeur,
        $startDate,
        $endDate,
        $site,
        string $groupByType = 'caisse' // 'caisse' ou 'typeMouvement'
    ): array {
        $endDate = (new \DateTime($endDate))->modify('+1 day');
        $query = $this->createQueryBuilder('m')
            ->where('m.site = :site')
            ->andWhere('m.saisiePar = :vendeur')
            ->andWhere('m.dateOperation BETWEEN :startDate AND :endDate')
            ->setParameter('site', $site)
            ->setParameter('vendeur', $vendeur)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);

        if ($groupByType === 'typeMouvement') {
            $query->select('SUM(m.montant) as solde', 'COUNT(m.id) as nbre', 'm as mouvement')
                ->groupBy('m.typeMouvement')
                ->orderBy('solde', 'DESC')
                ->addOrderBy('m.typeMouvement', 'ASC');
        } else {
            $query->select('SUM(m.montant) as solde')
                ->leftJoin('m.caisse', 'c')
                ->addSelect('c.id', 'c.nom')
                ->groupBy('m.caisse');
        }

        return $query->getQuery()->getResult();
    }
    /**
     * @return array
     */
    public function soldeCaisseParPeriodeParSiteAvecModePaie(
        $startDate,
        $endDate,
        $site,
        $devises,
        $caisses,
        $modePaie = null // Mode de paiement optionnel
    ): array {
        $endDate = (new \DateTime($endDate))->modify('+1 day');
        $query = $this->createQueryBuilder('m')
            ->select(
                'SUM(m.montant) as solde',
                'c.id as id_caisse',
                'c.type as type_caisse',
                'c.nom',
                'd.nom',
                'd.id as id_devise',
                'mp.id as id_modePaie'
            )
            ->leftJoin('m.devise', 'd')
            ->leftJoin('m.modePaie', 'mp')
            ->leftJoin('m.caisse', 'c')
            ->where('m.site = :site')
            ->andWhere('m.dateOperation BETWEEN :startDate AND :endDate')
            ->setParameter('site', $site)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);

        if ($modePaie !== null) {
            $query->andWhere('m.modePaie = :modePaie')
                ->setParameter('modePaie', $modePaie);
        }

        $results = $query
            ->groupBy('m.devise', 'm.caisse')
            ->orderBy('d.id')
            ->getQuery()
            ->getResult();

        // Générer le tableau final
        $finalResults = [];

        foreach ($devises as $devise) {
            foreach ($caisses as $caisse) {
                $trouve = false;
                foreach ($results as $resultat) {
                    if (
                        $resultat['id_devise'] === $devise->getId() &&
                        $resultat['id_caisse'] === $caisse->getId()
                    ) {
                        $finalResults[] = $resultat;
                        $trouve = true;
                        break;
                    }
                }
                if (!$trouve) {
                    $entry = [
                        'solde' => '0.00',
                        'id_caisse' => $caisse->getId(),
                        'type_caisse' => $caisse->getType(),
                        'designation' => $caisse->getNom(),
                        'nom' => $devise->getNom(),
                        'id_devise' => $devise->getId(),
                        'id_modePaie' => $modePaie !== null ? $modePaie->getId() : null,
                    ];
                    $finalResults[] = $entry;
                }
            }
        }

        return $finalResults;
    }

    /**
     * Retourne le solde des mouvements par type (et éventuellement par mode de paiement).
     *
     * @param \DateTime|string $startDate
     * @param \DateTime|string $endDate
     * @param mixed $site
     * @param mixed $devise
     * @param mixed|null $caisse
     * @param bool $groupByModePaie Active le groupement par mode de paiement
     * @return array
     */
    public function soldeCaisseParPeriodeParTypeParSiteParDeviseFlexible(
        $startDate,
        $endDate,
        $site,
        $devise,
        $caisse = null,
        bool $groupByModePaie = false
    ): array {
        $startDate = new \DateTime($startDate);
        $endDate = (new \DateTime($endDate))->modify('+1 day');

        $qb = $this->createQueryBuilder('m')
            ->select('SUM(m.montant) as solde, COUNT(m.id) as nbre, m.typeMouvement, m as mouvement')
            ->where('m.site = :site')
            ->andWhere('m.devise = :devise')
            ->andWhere('m.dateOperation BETWEEN :startDate AND :endDate')
            ->setParameter('site', $site)
            ->setParameter('devise', $devise)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);

        if ($caisse !== null) {
            $qb->andWhere('m.caisse = :caisse')
            ->setParameter('caisse', $caisse);
        }

        // Ajout du groupement obligatoire
        $qb->groupBy('m.typeMouvement');

        // Si demandé, on ajoute aussi le mode de paiement dans le groupement
        if ($groupByModePaie) {
            $qb->addGroupBy('m.modePaie');
        }

        return $qb->getQuery()->getResult();
    }



    /**
     * @return array
     */
    public function soldeCaisseParDeviseParSite($devises = null, $site = null, $startDate = null, $endDate = null): array
    {
        $query = $this->createQueryBuilder('m')
            ->select('SUM(m.montant) as solde', 'd.nom', 'd.id as id_devise')
            ->leftJoin('m.devise', 'd');

        // Gestion du site
        if ($site !== null) {
            $query->andWhere('m.site = :site')
                ->setParameter('site', $site);
        }

        // Gestion des dates
        if ($startDate !== null && $endDate !== null) {
            $startDateObj = new \DateTime($startDate);
            $endDateObj = (new \DateTime($endDate))->modify('+1 day');
            $query->andWhere('m.dateOperation BETWEEN :startDate AND :endDate')
                ->setParameter('startDate', $startDateObj)
                ->setParameter('endDate', $endDateObj);
        }

        $query->groupBy('m.devise')
            ->orderBy('d.id');

        $results = $query->getQuery()->getResult();

        // Si $devises est null, on retourne les résultats tels quels
        if ($devises === null) {
            return $results;
        }

        // Générer la liste finale avec toutes les devises
        $finalResults = [];
        foreach ($devises as $devise) {
            $found = false;
            foreach ($results as $result) {
                if ((int)$result['id_devise'] === $devise->getId()) {
                    $finalResults[] = $result;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $finalResults[] = [
                    'solde' => '0.00',
                    'nom' => $devise->getNom(),
                    'id_devise' => $devise->getId()
                ];
            }
        }

        return $finalResults;
    }


    public function getEtatDesCaisses($date1, $date2, $site, $devises): array
    {
        // Récupération des mouvements de caisse
        $mouvementCaisses = $this->mouvementCaisseParPeriodeParSite($date1, $date2, $site);

        $caisses_lieu = [];
        $devises_defaut = [];

        // Remplir les devises depuis la base de données
        foreach ($devises as $devise) {
            $devises_defaut[$devise->getId()] = $devise->getNom();
        }

        // Remplir les mouvements de caisse par type et devise
        foreach ($mouvementCaisses as $mouvement) {
            $caisse = $mouvement->getCaisse();
            $devise = $mouvement->getDevise();
            $modePaie = $mouvement->getModePaie();
            $designation = $caisse->getNom();
            
            if (!isset($caisses_lieu[$designation])) {
                $caisses_lieu[$designation] = [];
            }

            // Mise à jour ou ajout du mouvement dans la caisse correspondante
            $found = false;
            foreach ($caisses_lieu[$designation] as &$entry) {
                if ($entry['id_caisse'] === $caisse->getId() && $entry['id_devise'] === $devise->getId()) {
                    $entry['solde'] += $mouvement->getMontant();
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $caisses_lieu[$designation][] = [
                    'solde' => $mouvement->getMontant(),
                    'id_caisse' => $caisse->getId(),
                    'type_caisse' => $caisse->getType(),
                    'designation' => $designation,
                    'nomDevise' => $devise->getNom(),
                    'id_devise' => $devise->getId(),
                ];
            }
        }

        // Ajouter les devises manquantes avec solde = 0
        foreach ($caisses_lieu as $designation => &$entries) {
            $existing_devises = array_column($entries, 'id_devise');
            
            foreach ($devises_defaut as $id_devise => $nomDevise) {
                if (!in_array($id_devise, $existing_devises)) {
                    $entries[] = [
                        'solde' => 0.00,
                        'id_caisse' => $entries[0]['id_caisse'],
                        'type_caisse' => $entries[0]['type_caisse'],
                        'designation' => $designation,
                        'nomDevise' => $nomDevise,
                        'id_devise' => $id_devise,
                    ];
                }
            }
        }

        // Vérification des chèques (mode de paiement ID = 4) et exclusion des banques
        foreach ($mouvementCaisses as $mouvement) {
            $caisse = $mouvement->getCaisse();
            $devise = $mouvement->getDevise();
            $modePaie = $mouvement->getModePaie();
            
            if ($modePaie && $modePaie->getId() === 4 && $caisse->getType() !== 'banque') {
                if (!isset($caisses_lieu['caisse espèces chèque'])) {
                    $caisses_lieu['caisse espèces chèque'] = [];
                }
                
                $foundCheque = false;
                foreach ($caisses_lieu['caisse espèces chèque'] as &$chequeEntry) {
                    if ($chequeEntry['id_caisse'] === $caisse->getId() && $chequeEntry['id_devise'] === $devise->getId()) {
                        $chequeEntry['solde'] += $mouvement->getMontant();
                        $foundCheque = true;
                        break;
                    }
                }
                
                if (!$foundCheque) {
                    $caisses_lieu['caisse espèces chèque'][] = [
                        'solde' => $mouvement->getMontant(),
                        'id_caisse' => $caisse->getId(),
                        'type_caisse' => $caisse->getType(),
                        'designation' => 'caisse espèces chèque',
                        'nomDevise' => $devise->getNom(),
                        'id_devise' => $devise->getId(),
                    ];
                }
            }
        }

        // Soustraction du solde "caisse espèces" et "caisse espèces chèque" si devise = 1 (GNF)
        foreach ($caisses_lieu as $key => &$caisses) {
            // Vérification si la clé correspond à "caisse espèces"
            if ($key == 'caisse espèces') {
                foreach ($caisses as &$caisse) {
                    if (isset($caisse['solde']) && isset($caisse['id_devise']) && $caisse['id_devise'] == 1) {
                        $caisseEspècesBalance = $caisse['solde'];

                        if (isset($caisses_lieu['caisse espèces chèque'])) {
                            foreach ($caisses_lieu['caisse espèces chèque'] as $caisseCheque) {
                                if (isset($caisseCheque['solde']) && $caisseCheque['id_devise'] == 1) {
                                    $caisseChequeEspècesBalance = $caisseCheque['solde'];
                                    $caisse['solde'] = $caisseEspècesBalance - $caisseChequeEspècesBalance;
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }

        // Tri des caisses pour mettre "caisse espèces" et "caisse espèces chèque" en première position
        $priorityKeys = ['caisse espèces', 'caisse espèces chèque'];
        $priorityItems = [];
        $otherItems = [];

        foreach ($caisses_lieu as $key => $value) {
            if (in_array($key, $priorityKeys)) {
                $priorityItems[$key] = $value;
            } else {
                $otherItems[$key] = $value;
            }
        }

        // Fusionner les tableaux (les éléments prioritaires seront en tête)
        $caisses_lieu = $priorityItems + $otherItems;

        return $caisses_lieu;
    }


    /**
     * Retourne le solde global ou par caisse selon $groupByCaisse.
     *
     * @param string|null $caisse
     * @param string|null $devise
     * @param \DateTimeInterface|null $startDate
     * @param \DateTimeInterface|null $endDate
     * @param string|null $personnel
     * @param string|null $site
     * @param bool $groupByCaisse
     * @return array|float
     */
    public function findSoldeCaisse(
        $caisse = null,
        $devise = null,
        $startDate = null,
        $endDate = null,
        $personnel = null,
        $site = null,
        bool $groupByCaisse = false
    ) {
        $qb = $this->createQueryBuilder('m');

        // Sélection principale
        if ($groupByCaisse) {
            $qb->select('COALESCE(SUM(m.montant), 0) as solde')
            ->leftJoin('m.caisse', 'c')
            ->addSelect('c.id, c.designation');
        } else {
            $qb->select('COALESCE(SUM(m.montant), 0) as solde');
        }

        // Filtres optionnels
        if ($caisse !== null) {
            $qb->andWhere('m.caisse = :caisse')
            ->setParameter('caisse', $caisse);
        }
        if ($devise !== null) {
            $qb->andWhere('m.devise = :devise')
            ->setParameter('devise', $devise);
        }
        if ($personnel !== null) {
            $qb->andWhere('m.saisiePar = :personnel')
            ->setParameter('personnel', $personnel);
        }
        if ($site !== null) {
            $qb->andWhere('m.site = :site')
            ->setParameter('site', $site);
        }
        if ($startDate !== null && $endDate !== null) {
            $endDate = (new \DateTime($endDate->format('Y-m-d')))->modify('+1 day');
            $qb->andWhere('m.dateOperation BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);
        }

        // Groupement si nécessaire
        if ($groupByCaisse) {
            $qb->groupBy('m.caisse');
            return $qb->getQuery()->getResult();
        }

        // Retour du total global
        return (float) $qb->getQuery()->getSingleScalarResult();
    }

    

    /**
     * @return array
     */
    public function findSoldeCaisseGroupByDeviseAndCaisse($devises, $caisses, $startDate = null, $endDate = null, $site = null, $modePaie = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->select(
                'SUM(m.montant) as solde',
                'c.id as id_caisse',
                'c.type as type_caisse',
                'c.nom as designation',
                'd.nom as nomDevise',
                'd.id as id_devise',
                'mp.id as id_modePaie'
            )
            ->leftJoin('m.devise', 'd')
            ->leftJoin('m.caisse', 'c')
            ->leftJoin('m.modePaie', 'mp')
            ->groupBy('d.id, d.nom, c.id, c.type, c.nom, mp.id')
            ->orderBy('d.id');

        // Filtre site
        if ($site !== null) {
            $qb->andWhere('m.site = :site')
            ->setParameter('site', $site);
        }

        // Filtre mode de paiement
        if ($modePaie !== null) {
            $qb->andWhere('m.modePaie = :modePaie')
            ->setParameter('modePaie', $modePaie);
        }

        // Filtre dates
        if ($startDate !== null && $endDate !== null) {
            $endDateObj = (new \DateTime($endDate))->modify('+1 day');
            $qb->andWhere('m.dateOperation BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDateObj);
        }

        $results = $qb->getQuery()->getResult();

        // Tableau final avec toutes les combinaisons caisses/devise, même à zéro
        $finalResults = [];
        foreach ($devises as $devise) {
            foreach ($caisses as $caisse) {
                $trouve = false;
                foreach ($results as $resultat) {
                    if ($resultat['id_devise'] === $devise->getId() && $resultat['id_caisse'] === $caisse->getId()) {
                        $finalResults[] = $resultat;
                        $trouve = true;
                        break;
                    }
                }
                if (!$trouve) {
                    $finalResults[] = [
                        'solde' => '0.00',
                        'id_caisse' => $caisse->getId(),
                        'type_caisse' => $caisse->getType(),
                        'designation' => $caisse->getNom(),
                        'nomDevise' => $devise->getNom(),
                        'id_devise' => $devise->getId(),
                        'id_modePaie' => $modePaie ? (is_object($modePaie) ? $modePaie->getId() : $modePaie) : null
                    ];
                }
            }
        }

        return $finalResults;
    }

    /**
     * @return array
     */
    public function soldeCaisseGroupByDevise($devises, $type = [], $startDate = null, $endDate = null, $site = null, $modePaie = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->select(
                'SUM(m.montant) as solde',
                'd.nom as nomDevise',
                'd.id as id_devise'
            )
            ->leftJoin('m.devise', 'd')
            ->groupBy('m.devise')
            ->orderBy('d.id');

        // Filtre site
        if ($site !== null) {
            $qb->andWhere('m.site = :site')
            ->setParameter('site', $site);
        }

        if (!empty($type)) {
            $qb->andWhere('m.typeMouvement IN (:type)')
            ->setParameter('type', $type);
        }

        // Filtre mode de paiement
        if ($modePaie !== null) {
            $qb->andWhere('m.modePaie = :modePaie')
            ->setParameter('modePaie', $modePaie);
        }

        // Filtre dates
        if ($startDate !== null && $endDate !== null) {
            $endDateObj = (new \DateTime($endDate))->modify('+1 day');
            $qb->andWhere('m.dateOperation BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDateObj);
        } 

        $results = $qb->getQuery()->getResult();

        // Compléter avec toutes les devises même si solde = 0
        $finalResults = [];
        foreach ($devises as $devise) {
            $trouve = false;
            foreach ($results as $resultat) {
                if ($resultat['id_devise'] === $devise->getId()) {
                    $finalResults[] = $resultat;
                    $trouve = true;
                    break;
                }
            }
            if (!$trouve) {
                $finalResults[] = [
                    'solde' => '0.00',
                    'nomDevise' => $devise->getNom(),
                    'id_devise' => $devise->getId()
                ];
            }
        }

        return $finalResults;
    }



    public function findSoldeCaisseByTypeMouvement($site = null, $personnel = null, $startDate = null, $endDate = null, $devise = null, $caisse = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->select(
                'SUM(m.montant) as solde',
                'COUNT(m.id) as nbre',
                'm.typeMouvement as typeMouvement'
            )
            ->groupBy('m.typeMouvement')
            ->orderBy('solde', 'DESC')
            ->addOrderBy('m.typeMouvement', 'ASC');

        if ($site !== null) {
            $qb->andWhere('m.site = :site')
            ->setParameter('site', $site);
        }

        if ($caisse !== null) {
            $qb->andWhere('m.caisse = :caisse')
            ->setParameter('caisse', $caisse);
        }

        if ($devise !== null) {
            $qb->andWhere('m.devise = :devise')
            ->setParameter('devise', $devise);
        }

        if ($personnel !== null) {
            $qb->andWhere('m.saisiePar = :personnel')
            ->setParameter('personnel', $personnel);
        }

        if ($startDate !== null && $endDate !== null) {
            $endDateObj = (new \DateTime($endDate))->modify('+1 day');

            $qb->andWhere('m.dateOperation BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDateObj);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array
     */
    public function findChequeCaisse(
        $site = null, 
        $caisses = [], 
        $modePaie = null, 
        $startDate = null, 
        $endDate = null, 
        $bordereau = null,
        $etatOperation = [],
        int $pageEnCours = 1, 
        int $limit = 10
    ): array {
        // Sécurisation des paramètres
        $limit = abs($limit);
        $pageEnCours = max(1, $pageEnCours);

        // Ajuster la date de fin
        if ($endDate !== null) {
            $endDate = (new \DateTime($endDate))->modify('+1 day');
        }

        // Création de la query
        $qb = $this->createQueryBuilder('m')
            ->orderBy('m.dateSaisie', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult(($pageEnCours - 1) * $limit);

        // Conditions
        if ($site) {
            $qb->andWhere('m.site = :site')
            ->setParameter('site', $site);
        }

        if (!empty($caisses)) {
            $qb->andWhere('m.caisse IN (:caisses)')
            ->setParameter('caisses', $caisses);
        }

        if ($modePaie) {
            $qb->andWhere('m.modePaie IN (:mode)')
            ->setParameter('mode', (array)$modePaie); // cast en tableau si un seul élément
        }

        if ($etatOperation) {
            $qb->andWhere('m.etatOperation IN (:etatOperation)')
            ->setParameter('etatOperation', (array)$etatOperation); 
        }

        if ($bordereau) {
            # code...
            $qb->andWhere('m.bordereau LIKE :val')
            ->setParameter('val', '%' . $bordereau . '%');
        }

        if ($startDate && $endDate) {
            $qb->andWhere('m.dateSaisie BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);
        }

         $qb->setFirstResult(($pageEnCours - 1) * $limit)
        ->setMaxResults($limit);
        $paginator = new Paginator($qb->getQuery());

        return [
            'data'       => iterator_to_array($paginator),
            'nbrePages'  => ceil(count($paginator) / $limit),
            'pageEnCours'=> $pageEnCours,
            'limit'      => $limit,
            'total'      => count($paginator),
        ];
    }

    public function findMouvementCaisseParType(
        $type = [],
        $startDate = null,
        $endDate = null,
        $site = null,
        $devises = null,
        $modesPaie = null,
        ?bool $montantPositif = null
    ): array {
        if ($endDate) {
            $endDate = (new \DateTime($endDate))->modify('+1 day');
        }

        $qb = $this->createQueryBuilder('m')
            ->select(
                'SUM(m.montant) as montantTotal',
                'm.typeMouvement',
                'd.nom as nomDevise',
                'd.id as id_devise',
                'mp.designation as modePaiement',
                'mp.id as id_mode_paie'
            )
            ->leftJoin('m.devise', 'd')
            ->leftJoin('m.modePaie', 'mp');

        if ($site !== null) {
            $qb->andWhere('m.site = :site')
            ->setParameter('site', $site);
        }

        if (!empty($type)) {
            $qb->andWhere('m.typeMouvement IN (:type)')
            ->setParameter('type', $type);
        }

        if ($startDate !== null && $endDate !== null) {
            $qb->andWhere('m.dateOperation BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);
        }

        // Filtre sur le montant
        if ($montantPositif === true) {
            $qb->andWhere('m.montant >= 0');
        } elseif ($montantPositif === false) {
            $qb->andWhere('m.montant < 0');
        }

        $results = $qb->groupBy('m.devise', 'm.modePaie')
                    ->getQuery()
                    ->getResult();

        $finalResults = [];

        if ($devises !== null && $modesPaie !== null) {
            $typeStr = is_array($type) ? implode(',', $type) : (string) $type;

            foreach ($devises as $devise) {
                foreach ($modesPaie as $modePaie) {
                    $trouve = false;

                    foreach ($results as $resultat) {
                        if ($resultat['id_devise'] === $devise->getId()
                            && $resultat['id_mode_paie'] === $modePaie->getId()) {
                            // On force aussi typeMouvement en string ici
                            $resultat['typeMouvement'] = is_array($resultat['typeMouvement'])
                                ? implode(',', $resultat['typeMouvement'])
                                : (string) $resultat['typeMouvement'];

                            $finalResults[] = $resultat;
                            $trouve = true;
                            break;
                        }
                    }

                    if (!$trouve) {
                        $finalResults[] = [
                            'montantTotal' => '0.00',
                            'id_mode_paie' => $modePaie->getId(),
                            'modePaiement' => $modePaie->getDesignation(),
                            'nomDevise' => $devise->getNom(),
                            'id_devise' => $devise->getId(),
                            'typeMouvement' => $typeStr,
                        ];
                    }
                }
            }
        } 

        return $finalResults;
    }



     /**
     * @return array
     */
    public function totauxMouvementParTypeMouvement($devises, $startDate = null, $endDate = null, $site = null, $typeMouvement = [], ?bool $montantPositif = null): array
    {
        if ($endDate !== null) {
            $endDate = (new \DateTime($endDate))->modify('+1 day');
        }

        $qb = $this->createQueryBuilder('m')
            ->select('SUM(m.montant) as montantTotal', 'd.nom as nomDevise', 'd.id as id_devise')
            ->leftJoin('m.devise', 'd');

        if ($montantPositif === true) {
            $qb->andWhere('m.montant >= 0');
        } elseif ($montantPositif === false) {
            $qb->andWhere('m.montant < 0');
        }

        if ($site !== null) {
            $qb->andWhere('m.site = :site')
            ->setParameter('site', $site);
        }

        if (!empty($typeMouvement)) {
            $qb->andWhere('m.typeMouvement NOT IN (:typeMouvement)')
            ->setParameter('typeMouvement', $typeMouvement);
        }

        if ($startDate !== null && $endDate !== null) {
            $qb->andWhere('m.dateOperation BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);
        }

        $results = $qb->groupBy('m.devise')
                    ->getQuery()
                    ->getResult();

        // Reconstruit la liste finale avec toutes les devises
        $finalResults = [];
        foreach ($devises as $devise) {
            $trouve = false;
            foreach ($results as $resultat) {
                if ($resultat['id_devise'] === $devise->getId()) {
                    $finalResults[] = $resultat;
                    $trouve = true;
                    break;
                }
            }
            if (!$trouve) {
                $finalResults[] = [
                    'montantTotal' => '0.00',
                    'nomDevise' => $devise->getNom(),
                    'id_devise' => $devise->getId()
                ];
            }
        }

        return $finalResults;
    }


    /**
     * @return array
     */
    public function findOperationCaisse(
        $site = null,
        $caisse = null,
        $devise = null,
        $startDate = null,
        $endDate = null,
        int $pageEnCours = 1,
        int $limit = 50
    ): array {
        $query = $this->createQueryBuilder('m');

        // 🔹 Filtres dynamiques seulement si non null
        if ($caisse !== null) {
            $query->andWhere('m.caisse = :caisse')
                ->setParameter('caisse', $caisse);
        }

        if ($devise !== null) {
            $query->andWhere('m.devise = :devise')
                ->setParameter('devise', $devise);
        }

        if ($startDate !== null && $endDate !== null) {
            $endDateObj = (new \DateTime($endDate))->modify('+1 day');
            $query->andWhere('m.dateOperation BETWEEN :startDate AND :endDate')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDateObj);
        } 
        if ($site !== null) {
            $query->andWhere('m.site = :site')
                ->setParameter('site', $site);
        }

        // 🔹 Pagination et tri
        $query->orderBy('m.dateSaisie', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult(($pageEnCours - 1) * $limit);

        // 🔹 Exécution avec pagination
        $paginator = new Paginator($query);
        $data = $paginator->getQuery()->getResult();
        $nbrePages = ceil($paginator->count() / $limit);

        return [
            'data' => $data,
            'nbrePages' => $nbrePages,
            'pageEncours' => $pageEnCours,
            'limit' => $limit,
        ];
    }


    /**
     * @return float|null
     */
    public function findSoldeCaisseBeforeStartDate($startDate = null, $devise = null, $site = null, $caisse = null): ?float
    {
        $qb = $this->createQueryBuilder('m')
            ->select('SUM(m.montant) as cumulMontant');

        if ($devise !== null) {
            $qb->andWhere('m.devise = :devise')
            ->setParameter('devise', $devise);
        }

        // 🔹 Filtre date
        if ($startDate !== null) {
            $qb->andWhere('m.dateOperation < :startDate')
            ->setParameter('startDate', $startDate);
        }

        if ($site !== null) {
            $qb->andWhere('m.site = :site')
            ->setParameter('site', $site);
        } else {
            $qb->andWhere('m.site IS NULL');
        }

        if ($caisse !== null) {
            $qb->andWhere('m.caisse = :caisse')
            ->setParameter('caisse', $caisse);
        } else {
            $qb->andWhere('m.caisse IS NULL');
        }

        return (float) $qb->getQuery()->getSingleScalarResult();
    }




    



}
