<?php

namespace App\Controller\Logescom\Comptable\Bilan;

use DateTime;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\Site;
use App\Entity\ClotureCaisse;
use App\Entity\MouvementCaisse;
use App\Entity\CategorieOperation;
use App\Repository\CuveRepository;
use App\Repository\UserRepository;
use App\Repository\PompeRepository;
use App\Repository\CaisseRepository;
use App\Repository\DepenseRepository;
use App\Repository\RecetteRepository;
use App\Repository\JaugeageRepository;
use App\Repository\VersementRepository;
use App\Repository\CuveRemiseRepository;
use App\Repository\EntrepriseRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\FacturationRepository;
use App\Repository\ChequeEspeceRepository;
use App\Repository\ConfigDeviseRepository;
use App\Repository\DecaissementRepository;
use App\Repository\ClotureCaisseRepository;
use App\Repository\DepotPompisteRepository;
use App\Repository\TransfertFondRepository;
use App\Repository\ProductFactureRepository;
use App\Repository\CompteOperationRepository;
use App\Repository\MouvementCaisseRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\AchatFournisseurRepository;
use App\Repository\AttributionPompeRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\CategorieOperationRepository;
use App\Repository\ConfigModePaiementRepository;
use App\Repository\EchangeConfigDeviseRepository;
use App\Repository\FactureRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Repository\ListeProductAchatFournisseurRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/logescom/comptable/bilan')]
class BilanController extends AbstractController
{
    #[Route('/{site}', name: 'app_logescom_comptable_bilan_index')]
    public function index(Site $site, Request $request, SessionInterface $session, MouvementCaisseRepository $mouvementRep, UserRepository $userRep, ConfigDeviseRepository $deviseRep, CaisseRepository $caisseRep, DepenseRepository $depenseRep, RecetteRepository $recetteRepository, ConfigModePaiementRepository $modePaieRep, FactureRepository $facturationRep): Response
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

        

        $caisses = $caisseRep->findCaisse(site: $site);
        $devises = $deviseRep->findAll();
        if ($request->get("id_personnel")){
            $solde_caisses = $mouvementRep->findSoldeCaisse(personnel: $search, startDate:$date1, endDate:$date2, site: $site, groupByCaisse:true);

            $solde_types = $mouvementRep->findSoldeCaisseByTypeMouvement(personnel: $search, startDate:$date1, endDate:$date2, site: $site);
        }else{

            $solde_caisses = $mouvementRep->findSoldeCaisseGroupByDeviseAndCaisse(startDate:$date1, endDate:$date2, site: $site, devises: $devises, caisses: $caisses);

            $solde_caisses_cheque = $mouvementRep->findSoldeCaisseGroupByDeviseAndCaisse(startDate:$date1, endDate:$date2, site: $site, modePaie: 4, devises: $devises, caisses: $caisses);
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
                            'designation' => $cheque['designation'],
                            'nomDevise' => $cheque['nomDevise'],
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

            if ($request->get("search_caisse")){
                $solde_types = $mouvementRep->findSoldeCaisseByTypeMouvement(startDate:$date1, endDate:$date2, site: $site, devise: $search_devise, caisse: $search_caisse);
            }else{
                $solde_types = $mouvementRep->findSoldeCaisseByTypeMouvement(startDate:$date1, endDate:$date2, site: $site, devise: $search_devise);
            }

        }

        $solde_caisses_devises = $mouvementRep->soldeCaisseGroupByDevise(startDate:$date1, endDate:$date2, site: $site, devises:$devises);

        $solde_caisses_type = $mouvementRep->soldeCaisseGroupByDevise(type:'paiement', startDate:$date1, endDate:$date2, site: $site, devises:$devises);

        $facturations = $facturationRep->findFactureGroup(site: $site, startDate:$date1, endDate: $date2);

        // $chiffre_affaire = $facturationRep->findChiffreAffaire(startDate:$date1, endDate:$date2, site: $site);

        // $facturationPayees = $facturationRep->findFacturationPayees(startDate:$date1, endDate:$date2, site: $site);


        // $nombreDeVentes = $facturationRep->findNombreVente(startDate:$date1, endDate:$date2, site: $site);

        $totauxEncaissements = $mouvementRep->totauxMouvementParTypeMouvement(devises:$devises, startDate:$date1, endDate:$date2, site: $site, typeMouvement:['paiement'], montantPositif:true);

