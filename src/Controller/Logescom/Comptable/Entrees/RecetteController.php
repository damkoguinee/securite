<?php

namespace App\Controller\Logescom\Comptable\Entrees;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\Site;
use App\Entity\Recette;
use App\Form\RecetteType;
use App\Entity\SmsEnvoyes;
use App\Service\LogicielService;
use App\Service\OrangeSmsService;
use App\Entity\HistoriqueChangement;
use App\Repository\RecetteRepository;
use App\Repository\EntrepriseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\CategorieRecetteRepository;
use App\Repository\ConfigurationSmsRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/logescom/comptable/entrees/recette')]
class RecetteController extends AbstractController
{
    #[Route('/accueil/{site}', name: 'app_logescom_comptable_entrees_recette_index', methods: ['GET'])]
    public function index(Request $request, RecetteRepository $recetteRep,CategorieRecetteRepository $categorieRecetteRep, SessionInterface $session,  Site $site, EntrepriseRepository $entrepriseRep): Response
    {
        if ($request->get("categorie")){
            $search = $categorieRecetteRep->find($request->get("categorie"));
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
        if ($request->get("categorie")){
            $recettes = $recetteRep->findRecetteSearch(site: $site, startDate: $date1, endDate: $date2, categorie: $search, pageEnCours: $pageEncours, limit: 100);

            $cumulRecettes = $recetteRep->totalRecettes(site: $site, categorie: $search, startDate: $date1, endDate: $date2, alwaysGroupByDevise: true);

            $cumulGeneralRecettes = $recetteRep->totalRecettes(site: $site, categorie: $search, alwaysGroupByDevise: true);

        }else{
            $recettes = $recetteRep->findRecetteSearch(site: $site, startDate: $date1, endDate: $date2, pageEnCours: $pageEncours, limit: 100);

            $cumulRecettes = $recetteRep->totalRecettes(site: $site, startDate: $date1, endDate: $date2, alwaysGroupByDevise: true);

            $cumulGeneralRecettes = $recetteRep->totalRecettes(site: $site, alwaysGroupByDevise: true);
        }
        return $this->render('logescom/comptable/entrees/recette/index.html.twig', [
            'recettes' => $recettes,
            'cumulMontantOperations' => $cumulRecettes,
            'cumulGeneralMontantOperations' => $cumulGeneralRecettes,
            'entreprise' => $entrepriseRep->find(1),
            'site' => $site,
            'categories' => $categorieRecetteRep->findBy([], ['nom' => 'ASC']),
            'search' => $search,
            'categorie' => $search,
            'date1' => $date1,
            'date2' => $date2,
        ]);
    }

     #[Route('/new/{site}', name: 'app_logescom_comptable_entrees_recette_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, Site $site, ConfigurationSmsRepository $configSmsRep, LogicielService $service, OrangeSmsService $orangeService): Response
    {
        $recette = new Recette();
        $form = $this->createForm(RecetteType::class, $recette, ['site' => $site, 'recette' => $recette]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $montant = floatval(preg_replace('/[^0-9,.]/', '', $form->get('montant')->getData())); 
            $recette->setSite($site)
                        ->setSaisiePar($this->getUser())
                        ->setMontant($montant)
                        ->setDevise($form->getViewData()->getDevise())
                        ->setCaisse($form->getViewData()->getCaisse())
                        ->setModePaie($form->getViewData()->getModePaie())
                        ->setTaux(1)
                        ->setSite($site)
                        ->setTypeMouvement("recette")
                        ->setSaisiePar($this->getUser())
                        ->setDateOperation($form->getViewData()->getDateOperation())
                        ->setDateSaisie(new \DateTime("now"));

            $fichier = $form->get("document")->getData();
            if ($fichier) {
                $nomFichier= pathinfo($fichier->getClientOriginalName(), PATHINFO_FILENAME);
                $slugger = new AsciiSlugger();
                $nouveauNomFichier = $slugger->slug($nomFichier);
                $nouveauNomFichier .="_".uniqid();
                $nouveauNomFichier .= "." .$fichier->guessExtension();
                $fichier->move($this->getParameter("dossier_recette"),$nouveauNomFichier);
                $recette->setDocument($nouveauNomFichier);
            }
            $entityManager->persist($recette);
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

                            $message  = "💡 recette enregistrée 💡\n";
                            $message .= "Motif : " . $recette->getCommentaire() . "\n";
                            $message .= "Montant : " . number_format($recette->getMontant(), 0, ',', ' ') . " F CFA\n";
                            $message .= "Date d'enregistrement : " . date('d/m/Y à H:i') . "\n";
                            $message .= "Saisie effectuée par : " . ucwords($this->getUser()->getPrenom()) . " " . strtoupper($this->getUser()->getNom()) . ".";

                            
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
                                    ->setCommentaire('motif '.$recette->getCommentaire())
                                    ->setDateEnvoie(new \DateTime())
                                    ->setForfait($service->verifierForfaitDisponible());
                                $entityManager->persist($sms);
                                $entityManager->flush();

                            }
                            
                        }
                    }
                }
            }

            $this->addFlash("success", "Recette enregistré avec succès :)");
            return $this->redirectToRoute('app_logescom_comptable_entrees_recette_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('logescom/comptable/entrees/recette/new.html.twig', [
            'recette' => $recette,
            'form' => $form,
            'site' => $site,
        ]);
    }

    #[Route('/show/{id}/{site}', name: 'app_logescom_comptable_entrees_recette_show', methods: ['GET'])]
    public function show(Recette $recette, Site $site, EntrepriseRepository $entrepriseRep): Response
    {
        return $this->render('logescom/comptable/entrees/recette/show.html.twig', [
            'recette' => $recette,
            'site' => $site,
        ]);
    }

    #[Route('/edit/{id}/{site}', name: 'app_logescom_comptable_entrees_recette_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Recette $recette, EntityManagerInterface $entityManager, LogicielService $service, OrangeSmsService $orangeService, Site $site, ConfigurationSmsRepository $configSmsRep): Response
    {
        $form = $this->createForm(RecetteType::class, $recette, ['site' => $site, 'recette' => $recette]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $montant = floatval(preg_replace('/[^0-9,.]/', '', $form->get('montant')->getData()));
            $recette->setMontant($montant)
                        ->setSaisiePar($this->getUser())
                        ->setDateSaisie(new \DateTime("now"));
            $justificatif =$form->get("document")->getData();
            if ($justificatif) {
                if ($recette->getDocument()) {
                    $ancienJustificatif=$this->getParameter("dossier_recette")."/".$recette->getDocument();
                    if (file_exists($ancienJustificatif)) {
                        unlink($ancienJustificatif);
                    }
                }
                $nomJustificatif= pathinfo($justificatif->getClientOriginalName(), PATHINFO_FILENAME);
                $slugger = new AsciiSlugger();
                $nouveauNomJustificatif = $slugger->slug($nomJustificatif);
                $nouveauNomJustificatif .="_".uniqid();
                $nouveauNomJustificatif .= "." .$justificatif->guessExtension();
                $justificatif->move($this->getParameter("dossier_recette"),$nouveauNomJustificatif);
                $recette->setDocument($nouveauNomJustificatif);

            }
            $entityManager->persist($recette);
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
                            
                            

                            $message  = "⚠️ Alerte Modification recette ⚠️\n";
                            $message .= "La recette n° " . $recette->getId() . " d'un montant de " . number_format($montant, 0, ',', ' ') . " a été modifiée.\n";
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
                                    ->setCommentaire('recette numéro '.$recette->getId())
                                    ->setDateEnvoie(new \DateTime())
                                    ->setForfait($service->verifierForfaitDisponible());
                                $entityManager->persist($sms);
                                $entityManager->flush();

                            }
                            
                        }
                    }
                }
            }

            $this->addFlash("success", "Recette modifié avec succès :)");
            return $this->redirectToRoute('app_logescom_comptable_entrees_recette_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
            
        }

        return $this->render('logescom/comptable/entrees/recette/edit.html.twig', [
            'recette' => $recette,
            'form' => $form,
            'site' => $site
        ]);
    }

    #[Route('/confirm/delete/{id}', name: 'app_logescom_comptable_entrees_recette_confirm_delete', methods: ['GET', 'POST'])]
    public function confirmDelete(Recette $recette, Request $request): Response
    {
        $param = $request->request->get('param'); // Récupération du paramètre

        $route_suppression = $this->generateUrl('app_logescom_comptable_entrees_recette_delete', [
            'id' => $recette->getId(),
            'site' => $recette->getSite()->getId(),
        ]);
        

        return $this->render('logescom/comptable/entrees/recette/confirm_delete.html.twig', [
            'route_suppression' => $route_suppression,
            'param' => $param,
            'site' => $recette->getSite(),
            'entreprise' => $recette->getSite()->getEntreprise(),
            'operation' => $recette
        ]);
    }

    #[Route('/delete/{id}/{site}', name: 'app_logescom_comptable_entrees_recette_delete', methods: ['POST'])]
    public function delete(Request $request, Recette $recette, EntityManagerInterface $entityManager, Site $site, LogicielService $service, OrangeSmsService $orangeService, ConfigurationSmsRepository $configSmsRep, Filesystem $filesystem,): Response
    {
        if ($this->isCsrfTokenValid('delete'.$recette->getId(), $request->request->get('_token'))) {
            $justificatif = $recette->getDocument();
            $pdfPath = $this->getParameter("dossier_recette") . '/' . $justificatif;
            // Si le chemin du justificatif existe, supprimez également le fichier
            if ($justificatif && $filesystem->exists($pdfPath)) {
                $filesystem->remove($pdfPath);
            }

            $entityManager->remove($recette);

            $deleteReason = $request->request->get('delete_reason');
            $montant = $recette->getMontant();

            
            $dateOperation = $recette->getDateOperation() 
                ? $recette->getDateOperation()->format('d/m/Y H:i') 
                : 'Date non définie';

            $information = "Montant : {$montant} | Date : {$dateOperation}";

            $personnel = $this->getUser();
            $historiqueSup = new HistoriqueChangement();
            $historiqueSup->setSaisiePar($personnel)
                    ->setDateSaisie(new \DateTime())
                    ->setMotif($deleteReason)
                    ->setInformation($information)
                    ->setType('recette')
                    ->setSite($recette->getSite());
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
                            
                            $message  = "⚠️ Alerte Suppression recette ⚠️\n";
                            $message .= "La recette n° " . $recette->getId() . " d'un montant de " . number_format($recette->getMontant(), 0, ',', ' ') . " a été supprimée.\n";
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
                                    ->setCommentaire($recette->getCommentaire())
                                    ->setDateEnvoie(new \DateTime())
                                    ->setForfait($service->verifierForfaitDisponible());
                                $entityManager->persist($sms);
                                $entityManager->flush();

                            }
                            
                        }
                    }
                }
            }

            $this->addFlash("success", "Recette supprimé avec succès :)");
        }

        return $this->redirectToRoute('app_logescom_comptable_entrees_recette_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/recette/pdf/{site}', name: 'app_logescom_comptable_entrees_recette_pdf')]
    public function recettePdf(Site $site, RecetteRepository $recetteRepository, CategorieRecetteRepository $categorieRecetteRep, Request $request ): Response
    {       
        $logoPath = $this->getParameter('kernel.project_dir') . '/public/images/img_logos/'.$site->getEntreprise()->getLogo();
        $logoBase64 = base64_encode(file_get_contents($logoPath));

        if ($request->get("categorie")){
            $categorie = $categorieRecetteRep->find($request->get("categorie"));
        }else{
            $categorie = "";
        }

        $firstOp = $recetteRepository->findOneBy([], ['dateOperation' => 'ASC']);
        $date1 = $request->get("date1") ? $request->get("date1") : ($firstOp ? $firstOp->getDateOperation()->format('Y-m-d') : $request->get("date1"));
        $date2 = $request->get("date2") ? $request->get("date2") : date("Y-m-d");


        if ($request->get("categorie")){
            $recettes = $recetteRepository->findRecetteSearch(site: $site, startDate: $date1, endDate: $date2, categorie: $categorie);

            $cumulRecettes = $recetteRepository->totalRecettes(site: $site, categorie: $categorie, startDate: $date1, endDate: $date2, alwaysGroupByDevise: true);

        }else{
            $recettes = $recetteRepository->findRecetteSearch(site: $site, startDate: $date1, endDate: $date2);

            $cumulRecettes = $recetteRepository->totalRecettes(site: $site, startDate: $date1, endDate: $date2, alwaysGroupByDevise: true);
        }
        // Grouper les recettes par catégorie
        $recettesGroupeesParCategorie = [];
        foreach ($recettes['data'] as $dep) {
            $categorieRecette = $dep->getCategorieRecette()->getNom(); // Assume que getNom() retourne le nom de la catégorie
            if (!isset($recettesGroupeesParCategorie[$categorieRecette])) {
                $recettesGroupeesParCategorie[$categorieRecette] = [];
            }
            $recettesGroupeesParCategorie[$categorieRecette][] = $dep;
        }
        
        
        $html = $this->renderView('logescom/comptable/entrees/recette/recette_pdf.html.twig', [           
            'logoPath' => $logoBase64,
            'site' => $site,
            'recettesGroupeesParCategorie' => $recettesGroupeesParCategorie,
            'cumulRecettes' => $cumulRecettes,
            'date1' => $date1,
            'date2' => $date2,
            'categorie' => $categorie,
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
            'Content-Disposition' => 'inline; filename=recettes_'.date("d/m/Y à H:i").'".pdf"',
        ]);
    }

    
}
