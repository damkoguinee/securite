<?php

namespace App\Controller\Logescom\Comptable\Compte;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\User;
use App\Entity\Devise;
use App\Entity\site;
use App\Repository\UserRepository;
use App\Repository\ClientRepository;
use App\Repository\PersonelRepository;
use App\Repository\EntrepriseRepository;
use App\Repository\ConfigDeviseRepository;
use App\Repository\ConfigRegionRepository;
use App\Repository\MouvementCaisseRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\ConfigZoneAdresseRepository;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ConfigDivisionLocaleRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\ConfigurationLogicielRepository;
use App\Repository\MouvementCollaborateurRepository;
use App\Repository\ConfigRegionAdministrativeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/logescom/comptable/compte/compte/collaborateur')]
class CompteCollaborateurController extends AbstractController
{
    #[Route('/{site}', name: 'app_logescom_comptable_compte_compte_collaborateur')]
    public function index(site $site, Request $request, MouvementCollaborateurRepository $mouvementRep, ConfigDeviseRepository $deviseRep, ClientRepository $clientRep, UserRepository $userRep, ConfigRegionAdministrativeRepository $regionRep, PersonelRepository $personnelRep, ConfigDivisionLocaleRepository $divisionRep, ConfigZoneAdresseRepository $zoneRep): Response
    {
        if ($request->get("id_client_search")){
            $search = $request->get("id_client_search");
        }else{
            $search = "";
        }

        if ($request->get("date1")){
            $date1 = $request->get("date1");
            $date2 = $request->get("date2");

        }else{
            $date1 = date("Y-01-01");
            $date2 = date("Y-m-d");
        }
        $type1 = $request->get('type1') ? $request->get('type1') : 'client';
        $type2 = $request->get('type2') ? $request->get('type2') : 'client';
        $regions = $regionRep->findBy([], ['nom' => 'ASC']);


        if ($request->isXmlHttpRequest()) {
            $search = $request->query->get('search');
            $clients = $clientRep->findClientBySearch(search: $search, site: $site)['data'];
            $personnels = $personnelRep->findUserBySearch(search: $search, site: $site);

            $utilisateurs = array_merge($clients, $personnels);       
            $response = [];
            foreach ($utilisateurs as $client) {
                $response[] = [
                    'nom' => ucwords($client->getPrenom())." ".strtoupper($client->getNom()),
                    'id' => $client->getId()
                ]; // Mettez à jour avec le nom réel de votre propriété
            }
            return new JsonResponse($response);
        }
        $devises = $deviseRep->findAll();
        
        if (($type1 == 'personnel' and $type2 == 'personnel') or $request->get('id_personnel')) {
            
            if ($request->get("id_personnel")) {
                $clients = $personnelRep->findUserBySearch(id: $request->get("id_personnel"), site: $site);

            }elseif ($request->get("region")) {
                $clients = $personnelRep->findUserBySearch(site: $site, region: $request->get("region"));
            }else{
                $clients = $personnelRep->findUserBySearch(site: $site);

    
            }

        }else{

            if ($request->get("id_client_search")) {
                $clients = $clientRep->findClientBySearch(id: $request->get("id_client_search"), typeUser: ['client'], site: $site)['data'];

            }else{
                $clients = $clientRep->findClientBySearch(typeUser: [$type1, $type2], site: $site, limit: 1000)['data'];
            }
        }
        $comptes = [];
        foreach ($clients as $client) {
           $comptes[] = [
                'collaborateur' => $client,
                'soldes' => $mouvementRep->findSoldeCompteCollaborateur($client, $devises, $site)
            ];
        }

        $solde_general_type = [];
        foreach ($comptes as $compte) {
            foreach ($compte['soldes'] as $solde) {
                $devise = $solde['devise'];
                $montant = (float) $solde['montant'];

                if (!isset($solde_general_type[$devise])) {
                    // Initialisation de la devise si elle n'existe pas encore
                    $solde_general_type[$devise] = 0.0;
                }

                // Cumul des montants pour chaque devise
                $solde_general_type[$devise] += $montant;
            }
        }

        // Reformater le tableau pour correspondre à la structure attendue
        $solde_general_type = array_map(
            fn($montant, $devise) => ['montant' => number_format($montant, 2, '.', ''), 'devise' => $devise],
            $solde_general_type,
            array_keys($solde_general_type)
        );


        if ($request->get("region")){
            $region_find = $regionRep->find($request->get("region"));
        }else{
            $region_find = array();
        }
        return $this->render('logescom/comptable/compte/compte_collaborateur/index.html.twig', [
            'site' => $site,
            'search' => $search,
            'comptes' => $comptes,
            'devises'   => $devises,
            'regions' => $regions,
            'region_find' => $region_find,
            'type1' => $type1,
            'type2' => $type2,
            'solde_general_type' => $solde_general_type,
        ]);
    }