        $totalEntrees = $mouvementRep->totauxMouvementParTypeMouvement(devises:$devises, startDate:$date1, endDate:$date2, site: $site, montantPositif:true);

        $totalEntreesSansTransferts = $mouvementRep->totauxMouvementParTypeMouvement(devises:$devises, startDate:$date1, endDate:$date2, site: $site, typeMouvement:['transfert'], montantPositif:true);

        $totauxDecaissements = $mouvementRep->totauxMouvementParTypeMouvement(devises:$devises, startDate:$date1, endDate:$date2, site: $site, montantPositif:false);

        $totauxDecaissementsSansTransferts = $mouvementRep->totauxMouvementParTypeMouvement(devises:$devises, startDate:$date1, endDate:$date2, site: $site, typeMouvement:['transfert'], montantPositif:false);

        $totalDepenses = $depenseRep->totalDepenses(startDate:$date1, endDate:$date2, site: $site);

        $totalDepensesParDevise = $depenseRep->totalDepenses(startDate:$date1, endDate:$date2, site: $site, devise: $deviseRep->find(1));

        // $beneficeVentes = $commandeProdRep->findBenefice(startDate:$date1, endDate:$date2, site: $site);

        $modesPaie = $modePaieRep->findAll();
        
        $paiements_data = $mouvementRep->findMouvementCaisseParType('paiement', startDate:$date1, endDate:$date2, site: $site, devises:$devises, modesPaie:$modesPaie);

        $paiements = [];
        foreach ($paiements_data as $value) {
            foreach ($modesPaie as $modePaie) {
                if ($value['id_mode_paie'] == $modePaie->getId()) {
                    $paiements[$value['typeMouvement']][$value['typeMouvement']." ".$value['modePaiement']][] = $value;
                }
            }

        }

        $versements_data = $mouvementRep->findMouvementCaisseParType(['versement'], startDate:$date1, endDate:$date2, site: $site, devises:$devises, modesPaie:$modesPaie);

        $versements = [];
        foreach ($versements_data as $value) {
            foreach ($modesPaie as $modePaie) {
                if ($value['id_mode_paie'] == $modePaie->getId()) {
                    $versements[$value['typeMouvement']][$value['typeMouvement']." ".$value['modePaiement']][] = $value;
                }
            }

        }

        $transferts_data = $mouvementRep->findMouvementCaisseParType('transfert', startDate:$date1, endDate:$date2, site: $site, devises:$devises, modesPaie:$modesPaie, montantPositif:true);

        $transferts = [];
        foreach ($transferts_data as $value) {
            foreach ($modesPaie as $modePaie) {
                if ($value['id_mode_paie'] == $modePaie->getId()) {
                    $transferts[$value['typeMouvement']][$value['typeMouvement']." ".$value['modePaiement']][] = $value;
                }
            }

        }

        $transferts_data = $mouvementRep->findMouvementCaisseParType('transfert', startDate:$date1, endDate:$date2, site: $site, devises:$devises, modesPaie:$modesPaie, montantPositif:false);

        $transfertsSortie = [];
        foreach ($transferts_data as $value) {
            foreach ($modesPaie as $modePaie) {
                if ($value['id_mode_paie'] == $modePaie->getId()) {
                    $transfertsSortie[$value['typeMouvement']][$value['typeMouvement']." ".$value['modePaiement']][] = $value;
                }
            }

        }

        $recettes_data = $mouvementRep->findMouvementCaisseParType('recette', startDate:$date1, endDate:$date2, site: $site, devises:$devises, modesPaie:$modesPaie, montantPositif:true);

        $recettes = [];
        foreach ($recettes_data as $value) {
            foreach ($modesPaie as $modePaie) {
                if ($value['id_mode_paie'] == $modePaie->getId()) {
                    $recettes[$value['typeMouvement']][$value['typeMouvement']." ".$value['modePaiement']][] = $value;
                }
            }

        }

        $cuveRemises_data = $mouvementRep->findMouvementCaisseParType(['cuve remise'], startDate:$date1, endDate:$date2, site: $site, devises:$devises, modesPaie:$modesPaie, montantPositif:false);

