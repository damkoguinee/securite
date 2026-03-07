<?php

namespace App\Controller\Logescom\Comptable\Caisse;

use App\Entity\Site;
use App\Entity\LieuxVentes;
use App\Entity\ChequeEspece;
use App\Form\ChequeEspeceType;
use App\Entity\MouvementCaisse;
use App\Repository\UserRepository;
use App\Repository\DeviseRepository;
use App\Entity\MouvementCollaborateur;
use App\Repository\EntrepriseRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ChequeEspeceRepository;
use App\Repository\ConfigDeviseRepository;
use App\Repository\ModePaiementRepository;
use App\Repository\CompteOperationRepository;
use App\Repository\MouvementCaisseRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\CategorieOperationRepository;
use App\Repository\ClientRepository;
use App\Repository\ConfigModePaiementRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\MouvementCollaborateurRepository;
use App\Repository\PersonelRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/logescom/comptable/caisse/cheque/espece')]
class ChequeEspeceController extends AbstractController
{
    #[Route('/accueil/{site}', name: 'app_logescom_comptable_caisse_cheque_espece_index', methods: ['GET'])]
    public function index(ChequeEspeceRepository $chequeEspeceRep, ClientRepository $clientRep, PersonelRepository $personnelRep, Request $request, ChequeEspeceRepository $chequeEspeceRepository, Site $site, EntrepriseRepository $entrepriseRep): Response
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

        if ($request->isXmlHttpRequest()) {
            $search = $request->query->get('search');
            $clients = $clientRep->findUserBySearch(search: $search, site: $site);
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
        $pageEncours = $request->get('pageEnCours', 1);
        if ($request->get("id_client_search")){
            $chequeEspeces = $chequeEspeceRep->findChequeEspece(site:$site, collaborateur: $search, startDate:$date1, endDate:$date2, pageEnCours:$pageEncours, limit:50);
        }else{
            $chequeEspeces = $chequeEspeceRep->findChequeEspece(site:$site, startDate:$date1, endDate:$date2, pageEnCours:$pageEncours, limit:50);
        }
        return $this->render('logescom/comptable/caisse/cheque_espece/index.html.twig', [
            'chequeEspeces' => $chequeEspeces,
            'site' => $site,
            'search' => $search,
        ]);
    }

