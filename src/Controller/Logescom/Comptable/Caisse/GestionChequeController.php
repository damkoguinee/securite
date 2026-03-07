<?php

namespace App\Controller\Logescom\Comptable\Caisse;

use App\Entity\Site;
use App\Entity\LieuxVentes;
use App\Entity\Decaissement;
use App\Entity\GestionCheque;
use App\Entity\TransfertFond;
use App\Entity\MouvementCaisse;
use App\Repository\UserRepository;
use App\Repository\CaisseRepository;
use App\Repository\DeviseRepository;
use App\Entity\MouvementCollaborateur;
use App\Repository\EntrepriseRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\LieuxVentesRepository;
use App\Repository\ModePaiementRepository;
use App\Repository\GestionChequeRepository;
use App\Repository\TransfertFondRepository;
use App\Repository\CompteOperationRepository;
use App\Repository\MouvementCaisseRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\CategorieOperationRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/logescom/comptable/caisse/comptable/cheque')]
class GestionChequeController extends AbstractController
{
    #[Route('/accueil/{site}', name: 'app_logescom_comptable_caisse_comptable_cheque_index')]
    public function index(MouvementCaisseRepository $mouvementCaisseRep, CaisseRepository $caisseRep, UserRepository $userRep, Request $request, Site $site): Response
    {
        if ($request->get("search_cheque")){
            $search = $request->get("search_cheque");
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

        $pageEncours = $request->get('pageEnCours', 1);
        $caisses = $caisseRep->findCaisse(site:$site, type:['caisse']);
        if ($request->get("search_cheque")){
            $cheques = $mouvementCaisseRep->findChequeCaisse(site: $site, caisses: $caisses, modePaie: [4], startDate:$date1, endDate:$date2, bordereau: $search, pageEnCours:$pageEncours, limit: 50);
        }else{
            $cheques = $mouvementCaisseRep->findChequeCaisse(site: $site, caisses: $caisses, modePaie: [4], startDate:$date1, endDate:$date2, pageEnCours:$pageEncours, limit: 50);

        }

        $pageEncours = $request->get('pageEncoursTraites', 1);
        $cheques_traites = $mouvementCaisseRep->findChequeCaisse(site: $site, modePaie: [4], etatOperation:['traité', 'en attente'], startDate:$date1, endDate:$date2, pageEnCours:$pageEncours, limit: 50);


        return $this->render('logescom/comptable/caisse/comptable_cheque/index.html.twig', [
            'cheques' => $cheques,
            'cheques_traites' => $cheques_traites,
            'search' => $search,
            
            'site' => $site,
            'date1' => $date1,
            'date2' => $date2,
        ]);
    }

    #[Route('/show/{id}/{site}', name: 'app_logescom_comptable_caisse_comptable_cheque_show')]
    public function show(MouvementCaisse $mouvementCaisse, MouvementCaisseRepository $mouvementCaisseRep, CaisseRepository $caisseRep, UserRepository $userRep, Request $request, CategorieOperationRepository $catetgorieOpRep, CompteOperationRepository $compteOpRep, TransfertFondRepository $transfertRep, Site $site, LieuxVentesRepository $lieuVenteRep, DeviseRepository $deviseRep, GestionChequeRepository $comptableChequeRep, ModePaiementRepository $modePaieRep, EntityManagerInterface $em): Response
    {
        $categorie_op = $catetgorieOpRep->find(3);
        $compte_op = $compteOpRep->find(1);

        $comptable_cheque = $comptableChequeRep->findOneBy(['mouvementCaisse' => $mouvementCaisse]);

        if ($request->get('caisse_recep')) {
            $caisse_recep = $caisseRep->find($request->get('caisse_recep'));
            if ($comptable_cheque) {
                $comptable_cheque->setMontant($mouvementCaisse->getMontant())
                        ->setCaisseDepart($mouvementCaisse->getCaisse())
                        ->setCaisseReception($caisse_recep)
                        ->setSiteDepart($site)
                        ->setSiteReception($site)
                        ->setEnvoyePar($this->getUser())
                        ->setTraitePar($this->getUser())
                        ->setDateOperation(new \DateTime("now"))
                        ->setDateSaisie(new \DateTime("now"))
                        ->setDateReception(new \DateTime("now"))
                        ->setEtat("traité");
            }else{
                $comptable_cheque = new GestionCheque();
                $comptable_cheque->setMontant($mouvementCaisse->getMontant())
                        ->setCaisseDepart($mouvementCaisse->getCaisse())
                        ->setCaisseReception($caisse_recep)
                        ->setSiteDepart($site)
                        ->setSiteReception($site)
                        ->setEnvoyePar($this->getUser())
                        ->setTraitePar($this->getUser())
                        ->setDateOperation(new \DateTime("now"))
                        ->setDateSaisie(new \DateTime("now"))
                        ->setDateReception(new \DateTime("now"))
                        ->setEtat("traité");
                $mouvementCaisse->addGestionCheque($comptable_cheque);
            }
                    
            $mouvementCaisse->setEtatOperation("traité")
                    ->setDateSortie(new \DateTime("now"))
                    ->setDetailSortie("chèque transféré de : " .$mouvementCaisse->getCaisse()->getDesignation()." vers ".$caisse_recep->getDesignation())
                    ->setDateOperation(new \DateTime("now"))
                    ->setDateSaisie(new \DateTime("now"))
                    ->setSaisiePar($this->getUser())
                    ->setCaisse($caisse_recep)
                    ;
            $em->persist($mouvementCaisse);
            $em->flush();

            $this->addFlash("success", "Transfert enregistré avec succès :)");
            return $this->redirectToRoute('app_logescom_comptable_caisse_comptable_cheque_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
        }

        if ($request->get('caisse_recep_especes')) {
            $caisse_recep_especes = $caisseRep->find($request->get('caisse_recep_especes'));
            if ($comptable_cheque) {
                $comptable_cheque->setMontant($mouvementCaisse->getMontant())
                        ->setCaisseDepart($mouvementCaisse->getCaisse())
                        ->setCaisseReception($caisse_recep_especes)
                        ->setSiteDepart($site)
                        ->setSiteReception($site)
                        ->setEnvoyePar($this->getUser())
                        ->setTraitePar($this->getUser())
                        ->setDateOperation(new \DateTime("now"))
                        ->setDateSaisie(new \DateTime("now"))
                        ->setDateReception(new \DateTime("now"))
                        ->setEtat("traité");
            }else{
                $comptable_cheque = new GestionCheque();
                $comptable_cheque->setMontant($mouvementCaisse->getMontant())
                        ->setCaisseDepart($mouvementCaisse->getCaisse())
                        ->setCaisseReception($caisse_recep_especes)
                        ->setSiteDepart($site)
                        ->setSiteReception($site)
                        ->setEnvoyePar($this->getUser())
                        ->setTraitePar($this->getUser())
                        ->setDateOperation(new \DateTime("now"))
                        ->setDateSaisie(new \DateTime("now"))
                        ->setDateReception(new \DateTime("now"))
                        ->setEtat("traité");
                $mouvementCaisse->addGestionCheque($comptable_cheque);
            }
                    
            $mouvementCaisse->setEtatOperation("traité")
                    ->setDateSortie(new \DateTime("now"))
                    ->setDetailSortie("chèque espèces : " .$mouvementCaisse->getCaisse()->getDesignation()." vers ".$caisse_recep_especes->getDesignation())
                    ->setDateOperation(new \DateTime("now"))
                    ->setDateSaisie(new \DateTime("now"))
                    ->setModePaie($modePaieRep->find(1))
                    ->setSaisiePar($this->getUser())
                    ->setCaisse($caisse_recep_especes)
                    ;
            $em->persist($mouvementCaisse);
            $em->flush();

            $this->addFlash("success", "Transfert enregistré avec succès :)");
            return $this->redirectToRoute('app_logescom_comptable_caisse_comptable_cheque_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
        }

        if ($request->get('lieu_recep')) {
            $lieu_recep = $lieuVenteRep->find($request->get('lieu_recep'));
            

            $dateDuJour = new \DateTime();
            $referenceDate = $dateDuJour->format('ymd');
            $idSuivant =($transfertRep->findMaxId() + 1);
            $reference = "trans".$referenceDate . sprintf('%04d', $idSuivant);

            if ($comptable_cheque) {
                $comptable_cheque->setMontant($mouvementCaisse->getMontant())
                        ->setCaisseDepart($mouvementCaisse->getCaisse())
                        ->setSiteDepart($site)
                        ->setSiteReception($lieu_recep)
                        ->setEnvoyePar($this->getUser())
                        ->setDateOperation(new \DateTime("now"))
                        ->setDateSaisie(new \DateTime("now"))
                        ->setDateReception(new \DateTime("now"))
                        ->setEtat("non traité");
            }else{
                
                $comptable_cheque = new GestionCheque();
                $comptable_cheque->setMontant($mouvementCaisse->getMontant())
                        ->setCaisseDepart($mouvementCaisse->getCaisse())
                        ->setSiteDepart($site)
                        ->setSiteReception($lieu_recep)
                        ->setEnvoyePar($this->getUser())
                        ->setDateOperation(new \DateTime("now"))
                        ->setDateSaisie(new \DateTime("now"))
                        ->setDateReception(new \DateTime("now"))
                        ->setEtat("non traité");
                $mouvementCaisse->addGestionCheque($comptable_cheque);
            }
                    
            $mouvementCaisse->setEtatOperation("en attente")
                    ->setDateSortie(new \DateTime("now"))
                    ->setDetailSortie("chèque transféré de : " .$mouvementCaisse->getCaisse()->getDesignation()." vers ".$lieu_recep->getLieu()." ")
                    ->setDateOperation(new \DateTime("now"))
                    ->setDateSaisie(new \DateTime("now"))
                    ->setSaisiePar($this->getUser())
                    ;
            $em->persist($mouvementCaisse);
            $em->flush();

            $this->addFlash("success", "Transfert enregistré avec succès :)");
            return $this->redirectToRoute('app_logescom_comptable_caisse_comptable_cheque_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
        }

        if ($request->get('collaborateur')) {
            $collaborateur = $userRep->find($request->get('collaborateur'));

            if ($comptable_cheque) {
                $comptable_cheque->setMontant($mouvementCaisse->getMontant())
                        ->setCaisseDepart($mouvementCaisse->getCaisse())
                        ->setCaisseReception(null)
                        ->setCollaborateur($collaborateur)
                        ->setSiteDepart($site)
                        ->setSiteReception(null)
                        ->setEnvoyePar($this->getUser())
                        ->setTraitePar($this->getUser())
                        ->setDateOperation(new \DateTime("now"))
                        ->setDateSaisie(new \DateTime("now"))
                        ->setDateReception(new \DateTime("now"))
                        ->setEtat("traité");
            }else{
                $comptable_cheque = new GestionCheque();
                $comptable_cheque->setMontant($mouvementCaisse->getMontant())
                        ->setCaisseDepart($mouvementCaisse->getCaisse())
                        ->setCaisseReception(null)
                        ->setCollaborateur($collaborateur)
                        ->setSiteDepart($site)
                        ->setSiteReception(null)
                        ->setEnvoyePar($this->getUser())
                        ->setTraitePar($this->getUser())
                        ->setDateOperation(new \DateTime("now"))
                        ->setDateSaisie(new \DateTime("now"))
                        ->setDateReception(new \DateTime("now"))
                        ->setEtat("traité");
                $mouvementCaisse->addGestionCheque($comptable_cheque);
            }
                    
           
            $decaissement = new Decaissement();
            $mouvementCollab = new MouvementCollaborateur();
            $mouvementCollab->setCollaborateur($collaborateur)
                ->setOrigine("transfert cheque")
                ->setMontant(-$mouvementCaisse->getMontant())
                ->setDevise($deviseRep->find(1))
                ->setCaisse($mouvementCaisse->getCaisse())
                ->setSite($site)
                ->setTraitePar($this->getUser())
                ->setDateOperation(new \DateTime("now"))
                ->setDateSaisie(new \DateTime("now"));
            $comptable_cheque->addMouvementCollaborateur($mouvementCollab);
            $em->persist($comptable_cheque);

            $mouvementCaisse->setEtatOperation("traité")
            ->setDateSortie(new \DateTime("now"))
            ->setDetailSortie("chèque transféré de : " .$mouvementCaisse->getCaisse()->getDesignation()." vers ".$collaborateur->getPrenom()." ".$collaborateur->getNom())
            ->setDateOperation(new \DateTime("now"))
            ->setDateSaisie(new \DateTime("now"))
            ->setSaisiePar($this->getUser())
            ->setMontant(0)
            ;
            $em->persist($mouvementCaisse);
            $em->flush();

            $this->addFlash("success", "Transfert enregistré avec succès :)");
            return $this->redirectToRoute('app_logescom_comptable_caisse_comptable_cheque_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
        }
        if ($request->get("annuler")) {
            $caisse = $caisseRep->findCaisseByLieuByType($site, 'caisse');
            $caisse = $caisseRep->findOneBy(['id' => $caisse]);

            $comptable_cheque = $comptableChequeRep->findOneBy(['mouvementCaisse' => $mouvementCaisse]);

            $em->remove($comptable_cheque);

            $mouvementCaisse->setEtatOperation("non traité")
                    ->setDateSortie(null)
                    ->setDetailSortie(null)
                    ->setMontant($comptable_cheque->getMontant())
                    ->setCaisse($caisse)
                    ->setModePaie($modePaieRep->find(4));
            $em->persist($mouvementCaisse);
            $em->flush();

            $this->addFlash("success", "Transfert annulé avec succès :)");
            return $this->redirectToRoute('app_logescom_comptable_caisse_comptable_cheque_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
        }

        if ($request->isXmlHttpRequest()) {
            $search = $request->query->get('search');
            $clients = $userRep->findUserSearchByLieu($search, $site);    
            $response = [];
            foreach ($clients as $client) {
                $response[] = [
                    'nom' => ucwords($client->getPrenom())." ".strtoupper($client->getNom()),
                    'id' => $client->getId()
                ]; 
            }
            return new JsonResponse($response);
        }

        if ($request->get("id_client_search")){
            $collaborateur = $userRep->find($request->get("id_client_search"));;
        }else{
            $collaborateur = array();
        }

        $id_site = $site->getId();
        return $this->render('logescom/comptable/caisse/comptable_cheque/show.html.twig', [
            'cheque' => $mouvementCaisse,
            
            'site' => $site,
            'banques' => $caisseRep->findCaisseByLieu($site),
            'lieux' => $lieuVenteRep->findAllLieuxVenteExecept($id_site),
            'collaborateur' => $collaborateur,
        ]);
    }

    #[Route('/confirmation/{site}', name: 'app_logescom_comptable_caisse_comptable_cheque_confirmation')]
    public function confirmation(MouvementCaisseRepository $mouvementCaisseRep, CaisseRepository $caisseRep, UserRepository $userRep, Request $request, CategorieOperationRepository $catetgorieOpRep, CompteOperationRepository $compteOpRep, TransfertFondRepository $transfertRep, Site $site, LieuxVentesRepository $lieuVenteRep, GestionChequeRepository $comptableChequeRep, EntityManagerInterface $em): Response
    {
        if ($request->get("search_cheque")){
            $search = $request->get("search_cheque");
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

        $confirmation_cheques = $comptableChequeRep->findBy(['lieuVenteReception' => $site, 'etat' => 'non traité']);

        
        $id_site = $site->getId();
        return $this->render('logescom/comptable/caisse/comptable_cheque/confirmation_cheque.html.twig', [
            'cheques' => $confirmation_cheques,
            
            'site' => $site,
            'caisses' => $caisseRep->findCaisseByLieu($site),
            'lieux' => $lieuVenteRep->findAllLieuxVenteExecept($id_site),
            'search' => $search,
            'date1' => $date1,
            'date2' => $date2,
        ]);
    }

    #[Route('/confirmation/{id}/{site}', name: 'app_logescom_comptable_caisse_comptable_cheque_confirmation_validation')]
    public function confirmationValidation(GestionCheque $comptableCheque, MouvementCaisseRepository $mouvementCaisseRep, CaisseRepository $caisseRep, UserRepository $userRep, Request $request, CategorieOperationRepository $catetgorieOpRep, CompteOperationRepository $compteOpRep, TransfertFondRepository $transfertRep, Site $site, LieuxVentesRepository $lieuVenteRep, GestionChequeRepository $comptableChequeRep, EntityManagerInterface $em): Response
    {

        $categorie_op = $catetgorieOpRep->find(3);
        $compte_op = $compteOpRep->find(1);
        if ($request->get('confirmer')) {
            $caisse_recep = $caisseRep->find($request->get('caisse_recep'));  
            $type_caisse = $caisse_recep->getType(); 

            $comptableCheque->setEtat($type_caisse == "caisse" ? "en attente" : "traité")
                    ->setCaisseReception($caisse_recep)
                    ->setTraitePar($this->getUser())
                    ->setDateReception(new \DateTime("now"));
            $em->persist($comptableCheque);

            $mouvementCaisse = $comptableCheque->getMouvementCaisse();
            $mouvementCaisse->setEtatOperation($type_caisse == "caisse" ? "non traité" : "traité")
                    ->setDateSortie(new \DateTime("now"))
                    ->setDetailSortie("chèque transféré de : " .$mouvementCaisse->getCaisse()->getDesignation()." vers ".$caisse_recep->getDesignation())
                    ->setDateOperation(new \DateTime("now"))
                    ->setDateSaisie(new \DateTime("now"))
                    ->setSaisiePar($this->getUser())
                    ->setSite($site)
                    ->setCaisse($caisse_recep);
            $em->persist($mouvementCaisse);
            $em->flush();

            $this->addFlash("success", "Transfert econfirmé avec succès :)");
            return $this->redirectToRoute('app_logescom_comptable_caisse_comptable_cheque_confirmation', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
        }

        $confirmation_cheques = $comptableChequeRep->findBy(['lieuVenteReception' => $site, 'etat' => 'non traité']);
        
        $id_site = $site->getId();
        return $this->render('logescom/comptable/caisse/comptable_cheque/confirmation_cheque.html.twig', [
            'cheques' => $confirmation_cheques,
            
            'site' => $site,
            'caisses' => $caisseRep->findCaisseByLieu($site),
            'lieux' => $lieuVenteRep->findAllLieuxVenteExecept($id_site),
        ]);
    }
}