        $cuveRemises = [];
        foreach ($cuveRemises_data as $value) {
            foreach ($modesPaie as $modePaie) {
                if ($value['id_mode_paie'] == $modePaie->getId()) {
                    
                    $cuveRemises[$value['typeMouvement']][$value['typeMouvement']." ".$value['modePaiement']][] = $value;
                }
            }

        }

        $depenses_data = $mouvementRep->findMouvementCaisseParType(['depense'], startDate:$date1, endDate:$date2, site: $site, devises:$devises, modesPaie:$modesPaie, montantPositif:false);

        $depenses = [];
        foreach ($depenses_data as $value) {
            foreach ($modesPaie as $modePaie) {
                if ($value['id_mode_paie'] == $modePaie->getId()) {
                    
                    $depenses[$value['typeMouvement']][$value['typeMouvement']." ".$value['modePaiement']][] = $value;
                }
            }

        }

        $decaissements_data = $mouvementRep->findMouvementCaisseParType('decaissement', startDate:$date1, endDate:$date2, site: $site, devises:$devises, modesPaie:$modesPaie, montantPositif:false);

        $decaissements = [];
        foreach ($decaissements_data as $value) {
            foreach ($modesPaie as $modePaie) {
                if ($value['id_mode_paie'] == $modePaie->getId()) {
                    $decaissements[$value['typeMouvement']][$value['typeMouvement']." ".$value['modePaiement']][] = $value;
                }
            }

        }

        $avances_data = $mouvementRep->findMouvementCaisseParType('avance salaire', startDate:$date1, endDate:$date2, site: $site, devises:$devises, modesPaie:$modesPaie, montantPositif:false);

        $avances = [];
        foreach ($avances_data as $value) {
            foreach ($modesPaie as $modePaie) {
                if ($value['id_mode_paie'] == $modePaie->getId()) {
                    $avances[$value['typeMouvement']][$value['typeMouvement']." ".$value['modePaiement']][] = $value;
                }
            }

        }

        $salaires_data = $mouvementRep->findMouvementCaisseParType('salaire', startDate:$date1, endDate:$date2, site: $site, devises:$devises, modesPaie:$modesPaie, montantPositif:false);

        $salaires = [];
        foreach ($salaires_data as $value) {
            foreach ($modesPaie as $modePaie) {
                if ($value['id_mode_paie'] == $modePaie->getId()) {
                    $salaires[$value['typeMouvement']][$value['typeMouvement']." ".$value['modePaiement']][] = $value;
                }
            }

        }

