<?php

namespace App\Controller\Logescom\Comptable;

use App\Entity\Client;
use App\Entity\Facture;
use App\Entity\HistoriqueChangement;
use App\Entity\Site;
use App\Entity\SmsEnvoyes;
use App\Repository\CaisseRepository;
use App\Repository\ClientRepository;
use App\Repository\ConfigurationSmsRepository;
use App\Repository\ConfigZoneRattachementRepository;
use App\Repository\ContratSurveillanceRepository;
use App\Repository\DetailPaiementFactureRepository;
use App\Repository\FactureRepository;
use App\Service\Comptable\Facture\FactureFinder ;
use App\Service\Comptable\Facture\FactureGenerator;
use App\Service\Comptable\Facture\FactureGrouper;
use App\Service\Comptable\Facture\FacturePdfGenerator;
use App\Service\LogicielService;
use App\Service\OrangeSmsService;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/logescom/comptable/facture')]
final class FactureController extends AbstractController
{
    #[Route('/index/{site}', name: 'app_logescom_comptable_facture_index', methods: ['GET'])]
    public function index(
        FactureRepository $factureRep, 
        Site $site, 
        ContratSurveillanceRepository $contratRep, 
        FactureGrouper $factureGroup, 
        Request $request, 
        ConfigZoneRattachementRepository $zoneRep
    ): Response
    {
         // Récupération des filtres de recherche
        $search = $request->query->get("search", '');
        $date1 = $request->query->get('date1', date("Y-m-01"));
        $date2 = $request->query->get('date2', date("Y-m-d"));

        // filtre par contrat
        $contratId = $request->query->get('id_contrat_search');
        $contrat = $contratId ? $contratRep->find($contratId) : null;

        // pagination et filtre par zone
        $pageEncours = $request->query->get('pageEnCours', 1);
        $zone = $request->get('zone');
        $zones = $zone ? [$zone] : [];

        // recherche des factures
        $factures = $factureRep->findFactureSearch(
            site: $site,
            contrat: $contrat,
            zones: $zones,
            search: $search,
            startDate: $date1,
            endDate: $date2,
            pageEnCours: $pageEncours,
            limit: 100
        );

        // Regroupement des factures par client et mois
        $facturesGroup = $factureGroup->groupClientAndMonth($factures['data']);

        return $this->render('logescom/comptable/facture/index.html.twig', [
            'facturesGroup' => $facturesGroup,
            'factures' => $factures,
            'search' => $search,            
            'site' => $site,
            'date1' => $date1,
            'date2' => $date2,
            'zones' => $zoneRep->findAll()
        ]);
    }

