<?php

namespace App\Controller\Logescom\Comptable\Sorties;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\Site;
use App\Entity\SmsEnvoyes;
use App\Entity\LieuxVentes;
use App\Entity\Decaissement;
use App\Entity\Modification;
use App\Form\DecaissementType;
use App\Entity\MouvementCaisse;
use App\Service\LogicielService;
use App\Service\OrangeSmsService;
use App\Entity\DeleteDecaissement;
use App\Repository\UserRepository;
use App\Entity\HistoriqueChangement;
use App\Repository\ClientRepository;
use App\Repository\DeviseRepository;
use App\Entity\MouvementCollaborateur;
use App\Repository\PersonelRepository;
use App\Repository\EntrepriseRepository;
use App\Repository\ForfaitSmsRepository;
use App\Repository\SmsEnvoyesRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\LieuxVentesRepository;
use App\Repository\ConfigDeviseRepository;
use App\Repository\DecaissementRepository;
use App\Repository\ModificationRepository;
use Symfony\Component\Filesystem\Filesystem;
use App\Repository\CompteOperationRepository;
use App\Repository\MouvementCaisseRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\ConfigurationSmsRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\CategorieOperationRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\String\Slugger\AsciiSlugger;
use App\Repository\ConfigurationLogicielRepository;
use App\Repository\MouvementCollaborateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/logescom/comptable/sorties/decaissement')]
class DecaissementController extends AbstractController
{
    #[Route('/accueil/{site}', name: 'app_logescom_comptable_sorties_decaissement_index', methods: ['GET'])]
    public function index(DecaissementRepository $decaissementRepository, UserRepository $userRep, PersonelRepository $personnelRep, Request $request, Site $site, EntrepriseRepository $entrepriseRep): Response
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
            $utilisateurs = $userRep->findUserBySearch(value: $search, site: $site);     
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
            $decaissements = $decaissementRepository->findDecaissementSearch(site: $site, search: $search, startDate: $date1, endDate: $date2, pageEnCours: $pageEncours, limit: 100);
        }else{
            $decaissements = $decaissementRepository->findDecaissementSearch(site: $site, startDate: $date1, endDate: $date2, pageEnCours: $pageEncours, limit: 100);

        }
        return $this->render('logescom/comptable/sorties/decaissement/index.html.twig', [
            'decaissements' => $decaissements,
            'search' => $search,            
            'site' => $site,
            'date1' => $date1,
            'date2' => $date2,
        ]);
    }

    #[Route('/new/{site}', name: 'app_logescom_comptable_sorties_decaissement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, Site $site, ConfigDeviseRepository $deviseRep, DecaissementRepository $decaissementRep, MouvementCollaborateurRepository $mouvementCollabRep, UserRepository $userRep, ClientRepository $clientRep, PersonelRepository $personnelRep, ConfigurationSmsRepository $configSmsRep, ForfaitSmsRepository $forfaitSmsRep, LogicielService $service, OrangeSmsService $orangeService, EntrepriseRepository $entrepriseRep): Response
    {
        if ($request->isXmlHttpRequest()) {
            $search = $request->query->get('search');
            $utilisateurs = $userRep->findUserBySearch(value: $search, site: $site); 
            $response = [];
            foreach ($utilisateurs as $client) {
                $response[] = [
                    'nom' => ucwords($client->getPrenom())." ".strtoupper($client->getNom()),
                    'id' => $client->getId()
                ]; 
            }
            return new JsonResponse($response);
        }
        $decaissement = new Decaissement();
        $form = $this->createForm(DecaissementType::class, $decaissement, ['site' => $site, 'decaissement' => $decaissement]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $montant = floatval(preg_replace('/[^0-9,.]/', '', $form->get('montant')->getData()));
            $dateDuJour = new \DateTime();
            $referenceDate = $dateDuJour->format('ymd');
            $idSuivant =($decaissementRep->findMaxId() + 1);
            $reference = "dec".$referenceDate . sprintf('%04d', $idSuivant);
            $client = $request->get('collaborateur');
            $client = $userRep->find($client);

            $decaissement->setSite($site)
                        ->setCollaborateur($client)
                        ->setSaisiePar($this->getUser())
                        ->setReference($reference)
                        ->setMontant(-$montant)
                        ->setDateSaisie(new \DateTime("now"))
                        ->setDevise($form->getViewData()->getDevise())
                        ->setCaisse($form->getViewData()->getCaisse())
                        ->setModePaie($form->getViewData()->getModePaie())
                        ->setTaux(1)
                        ->setSite($site)
                        ->setTypeMouvement('decaissement');

            $fichier = $form->get("document")->getData();
            if ($fichier) {
                $nomFichier= pathinfo($fichier->getClientOriginalName(), PATHINFO_FILENAME);
                $slugger = new AsciiSlugger();
                $nouveauNomFichier = $slugger->slug($nomFichier);
                $nouveauNomFichier .="_".uniqid();
                $nouveauNomFichier .= "." .$fichier->guessExtension();
                $fichier->move($this->getParameter("dossier_decaissement"),$nouveauNomFichier);
                $decaissement->setDocument($nouveauNomFichier);
            }

            $mouvement_collab = new MouvementCollaborateur();

            $devise = $form->getViewData()->getDevise();
            
            $mouvement_collab->setCollaborateur($client)
                    ->setOrigine("decaissement")
                    ->setMontant(- $montant)
                    ->setDevise($devise)
                    ->setSite($site)
                    ->setDateOperation($form->getViewData()->getDateOperation())
                    ->setDateSaisie(new \DateTime("now"));
            $decaissement->addMouvementCollaborateur($mouvement_collab);
            $entityManager->persist($decaissement);
            $entityManager->flush();

            $dernierDecaissement = $decaissementRep->findOneBy([], ['id' => 'DESC']);
            # gestion de notification sms 
            if ($service->estConnecteInternet()) {// vérifie si il ya une connexion internet

                # on verifie si l'envoi de notification est actif pour le decaissement

                $etat_notification = $configSmsRep->findOneBy(['nom' => 'decaissement', 'etat' => 'actif']);
                if ($etat_notification) {              
                    if ($service->verifierForfaitDisponible()) {// verifie si il ya un forfait disponible
                        // Forfait disponible : envoyer le SMS
                        $telephone = $client->getTelephone();
                        $telephone = $service->normaliserTelephone($telephone);
                        if ($telephone and strlen($telephone) >= 9) {

                            $recipientPhoneNumber = $telephone;
                            $countrySenderNumber = 'tel:+2240000'; 
                            
                            $soldes_collaborateur = $mouvementCollabRep->findSoldeCollaborateur($client);
                            $soldeDetails = '';
                            if (!empty($soldes_collaborateur)) {
                                foreach ($soldes_collaborateur as $solde) {
                                    $devise_solde = strtoupper($solde['devise']);
                                    if ($solde['montant'] > 0) {
                                        $montant_solde = number_format($solde['montant'], 0, ',', ' ');
                                        $soldeDetails .="Nous vous devons ". $montant_solde . ' ' . $devise_solde . "\n";
                                    }else{
                                        $montant_solde = number_format(-$solde['montant'], 0, ',', ' ');
                                        $soldeDetails .="Vous nous devez ". $montant_solde . ' ' . $devise_solde . "\n";
                                    }
                                }
                            } else {
                                $soldeDetails = "Solde indisponible.\n";
                            }

                            if (!$etat_notification->getMessage()) {
                                    $message = "Cher " . ucwords($client->getPrenom()) . ",\n";
                                    $message .= "Nous avons effectué un décaissement de " . number_format($montant, 0, ',', ' ') . " " . strtoupper($devise->getNom()) . " en votre faveur.\n";
                                    $message .= "A la date du " . date('d/m/Y à H:i') . " :\n";
                                    $message .= $soldeDetails;
                                    $message .= "\nNous vous remercions de votre confiance.\n";
                                    $message .= "Cordialement,\n";
                                    $message .= $site->getEntreprise()->getNom() . ".\n";
                                }else{
                                    $message = $etat_notification->getMessage();
                                    $replacements = [
                                        '{{prenom}}' => ucwords($client->getPrenom()),
                                        '{{montant}}' => number_format($montant, 0, ',', ' '),
                                        '{{devise}}' => strtoupper($devise->getNom()),
                                        '{{date_heure}}' => date('d/m/Y à H:i'),
                                        '{{details_solde}}' => $soldeDetails,
                                        '{{nom_entreprise}}' => $site->getEntreprise()->getNom(),
                                    ];
                                    
                                    foreach ($replacements as $tag => $value) {
                                        $message = str_replace($tag, $value, $message);
                                    }

                                }
                            
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
                                    ->setCommentaire('decaissement numéro '.$dernierDecaissement->getReference())
                                    ->setDateEnvoie(new \DateTime())
                                    ->setForfait($service->verifierForfaitDisponible());
                                $entityManager->persist($sms);
                                $entityManager->flush();

                            }
                            
                        }
                    }
                }
            }

            $this->addFlash("success", "decaissement enregistré avec succès :)");
            return $this->redirectToRoute('app_logescom_comptable_sorties_decaissement_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
        }

        if ($request->get("id_client_search")){
            $client_find = $userRep->find($request->get("id_client_search"));
            $soldes_collaborateur = $mouvementCollabRep->findSoldeCollaborateur($client_find);
        }else{
            $client_find = array();
            $soldes_collaborateur = array();
        }
        return $this->render('logescom/comptable/sorties/decaissement/new.html.twig', [
            'decaissement' => $decaissement,
            'form' => $form,
            
            'site' => $site,
            'client_find' => $client_find,
            'soldes_collaborateur' => $soldes_collaborateur,
        ]);
    }

    #[Route('/show/{id}/{site}', name: 'app_logescom_comptable_sorties_decaissement_show', methods: ['GET'])]
    public function show(Decaissement $decaissement, Site $site, EntrepriseRepository $entrepriseRep): Response
    {
        return $this->render('logescom/comptable/sorties/decaissement/show.html.twig', [
            'decaissement' => $decaissement,
            'site' => $site,
        ]);
    }

    #[Route('/edit/{id}/{site}', name: 'app_logescom_comptable_sorties_decaissement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Decaissement $decaissement, DecaissementRepository $decaissementRep, EntityManagerInterface $entityManager, UserRepository $userRep, MouvementCollaborateurRepository $mouvementCollabRep, MouvementCaisseRepository $mouvementCaisseRep, LogicielService $service, OrangeSmsService $orangeService, Site $site, ConfigurationSmsRepository $configSmsRep, EntrepriseRepository $entrepriseRep): Response
    {
        if ($request->isXmlHttpRequest()) {
            $search = $request->query->get('search');
            $clients = $userRep->findUserBySearch(value: $search, site: $site);    
            $response = [];
            foreach ($clients as $client) {
                $response[] = [
                    'nom' => ucwords($client->getPrenom())." ".strtoupper($client->getNom()),
                    'id' => $client->getId()
                ]; // Mettez à jour avec le nom réel de votre propriété
            }
            return new JsonResponse($response);
        }

        $decaissement->setMontant(-$decaissement->getMontant());
        $form = $this->createForm(DecaissementType::class, $decaissement, ['site' => $site, 'decaissement' => $decaissement]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $montantString = $form->get('montant')->getData();
            $montantString = preg_replace('/[^0-9,.]/', '', $montantString);
            $montant = floatval($montantString);
            $client = $request->get('collaborateur');
            $client = $userRep->find($client);
            $decaissement->setMontant(-$montant)
                        ->setCollaborateur($client)
                        ->setSaisiePar($this->getUser())
                        ->setDateSaisie(new \DateTime("now"));
            $justificatif =$form->get("document")->getData();
            if ($justificatif) {
                if ($decaissement->getDocument()) {
                    $ancienJustificatif=$this->getParameter("dossier_decaissement")."/".$decaissement->getDocument();
                    if (file_exists($ancienJustificatif)) {
                        unlink($ancienJustificatif);
                    }
                }
                $nomJustificatif= pathinfo($justificatif->getClientOriginalName(), PATHINFO_FILENAME);
                $slugger = new AsciiSlugger();
                $nouveauNomJustificatif = $slugger->slug($nomJustificatif);
                $nouveauNomJustificatif .="_".uniqid();
                $nouveauNomJustificatif .= "." .$justificatif->guessExtension();
                $justificatif->move($this->getParameter("dossier_decaissement"),$nouveauNomJustificatif);
                $decaissement->setDocument($nouveauNomJustificatif);

            }

            $mouvement_collab = $mouvementCollabRep->findOneBy(['decaissement' => $decaissement]); 
            $mouvement_collab->setCollaborateur($client)
                    ->setMontant(- $montant)
                    ->setDevise($form->getViewData()->getDevise())
                    ->setSite($site)
                    ->setDateOperation($form->getViewData()->getDateOperation())
                    ->setDateSaisie(new \DateTime("now"));

           
            $entityManager->persist($decaissement);
            $entityManager->flush();

            # gestion envoi sms alert
            if ($service->estConnecteInternet()) {// vérifie si il ya une connexion internet
                # on verifie si l'envoi de notification est actif pour le decaissement
                $etat_notification = $configSmsRep->findOneBy(['nom' => 'suppression_modification', 'etat' => 'actif']);

                if ($etat_notification) {                
                    if ($service->verifierForfaitDisponible()) {// verifie si il ya un forfait disponible
                        // Forfait disponible : envoyer le SMS
                        $telephone = $site->getEntreprise()->getTelephone();
                        $telephone = $service->normaliserTelephone($telephone);
                        if ($telephone and strlen($telephone) >= 9) {

                            $recipientPhoneNumber = $telephone;
                            $countrySenderNumber = 'tel:+2240000'; 

                            $message  = "⚠️ Alerte Modification Décaissement ⚠️\n";
                            $message .= "Le decaissement n° " . $decaissement->getReference() . " de " . $decaissement->getCollaborateur()->getPrenom() . " " . $decaissement->getCollaborateur()->getNom() . " d'un montant de " . number_format($decaissement->getMontant(), 0, ',', ' ') . " a été modifié.\n";
                            $message .= "Date de modification : " . date('d/m/Y à H:i') . ".\n";
                            $message .= "Modifié par : " . ucwords($this->getUser()->getPrenom()) . " " . strtoupper($this->getUser()->getNom()) . ".";
                            
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
                                    ->setCommentaire('decaissement numéro '.$decaissement->getReference())
                                    ->setDateEnvoie(new \DateTime())
                                    ->setForfait($service->verifierForfaitDisponible());
                                $entityManager->persist($sms);
                                $entityManager->flush();

                            }
                            
                        }
                    }
                }
            }

            $this->addFlash("success", "Decaissement modifié avec succès :)");
            return $this->redirectToRoute('app_logescom_comptable_sorties_decaissement_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
            
        }

        if ($request->get("id_client_search")){
            $client_find = $userRep->find($request->get("id_client_search"));
            $soldes_collaborateur = $mouvementCollabRep->findSoldeCollaborateur($client_find);
        }else{
            $client_find = $decaissement->getCollaborateur();
            $soldes_collaborateur = $mouvementCollabRep->findSoldeCollaborateur($client_find);
        }

        return $this->render('logescom/comptable/sorties/decaissement/edit.html.twig', [
            'decaissement' => $decaissement,
            'form' => $form,
            
            'site' => $site,
            'client_find' => $client_find,
            'soldes_collaborateur' => $soldes_collaborateur
        ]);
    }

    #[Route('/confirm/delete/{id}', name: 'app_logescom_comptable_sorties_decaissement_confirm_delete', methods: ['GET', 'POST'])]
    public function confirmDelete(Decaissement $decaissement, Request $request): Response
    {
        $param = $request->request->get('param'); // Récupération du paramètre

        $route_suppression = $this->generateUrl('app_logescom_comptable_sorties_decaissement_delete', [
            'id' => $decaissement->getId(),
            'site' => $decaissement->getSite()->getId(),
        ]);
        

        return $this->render('logescom/comptable/sorties/decaissement/confirm_delete.html.twig', [
            'route_suppression' => $route_suppression,
            'param' => $param,
            'site' => $decaissement->getSite(),
            'entreprise' => $decaissement->getSite()->getEntreprise(),
            'operation' => $decaissement
        ]);
    }

    #[Route('/delete/{id}/{site}', name: 'app_logescom_comptable_sorties_decaissement_delete', methods: ['POST'])]
    public function delete(Request $request, Decaissement $decaissement, EntityManagerInterface $entityManager, Site $site, LogicielService $service, OrangeSmsService $orangeService, ConfigurationSmsRepository $configSmsRep, Filesystem $filesystem,): Response
    {
        if ($this->isCsrfTokenValid('delete'.$decaissement->getId(), $request->request->get('_token'))) {
            $justificatif = $decaissement->getDocument();
            $pdfPath = $this->getParameter("dossier_decaissement") . '/' . $justificatif;
            // Si le chemin du justificatif existe, supprimez également le fichier
            if ($justificatif && $filesystem->exists($pdfPath)) {
                $filesystem->remove($pdfPath);
            }

            $entityManager->remove($decaissement);

            $deleteReason = $request->request->get('delete_reason');
            $reference = $decaissement->getReference();
            $montant = $decaissement->getMontant();

            
            $dateOperation = $decaissement->getDateOperation() 
                ? $decaissement->getDateOperation()->format('d/m/Y H:i') 
                : 'Date non définie';

            $information = "Reference {$reference} | Client : {$decaissement->getCollaborateur()->getNomComplet()} | Montant : {$montant} | Date : {$dateOperation}";

            $personnel = $this->getUser();
            $historiqueSup = new HistoriqueChangement();
            $historiqueSup->setSaisiePar($personnel)
                    ->setDateSaisie(new \DateTime())
                    ->setMotif($deleteReason)
                    ->setUser($decaissement->getCollaborateur())
                    ->setInformation($information)
                    ->setType('decaissement')
                    ->setSite($decaissement->getSite());
            $entityManager->persist($historiqueSup);

            $entityManager->flush();

            # gestion envoi sms alert
            if ($service->estConnecteInternet()) {// vérifie si il ya une connexion internet
                # on verifie si l'envoi de notification est actif pour le decaissement
                $etat_notification = $configSmsRep->findOneBy(['nom' => 'suppression_modification', 'etat' => 'actif']);

                if ($etat_notification) {                
                    if ($service->verifierForfaitDisponible()) {// verifie si il ya un forfait disponible
                        // Forfait disponible : envoyer le SMS
                        $telephone = $site->getEntreprise()->getTelephone();
                        $telephone = $service->normaliserTelephone($telephone);
                        if ($telephone and strlen($telephone) >= 9) {

                            $recipientPhoneNumber = $telephone;
                            $countrySenderNumber = 'tel:+2240000'; 
                            
                            

                            $message  = "⚠️ Alerte Suppression décaissement ⚠️\n";
                            $message .= "Le decaissement n° " . $decaissement->getReference() . " de " . $decaissement->getCollaborateur()->getPrenom() . " " . $decaissement->getCollaborateur()->getNom() . " d'un montant de " . number_format($decaissement->getMontant(), 0, ',', ' ') . " a été supprimé.\n";
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
                                    ->setCommentaire($decaissement->getCommentaire())
                                    ->setDateEnvoie(new \DateTime())
                                    ->setForfait($service->verifierForfaitDisponible());
                                $entityManager->persist($sms);
                                $entityManager->flush();

                            }
                            
                        }
                    }
                }
            }

            $this->addFlash("success", "decaissement supprimé avec succès :)");
        }

        return $this->redirectToRoute('app_logescom_comptable_sorties_decaissement_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/pdf/reçu/{id}/{site}', name: 'app_logescom_comptable_sorties_decaissement_recu_pdf', methods: ['GET'])]
    public function recuPdf(Decaissement $decaissement, Site $site, MouvementCollaborateurRepository $mouvementCollabRep,)
    {
        $entreprise = $site->getEntreprise();
        $logoPath = $this->getParameter('kernel.project_dir') . '/public/images/img_logos/'.$entreprise->getLogo();
        $logoBase64 = base64_encode(file_get_contents($logoPath));

        $soleCollaborateur = $mouvementCollabRep->findSoldeCollaborateur($decaissement->getCollaborateur());

        $collaborateur = $decaissement->getCollaborateur();
        $dateOp = $decaissement->getDateOperation();

        $ancienSoleCollaborateur = $mouvementCollabRep->findAncienSoldeCollaborateur($collaborateur, $dateOp);

        $html = $this->renderView('logescom/comptable/sorties/decaissement/recu_pdf.html.twig', [
            'decaissement' => $decaissement,
            'solde_collaborateur' => $soleCollaborateur,
            'ancien_solde' => $ancienSoleCollaborateur,
            'logoPath' => $logoBase64,
            'site' => $site,

            // 'qrCode'    => $qrCode,
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
            'Content-Disposition' => 'inline; filename="réçu_decaissement.pdf"',
        ]);
    }
}
