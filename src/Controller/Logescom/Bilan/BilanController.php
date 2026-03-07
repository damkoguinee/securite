<?php

namespace App\Controller\Logescom\Bilan;

use App\Entity\Site;
use App\Entity\ClotureCaisse;
use App\Entity\MouvementCaisse;
use App\Repository\UserRepository;
use App\Repository\CaisseRepository;
use App\Repository\DeviseRepository;
use App\Repository\EntrepriseRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ClotureCaisseRepository;
use App\Repository\CompteOperationRepository;
use App\Repository\MouvementCaisseRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\CategorieOperationRepository;
use App\Repository\ChequeEspeceRepository;
use App\Repository\CommandeProductRepository;
use App\Repository\ConfigDeviseRepository;
use App\Repository\ConfigModePaiementRepository;
use App\Repository\DecaissementRepository;
use App\Repository\DepenseRepository;
use App\Repository\EchangeDeviseRepository;
use App\Repository\FacturationRepository;
use App\Repository\PaiementRepository;
use App\Repository\RecetteRepository;
use App\Repository\ReservationRepository;
use App\Repository\TransfertFondRepository;
use App\Repository\VersementRepository;
use DateTime;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/logescom/bilan')]
class BilanController extends AbstractController
{
    #[Route('/{site}', name: 'app_logescom_bilan_index')]
    public function index(Site $site, Request $request, SessionInterface $session, ReservationRepository $reservationRep, MouvementCaisseRepository $mouvementRep, UserRepository $userRep, ConfigDeviseRepository $deviseRep, CaisseRepository $caisseRep, PaiementRepository $paiementRep, VersementRepository $versementRepository,  DepenseRepository $depenseRep, EntrepriseRepository $entrepriseRep, ConfigModePaiementRepository $modePaieRep): Response
    {
        if ($request->get("date1")){
            $date1 = $request->get("date1");
            $date2 = $request->get("date2");

        }else{
            $date1 = date("Y-m-d");
            $date2 = date("Y-m-d");
        }

        if ($request->query->get('id_personnel') or $request->isXmlHttpRequest()) {
            $date1 = $date1;
            $date2 = $date2;
        }else{
            if ($request->get("date1")){
                $date1 = $request->get("date1");
                $date2 = $request->get("date2");
            }else{
                $date1 = date("Y-m-d");
                $date2 = date("Y-m-d");
            }
            $date1 = $date1;
            $date2 = $date2;

            $session->set("session_date1", $date1);
            $session->set("session_date2", $date2);
        }

        if ($request->get("id_personnel")){
            $search = $request->get("id_personnel");
        }else{
            $search = "";
        }

        if ($request->get("search_devise")){
            $search_devise = $deviseRep->find($request->get("search_devise"));
        }else{
            $search_devise = $deviseRep->find(1);
        }

        if ($request->get("search_caisse")){
            $search_caisse = $caisseRep->find($request->get("search_caisse"));
        }else{
            $search_caisse = $caisseRep->findOneBy([]);
        }

        if ($request->isXmlHttpRequest()) {
            if ( $request->query->get('search_personnel')) {
                $search = $request->query->get('search_personnel');
                $clients = $userRep->findPersonnelSearchByLieu($search, $site);    
                $response = [];
                foreach ($clients as $client) {
                    $response[] = [
                        'nom' => ucwords($client->getPrenom())." ".strtoupper($client->getNom()),
                        'id' => $client->getId()
                    ]; // Mettez à jour avec le nom réel de votre propriété
                }
                return new JsonResponse($response);
            }
        }
        $caisses = $caisseRep->findBy(['site' => $site]);
        $devises = $deviseRep->findAll();
        $modepaie = $modePaieRep->find(4);
        if ($request->get("id_personnel")){
            $solde_caisses = $mouvementRep->soldeCaisseParPeriodeParVendeurParSiteFlexible($search, $date1, $date2, $site, 'caisse');
        }else{
            $solde_caisses = $mouvementRep->soldeCaisseParPeriodeParSiteAvecModePaie($date1, $date2, $site, $devises, $caisses);
            $solde_caisses_cheque = $mouvementRep->soldeCaisseParPeriodeParSiteAvecModePaie($date1, $date2, $site, $devises, $caisses, $modepaie);

            // Parcourir les soldes des caisses
            foreach ($solde_caisses as &$solde_caisse) {
                // On vérifie si c'est une caisse
                if ($solde_caisse['type_caisse'] === 'caisse') {
                    // Rechercher le solde correspondant dans solde_caisses_cheque
                    foreach ($solde_caisses_cheque as $cheque) {
                        // Vérifier si la caisse et la devise correspondent
                        if ($solde_caisse['id_caisse'] == $cheque['id_caisse'] && $solde_caisse['id_devise'] == $cheque['id_devise']) {
                            // Déduire le montant du chèque du solde de la caisse
                            $solde_caisse['solde'] -= $cheque['solde'];
                        }
                    }
                }
            }

            // Vous pouvez maintenant utiliser $solde_caisses mis à jour avec les déductions de chèques
            $caisses_lieu = [];
            foreach ($solde_caisses as $solde) {
                foreach ($caisses as $caisse) {
                    if ($solde['id_caisse'] == $caisse->getId()) {
                        $caisses_lieu[$caisse->getNom()][] = $solde;
                    } 
                }
            }

            // Maintenant, on ajoute 'caisse_cheque' au même niveau uniquement si le type de caisse est 'caisse'
            foreach ($solde_caisses_cheque as $cheque) {
                foreach ($caisses as $caisse) {
                    if ($cheque['id_caisse'] == $caisse->getId() && $cheque['type_caisse'] == 'caisse') {
                        // On ajoute uniquement si le type de caisse est 'caisse'
                        $caisses_lieu[$caisse->getNom() . ' chèque'][] = [
                            'solde' => $cheque['solde'],
                            'id_caisse' => $cheque['id_caisse'],
                            'type_caisse' => $cheque['type_caisse'],
                            'designation' => $cheque['nom'],
                            'nom' => $cheque['nom'],
                            'id_devise' => $cheque['id_devise']
                        ];
                    }
                }
            }
            uksort($caisses_lieu, function($a, $b) {
                // Priorité à "caisse espèces" et "caisse espèces cheque"
                $priorites = ['caisse', 'caisse chèque'];
            
                // Vérification si $a ou $b sont dans la liste des priorités
                $indexA = array_search($a, $priorites);
                $indexB = array_search($b, $priorites);
            
                // Si $a est prioritaire et pas $b, $a vient avant
                if ($indexA !== false && $indexB === false) {
                    return -1;
                }
            
                // Si $b est prioritaire et pas $a, $b vient avant
                if ($indexB !== false && $indexA === false) {
                    return 1;
                }
            
                // Si $a et $b sont tous les deux dans la liste des priorités, on respecte leur ordre dans la liste
                if ($indexA !== false && $indexB !== false) {
                    return $indexA - $indexB;
                }
            
                // Si ni $a ni $b ne sont dans la liste des priorités, on applique la logique habituelle pour les caisses avec 'cheque'
                if (strpos($a, 'cheque') === false && strpos($b, 'cheque') !== false && strpos($b, $a) === 0) {
                    return -1;
                }
            
                if (strpos($b, 'cheque') === false && strpos($a, 'cheque') !== false && strpos($a, $b) === 0) {
                    return 1;
                }
            
                // Sinon, tri alphabétique standard
                return strcmp($a, $b);
            });

        }

        $solde_caisses_devises = $mouvementRep->soldeCaisseParDeviseParSite($devises, $site, $date1, $date2);

        $nombreDeVentes = $reservationRep->nombreDeReservationsParPeriodeParSite($date1, $date2, $site);
        $totalDepenses = $depenseRep->totalDepenses($site, NULL, NULL, $date1, $date2);
        $resultats = $reservationRep->calculBilanReservations($site, $date1, $date2);


        if ($request->get("search_caisse")) {
            $solde_types = $mouvementRep->soldeCaisseParPeriodeParTypeParSiteParDeviseFlexible($date1, $date2, $site, $search_devise, $caisse, true);
        } else {
            $solde_types = $mouvementRep->soldeCaisseParPeriodeParTypeParSiteParDeviseFlexible($date1, $date2, $site, $search_devise, NULL, true);
        }

        // Organiser les données par mode de paiement
        $solde_types_par_mode = [];
        foreach ($solde_types as $solde_type) {
            $modePaie = $solde_type['mouvement']->getModePaie()->getDesignation();
            $typeMouvement = $solde_type['mouvement']->getTypeMouvement();

            if (!isset($solde_types_par_mode[$typeMouvement])) {
                $solde_types_par_mode[$typeMouvement] = [];
            }

            if (!isset($solde_types_par_mode[$typeMouvement][$modePaie])) {
                $solde_types_par_mode[$typeMouvement][$modePaie] = [
                    'mouvement' => $solde_type['mouvement'],
                    'solde' => 0,
                    'nbre' => 0
                ];
            }

            $solde_types_par_mode[$typeMouvement][$modePaie]['solde'] += $solde_type['solde'];
            $solde_types_par_mode[$typeMouvement][$modePaie]['nbre'] += $solde_type['nbre'];
        }

        // Calculer les totaux pour chaque type de mouvement
        $totals = [];
        foreach ($solde_types_par_mode as $typeMouvement => $modes) {
            $totals[$typeMouvement] = [
                'nbre' => array_sum(array_column($modes, 'nbre')),
                'solde' => array_sum(array_column($modes, 'solde'))
            ];
        }

        return $this->render('logescom/bilan/bilan/index.html.twig', [
            'solde_caisses' => $caisses_lieu,
            'solde_caisses_devises' => $solde_caisses_devises,
            'nombre_ventes' => $nombreDeVentes,
            'total_depenses' => $totalDepenses,
            'chiffre_affaire' => $resultats,
            'solde_types' => $solde_types_par_mode,
            'totals' => $totals,
            'site'   => $site,
            'liste_caisse' => $caisseRep->findBy(['site' => $site]),
            'search' => $search,
            'search_devise' => $search_devise,
            'search_caisse' => $search_caisse,
            'devises' => $devises,
            'caisses' => $caisses,
            'date1' => $date1,
            'date2' => $date2,
        ]);
    }