    #[Route('/new/{site}', name: 'app_logescom_comptable_caisse_cheque_espece_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, Site $site, ChequeEspeceRepository $chequeEspeceRep, MouvementCaisseRepository $mouvementRep, ConfigDeviseRepository $deviseRep, MouvementCollaborateurRepository $mouvementCollabRep, UserRepository $userRep, ClientRepository $clientRep, PersonelRepository $personnelRep, ConfigModePaiementRepository $modePaieRep): Response
    {
        if ($request->get("id_client_search")){
            $client_find = $userRep->find($request->get("id_client_search"));
            $soldes_collaborateur = $mouvementCollabRep->findSoldeCollaborateur($client_find);
        }else{
            $client_find = array();
            $soldes_collaborateur = array();
        }

        if ($request->isXmlHttpRequest()) {
            $search = $request->query->get('search');
            $clients = $clientRep->findUserBySearch(search: $search, site: $site);
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
        $chequeEspece = new ChequeEspece();
        $form = $this->createForm(ChequeEspeceType::class, $chequeEspece, ['site' => $site]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $montant_cheque = floatval(preg_replace('/[^0-9.]/', '', str_replace(',', '.', $form->get('montantCheque')->getData())));
            $montant_recu = floatval(preg_replace('/[^0-9.]/', '', str_replace(',', '.', $form->get('montantRecu')->getData())));

            $modePaie = $modePaieRep->find(4);

            $caisse = $form->getViewData()->getCaisseRetrait();
            $devise = $deviseRep->find(1);          
            $solde_caisse = $mouvementRep->findSoldeCaisse($caisse, $devise);
            if ($solde_caisse >= $montant_recu) {
                $client = $request->get('collaborateur');
                $client = $userRep->find($client);

                $chequeEspece->setSite($site)
                            ->setCollaborateur($client)
                            ->setSaisiePar($this->getUser())
                            ->setMontantCheque($montant_cheque)
                            ->setMontantRecu($montant_recu)
                            ->setDateSaisie(new \DateTime("now"));

                $mouvement_caisse_depot = new MouvementCaisse();
                $mouvement_caisse_depot->setTypeMouvement("versement")
                        ->setMontant($montant_cheque)
                        ->setDevise($devise)
                        ->setCaisse($form->getViewData()->getCaisseDepot())
                        ->setSite($site)
                        ->setSaisiePar($this->getUser())
                        ->setModePaie($modePaie)
                        ->setBordereau($form->getViewData()->getNumeroCheque())
                        ->setDateOperation($form->getViewData()->getDateOperation())
                        ->setDateSaisie(new \DateTime("now"));
                $chequeEspece->addMouvementCaiss($mouvement_caisse_depot);

                $mouvement_caisse_retrait = new MouvementCaisse();
                $mouvement_caisse_retrait->setTypeMouvement("decaissement")
                        ->setMontant(-$montant_recu)
                        ->setModePaie($modePaieRep->find(1))
                        ->setDevise($devise)
                        ->setSaisiePar($this->getUser())
                        ->setCaisse($form->getViewData()->getCaisseRetrait())
                        ->setSite($site)
                        ->setDateOperation($form->getViewData()->getDateOperation())
                        ->setDateSaisie(new \DateTime("now"));
                $chequeEspece->addMouvementCaiss($mouvement_caisse_retrait);

                $mouvement_collab_depot = new MouvementCollaborateur();
                $mouvement_collab_depot->setCollaborateur($client)
                        ->setOrigine("versement")
                        ->setMontant($montant_cheque)
                        ->setDevise($devise)
                        ->setSite($site)
                        ->setDateOperation($form->getViewData()->getDateOperation())
                        ->setDateSaisie(new \DateTime("now"));
                $chequeEspece->addMouvementCollaborateur($mouvement_collab_depot);

                $mouvement_collab_retrait = new MouvementCollaborateur();
                $mouvement_collab_retrait->setCollaborateur($client)
                        ->setOrigine("decaissement")
                        ->setMontant(-$montant_recu)
                        ->setDevise($devise)
                        ->setSite($site)
                        ->setDateOperation($form->getViewData()->getDateOperation())
                        ->setDateSaisie(new \DateTime("now"));
                $chequeEspece->addMouvementCollaborateur($mouvement_collab_retrait);
                
                $entityManager->persist($chequeEspece);
                $entityManager->flush();

                $this->addFlash("success", "l'opération à été enregistrée avec succès :)");
                return $this->redirectToRoute('app_logescom_comptable_caisse_cheque_espece_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
            }else{
                $this->addFlash("warning", "Le montant disponible en caisse est insuffisant");
                // Récupérer l'URL de la page précédente
                $referer = $request->headers->get('referer');
                if ($referer) {
                    $formView = $form->createView();
                    return $this->render('logescom/comptable/caisse/cheque_espece/new.html.twig', [
                        
                        'site' => $site,
                        'form' => $formView,
                        'cheque_espece' => $chequeEspece,
                        'referer' => $referer,
                        'client_find' => $client_find,
                        'soldes_collaborateur' => $soldes_collaborateur,
                    ]);
                }
            }
            
        }

        return $this->render('logescom/comptable/caisse/cheque_espece/new.html.twig', [
            'cheque_espece' => $chequeEspece,
            'form' => $form,
            
            'site' => $site,
            'client_find' => $client_find,
            'soldes_collaborateur' => $soldes_collaborateur,

        ]);
    }

    #[Route('show/{id}/{site}', name: 'app_logescom_comptable_caisse_cheque_espece_show', methods: ['GET'])]
    public function show(ChequeEspece $chequeEspece, Site $site, EntrepriseRepository $entrepriseRep): Response
    {
        return $this->render('logescom/comptable/caisse/cheque_espece/show.html.twig', [
            'cheque_espece' => $chequeEspece,
            
            'site' => $site,
        ]);
    }

    #[Route('/edit/{id}/{site}', name: 'app_logescom_comptable_caisse_cheque_espece_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ChequeEspece $chequeEspece, ChequeEspeceRepository $chequeEspeceRep, EntityManagerInterface $entityManager, UserRepository $userRep, MouvementCollaborateurRepository $mouvementCollabRep, MouvementCaisseRepository $mouvementCaisseRep, ConfigDeviseRepository $deviseRep, MouvementCaisseRepository $mouvementRep, Site $site, ClientRepository $clientRep, PersonelRepository $personnelRep): Response
    {
        if ($request->get("id_client_search")){
            $client_find = $userRep->find($request->get("id_client_search"));
            $soldes_collaborateur = $mouvementCollabRep->findSoldeCollaborateur($client_find);
        }else{
            $client_find = $chequeEspece->getCollaborateur();
            $soldes_collaborateur = $mouvementCollabRep->findSoldeCollaborateur($client_find);
        }

        if ($request->isXmlHttpRequest()) {
            $search = $request->query->get('search');
            $clients = $clientRep->findUserBySearch(search: $search, site: $site);
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

        $form = $this->createForm(ChequeEspeceType::class, $chequeEspece, ['site' => $site]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $montant_cheque = floatval(preg_replace('/[^0-9.]/', '', str_replace(',', '.', $form->get('montantCheque')->getData())));
            $montant_recu = floatval(preg_replace('/[^0-9.]/', '', str_replace(',', '.', $form->get('montantRecu')->getData())));

            $caisse = $form->getViewData()->getCaisseRetrait();
            $devise = $deviseRep->find(1);          
            $solde_caisse = $mouvementRep->findSoldeCaisse($caisse, $devise);
            if ($solde_caisse >= $montant_recu) {

                $client = $request->get('client');
                $client = $userRep->find($client);

                $chequeEspece->setSite($site)
                            ->setCollaborateur($client)
                            ->setSaisiePar($this->getUser())
                            ->setMontantCheque($montant_cheque)
                            ->setMontantRecu($montant_recu)
                            ->setDateSaisie(new \DateTime("now"));

                $mouvement_caisse_depot = $mouvementCaisseRep->findOneBy(['chequeEspece' => $chequeEspece]);
                $mouvement_caisse_depot->setMontant($montant_cheque)
                        ->setCaisse($form->getViewData()->getCaisseDepot())
                        ->setSite($site)
                        ->setSaisiePar($this->getUser())
                        ->setBordereau($form->getViewData()->getNumeroCheque())
                        ->setDateOperation($form->getViewData()->getDateOperation())
                        ->setDateSaisie(new \DateTime("now"));
                $chequeEspece->addMouvementCaiss($mouvement_caisse_depot);

                $mouvement_caisse_retrait = $mouvementCaisseRep->findOneBy(['chequeEspece' => $chequeEspece]);
                $mouvement_caisse_retrait->setMontant(-$montant_recu)
                        ->setDevise($devise)
                        ->setCaisse($form->getViewData()->getCaisseRetrait())
                        ->setSite($site)
                        ->setSaisiePar($this->getUser())
                        ->setDateOperation($form->getViewData()->getDateOperation())
                        ->setDateSaisie(new \DateTime("now"));
                $chequeEspece->addMouvementCaiss($mouvement_caisse_retrait);

                $mouvement_collab_depot = $mouvementCollabRep->findOneBy(['chequeEspece' => $chequeEspece]);
                $mouvement_collab_depot->setCollaborateur($client)
                        ->setMontant($montant_cheque)
                        ->setSite($site)
                        ->setSaisiePar($this->getUser())
                        ->setDateOperation($form->getViewData()->getDateOperation())
                        ->setDateSaisie(new \DateTime("now"));
                $chequeEspece->addMouvementCollaborateur($mouvement_collab_depot);

                $mouvement_collab_retrait = $mouvementCollabRep->findOneBy(['chequeEspece' => $chequeEspece]);
                $mouvement_collab_retrait->setCollaborateur($client)
                        ->setMontant(-$montant_recu)
                        ->setSite($site)
                        ->setSaisiePar($this->getUser())
                        ->setDateOperation($form->getViewData()->getDateOperation())
                        ->setDateSaisie(new \DateTime("now"));
                $chequeEspece->addMouvementCollaborateur($mouvement_collab_retrait);
                
                $entityManager->persist($chequeEspece);
                $entityManager->flush();

                $this->addFlash("success", "l'opération à été modifiée avec succès :)");
                return $this->redirectToRoute('app_logescom_comptable_caisse_cheque_espece_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
            }else{
                $this->addFlash("warning", "Le montant disponible en caisse est insuffisant");
                // Récupérer l'URL de la page précédente
                $referer = $request->headers->get('referer');
                if ($referer) {
                    $formView = $form->createView();
                    return $this->render('logescom/comptable/caisse/cheque_espece/new.html.twig', [
                        
                        'site' => $site,
                        'form' => $formView,
                        'cheque_espece' => $chequeEspece,
                        'referer' => $referer,
                        'client_find' => $client_find,
                        'soldes_collaborateur' => $soldes_collaborateur,
                    ]);
                }
            }
            
        }

        return $this->render('logescom/comptable/caisse/cheque_espece/edit.html.twig', [
            'cheque_espece' => $chequeEspece,
            'form' => $form,
            
            'site' => $site,
            'client_find' => $client_find,
            'soldes_collaborateur' => $soldes_collaborateur
        ]);
    }

    #[Route('/delete/{id}/{site}', name: 'app_logescom_comptable_caisse_cheque_espece_delete', methods: ['POST'])]
    public function delete(Request $request, ChequeEspece $chequeEspece, EntityManagerInterface $entityManager, Site $site, EntrepriseRepository $entrepriseRep): Response
    {
        if ($this->isCsrfTokenValid('delete'.$chequeEspece->getId(), $request->request->get('_token'))) {
            $entityManager->remove($chequeEspece);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_logescom_comptable_caisse_cheque_espece_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
    }
}
