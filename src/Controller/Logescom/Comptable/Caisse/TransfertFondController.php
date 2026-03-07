<?php

namespace App\Controller\Logescom\Comptable\Caisse;

use App\Entity\Site;
use App\Entity\SmsEnvoyes;
use App\Entity\TransfertFond;
use App\Entity\MouvementCaisse;
use App\Form\TransfertFondType;
use App\Service\LogicielService;
use App\Service\OrangeSmsService;
use App\Entity\ConfigModePaiement;
use App\Entity\HistoriqueChangement;
use App\Repository\CaisseRepository;
use App\Entity\MouvementCollaborateur;
use App\Repository\EntrepriseRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ModePaiementRepository;
use App\Repository\TransfertFondRepository;
use Symfony\Component\Filesystem\Filesystem;
use App\Repository\MouvementCaisseRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\ConfigurationSmsRepository;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\TransfertProductsRepository;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ConfigModePaiementRepository;
use Symfony\Component\String\Slugger\AsciiSlugger;
use App\Repository\ConfigCompteOperationRepository;
use App\Repository\ConfigCategorieOperationRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/logescom/comptable/caisse/transfert/fond')]
class TransfertFondController extends AbstractController
{
    #[Route('/accueil/{site}', name: 'app_logescom_comptable_caisse_transfert_fond_index', methods: ['GET'])]
    public function index(TransfertFondRepository $transfertFondRepository, Request $request, CaisseRepository $caisseRep, Site $site): Response
    {

        if ($request->get("caisse")){
            $search = $caisseRep->find($request->get("caisse"));
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
        if ($request->get("caisse")){
            $transferts = $transfertFondRepository->findTransfertSearch(site:$site, caisseDepart:$search, startDate:$date1, endDate:$date2, pageEnCours:$pageEncours, limit:100);
        }else{
            $transferts = $transfertFondRepository->findTransfertSearch(site:$site, startDate:$date1, endDate:$date2, pageEnCours:$pageEncours, limit:100);

        }

        return $this->render('logescom/comptable/caisse/transfert_fond/index.html.twig', [
            'transfert_fonds' => $transferts,
            'site' => $site,
            'search' => $search,
            'caisses' => $caisseRep->findCaisse(site: $site),
            'date1' => $date1,
            'date2' => $date2,
        ]);
    }

    #[Route('/new/{site}', name: 'app_logescom_comptable_caisse_transfert_fond_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, Site $site, TransfertFondRepository $transfertRep, MouvementCaisseRepository $mouvementRep, ConfigModePaiementRepository $modePaieRep): Response
    {
        $transfertFond = new TransfertFond();
        $form = $this->createForm(TransfertFondType::class, $transfertFond, ['site' => $site]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $montantString = $form->get('montant')->getData();
            $montantString = preg_replace('/[^0-9]/', '', $montantString);
            $montant = floatval($montantString);
            $dateDuJour = new \DateTime();
            $referenceDate = $dateDuJour->format('ymd');
            $idSuivant =($transfertRep->findMaxId() + 1);
            $reference = "trans".$referenceDate . sprintf('%04d', $idSuivant);

            $caisse_depart = $form->getViewData()->getCaisse();
            $caisse_recep = $form->getViewData()->getCaisseReception();
            // $caisse_recep = $caisseRep->find($request->get('caisse_reception'));
            $devise = $form->getViewData()->getDevise();
            

            if (empty($caisse_depart) and empty($caisse_recep)) {
                $this->addFlash("warning", "vous devez selectionner au moins une caisse");
                // Récupérer l'URL de la page précédente
                $referer = $request->headers->get('referer');
                if ($referer) {
                    $formView = $form->createView();
                    return $this->render('logescom/comptable/caisse/transfert_fond/new.html.twig', [
                        'site' => $site,
                        'form' => $formView,
                        'transfert_fond' => $transfertFond,
                        'referer' => $referer,
                    ]);
                }
            }else{

                $solde_caisse = $caisse_depart ? $mouvementRep->findSoldeCaisse(caisse: $caisse_depart, devise:$devise) : 1000000000000000000000000000000;
                if ($solde_caisse >= $montant) {                  
                    
                    if (!empty($caisse_depart) and !empty($caisse_recep)) {
                        $transfertFond->setSite($site)
                            ->setSaisiePar($this->getUser())
                            ->setReference($reference)
                            ->setMontant(- $montant)
                            ->setTypeMouvement('transfert')
                            ->setCommentaire($transfertFond->getCommentaire())
                            ->setDateSaisie(new \DateTime("now"))
                            ->setTaux(1);    
                        $fichier = $form->get("document")->getData();
                        if ($fichier) {
                            $nomFichier= pathinfo($fichier->getClientOriginalName(), PATHINFO_FILENAME);
                            $slugger = new AsciiSlugger();
                            $nouveauNomFichier = $slugger->slug($nomFichier);
                            $nouveauNomFichier .="_".uniqid();
                            $nouveauNomFichier .= "." .$fichier->guessExtension();
                            $fichier->move($this->getParameter("dossier_transfert"),$nouveauNomFichier);
                            $transfertFond->setDocument($nouveauNomFichier);
                        }

                        $entityManager->persist($transfertFond);
                        $transfert_recep = new TransfertFond();
                        $transfert_recep->setSite($site)
                                ->setCaisse($transfertFond->getCaisseReception())
                                ->setCaisseReception($transfertFond->getCaisse())
                                ->setDevise($transfertFond->getDevise())
                                ->setModePaie($transfertFond->getModePaie())
                                ->setSaisiePar($this->getUser())
                                ->setReference($reference)
                                ->setMontant($montant)
                                ->setCommentaire($transfertFond->getCommentaire())
                                ->setDateOperation($transfertFond->getDateOperation())
                                ->setTypeMouvement('transfert')
                                ->setDateSaisie(new \DateTime("now"))
                                ->setTaux(1);
                        $entityManager->persist($transfert_recep);
                    }elseif (empty($caisse_depart)) {

                        $transfert_recep = new TransfertFond();
                        $transfert_recep->setSite($site)
                                ->setCaisse($transfertFond->getCaisseReception())
                                ->setCaisseReception($transfertFond->getCaisse())
                                ->setDevise($transfertFond->getDevise())
                                ->setModePaie($transfertFond->getModePaie())
                                ->setSaisiePar($this->getUser())
                                ->setReference($reference)
                                ->setMontant($montant)
                                ->setCommentaire($transfertFond->getCommentaire())
                                ->setDateOperation($transfertFond->getDateOperation())
                                ->setTypeMouvement('transfert')
                                ->setDateSaisie(new \DateTime("now"))
                                ->setTaux(1);
                        $entityManager->persist($transfert_recep);
                        
                    }else{

                        $transfertFond->setSite($site)
                            ->setSaisiePar($this->getUser())
                            ->setReference($reference)
                            ->setMontant(- $montant)
                            ->setTypeMouvement('transfert')
                            ->setDateSaisie(new \DateTime("now"))
                            ->setTaux(1);    
                        $fichier = $form->get("document")->getData();
                        if ($fichier) {
                            $nomFichier= pathinfo($fichier->getClientOriginalName(), PATHINFO_FILENAME);
                            $slugger = new AsciiSlugger();
                            $nouveauNomFichier = $slugger->slug($nomFichier);
                            $nouveauNomFichier .="_".uniqid();
                            $nouveauNomFichier .= "." .$fichier->guessExtension();
                            $fichier->move($this->getParameter("dossier_transfert"),$nouveauNomFichier);
                            $transfertFond->setDocument($nouveauNomFichier);
                        }

                        $entityManager->persist($transfertFond);

                    }
                    $entityManager->flush();
    
                    $this->addFlash("success", "transfert enregistré avec succès :)");
                    return $this->redirectToRoute('app_logescom_comptable_caisse_transfert_fond_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
                }else{
                    $this->addFlash("warning", "Le montant disponible en caisse est insuffisant");
                    // Récupérer l'URL de la page précédente
                    $referer = $request->headers->get('referer');
                    if ($referer) {
                        $formView = $form->createView();
                        return $this->render('logescom/comptable/caisse/transfert_fond/new.html.twig', [
                            'site' => $site,
                            'form' => $formView,
                            'transfert_fond' => $transfertFond,
                            'referer' => $referer,
                        ]);
                    }
                }
            }


            return $this->redirectToRoute('app_logescom_comptable_caisse_transfert_fond_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('logescom/comptable/caisse/transfert_fond/new.html.twig', [
            'transfert_fond' => $transfertFond,
            'form' => $form,
            'site' => $site,
        ]);
    }

    #[Route('/show/{id}/{site}', name: 'app_logescom_comptable_caisse_transfert_fond_show', methods: ['GET'])]
    public function show(TransfertFond $transfertFond, Site $site, EntrepriseRepository $entrepriseRep): Response
    {
        return $this->render('logescom/comptable/caisse/transfert_fond/show.html.twig', [
            'transfert_fond' => $transfertFond,
            'site' => $site,
        ]);
    }

    #[Route('/edit/{id}/{site}', name: 'app_logescom_comptable_caisse_transfert_fond_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TransfertFond $transfertFond, EntityManagerInterface $entityManager, Site $site, SessionInterface $session, TransfertFondRepository $transfertRep, MouvementCaisseRepository $mouvementRep): Response
    {
        $transfertFond->setMontant(-$transfertFond->getMontant());
        $form = $this->createForm(TransfertFondType::class, $transfertFond, ['site' => $site]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $montantString = $form->get('montant')->getData();
            $montantString = preg_replace('/[^0-9]/', '', $montantString);
            $montant = floatval($montantString);

            $caisse_depart = $form->getViewData()->getCaisse();
            $caisse_recep = $form->getViewData()->getCaisseReception();
            $devise = $form->getViewData()->getDevise();

            if (empty($caisse_depart) and empty($caisse_recep)) {
                $this->addFlash("warning", "vous devez saisir au moins une caisse");
                // Récupérer l'URL de la page précédente
                $referer = $request->headers->get('referer');
                if ($referer) {
                    $formView = $form->createView();
                    return $this->render('logescom/comptable/caisse/transfert_fond/new.html.twig', [
                        'site' => $site,
                        'form' => $formView,
                        'transfert_fond' => $transfertFond,
                        'referer' => $referer,
                    ]);
                }
            }else{
                $transferts = $transfertRep->findBy(['reference' => $transfertFond->getReference()]); 

                foreach ($transferts as $transfert) {
                    $entityManager->remove($transfert);
                }  
                $solde_caisse = $caisse_depart ? $mouvementRep->findSoldeCaisse(caisse:$caisse_depart, devise: $devise) : 1000000000000000000000000000000;

                if ($solde_caisse >= $montant) {
                    if (!empty($caisse_depart) and !empty($caisse_recep)) {
                        $transfertFond->setSite($site)
                            ->setSaisiePar($this->getUser())
                            ->setMontant(- $montant)
                            ->setTypeMouvement('transfert')
                            ->setDateSaisie(new \DateTime("now"))
                            ->setTaux(1);    
                        $justificatif =$form->get("document")->getData();
                        if ($justificatif) {
                            if ($transfertFond->getDocument()) {
                                $ancienJustificatif=$this->getParameter("dossier_transfert")."/".$transfertFond->getDocument();
                                if (file_exists($ancienJustificatif)) {
                                    unlink($ancienJustificatif);
                                }
                            }
                            $nomJustificatif= pathinfo($justificatif->getClientOriginalName(), PATHINFO_FILENAME);
                            $slugger = new AsciiSlugger();
                            $nouveauNomJustificatif = $slugger->slug($nomJustificatif);
                            $nouveauNomJustificatif .="_".uniqid();
                            $nouveauNomJustificatif .= "." .$justificatif->guessExtension();
                            $justificatif->move($this->getParameter("dossier_transfert"),$nouveauNomJustificatif);
                            $transfertFond->setDocument($nouveauNomJustificatif);
        
                        }

                        $entityManager->persist($transfertFond);

                        $transfert_recep = new TransfertFond();
                        $transfert_recep->setSite($site)
                                ->setCaisse($transfertFond->getCaisseReception())
                                ->setCaisseReception($transfertFond->getCaisse())
                                ->setDevise($transfertFond->getDevise())
                                ->setModePaie($transfertFond->getModePaie())
                                ->setSaisiePar($this->getUser())
                                ->setMontant($montant)
                                ->setCommentaire($transfertFond->getCommentaire())
                                ->setDateOperation($transfertFond->getDateOperation())
                                ->setTypeMouvement('transfert')
                                ->setDateSaisie(new \DateTime("now"))
                                ->setTaux(1);
                        $entityManager->persist($transfert_recep);
                    }elseif (empty($caisse_depart)) {

                        $transfert_recep = new TransfertFond();
                        $transfert_recep->setSite($site)
                                ->setCaisse($transfertFond->getCaisseReception())
                                ->setCaisseReception($transfertFond->getCaisse())
                                ->setDevise($transfertFond->getDevise())
                                ->setModePaie($transfertFond->getModePaie())
                                ->setSaisiePar($this->getUser())
                                ->setMontant($montant)
                                ->setCommentaire($transfertFond->getCommentaire())
                                ->setDateOperation($transfertFond->getDateOperation())
                                ->setTypeMouvement('transfert')
                                ->setDateSaisie(new \DateTime("now"))
                                ->setTaux(1);
                        $entityManager->persist($transfert_recep);
                        
                    }else{

                        $transfertFond->setSite($site)
                            ->setSaisiePar($this->getUser())
                            ->setMontant(- $montant)
                            ->setTypeMouvement('transfert')
                            ->setDateSaisie(new \DateTime("now"))
                            ->setTaux(1);    
                        $fichier = $form->get("document")->getData();
                        if ($fichier) {
                            $nomFichier= pathinfo($fichier->getClientOriginalName(), PATHINFO_FILENAME);
                            $slugger = new AsciiSlugger();
                            $nouveauNomFichier = $slugger->slug($nomFichier);
                            $nouveauNomFichier .="_".uniqid();
                            $nouveauNomFichier .= "." .$fichier->guessExtension();
                            $fichier->move($this->getParameter("dossier_transfert"),$nouveauNomFichier);
                            $transfertFond->setDocument($nouveauNomFichier);
                        }

                        $entityManager->persist($transfertFond);
        
                    }
                    $entityManager->flush();
    
                    $this->addFlash("success", "transfert modifié avec succès :)");
                    return $this->redirectToRoute('app_logescom_comptable_caisse_transfert_fond_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
                }else{
                    $this->addFlash("warning", "Le montant disponible en caisse est insuffisant");
                    // Récupérer l'URL de la page précédente
                    $referer = $request->headers->get('referer');
                    if ($referer) {
                        $formView = $form->createView();
                        return $this->render('logescom/comptable/caisse/transfert_fond/new.html.twig', [

                            'site' => $site,
                            'form' => $formView,
                            'transfert_fond' => $transfertFond,
                            'referer' => $referer,
                        ]);
                    }
                }
            }


            return $this->redirectToRoute('app_logescom_comptable_caisse_transfert_fond_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('logescom/comptable/caisse/transfert_fond/edit.html.twig', [
            'transfert_fond' => $transfertFond,
            'form' => $form,
            'site' => $site,
        ]);
    }

    #[Route('/confirm/delete/{id}', name: 'app_logescom_comptable_caisse_transfert_fond_confirm_delete', methods: ['GET', 'POST'])]
    public function confirmDelete(TransfertFond $transfertFond, Request $request): Response
    {
        $param = $request->request->get('param'); // Récupération du paramètre

        $route_suppression = $this->generateUrl('app_logescom_comptable_caisse_transfert_fond_delete', [
            'id' => $transfertFond->getId(),
            'site' => $transfertFond->getSite()->getId(),
        ]);
        

        return $this->render('logescom/comptable/caisse/transfert_fond/confirm_delete.html.twig', [
            'route_suppression' => $route_suppression,
            'param' => $param,
            'site' => $transfertFond->getSite(),
            'entreprise' => $transfertFond->getSite()->getEntreprise(),
            'operation' => $transfertFond
        ]);
    }

    #[Route('/delete/{id}/{site}', name: 'app_logescom_comptable_caisse_transfert_fond_delete', methods: ['POST'])]
    public function delete(Request $request, TransfertFond $transfertFond, TransfertFondRepository $transfertRep, EntityManagerInterface $entityManager, Site $site, LogicielService $service, OrangeSmsService $orangeService, ConfigurationSmsRepository $configSmsRep, Filesystem $filesystem,): Response
    {
        if ($this->isCsrfTokenValid('delete'.$transfertFond->getId(), $request->request->get('_token'))) {
            $justificatif = $transfertFond->getDocument();
            $pdfPath = $this->getParameter("dossier_transfert") . '/' . $justificatif;
            // Si le chemin du justificatif existe, supprimez également le fichier
            if ($justificatif && $filesystem->exists($pdfPath)) {
                $filesystem->remove($pdfPath);
            }

            $transferts = $transfertRep->findBy(['reference' => $transfertFond->getReference()]);
            foreach ($transferts as $value) {
                $entityManager->remove($value);
            }

            $deleteReason = $request->request->get('delete_reason');
            $montant = $transfertFond->getMontant();

            
            $dateOperation = $transfertFond->getDateOperation() 
                ? $transfertFond->getDateOperation()->format('d/m/Y H:i') 
                : 'Date non définie';

            $information = "Montant : {$montant} | Date : {$dateOperation}";

            $personnel = $this->getUser();
            $historiqueSup = new HistoriqueChangement();
            $historiqueSup->setSaisiePar($personnel)
                    ->setDateSaisie(new \DateTime())
                    ->setMotif($deleteReason)
                    ->setInformation($information)
                    ->setType('transfertFond')
                    ->setSite($transfertFond->getSite());
            $entityManager->persist($historiqueSup);

            $entityManager->flush();

            # comptable envoi sms alert
            if ($service->estConnecteInternet()) {// vérifie si il ya une connexion internet
                # on verifie si l'envoi de notification est actif pour le versement
                $etat_notification = $configSmsRep->findOneBy(['nom' => 'suppression_modification', 'etat' => 'actif']);

                if ($etat_notification) {                
                    if ($service->verifierForfaitDisponible()) {// verifie si il ya un forfait disponible
                        // Forfait disponible : envoyer le SMS
                        $telephone = $site->getEntreprise()->getTelephone();
                        $telephone = $service->normaliserTelephone($telephone);
                        if ($telephone and strlen($telephone) >= 9) {

                            $recipientPhoneNumber = $telephone;
                            $countrySenderNumber = 'tel:+2240000'; 
                            
                            $message  = "⚠️ Alerte Suppression transfert des fonds ⚠️\n";
                            $message .= "La transfert de fond n° " . $transfertFond->getId() . " d'un montant de " . number_format($transfertFond->getMontant(), 0, ',', ' ') . " a été supprimée.\n";
                            $message .= "Date de suppression : " . date('d/m/Y à H:i') . ".\n";
                            $message .= "Supprimé par : " . ucwords($this->getUser()->getPrenom()) . " " . strtoupper($this->getUser()->getNom()) . ".";
                            
                            $senderName = $site->getEntreprise()->getNom(); // Nom de l'expéditeur
                            // Appel au service pour envoyer le SMS
                        
                            $response = $orangeService->sendSms(
                                $recipientPhoneNumber,
                                $countrySenderNumber,
                                $message,
                                $senderName
                            );
                            // Vérification si le sms est bien envoyé
                            if (isset($response['outboundSMSMessageRequest']['resourceURL'])) {

                                $sms = new SmsEnvoyes();
                                $sms->setDestinataire($telephone)
                                    ->setMessage($message)
                                    ->setCommentaire($transfertFond->getCommentaire())
                                    ->setDateEnvoie(new \DateTime())
                                    ->setForfait($service->verifierForfaitDisponible());
                                $entityManager->persist($sms);
                                $entityManager->flush();

                            }
                            
                        }
                    }
                }
            }

            $this->addFlash("success", "TransfertFond supprimé avec succès :)");
        }

        return $this->redirectToRoute('app_logescom_comptable_caisse_transfert_fond_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
    }
}