    #[Route('/etat/caisse/{site}', name: 'app_logescom_bilan_etat_caisse')]
    public function etatCaisse(Site $site, CaisseRepository $caisseRep, ConfigDeviseRepository $deviseRep, MouvementCaisseRepository $mouvementRep, Request $request, UserRepository $userRep, EntrepriseRepository $entrepriseRep): Response
    {
        if ($request->get("date1")){
            $date1 = $request->get("date1");
            $date2 = $request->get("date2");

        }else{
            $date1 = date("2000-m-d");
            $date2 = date("Y-m-d");
        }
        
        $devises = $deviseRep->findAll();

        $caisses_lieu = $mouvementRep->getEtatDesCaisses($date1, $date2, $site, $devises);

        $solde_caisses_devises = $mouvementRep->soldeCaisseParDeviseParSite($devises, $site);
       
        return $this->render('logescom/bilan/bilan/etat_caisse.html.twig', [
            'solde_caisses' => $caisses_lieu,
            'solde_caisses_devises' => $solde_caisses_devises,
            'site'   => $site,
            'devises' => $devises,

        ]);
    }

    #[Route('/detail/operation/{site}', name: 'app_logescom_bilan_detail_operation')]
    public function detailOperation(Site $site, Request $request, MouvementCaisseRepository $mouvementRep, DeviseRepository $deviseRep, PaiementRepository $paiementRep, VersementRepository $versementRepository,  DepenseRepository $depenseRep,  ClotureCaisseRepository $clotureRep, TransfertFondRepository $transfertFondRep, ConfigModepaiementRepository $modePaieRep, DecaissementRepository $decaissementRep, ChequeEspeceRepository $chequeEspeceRep, EchangeDeviseRepository $EchangeDeviseRepository, EntrepriseRepository $entrepriseRep): Response
    {
        if ($request->get("date1")){
            $date1 = $request->get("date1");
            $date2 = $request->get("date2");

        }else{
            $date1 = date("Y-m-d");
            $date2 = date("Y-m-d");
        }
        $devises = $deviseRep->findAll();
        $pageEncours = $request->get('pageEncours', 1);
        if ($request->get('operation')) {
            $operation_search = $request->get("operation");
            if ($operation_search == 'facturation') {
                $operations_search = $facturationRep->findFacturationByLieuPaginated($site, $date1, $date2, $pageEncours, 10000);

                $operationsCredit_search = $facturationRep->findFacturationCreditByLieuPaginated($site, $date1, $date2, $pageEncours, 10000);
            }elseif ($operation_search == 'versement') {

                $operations_search = $versementRepository->findVersementByLieuPaginated($site, $date1, $date2, $pageEncours, 10000);

            }elseif ($operation_search == 'recette') {
                $operations_search = $recetteRepository->findRecetteByLieuPaginated($site, $date1, $date2, $pageEncours, 10000);

            }elseif ($operation_search == 'decaissement') {
                $operations_search = $decaissementRep->findDecaissementByLieuPaginated($site, $date1, $date2, $pageEncours, 10000);
            }elseif ($operation_search == 'depenses') {
                $operations_search = $depenseRep->findDepensesByLieuPaginated($site, $date1, $date2, $pageEncours, 10000);
            }elseif ($operation_search == 'transfert') {
                $operations_search = $transfertFondRep->findTransfertByLieuPaginated($site, $date1, $date2, $pageEncours, 10000);

            }elseif ($operation_search == 'cloture') {
                $operations_search = $clotureRep->listeDesCloturesParPeriodeParSitePaginated($site, $date1, $date2, $pageEncours, 10000);
            }elseif ($operation_search == 'echange') {
                $operations_search = $EchangeDeviseRepository->findTransfertByLieuPaginated($site, $date1, $date2, $pageEncours, 10000);

            }elseif ($operation_search == 'cheques-especes') {
                $operations_search = $chequeEspeceRep->findVersementByLieuPaginated($site, $date1, $date2, $pageEncours, 10000);

            }else{
                $operations_search = array();
                $operationsCredit_search = array();

            }

            $cumul_operations = $mouvementRep->soldeCaisseParDeviseParSiteParType($operation_search, $date1, $date2, $site, $devises);

        }else{
            $operation_search = array();
            $operations_search = array();
            $cumul_operations = array();
            $operationsCredit_search = array();

        }
        $compte_operations = $mouvementRep->compteOperationParPeriodeParSite($date1, $date2, $site);

        $solde_caisses_devises = $mouvementRep->soldeCaisseParDeviseParSite($date1, $date2, $site, $devises);
        return $this->render('logescom/bilan/bilan/detail_operation.html.twig', [ 
            'entreprise' => $entrepriseRep->find(1),
            'site'   => $site,           
            'solde_caisses_devises' => $solde_caisses_devises,
            'date1' => $date1,
            'date2' => $date2,
            'operation_search' => $operation_search ? $operation_search : array(), 
            'op_facturations' => $operations_search,
            'op_facturationsCredits' => $operationsCredit_search,
            'compte_operations' => $compte_operations,
            'cumul_operations' => $cumul_operations,
        ]);
    }