    #[Route('/detail/{site}', name: 'app_logescom_comptable_compte_compte_collaborateur_detail')]
    public function detailCompte(site $site, UserRepository $userRep, Request $request, MouvementCollaborateurRepository $mouvementRep, ConfigDeviseRepository $deviseRep): Response
    {
        if ($request->get("date1")){
            $date1 = $request->get("date1");
            $date2 = $request->get("date2");

        }else{
            $date1 = date("Y-01-01");
            $date2 = date("Y-m-d");
        }
        $user = $userRep->find($request->get('user'));
        $devise = $deviseRep->findOneBy(['nom' => $request->get('devise')]);
        $pageEncours = $request->get('pageEncours', 1);

        $mouvements = $mouvementRep->findSoldeDetailByCollaborateur(collaborateur:$user, devise:$devise, startDate:$date1, endDate:$date2, pageEnCours:$pageEncours, limit:20000, site:$site);
        
        $solde_init = $mouvementRep->findSumMontantBeforeStartDate(collaborateur:$user, devise:$devise, startDate:$date1, site:$site);
        
        return $this->render('logescom/comptable/compte/compte_collaborateur/detail_compte.html.twig', [
            'site' => $site,
            'mouvements' =>$mouvements,
            'solde_init' => $solde_init,
            'user' => $user,
            'devise' => $devise,
        ]);
    }