    #[Route('/new/{site}', name: 'app_logescom_comptable_facture_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        Site $site,
        ClientRepository $clientRep,
        FactureGenerator $factureGenerator,
        
    ): Response 
    {
        // 🔍 filtre mois et année et client
        $mois = $request->get('mois');
        $annee = $request->get('annee');
        $clientId = $request->get('client');
        
        if ($mois && $annee) {
            // generation des factures
            $result = $factureGenerator->generateFactures(
                site: $site, 
                mois: $mois, 
                annee: $annee, 
                clientId: $clientId = null, 
                user: $this->getUser()
            );

            // 🔔 message final
            if ($result['count'] > 0) {
                $this->addFlash('success',
                    $result['count']." facture(s) générée(s) pour ".$result['periode']->format('F Y')
                );
            } else {
                $this->addFlash('warning', "Aucune facture à générer pour cette période.");
            }

            return $this->redirectToRoute('app_logescom_comptable_facture_index', [
                'site' => $site->getId()
            ]);

        }

        $clients = $clientRep->findClientBySearch(site: $site, limit: 1000)['data'];
        // Page formulaire
        return $this->render('logescom/comptable/facture/new.html.twig', [
            'site' => $site,
            'clients' => $clients
        ]);
    }




    #[Route('/show/{id}/{site}', name: 'app_logescom_comptable_facture_show', methods: ['GET'])]
    public function show(Facture $facture, Site $site): Response
    {
        return $this->render('logescom/comptable/facture/show.html.twig', [
            'facture' => $facture,
            'site' => $site,
        ]);
    }

    #[Route('/contrat/search/{site}', name: 'app_logescom_facture_contrat_search', methods: ['GET'])]
    public function searchContrat(
        Request $request,
        Site $site,
        ContratSurveillanceRepository $contratRep
    ): JsonResponse {

        $searchContrat = $request->query->get('searchContrat', '');

        $contrats = $contratRep->findContratBySearch(
            search: $searchContrat,
            site: $site
        );

        $response = [];

        foreach ($contrats['data'] as $contrat) {

            $client = $contrat->getBien()->getClient();

            $response[] = [
                'nom' => $client->getNomComplet().' '.$contrat->getBien()->getNom().' '.$client->getTelephone(),
                'id' => $contrat->getId()
            ];
        }

        return $this->json($response);
    }


    #[Route('/confirm/delete/{id}', name: 'app_logescom_comptable_facture_confirm_delete', methods: ['GET', 'POST'])]
    public function confirmDelete(Facture $facture, Request $request): Response
    {
        $param = $request->request->get('param'); // Récupération du paramètre

        $route_suppression = $this->generateUrl('app_logescom_comptable_facture_delete', [
            'id' => $facture->getId(),
            'site' => $facture->getSite()->getId(),
        ]);
        

        return $this->render('logescom/comptable/facture/confirm_delete.html.twig', [
            'route_suppression' => $route_suppression,
            'param' => $param,
            'site' => $facture->getSite(),
            'entreprise' => $facture->getSite()->getEntreprise(),
            'operation' => $facture
        ]);
    }

    #[Route('/delete/{id}/{site}', name: 'app_logescom_comptable_facture_delete', methods: ['POST'])]
    public function delete(
        Request $request, 
        Facture $facture, 
        EntityManagerInterface $entityManager, 
        Site $site): Response
    {
        if ($this->isCsrfTokenValid('delete'.$facture->getId(), $request->request->get('_token'))) {
            if ($facture->getPaiements()->first()) {
                $this->addFlash(
                    "warning", "impossible de supprimer cette facture car elle contient des paiements"
                );
                return $this->redirectToRoute('app_logescom_comptable_facture_show', [
                    'id' => $facture ->getId(),   
                    'site' => $site->getId()
                    ], 
                    Response::HTTP_SEE_OTHER
                );
            }
            $entityManager->remove($facture);

            $this->addFlash("success", "facture supprimé avec succès :)");
        }

        return $this->redirectToRoute('app_logescom_comptable_facture_index', [
            'site' => $site->getId()
        ], Response::HTTP_SEE_OTHER);
    }


    #[Route('/pdf/facture/{site}', name: 'app_logescom_comptable_facture_pdf', methods: ['GET'])]
    public function facturePdf(
        Site $site,
        Request $request,
        FactureGrouper $grouper,
        FacturePdfGenerator $pdfGenerator,
        FactureFinder $finder,
        CaisseRepository $caisseRepository
    ): Response {

        $factures = $finder->findFactures($site, $request);

        $facturesGroup = $grouper->groupFactureForPDF($factures);

        $banques = $caisseRepository->findBy(['type' => 'banque']);

        return $pdfGenerator->generate($facturesGroup, $site, $banques);
    }

    #[Route('/contrat/{site}', name: 'app_logescom_comptable_contrat', methods: ['GET'])]
    public function contrat(
        ContratSurveillanceRepository $contratRep, 
        Site $site, Request $request, 
        ConfigZoneRattachementRepository $zoneRep
    ): Response
    {
         if ($request->get("id_client_search")){
            $search = $request->get("id_client_search");
        }else{
            $search = "";
        }
       
        if ($request->isXmlHttpRequest()) {
            $search = $request->query->get('search');
            $contrats = $contratRep->findContratBySearch(search: $search, site: $site);    
            $response = [];
            foreach ($contrats['data'] as $contrat) {
                $response[] = [
                    'nom' => ($contrat->getbien()->getClient()->getNomComplet()).' '.$contrat->getbien()->getClient()->getTelephone(),
                    'id' => $contrat->getId()
                ]; // Mettez à jour avec le nom réel de votre propriété
            }
            return new JsonResponse($response);
        }


        if ($request->get("id_client_search")){
            $contrats = $contratRep->findContratBySearch(id: $search);
        }else{

            $pageEncours = $request->get('pageEnCours', 1);
            $contrats = $contratRep->findContratBySearch(
                site: $site, statut:['actif'], 
                zones: $request->get('zone') ?? null, 
                pageEnCours: $pageEncours, 
                limit: 100
            );
        }

        // 🔥 Récupération pagination
        $contratsData = $contrats['data'];
        $nbrePages    = $contrats['nbrePages'];
        $pageEnCours  = $contrats['pageEnCours'];
        $limit        = $contrats['limit'];
        $total        = $contrats['total'];
        
        // 🔥 Groupement par client
        $contratsGroup = [];

        foreach ($contratsData as $contrat) {

            // On récupère le client via le bien
            $client = $contrat->getBien()->getClient();
            $clientId = $client ? $client->getId() : 'sans_client';

            if (!isset($contratsGroup[$clientId])) {
                $contratsGroup[$clientId] = [
                    'client'   => $client,
                    'contrats' => []
                ];
            }

            $contratsGroup[$clientId]['contrats'][] = $contrat;
        }

        return $this->render('logescom/comptable/facture/contrat.html.twig', [
            'contratsGroup' => $contratsGroup,
            'nbrePages'     => $nbrePages,
            'pageEnCours'   => $pageEnCours,
            'limit'         => $limit,
            'total'         => $total,
            'site' => $site,
            'zones' => $zoneRep->findAll()
        ]);
    }

    

    #[Route(
    '/contrat/historique/paiement/{client}/{site}', 
    name: 'app_logescom_comptable_contrat_historique_paiement', 
    methods: ['GET']
    )]
    public function historiquePaiementClient(
        Client $client,
        Site $site,
        DetailPaiementFactureRepository $detailPaiementRep,
        ContratSurveillanceRepository $contratRep,
    ): Response {

        $moisListe = range(1, 12);
        $paiementsParAnnee = [];

        // Paiements du client
        $detailPaiements = $detailPaiementRep->findDetailPaiement(site: $site, client: $client);

        foreach ($detailPaiements as $detail) {

            $facture  = $detail->getFacture();
            $contrat  = $facture->getContrat();
            $paiement = $detail->getPaiement();
            $montant  = $detail->getMontant(); // montant affecté à cette facture

            // Année et mois basés sur la facture
            $annee = $facture->getPeriodeDebut()->format('Y');
            $mois  = $facture->getPeriodeDebut()->format('n');
            $contratId = $contrat->getId();

            /** 1️⃣ Crée année si vide */
            if (!isset($paiementsParAnnee[$annee])) {
                $paiementsParAnnee[$annee] = [];
            }

            /** 2️⃣ Crée contrat si vide */
            if (!isset($paiementsParAnnee[$annee][$contratId])) {
                $paiementsParAnnee[$annee][$contratId] = [];

                // Initialise les 12 mois du contrat
                foreach ($moisListe as $m) {
                    $paiementsParAnnee[$annee][$contratId][$m] = [];
                }
            }

            /** 3️⃣ Ajoute l'entrée */
            $paiementsParAnnee[$annee][$contratId][$mois][] = [
                'paiement' => $paiement,
                'facture'  => $facture,
                'montant'  => $montant,
                'contrat' => $contrat
            ];
        }

        // Trie années décroissantes
        krsort($paiementsParAnnee);

        $contrats = $contratRep->findContrat(site: $site, client: $client);
        $contratsById = [];
        foreach ($contrats as $contrat) {
            $contratsById[$contrat->getId()] = $contrat;
        }
        return $this->render('logescom/comptable/facture/contrat_facture.html.twig', [
            'client' => $client,
            'site'   => $site,
            'paiementsParAnnee' => $paiementsParAnnee,
            'contratClients' => $contratsById
        ]);
    }



    #[Route('/facture/impaye/{site}', name: 'app_logescom_comptable_facture_impaye', methods: ['GET'])]
    public function factureImpaye(
        Site $site,
        ClientRepository $clientRep,
        ContratSurveillanceRepository $contratRep,
        FactureRepository $factureRep,
        DetailPaiementFactureRepository $detailPaiementRep,
        Request $request
    ): Response {

        $filtreContratId = $request->query->get('contratId');
        $filtreContrat = $filtreContratId ? $contratRep->find($filtreContratId) : null;

        $search = $request->get('search') ?? NULL ;
        /** 🔎 1. Récupération des contrats du site (filtré par client si besoin) */
        $contrats = $contratRep->findContrat(site: $site, id: $filtreContrat, search: $search);

        /** Conteneurs */
        $impayes = [];
        $moisLabels = [];

        $formatter = new \IntlDateFormatter(
            'fr_FR',
            \IntlDateFormatter::NONE,
            \IntlDateFormatter::NONE,
            null,
            null,
            'LLLL yyyy'
        );

        /** Aujourd’hui : limite de facturation */
        $moisActuel = new \DateTimeImmutable('first day of this month');

        foreach ($contrats as $contrat) {

            $dateDebut = $contrat->getDateDebut();
            $dateFin = $contrat->getDateFin() ?? $moisActuel;

            if ($dateDebut > $moisActuel) continue; // contrat futur → skip

            /** 🗓️ Génération des mois du contrat */
            $start = new \DateTimeImmutable($dateDebut->format('Y-m-01'));
            $end = new \DateTimeImmutable($dateFin->format('Y-m-01'));

            $period = new \DatePeriod($start, new \DateInterval('P1M'), $end->modify('+1 month'));

            /** 🔎 Paiements du contrat : groupés par "YYYY-MM" */
            $details = $detailPaiementRep->findDetailPaiement(contrat:$contrat);
            
            $paiementsParMois = [];
            foreach ($details as $d) {
                $fact = $d->getFacture();
                $key = $fact->getPeriodeDebut()->format("Y-m");

                $paiementsParMois[$key] = ($paiementsParMois[$key] ?? 0) + floatval($d->getMontant());
            }

            /** 🔍 Récupération des factures du contrat */
            $factures = $factureRep->findBy(['contrat' => $contrat]);

            /** Regroupe les factures par mois */
            $facturesParMois = [];
            foreach ($factures as $fact) {
                $key = $fact->getPeriodeDebut()->format("Y-m");

                $facturesParMois[$key] = ($facturesParMois[$key] ?? 0) + floatval($fact->getMontantTotal());
            }

            /** 💡 Calcul impayés par mois */
            foreach ($period as $month) {

                $key = $month->format("Y-m");

                if (!isset($moisLabels[$key])) {
                    $moisLabels[$key] = ucfirst($formatter->format($month));
                }

                $montantFacture = $facturesParMois[$key] ?? 0;
                $montantPaye    = $paiementsParMois[$key] ?? 0;

                $reste = $montantFacture - $montantPaye;

                if ($montantFacture > 0 && $reste > 0) {
                    $impayes[$key][] = [
                        'contrat' => $contrat,
                        'mois'    => $month->format('m'),
                        'annee'   => $month->format('Y'),
                        'facture' => $montantFacture,
                        'paye'    => $montantPaye,
                        'reste'   => $reste,
                    ];
                }
            }
        }
        /** Trie du plus récent au plus ancien */
        krsort($impayes);

        /** 🔎 Recherche des factures non générées */
        foreach ($contrats as $contrat) {

            // $dateDebut = $contrat->getDateDebut();
            $dateDebut = new \DateTime(date("Y-10-01"));
            $dateFinContrat = $contrat->getDateFin();
            $dateFin = $dateFinContrat ? min($dateFinContrat, $moisActuel) : $moisActuel;

            // Contrat futur → aucune facture attendue
            if ($dateDebut > $moisActuel) {
                continue;
            }

            /** 🗓️ Génération de tous les mois depuis le début du contrat */
            $start = new \DateTimeImmutable($dateDebut->format('Y-m-01'));
            $end   = new \DateTimeImmutable($dateFin->format('Y-m-01'));

            $period = new \DatePeriod($start, new \DateInterval('P1M'), $end->modify('+1 month'));

            /** 🔍 Récupérer toutes les factures existantes du contrat */
            $factures = $factureRep->findBy(['contrat' => $contrat]);

            /** Indexer les factures par mois (YYYY-MM) */
            $facturesParMois = [];
            foreach ($factures as $fact) {
                $key = $fact->getPeriodeDebut()->format("Y-m");
                $facturesParMois[$key] = true; // bool suffit
            }

            /** 🧮 Détection des mois sans facture */
            foreach ($period as $month) {

                $key = $month->format('Y-m');

                if (!isset($facturesParMois[$key])) {
                    // FACTURE NON GÉNÉRÉE POUR CE MOIS
                    $facturesNonGenerees[$contrat->getId()][] = [
                        'contrat' => $contrat,
                        'mois'    => (int)$month->format('m'),
                        'annee'   => (int)$month->format('Y'),
                        'label'   => $month->format('F Y'),
                    ];
                }
            }
        }

        return $this->render('logescom/comptable/facture/facture_impaye.html.twig', [
            'site'       => $site,
            'impayes'    => $impayes,
            'moisLabels' => $moisLabels,
            'contrats'    => $filtreContrat ? [$filtreContrat] : $contrats,
            'selectedContrat' => $filtreContratId,
            'facturesNonGenerees' => $facturesNonGenerees ?? NULL,
        ]);
    }




    


}
