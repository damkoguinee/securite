<?php

namespace App\Controller\Logescom\Comptable\Caisse;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\Site;
use App\Entity\Developpeur;
use App\Entity\TransfertFond;
use App\Entity\MouvementCaisse;
use App\Form\TransfertFondType;
use App\Repository\CaisseRepository;
use App\Repository\DeviseRepository;
use App\Entity\MouvementCollaborateur;
use App\Repository\EntrepriseRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ConfigDeviseRepository;
use App\Repository\ModePaiementRepository;
use App\Repository\TransfertFondRepository;
use Symfony\Component\Filesystem\Filesystem;
use App\Repository\CompteOperationRepository;
use App\Repository\MouvementCaisseRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\TransfertProductsRepository;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\CategorieOperationRepository;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/logescom/comptable/caisse/caisse')]
class CaisseController extends AbstractController
{
    #[Route('/releve/{site}', name: 'app_logescom_comptable_caisse_releve', methods: ['GET'])]
    public function releveCaisse(MouvementCaisseRepository $mouvementCaisseRep, ConfigDeviseRepository $deviseRep, Request $request, CaisseRepository $caisseRep, Site $site): Response
    {
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

        if ($request->get("date1")){
            $date1 = $request->get("date1");
            $date2 = $request->get("date2");

        }else{
            $date1 = date("Y-01-01");
            $date2 = date("Y-m-d");
        }

        $pageEncours = $request->get('pageEncours', 1);
        
        $operations = $mouvementCaisseRep->findOperationCaisse(site:$site, caisse:$search_caisse, devise:$search_devise, startDate:$date1, endDate:$date2, pageEnCours:$pageEncours, limit:50);

        $solde_generale = $mouvementCaisseRep->findSoldeCaisse(caisse:$search_caisse , devise:$search_devise);
        $solde_selection = 0;

        foreach ($operations['data'] as $operation) {
            $montant = floatval($operation->getMontant());
            $solde_selection += $montant;
        }

        $solde_initial = $mouvementCaisseRep->findSoldeCaisseBeforeStartDate(startDate:$date1, devise:$search_devise, site:$site, caisse:$search_caisse);
        // dd($operations['data'][15]->getVersement()->getReference());
        return $this->render('logescom/comptable/caisse/index.html.twig', [
            
            'site' => $site,
            'liste_caisse' => $caisseRep->findCaisse($site),
            'date1' => $date1,
            'date2' => $date2,
            'search_devise' => $search_devise,
            'search_caisse' => $search_caisse,
            'devises' => $deviseRep->findAll(),
            'operations' => $operations,
            'solde_general' => $solde_generale,
            'solde_selection' => $solde_selection,
            'solde_initial' => $solde_initial

        ]);
    }

    #[Route('/pdf/releve/caisse/{site}', name: 'app_logescom_comptable_caisse_releve_pdf')]
    public function releveCaissePdf(MouvementCaisseRepository $mouvementCaisseRep, ConfigDeviseRepository $deviseRep, Request $request, CaisseRepository $caisseRep, Site $site, EntrepriseRepository $entrepriseRep)
    {       
        $logoPath = $this->getParameter('kernel.project_dir') . '/public/images/img_logos/'.$site->getEntreprise()->getLogo();
        $logoBase64 = base64_encode(file_get_contents($logoPath));
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

        if ($request->get("date1")){
            $date1 = $request->get("date1");
            $date2 = $request->get("date2");

        }else{
            $date1 = date("Y-01-01");
            $date2 = date("Y-m-d");
        }

        $pageEncours = $request->get('pageEncours', 1);
        
        $operations = $mouvementCaisseRep->findOperationCaisse(site:$site, caisse:$search_caisse, devise:$search_devise, startDate:$date1, endDate:$date2, pageEnCours:$pageEncours, limit:500);

        $solde_generale = $mouvementCaisseRep->findSoldeCaisse(caisse:$search_caisse , devise:$search_devise);
        $solde_selection = 0;

        foreach ($operations['data'] as $operation) {
            $montant = floatval($operation->getMontant());
            $solde_selection += $montant;
        }

        $solde_initial = $mouvementCaisseRep->findSoldeCaisseBeforeStartDate(startDate:$date1, devise:$search_devise, site:$site, caisse:$search_caisse);

        $html = $this->renderView('logescom/comptable/caisse/pdf_releve_caisse.html.twig', [        
            'logoPath' => $logoBase64,
            'entreprise' => $entrepriseRep->find(1),
            'site' => $site,
            'date1' => $date1,
            'date2' => $date2,
            'search_devise' => $search_devise,
            'search_caisse' => $search_caisse,
            'operations' => $operations,
            'solde_general' => $solde_generale,
            'solde_selection' => $solde_selection,
            'solde_initial' => $solde_initial
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
            'Content-Disposition' => 'inline; filename=releve_caisse_'.date("d/m/Y à H:i").'".pdf"',
        ]);
    }
}