        return $this->render('logescom/comptable/bilan/index.html.twig', [
            'solde_caisses' => $caisses_lieu,
            'solde_types' => $solde_types,
            'solde_caisses_devises' => $solde_caisses_devises,
            'solde_caisses_type' => $solde_caisses_type,

            'nombre_ventes' => 0,
            'total_depenses' => $totalDepenses,
            'total_depenses_devise' => $totalDepensesParDevise,
            'benefice_ventes' => 0,
            'chiffre_affaire' => 0,

            'facturations' => $facturations,

            'paiements' => $paiements,
            'paiements_payees' => 0,
            'solde_caisses_type' => $solde_caisses_type,
            'chiffre_affaire' => 0,
            'versements' => $versements,
            'transferts' => $transferts,
            'transfertsSortie' => $transfertsSortie,
            'recettes' => $recettes,
            'decaissements' => $decaissements,
            'depenses' => $depenses,
            'cuveRemises' => $cuveRemises,
            'avances' => $avances,
            'salaires' => $salaires,


            'totauxEncaissements' => $totauxEncaissements,
            'totalEntrees' => $totalEntrees,
            'totalEntreesSansTransferts' => $totalEntreesSansTransferts,
            'totauxDecaissements' => $totauxDecaissements,
            'totauxDecaissementsSansTransferts' => $totauxDecaissementsSansTransferts,


            'site'   => $site,
            'liste_caisse' => $caisseRep->findCaisse($site),
            'search' => $search,
            'search_devise' => $search_devise,
            'search_caisse' => $search_caisse,
            'devises' => $devises,
            'date1' => $date1,
            'date2' => $date2,
        ]);
    }

    #[Route('/bilan/pdf/{site}', name: 'app_logescom_comptable_bilan_pdf')]
    public function bilanPdf(Site $site, Request $request,SessionInterface $session, MouvementCaisseRepository $mouvementRep, ConfigDeviseRepository $deviseRep, CaisseRepository $caisseRep, DepenseRepository $depenseRep, ConfigModePaiementRepository $modePaieRep): Response
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

        

        $caisses = $caisseRep->findCaisse(site: $site);
        $devises = $deviseRep->findAll();
        if ($request->get("id_personnel")){
            $solde_caisses = $mouvementRep->findSoldeCaisse(personnel: $search, startDate:$date1, endDate:$date2, site: $site, groupByCaisse:true);

            $solde_types = $mouvementRep->findSoldeCaisseByTypeMouvement(personnel: $search, startDate:$date1, endDate:$date2, site: $site);
        }else{

            $solde_caisses = $mouvementRep->findSoldeCaisseGroupByDeviseAndCaisse(startDate:$date1, endDate:$date2, site: $site, devises: $devises, caisses: $caisses);

            $solde_caisses_cheque = $mouvementRep->findSoldeCaisseGroupByDeviseAndCaisse(startDate:$date1, endDate:$date2, site: $site, modePaie: 4, devises: $devises, caisses: $caisses);
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
                            'designation' => $cheque['designation'],
                            'nomDevise' => $cheque['nomDevise'],
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

            if ($request->get("search_caisse")){
                $solde_types = $mouvementRep->findSoldeCaisseByTypeMouvement(startDate:$date1, endDate:$date2, site: $site, devise: $search_devise, caisse: $search_caisse);
            }else{
                $solde_types = $mouvementRep->findSoldeCaisseByTypeMouvement(startDate:$date1, endDate:$date2, site: $site, devise: $search_devise);
            }

        }

        $solde_caisses_devises = $mouvementRep->soldeCaisseGroupByDevise(startDate:$date1, endDate:$date2, site: $site, devises:$devises);

        $solde_caisses_type = $mouvementRep->soldeCaisseGroupByDevise(type:'facturation', startDate:$date1, endDate:$date2, site: $site, devises:$devises);


        // $chiffre_affaire = $facturationRep->findChiffreAffaire(startDate:$date1, endDate:$date2, site: $site);

        // $facturationPayees = $facturationRep->findFacturationPayees(startDate:$date1, endDate:$date2, site: $site);


        // $nombreDeVentes = $facturationRep->findNombreVente(startDate:$date1, endDate:$date2, site: $site);

        $totauxEncaissements = $mouvementRep->totauxMouvementParTypeMouvement(devises:$devises, startDate:$date1, endDate:$date2, site: $site, typeMouvement:['facturation'], montantPositif:true);

        $totalEntrees = $mouvementRep->totauxMouvementParTypeMouvement(devises:$devises, startDate:$date1, endDate:$date2, site: $site, montantPositif:true);

        $totalEntreesSansTransferts = $mouvementRep->totauxMouvementParTypeMouvement(devises:$devises, startDate:$date1, endDate:$date2, site: $site, typeMouvement:['transfert'], montantPositif:true);

        $totauxDecaissements = $mouvementRep->totauxMouvementParTypeMouvement(devises:$devises, startDate:$date1, endDate:$date2, site: $site, montantPositif:false);

        $totauxDecaissementsSansTransferts = $mouvementRep->totauxMouvementParTypeMouvement(devises:$devises, startDate:$date1, endDate:$date2, site: $site, typeMouvement:['transfert'], montantPositif:false);

        $totalDepenses = $depenseRep->totalDepenses(startDate:$date1, endDate:$date2, site: $site);

        $totalDepensesParDevise = $depenseRep->totalDepenses(startDate:$date1, endDate:$date2, site: $site, devise: $deviseRep->find(1));

        // $beneficeVentes = $commandeProdRep->findBenefice(startDate:$date1, endDate:$date2, site: $site);

        $modesPaie = $modePaieRep->findAll();
        
        $facturations_data = $mouvementRep->findMouvementCaisseParType('facturation', startDate:$date1, endDate:$date2, site: $site, devises:$devises, modesPaie:$modesPaie);

        $facturations = [];
        foreach ($facturations_data as $value) {
            foreach ($modesPaie as $modePaie) {
                if ($value['id_mode_paie'] == $modePaie->getId()) {
                    $facturations[$value['typeMouvement']][$value['typeMouvement']." ".$value['modePaiement']][] = $value;
                }
            }

        }

        $versements_data = $mouvementRep->findMouvementCaisseParType('versement', startDate:$date1, endDate:$date2, site: $site, devises:$devises, modesPaie:$modesPaie);

        $versements = [];
        foreach ($versements_data as $value) {
            foreach ($modesPaie as $modePaie) {
                if ($value['id_mode_paie'] == $modePaie->getId()) {
                    $versements[$value['typeMouvement']][$value['typeMouvement']." ".$value['modePaiement']][] = $value;
                }
            }

        }

        $transferts_data = $mouvementRep->findMouvementCaisseParType('transfert', startDate:$date1, endDate:$date2, site: $site, devises:$devises, modesPaie:$modesPaie, montantPositif:true);

        $transferts = [];
        foreach ($transferts_data as $value) {
            foreach ($modesPaie as $modePaie) {
                if ($value['id_mode_paie'] == $modePaie->getId()) {
                    $transferts[$value['typeMouvement']][$value['typeMouvement']." ".$value['modePaiement']][] = $value;
                }
            }

        }

        $transferts_data = $mouvementRep->findMouvementCaisseParType('transfert', startDate:$date1, endDate:$date2, site: $site, devises:$devises, modesPaie:$modesPaie, montantPositif:false);

        $transfertsSortie = [];
        foreach ($transferts_data as $value) {
            foreach ($modesPaie as $modePaie) {
                if ($value['id_mode_paie'] == $modePaie->getId()) {
                    $transfertsSortie[$value['typeMouvement']][$value['typeMouvement']." ".$value['modePaiement']][] = $value;
                }
            }

        }

        $recettes_data = $mouvementRep->findMouvementCaisseParType('recette', startDate:$date1, endDate:$date2, site: $site, devises:$devises, modesPaie:$modesPaie, montantPositif:true);

        $recettes = [];
        foreach ($recettes_data as $value) {
            foreach ($modesPaie as $modePaie) {
                if ($value['id_mode_paie'] == $modePaie->getId()) {
                    $recettes[$value['typeMouvement']][$value['typeMouvement']." ".$value['modePaiement']][] = $value;
                }
            }

        }

        $cuveRemises_data = $mouvementRep->findMouvementCaisseParType(['cuve remise'], startDate:$date1, endDate:$date2, site: $site, devises:$devises, modesPaie:$modesPaie, montantPositif:false);

        $cuveRemises = [];
        foreach ($cuveRemises_data as $value) {
            foreach ($modesPaie as $modePaie) {
                if ($value['id_mode_paie'] == $modePaie->getId()) {
                    
                    $cuveRemises[$value['typeMouvement']][$value['typeMouvement']." ".$value['modePaiement']][] = $value;
                }
            }

        }

        $depenses_data = $mouvementRep->findMouvementCaisseParType(['depense'], startDate:$date1, endDate:$date2, site: $site, devises:$devises, modesPaie:$modesPaie, montantPositif:false);

        $depenses = [];
        foreach ($depenses_data as $value) {
            foreach ($modesPaie as $modePaie) {
                if ($value['id_mode_paie'] == $modePaie->getId()) {
                    
                    $depenses[$value['typeMouvement']][$value['typeMouvement']." ".$value['modePaiement']][] = $value;
                }
            }

        }

        $decaissements_data = $mouvementRep->findMouvementCaisseParType('decaissement', startDate:$date1, endDate:$date2, site: $site, devises:$devises, modesPaie:$modesPaie, montantPositif:false);

        $decaissements = [];
        foreach ($decaissements_data as $value) {
            foreach ($modesPaie as $modePaie) {
                if ($value['id_mode_paie'] == $modePaie->getId()) {
                    $decaissements[$value['typeMouvement']][$value['typeMouvement']." ".$value['modePaiement']][] = $value;
                }
            }

        }

        $avances_data = $mouvementRep->findMouvementCaisseParType('avance salaire', startDate:$date1, endDate:$date2, site: $site, devises:$devises, modesPaie:$modesPaie, montantPositif:false);

        $avances = [];
        foreach ($avances_data as $value) {
            foreach ($modesPaie as $modePaie) {
                if ($value['id_mode_paie'] == $modePaie->getId()) {
                    $avances[$value['typeMouvement']][$value['typeMouvement']." ".$value['modePaiement']][] = $value;
                }
            }

        }

        $salaires_data = $mouvementRep->findMouvementCaisseParType('salaire', startDate:$date1, endDate:$date2, site: $site, devises:$devises, modesPaie:$modesPaie, montantPositif:false);

        $salaires = [];
        foreach ($salaires_data as $value) {
            foreach ($modesPaie as $modePaie) {
                if ($value['id_mode_paie'] == $modePaie->getId()) {
                    $salaires[$value['typeMouvement']][$value['typeMouvement']." ".$value['modePaiement']][] = $value;
                }
            }

        }

        // bilan depenses 

        
        $depenses = $depenseRep->findDepenseSearch(site: $site, startDate: $date1, endDate: $date2);

        $cumulDepenses = $depenseRep->totalDepenses(site: $site, startDate: $date1, endDate: $date2, alwaysGroupByDevise: true);
        
        // Grouper les dépenses par catégorie
        $depensesGroupeesParCategorie = [];
        foreach ($depenses['data'] as $dep) {
            $categorieDepense = $dep->getCategorieDepense()->getNom(); // Assume que getNom() retourne le nom de la catégorie
            if (!isset($depensesGroupeesParCategorie[$categorieDepense])) {
                $depensesGroupeesParCategorie[$categorieDepense] = [];
            }
            $depensesGroupeesParCategorie[$categorieDepense][] = $dep;
        }

        $etablissement = $site->getEntreprise();
        $logoPath = $this->getParameter('kernel.project_dir') . '/public/images/img_logos/'.$etablissement->getLogo();
        $logoBase64 = base64_encode(file_get_contents($logoPath));

        $html = $this->renderView('logescom/comptable/bilan/bilan_pdf.html.twig', [
            'logoPath' => $logoBase64,
            'solde_caisses' => $caisses_lieu,
            'solde_types' => $solde_types,
            'solde_caisses_devises' => $solde_caisses_devises,
            'solde_caisses_type' => $solde_caisses_type,

            'nombre_ventes' => 0,
            'total_depenses' => $totalDepenses,
            'total_depenses_devise' => $totalDepensesParDevise,
            'benefice_ventes' => 0,
            'chiffre_affaire' => 0,

            'facturations' => $facturations,
            'facturations_payees' => 0,
            'solde_caisses_type' => $solde_caisses_type,
            'chiffre_affaire' => 0,
            'versements' => $versements,
            'transferts' => $transferts,
            'transfertsSortie' => $transfertsSortie,
            'recettes' => $recettes,
            'decaissements' => $decaissements,
            'depenses' => $depenses,
            'cuveRemises' => $cuveRemises,
            'avances' => $avances,
            'salaires' => $salaires,


            'totauxEncaissements' => $totauxEncaissements,
            'totalEntrees' => $totalEntrees,
            'totalEntreesSansTransferts' => $totalEntreesSansTransferts,
            'totauxDecaissements' => $totauxDecaissements,
            'totauxDecaissementsSansTransferts' => $totauxDecaissementsSansTransferts,


            'site'   => $site,
            'liste_caisse' => $caisseRep->findCaisse($site),
            'devises' => $devises,
            'date1' => $date1,
            'date2' => $date2,


            'depensesGroupeesParCategorie' => $depensesGroupeesParCategorie,
            'cumulDepenses' => $cumulDepenses,
        ]);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set("isPhpEnabled", true);
        $options->set("isHtml5ParserEnabled", true);
    
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
    
        // return new Response($dompdf->output(), 200, [
        //     'Content-Type' => 'application/pdf',
        //     'Content-Disposition' => 'attachment; filename="bilan_'.$site->getNom().'_'.$date1.'_'.$date2.'.pdf"',
        // ]);

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="bilan_' . $site->getNom() . '_' . str_replace(['/', ':'], '-', $date1 . '_' . $date2) . '.pdf"',
        ]);

    }

    #[Route('/etat/{site}', name: 'app_logescom_comptable_bilan_etat_caisse')]
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
        return $this->render('logescom/comptable/bilan/etat_caisse.html.twig', [
            'solde_caisses' => $caisses_lieu,
            'solde_caisses_devises' => $solde_caisses_devises,
            'entreprise' => $entrepriseRep->find(1),
            'site'   => $site,
            'devises' => $devises,

        ]);
    }
}