    #[Route('/cloture/{site}', name: 'app_logescom_bilan_cloture')]
    public function cloture(Site $site, Request $request, SessionInterface $session, MouvementCaisseRepository $mouvementRep, UserRepository $userRep, DeviseRepository $deviseRep, CaisseRepository $caisseRep, CategorieOperationRepository $catetgorieOpRep, CompteOperationRepository $compteOpRep, ConfigModepaiementRepository $modePaieRep, ClotureCaisseRepository $clotureRep, EntrepriseRepository $entrepriseRep, EntityManagerInterface $em): Response
    {
        $journee = new DateTime(date("Y-m-d"));
        if ($request->get('montant_reel')) {
            $montant_reel = floatval(preg_replace('/[^-0-9,.]/', '', $request->get('montant_reel')));
            $montant_theo = floatval(preg_replace('/[^-0-9,.]/', '', $request->get('montant_theo')));
            $difference = $montant_theo - $montant_reel;
            $caisse = $caisseRep->find($request->get('id_caisse'));
            $devise = $deviseRep->find($request->get('id_devise'));
            $clotureCaisse = new ClotureCaisse();
            $clotureCaisse->setMontantTheo($montant_theo)
                    ->setMontantReel($montant_reel)
                    ->setDifference($difference)
                    ->setJournee(new \DateTime("now"))
                    ->setDevise($devise)
                    ->setCaisse($caisse)
                    ->setLieuVente($site)
                    ->setSaisiePar($this->getUser())
                    ->setDateSaisie(new \DateTime("now"));
            $mouvementCaisse = new MouvementCaisse();

            $categorie_op = $catetgorieOpRep->find(7);
            $compte_op = $compteOpRep->find(7);
            $mouvementCaisse->setCategorieOperation($categorie_op)
                    ->setCompteOperation($compte_op)
                    ->setTypeMouvement("cloture")
                    ->setMontant(- $difference)
                    ->setModePaie($modePaieRep->find(1))
                    ->setSaisiePar($this->getUser())
                    ->setDevise($devise)
                    ->setCaisse($caisse)
                    ->setEtatOperation('traite')
                    ->setLieuVente($site)
                    ->setDateOperation(new \DateTime("now"))
                    ->setDateSaisie(new \DateTime("now"));
            $clotureCaisse->addMouvementCaiss($mouvementCaisse);
            $em->persist($clotureCaisse);
            $em->flush();
            $this->addFlash("success", "Caisse clôturée avec succés :) ");
            return new RedirectResponse($this->generateUrl('app_logescom_bilan_cloture', ['site' => $site->getId(), 'search' => $request->get("search")]));
        }

        $caisses = $caisseRep->findCaisseByLieuByType($site, 'caisse');
        $devises = $deviseRep->findAll();
        $solde_caisses_especes = $mouvementRep->soldeCaisseGeneralChequesParDeviseParSiteParModePaie($site, $devises, $caisses, 'espèces');
        $caisses_especes_lieu = [];
        foreach ($solde_caisses_especes as $solde) {
            // dd($solde);
            foreach ($caisses as $caisse) {
                if ($solde['id_caisse'] == $caisse->getId()) {
                    $caisses_especes_lieu[$caisse->getNom()][] = $solde;
                } 
            }
        }

        // dd($caisses_especes_lieu);

        $solde_caisses_cheques = $mouvementRep->soldeCaisseGeneralChequesParDeviseParSiteParModePaie($site, $devises, $caisses, 'chèque');
        $caisses_cheques_lieu = [];
        foreach ($solde_caisses_cheques as $solde) {
            foreach ($caisses as $caisse) {
                if ($solde['id_caisse'] == $caisse->getId()) {
                    $caisses_cheques_lieu[$caisse->getNom()][] = $solde;
                } 
            }
        }

        $banques = $caisseRep->findCaisseByLieuByType($site, 'banque');
        $solde_banques = $mouvementRep->soldeBanquesGeneralParDeviseParSite($site, $devises, $banques);
        $banques_lieu = [];
        foreach ($solde_banques as $solde) {
            foreach ($banques as $banque) {
                if ($solde['id_caisse'] == $banque->getId()) {
                    $banques_lieu[$banque->getDesignation()][] = $solde;
                } 
            }
        }


        return $this->render('logescom/bilan/bilan/cloture.html.twig', [
            'solde_caisses_especes' => $caisses_especes_lieu,
            'solde_caisses_cheques' => $caisses_cheques_lieu,
            'solde_banques' => $banques_lieu,
            'liste_clotures' => $clotureRep->findBy(['lieuVente' => $site]),
            'entreprise' => $entrepriseRep->find(1),
            'site'   => $site,
            'liste_caisse' => $caisseRep->findCaisseByLieu($site),
            'devises' => $devises,
        ]);
    }
}
