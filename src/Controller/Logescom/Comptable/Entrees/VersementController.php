<?php

namespace App\Controller\Logescom\Comptable\Entrees;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\Site;
use App\Entity\Versement;
use App\Entity\SmsEnvoyes;
use App\Entity\LieuxVentes;
use App\Form\VersementType;
use App\Entity\Modification;
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
use App\Repository\VersementRepository;
use App\Repository\EntrepriseRepository;
use App\Repository\ForfaitSmsRepository;
use App\Repository\SmsEnvoyesRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\LieuxVentesRepository;
use App\Repository\ConfigDeviseRepository;
use App\Repository\ModificationRepository;
use App\Repository\CompteOperationRepository;
use App\Repository\MouvementCaisseRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\ConfigurationSmsRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\CategorieOperationRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\ConfigurationLogicielRepository;
use App\Repository\MouvementCollaborateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/logescom/comptable/entrees/versement')]
class VersementController extends AbstractController
{
    #[Route('/accueil/{site}', name: 'app_logescom_comptable_entrees_versement_index', methods: ['GET'])]
    public function index(VersementRepository $versementRepository, UserRepository $userRep, Request $request, Site $site): Response
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
            $utilisateurs = $userRep->findUserBySearch(value:$search, site: $site);    
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
            $versements = $versementRepository->findVersementSearch(site: $site, search: $search, startDate: $date1, endDate: $date2, pageEnCours: $pageEncours, limit: 100);
        }else{
            $versements = $versementRepository->findVersementSearch(site: $site, startDate: $date1, endDate: $date2, pageEnCours: $pageEncours, limit: 100);

        }
        return $this->render('logescom/comptable/entrees/versement/index.html.twig', [
            'versements' => $versements,
            'search' => $search,
            
            'site' => $site,
            'date1' => $date1,
            'date2' => $date2,
        ]);
    }

    #[Route('/new/{site}', name: 'app_logescom_comptable_entrees_versement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, Site $site, ConfigDeviseRepository $deviseRep, VersementRepository $versementRep, MouvementCollaborateurRepository $mouvementCollabRep, UserRepository $userRep, ConfigurationSmsRepository $configSmsRep, ForfaitSmsRepository $forfaitSmsRep, LogicielService $service, OrangeSmsService $orangeService): Response
    {
        if ($request->isXmlHttpRequest()) {
            $search = $request->query->get('search');
            $utilisateurs = $userRep->findUserBySearch(value:$search, site: $site);    
            $response = [];
            foreach ($utilisateurs as $client) {
                $response[] = [
                    'nom' => ucwords($client->getPrenom())." ".strtoupper($client->getNom()),
                    'id' => $client->getId()
                ]; // Mettez à jour avec le nom réel de votre propriété
            }
            return new JsonResponse($response);
        }
        $versement = new Versement();
        $form = $this->createForm(VersementType::class, $versement, ['site' => $site, 'versement' => $versement]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $montant = floatval(preg_replace('/[^0-9,.]/', '', $form->get('montant')->getData()));
            $dateDuJour = new \DateTime();
            $referenceDate = $dateDuJour->format('ymd');
            $idSuivant =($versementRep->findMaxId() + 1);
            $reference = "vers".$referenceDate . sprintf('%04d', $idSuivant);
            $client = $request->get('collaborateur');
            $client = $userRep->find($client);

            $versement->setSite($site)
                        ->setCollaborateur($client)
                        ->setSaisiePar($this->getUser())
                        ->setReference($reference)
                        ->setTaux($versement->getTaux() ?? 1)
                        ->setMontant($montant)
                        ->setDateSaisie(new \DateTime("now"))
                        ->setTypeMouvement("versement")
                        ->setDevise($form->getViewData()->getDevise())
                        ->setCaisse($form->getViewData()->getCaisse())
                        ->setModePaie($form->getViewData()->getModePaie())
                        ->setDateOperation($form->getViewData()->getDateOperation());

            $mouvement_collab = new MouvementCollaborateur();

            $taux = $form->getViewData()->getTaux();
            if ($taux == 1) {
                $montant = $montant;
                $devise = $form->getViewData()->getDevise();
            }else{
                $montant = $montant * $taux;
                $devise = $deviseRep->find(1);
            }
            $mouvement_collab->setCollaborateur($client)
                    ->setOrigine("versement")
                    ->setMontant($montant)
                    ->setDevise($devise)
                    ->setSite($site)
                    ->setDateOperation($form->getViewData()->getDateOperation())
                    ->setDateSaisie(new \DateTime("now"));
            $versement->addMouvementCollaborateur($mouvement_collab);
            $entityManager->persist($versement);
            $entityManager->flush();

            $dernierVersement = $versementRep->findOneBy([], ['id' => 'DESC']);
            # gestion de notification sms 
            if ($service->estConnecteInternet()) {// vérifie si il ya une connexion internet

                # on verifie si l'envoi de notification est actif pour le versement

                $etat_notification = $configSmsRep->findOneBy(['nom' => 'versement', 'etat' => 'actif']);
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
                                $message .= "Nous accusons réception de votre versement de " . number_format($montant, 0, ',', ' ') . " " . strtoupper($devise->getNom()) . " enregistré avec succès.\n";
                                $message .= "A la date du " . date('d/m/Y à H:i') . " :\n";
                                $message .= $soldeDetails;
                                $message .= "Nous vous remercions de votre confiance.\n";
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
                                    ->setCommentaire('versement numéro '.$dernierVersement->getReference())
                                    ->setDateEnvoie(new \DateTime())
                                    ->setForfait($service->verifierForfaitDisponible());
                                $entityManager->persist($sms);
                                $entityManager->flush();

                            }
                            
                        }
                    }
                }
            }

            $this->addFlash("success", "versement enregistré avec succès :)");
            return $this->redirectToRoute('app_logescom_comptable_entrees_versement_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
        }

        if ($request->get("id_client_search")){
            $client_find = $userRep->find($request->get("id_client_search"));
            $soldes_collaborateur = $mouvementCollabRep->findSoldeCollaborateur($client_find);
        }else{
            $client_find = array();
            $soldes_collaborateur = array();
        }

        return $this->render('logescom/comptable/entrees/versement/new.html.twig', [
            'versement' => $versement,
            'form' => $form,            
            'site' => $site,
            'client_find' => $client_find,
            'soldes_collaborateur' => $soldes_collaborateur,
        ]);
    }

    #[Route('/show/{id}/{site}', name: 'app_logescom_comptable_entrees_versement_show', methods: ['GET'])]
    public function show(Versement $versement, Site $site): Response
    {
        return $this->render('logescom/comptable/entrees/versement/show.html.twig', [
            'versement' => $versement,
            'site' => $site,
        ]);
    }

    #[Route('/edit/{id}/{site}', name: 'app_logescom_comptable_entrees_versement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Versement $versement, VersementRepository $versementRep, EntityManagerInterface $entityManager, UserRepository $userRep, MouvementCollaborateurRepository $mouvementCollabRep, MouvementCaisseRepository $mouvementCaisseRep, LogicielService $service, OrangeSmsService $orangeService, Site $site, ConfigurationSmsRepository $configSmsRep): Response
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

        $form = $this->createForm(VersementType::class, $versement, ['site' => $site, 'versement' => $versement]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $montantString = $form->get('montant')->getData();
            $montantString = preg_replace('/[^0-9,.]/', '', $montantString);
            $montant = floatval($montantString);
            $client = $request->get('collaborateur');
            $client = $userRep->find($client);
            $versement->setMontant($montant)
                        ->setCollaborateur($client)
                        ->setSaisiePar($this->getUser())
                        ->setDateSaisie(new \DateTime("now"));

            $mouvement_collab = $mouvementCollabRep->findOneBy(['versement' => $versement]); 
            $mouvement_collab->setCollaborateur($client)
                    ->setMontant($montant)
                    ->setDevise($form->getViewData()->getDevise())
                    ->setSite($site)
                    ->setDateOperation($form->getViewData()->getDateOperation())
                    ->setDateSaisie(new \DateTime("now"));
            $entityManager->persist($versement);
            $entityManager->flush();

            # gestion envoi sms alert
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

                            $message  = "⚠️ Alerte Modification Dépôt ⚠️\n";
                            $message .= "Le versement n° " . $versement->getReference() . " de " . $versement->getCollaborateur()->getPrenom() . " " . $versement->getCollaborateur()->getNom() . " d'un montant de " . number_format($versement->getMontant(), 0, ',', ' ') . " a été modifié.\n";
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
                                    ->setCommentaire('versement numéro '.$versement->getReference())
                                    ->setDateEnvoie(new \DateTime())
                                    ->setForfait($service->verifierForfaitDisponible());
                                $entityManager->persist($sms);
                                $entityManager->flush();

                            }
                            
                        }
                    }
                }
            }

            $this->addFlash("success", "Versement modifié avec succès :)");
            return $this->redirectToRoute('app_logescom_comptable_entrees_versement_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
            
        }

        if ($request->get("id_client_search")){
            $client_find = $userRep->find($request->get("id_client_search"));
            $soldes_collaborateur = $mouvementCollabRep->findSoldeCollaborateur($client_find);
        }else{
            $client_find = $versement->getCollaborateur();
            $soldes_collaborateur = $mouvementCollabRep->findSoldeCollaborateur($client_find);
        }

        return $this->render('logescom/comptable/entrees/versement/edit.html.twig', [
            'versement' => $versement,
            'form' => $form,
            
            'site' => $site,
            'client_find' => $client_find,
            'soldes_collaborateur' => $soldes_collaborateur
        ]);
    }

    #[Route('/confirm/delete/{id}', name: 'app_logescom_comptable_entrees_versement_confirm_delete', methods: ['GET', 'POST'])]
    public function confirmDelete(Versement $versement, Request $request): Response
    {
        $param = $request->request->get('param'); // Récupération du paramètre

        $route_suppression = $this->generateUrl('app_logescom_comptable_entrees_versement_delete', [
            'id' => $versement->getId(),
            'site' => $versement->getSite()->getId(),
        ]);
        

        return $this->render('logescom/comptable/entrees/versement/confirm_delete.html.twig', [
            'route_suppression' => $route_suppression,
            'param' => $param,
            'site' => $versement->getSite(),
            'entreprise' => $versement->getSite()->getEntreprise(),
            'operation' => $versement
        ]);
    }

    #[Route('/delete/{id}/{site}', name: 'app_logescom_comptable_entrees_versement_delete', methods: ['POST'])]
    public function delete(Request $request, Versement $versement, EntityManagerInterface $entityManager, Site $site, LogicielService $service, OrangeSmsService $orangeService, ConfigurationSmsRepository $configSmsRep): Response
    {
        if ($this->isCsrfTokenValid('delete'.$versement->getId(), $request->request->get('_token'))) {

            $entityManager->remove($versement);

            $deleteReason = $request->request->get('delete_reason');
            $reference = $versement->getReference();
            $montant = $versement->getMontant();

            
            $dateOperation = $versement->getDateOperation() 
                ? $versement->getDateOperation()->format('d/m/Y H:i') 
                : 'Date non définie';

            $information = "Reference {$reference} | Client : {$versement->getCollaborateur()->getNomComplet()} | Montant : {$montant} | Date : {$dateOperation}";

            $personnel = $this->getUser();
            $historiqueSup = new HistoriqueChangement();
            $historiqueSup->setSaisiePar($personnel)
                    ->setDateSaisie(new \DateTime())
                    ->setMotif($deleteReason)
                    ->setUser($versement->getCollaborateur())
                    ->setInformation($information)
                    ->setType('versement')
                    ->setSite($versement->getSite());
            $entityManager->persist($historiqueSup);

            $entityManager->flush();

            # gestion envoi sms alert
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
                            
                            

                            $message  = "⚠️ Alerte Suppression dépôt ⚠️\n";
                            $message .= "Le versement n° " . $versement->getReference() . " de " . $versement->getCollaborateur()->getPrenom() . " " . $versement->getCollaborateur()->getNom() . " d'un montant de " . number_format($versement->getMontant(), 0, ',', ' ') . " a été supprimé.\n";
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
                                    ->setCommentaire($versement->getCommentaire())
                                    ->setDateEnvoie(new \DateTime())
                                    ->setForfait($service->verifierForfaitDisponible());
                                $entityManager->persist($sms);
                                $entityManager->flush();

                            }
                            
                        }
                    }
                }
            }

            $this->addFlash("success", "versement supprimé avec succès :)");
        }

        return $this->redirectToRoute('app_logescom_comptable_entrees_versement_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/pdf/reçu/{id}/{site}', name: 'app_logescom_comptable_entrees_versement_recu_pdf', methods: ['GET'])]
    public function recuPdf(Versement $versement, Site $site, MouvementCollaborateurRepository $mouvementCollabRep,)
    {
        $entreprise = $site->getEntreprise();
        $logoPath = $this->getParameter('kernel.project_dir') . '/public/images/img_logos/'.$entreprise->getLogo();
        $logoBase64 = base64_encode(file_get_contents($logoPath));

        $soleCollaborateur = $mouvementCollabRep->findSoldeCollaborateur($versement->getCollaborateur());

        $collaborateur = $versement->getCollaborateur();
        $dateOp = $versement->getDateOperation();

        $ancienSoleCollaborateur = $mouvementCollabRep->findAncienSoldeCollaborateur($collaborateur, $dateOp);

        $html = $this->renderView('logescom/comptable/entrees/versement/recu_pdf.html.twig', [
            'versement' => $versement,
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
            'Content-Disposition' => 'inline; filename="réçu_versement.pdf"',
        ]);
    }
}