    #[Route('/pdf/compte/{site}', name: 'app_logescom_comptable_compte_compte_collaborateur_pdf_compte')]
    public function ComptePdf(Request $request, site $site, MouvementCollaborateurRepository $mouvementRep, ConfigDeviseRepository $deviseRep, ClientRepository $clientRep, UserRepository $userRep, ConfigRegionAdministrativeRepository $regionRep, PersonelRepository $personnelRep)
    {       
        $logoPath = $this->getParameter('kernel.project_dir') . '/public/images/img_logos/'.$site->getEntreprise()->getLogo();
        $logoBase64 = base64_encode(file_get_contents($logoPath));
        if ($request->get("id_client_search")){
            $search = $request->get("id_client_search");
        }else{
            $search = "";
        }

        if ($request->get("date1")){
            $date1 = $request->get("date1");
            $date2 = $request->get("date2");

        }else{
            $date1 = date("Y-01-01");
            $date2 = date("Y-m-d");
        }
        $type1 = $request->get('type1') ? $request->get('type1') : 'client';
        $type2 = $request->get('type2') ? $request->get('type2') : 'client-fournisseur';

        $regions = $regionRep->findBy([], ['nom' => 'ASC']);
        $devises = $deviseRep->findAll();
        
        if (($type1 == 'personnel' and $type2 == 'personnel') or $request->get('id_personnel')) {
            
            if ($request->get("id_personnel")) {
                $clients = $personnelRep->findUserBySearch(id: $request->get("id_personnel"), site: $site);

            }elseif ($request->get("region")) {
                $clients = $personnelRep->findUserBySearch(site: $site, region: $request->get("region"));
            }else{
                $clients = $personnelRep->findUserBySearch(site: $site);
            }

        }else{

            if ($request->get("id_client_search")) {
                $clients = $clientRep->findClientBySearch(id: $request->get("id_client_search"), typeUser: ['client'], site: $site);

            }elseif ($request->get("region")) {
                $clients = $clientRep->findClientBySearch(site: $site, region: $request->get("region"));
            }else{
                $clients = $clientRep->findClientBySearch(typeUser: [$type1, $type2], site: $site, limit: 1000);
            }
        }

        $comptes = [];
        foreach ($clients as $client) {
            $mouvements = $mouvementRep->findSoldeCompteCollaborateur($client, $devises, $site);
               // Filtrer les mouvements pour ne garder que ceux avec au moins un montant différent de zéro
            $filteredMouvements = array_filter($mouvements, function($mouvement) {
                return $mouvement['montant'] != 0;
            });
            
            // Si des mouvements non nuls existent
            if (!empty($filteredMouvements)) {
                foreach ($devises as $devise) {
                    // Ajouter le nom de devise à la liste
                    $codesDevises[] = $devise->getNom();
                }
                
                // Obtenir les codes de devise uniques à partir des mouvements filtrés
                $devisesExistants = array_column($filteredMouvements, 'devise');
                
                // Combiner les devises existantes avec les devises de référence
                $devisesPossibles = array_unique(array_merge($codesDevises, $devisesExistants));
                // Ajouter les montants manquants avec une valeur de 0.00
                $updatedMouvements = [];
                foreach ($devisesPossibles as $devise) {
                    $montant = '0.00';
                    foreach ($filteredMouvements as $mouvement) {
                        if ($mouvement['devise'] === $devise) {
                            $montant = $mouvement['montant'];
                            break;
                        }
                    }
                    $updatedMouvements[] = [
                        'montant' => $montant,
                        'devise' => $devise
                    ];
                }
                
                // Vérifier si au moins un montant est différent de zéro
                $hasNonZeroMontant = false;
                foreach ($updatedMouvements as $mouvement) {
                    if ($mouvement['montant'] != '0.00') {
                        $hasNonZeroMontant = true;
                        break;
                    }
                }
                
                // Ajouter seulement si des mouvements non nuls existent
                if ($hasNonZeroMontant) {
                    $comptes[] = [
                        'collaborateur' => $client,
                        'soldes' => $updatedMouvements
                    ];
                }
            }
        }

        if ($request->get("region")){
            $region_find = $regionRep->find($request->get("region"));
        }else{
            $region_find = array();
        }
        $solde_general_type = [];
        
        foreach ($comptes as $compte) {
            foreach ($compte['soldes'] as $solde) {
                $devise = $solde['devise'];
                $montant = (float) $solde['montant'];

                if (!isset($solde_general_type[$devise])) {
                    // Initialisation de la devise si elle n'existe pas encore
                    $solde_general_type[$devise] = 0.0;
                }

                // Cumul des montants pour chaque devise
                $solde_general_type[$devise] += $montant;
            }
        }

        // Reformater le tableau pour correspondre à la structure attendue
        $solde_general_type = array_map(
            fn($montant, $devise) => ['montant' => number_format($montant, 2, '.', ''), 'devise' => $devise],
            $solde_general_type,
            array_keys($solde_general_type)
        );
        
        $html = $this->renderView('logescom/comptable/compte/compte_collaborateur/pdf_compte.html.twig', [
            'comptes' => $comptes,
            'devises'   => $devises,
            'regions' => $regions,
            'region_find' => $region_find,
            'type1' => $type1,
            'type2' => $type2,
            'solde_general_type' => $solde_general_type,          
            'logoPath' => $logoBase64,
            'site' => $site,
        ]);

        // Configurez Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set("isPhpEnabled", true);
        $options->set("isHtml5ParserEnabled", true);

        // Instancier Dompdf
        $dompdf = new Dompdf($options);

        // Charger le contenu HTML
        $dompdf->loadHtml($html);

        // Définir la taille du papier (A4 par défaut)
        $dompdf->setPaper('A4', 'portrait');

        // Rendre le PDF (stream le PDF au navigateur)
        $dompdf->render();

        // Renvoyer une réponse avec le contenu du PDF
        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename=compte_'.date("d/m/Y à H:i").'".pdf"',
        ]);
    }


    #[Route('/pdf/detail/compte/{site}', name: 'app_logescom_comptable_compte_compte_collaborateur_pdf_detail_compte')]
    public function detailComptePdf(Request $request, site $site, UserRepository $userRep, ConfigDeviseRepository $deviseRep,  MouvementCollaborateurRepository $mouvementRep)
    {       
        $logoPath = $this->getParameter('kernel.project_dir') . '/public/images/img_logos/'.$site->getEntreprise()->getLogo();
        $logoBase64 = base64_encode(file_get_contents($logoPath));

        if ($request->get("date1")){
            $date1 = $request->get("date1");
            $date2 = $request->get("date2");

        }else{
            $date1 = date("Y-01-01");
            $date2 = date("Y-m-d");
        }
        $user = $userRep->find($request->get('user'));
        $devise = $deviseRep->findOneBy(['nom' => $request->get('devise')]);
        $pageEncours = $request->get('pageEncours', 1);

        $mouvements = $mouvementRep->findSoldeDetailByCollaborateur(collaborateur:$user, devise:$devise, startDate:$date1, endDate:$date2, pageEnCours:$pageEncours, limit:20000, site:$site);
        
        $solde_init = $mouvementRep->findSumMontantBeforeStartDate(collaborateur:$user, devise:$devise, startDate:$date1, site:$site);

        $html = $this->renderView('logescom/comptable/compte/compte_collaborateur/pdf_detail_compte.html.twig', [
            'mouvements' => $mouvements,
            'solde_init' => $solde_init,
            'user' => $user,
            'devise' => $devise,
            'date1' => $date1,            
            'date2' => $date2,           
            'logoPath' => $logoBase64,
            'site' => $site,
        ]);

        // Configurez Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set("isPhpEnabled", true);
        $options->set("isHtml5ParserEnabled", true);

        // Instancier Dompdf
        $dompdf = new Dompdf($options);

        // Charger le contenu HTML
        $dompdf->loadHtml($html);

        // Définir la taille du papier (A4 par défaut)
        $dompdf->setPaper('A4', 'portrait');

        // Rendre le PDF (stream le PDF au navigateur)
        $dompdf->render();

        // Renvoyer une réponse avec le contenu du PDF
        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename=détail_compte_'.date("d/m/Y à H:i").'".pdf"',
        ]);
    }

    #[Route('/inactif/{site}', name: 'app_logescom_comptable_compte_compte_collaborateur_inactif')]
    public function compteInactif(site $site, Request $request, MouvementCollaborateurRepository $mouvementRep, ConfigDeviseRepository $deviseRep, ClientRepository $clientRep, UserRepository $userRep, PersonelRepository $personnelRep): Response
    {
        if ($request->get("id_client_search")){
            $search = $request->get("id_client_search");
        }else{
            $search = "";
        }

        $type1 = $request->get('type1') ? $request->get('type1') : 'client';
        $type2 = $request->get('type2') ? $request->get('type2') : 'client-fournisseur';

        if ($request->isXmlHttpRequest()) {
            $search = $request->query->get('search');
            if ($request->query->get('search_personnel')) {
                $clients = $userRep->findPersonnelSearchByLieu($search, $site);    
            }else{

                $clients = $userRep->findClientSearchByLieu($search, $site);    
            }
            $response = [];
            foreach ($clients as $client) {
                $response[] = [
                    'nom' => ($client->getClients()[0]->getSociete() ? ucwords(($client->getClients()[0]->getSociete() )) : ucwords($client->getPrenom())." ".strtoupper($client->getNom()."  Ref: ".strtoupper($client->getReference()))),
                    'id' => $client->getId()
                ]; // Mettez à jour avec le nom réel de votre propriété
            }
            return new JsonResponse($response);
        }

        if ($request->get("limit")){
            $limit = $request->get("limit");
        }else{
            $limit = 30;
        } 
        $devises = $deviseRep->findBy(['id' => 1]);
        
        

        if ($request->get("id_client_search")) {
            $clients = $clientRep->findClientSearchByTypeByLieu($type1, $type2, $site, $request->get("id_client_search"));

        }elseif ($request->get("region")) {

            $clients = $clientRep->findClientSearchByTypeByLieuByRegion($type1, $type2, $site, $request->get("region"));

        }else{
            
            $clients = $clientRep->findClientByTypeByLieu($type1, $type2, $site);    
        }
        
        $comptesInactifs = [];
        foreach ($clients as $client) {
            $soldes = $mouvementRep->comptesInactif($client, $limit, $devises);
            if ($soldes) {
                $comptesInactifs[] = [
                    'collaborateur' => $client,
                    'soldes' => $mouvementRep->comptesInactif($client, $limit, $devises),
                    'derniereOp' => $mouvementRep->findOneBy(['collaborateur' => $client], ['id' => 'DESC'])
                ];
            }
        }

        if ($request->get("region")){
            $region_find = array();
        }else{
            $region_find = array();
        }
        return $this->render('logescom/comptable/compte/compte_collaborateur/compte_inactif.html.twig', [
            'entreprise' => $entrepriseRep->find(1),
            'site' => $site,
            'search' => $search,
            'comptes' => $comptesInactifs,
            'devises'   => $devises,
            'region_find' => $region_find,
            'type1' => $type1,
            'type2' => $type2,
            'limit' => $limit,
        ]);
    }
}
