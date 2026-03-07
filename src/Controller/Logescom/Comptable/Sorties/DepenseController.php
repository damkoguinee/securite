<?php

namespace App\Controller\Logescom\Comptable\Sorties;

use App\Entity\CategorieDepense;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\Site;
use App\Entity\SmsEnvoyes;
use App\Entity\LieuxVentes;
use App\Entity\Depense;
use App\Entity\Modification;
use App\Form\DepenseType;
use App\Entity\MouvementCaisse;
use App\Service\LogicielService;
use App\Service\OrangeSmsService;
use App\Entity\DeleteDepense;
use App\Repository\UserRepository;
use App\Entity\HistoriqueChangement;
use App\Repository\ClientRepository;
use App\Repository\DeviseRepository;
use App\Entity\MouvementCollaborateur;
use App\Repository\CategorieDepenseRepository;
use App\Repository\PersonelRepository;
use App\Repository\EntrepriseRepository;
use App\Repository\ForfaitSmsRepository;
use App\Repository\SmsEnvoyesRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\LieuxVentesRepository;
use App\Repository\ConfigDeviseRepository;
use App\Repository\DepenseRepository;
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

#[Route('/logescom/comptable/sorties/depense')]
class DepenseController extends AbstractController
{
    #[Route('/accueil/{site}', name: 'app_logescom_comptable_sorties_depense_index', methods: ['GET'])]
    public function index(DepenseRepository $depenseRepository, CategorieDepenseRepository $categorieDepenseRep, Request $request, Site $site): Response
    {
        if ($request->get("categorie")){
            $search = $categorieDepenseRep->find($request->get("categorie"));
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
            $depenses = $depenseRepository->findDepenseSearch(site: $site, startDate: $date1, endDate: $date2, categorie: $search, pageEnCours: $pageEncours, limit: 100);

            $cumulDepenses = $depenseRepository->totalDepenses(site: $site, categorie: $search, startDate: $date1, endDate: $date2, alwaysGroupByDevise: true);

            $cumulGeneralDepenses = $depenseRepository->totalDepenses(site: $site, categorie: $search, alwaysGroupByDevise: true);

        }else{
            $depenses = $depenseRepository->findDepenseSearch(site: $site, startDate: $date1, endDate: $date2, pageEnCours: $pageEncours, limit: 100);

            $cumulDepenses = $depenseRepository->totalDepenses(site: $site, startDate: $date1, endDate: $date2, alwaysGroupByDevise: true);

            $cumulGeneralDepenses = $depenseRepository->totalDepenses(site: $site, alwaysGroupByDevise: true);
        }

        return $this->render('logescom/comptable/sorties/depense/index.html.twig', [
            'depenses' => $depenses,
            'site' => $site,
            'categories' => $categorieDepenseRep->findBy([], ['nom' => 'ASC']),
            'search' => $search,
            'cumulMontantOperations' => $cumulDepenses,
            'cumulGeneralMontantOperations' => $cumulGeneralDepenses,
            'categorie' => $search

        ]);
    }

    #[Route('/new/{site}', name: 'app_logescom_comptable_sorties_depense_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, Site $site, ConfigurationSmsRepository $configSmsRep, LogicielService $service, OrangeSmsService $orangeService): Response
    {
        $depense = new Depense();
        $form = $this->createForm(DepenseType::class, $depense, ['site' => $site, 'depense' => $depense]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $montant = floatval(preg_replace('/[^0-9,.]/', '', $form->get('montant')->getData())); 
            $depense->setSite($site)
                        ->setSaisiePar($this->getUser())
                        ->setMontant(-$montant)
                        ->setDevise($form->getViewData()->getDevise())
                        ->setCaisse($form->getViewData()->getCaisse())
                        ->setModePaie($form->getViewData()->getModePaie())
                        ->setTaux(1)
                        ->setSite($site)
                        ->setTypeMouvement("depense")
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
                $fichier->move($this->getParameter("dossier_depense"),$nouveauNomFichier);
                $depense->setDocument($nouveauNomFichier);
            }
            $entityManager->persist($depense);
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

                            $message  = "💡 Dépense enregistrée 💡\n";
                            $message .= "Motif : " . $depense->getCommentaire() . "\n";
                            $message .= "Montant : " . number_format($depense->getMontant(), 0, ',', ' ') . " F CFA\n";
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
                                    ->setCommentaire('motif '.$depense->getCommentaire())
                                    ->setDateEnvoie(new \DateTime())
                                    ->setForfait($service->verifierForfaitDisponible());
                                $entityManager->persist($sms);
                                $entityManager->flush();

                            }
                            
                        }
                    }
                }
            }

            $this->addFlash("success", "Depense enregistré avec succès :)");
            return $this->redirectToRoute('app_logescom_comptable_sorties_depense_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('logescom/comptable/sorties/depense/new.html.twig', [
            'depense' => $depense,
            'form' => $form,
            'site' => $site,
        ]);
    }

    #[Route('/show/{id}/{site}', name: 'app_logescom_comptable_sorties_depense_show', methods: ['GET'])]
    public function show(Depense $depense, Site $site, EntrepriseRepository $entrepriseRep): Response
    {
        return $this->render('logescom/comptable/sorties/depense/show.html.twig', [
            'depense' => $depense,
            'site' => $site,
        ]);
    }

    #[Route('/edit/{id}/{site}', name: 'app_logescom_comptable_sorties_depense_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Depense $depense, DepenseRepository $depenseRep, EntityManagerInterface $entityManager, UserRepository $userRep, MouvementCollaborateurRepository $mouvementCollabRep, MouvementCaisseRepository $mouvementCaisseRep, LogicielService $service, OrangeSmsService $orangeService, Site $site, ConfigurationSmsRepository $configSmsRep, EntrepriseRepository $entrepriseRep): Response
    {
        $depense->setMontant(-$depense->getMontant());
        $form = $this->createForm(DepenseType::class, $depense, ['site' => $site, 'depense' => $depense]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $montant = floatval(preg_replace('/[^0-9,.]/', '', $form->get('montant')->getData()));
            $depense->setMontant(-$montant)
                        ->setSaisiePar($this->getUser())
                        ->setDateSaisie(new \DateTime("now"));
            $justificatif =$form->get("document")->getData();
            if ($justificatif) {
                if ($depense->getDocument()) {
                    $ancienJustificatif=$this->getParameter("dossier_depense")."/".$depense->getDocument();
                    if (file_exists($ancienJustificatif)) {
                        unlink($ancienJustificatif);
                    }
                }
                $nomJustificatif= pathinfo($justificatif->getClientOriginalName(), PATHINFO_FILENAME);
                $slugger = new AsciiSlugger();
                $nouveauNomJustificatif = $slugger->slug($nomJustificatif);
                $nouveauNomJustificatif .="_".uniqid();
                $nouveauNomJustificatif .= "." .$justificatif->guessExtension();
                $justificatif->move($this->getParameter("dossier_depense"),$nouveauNomJustificatif);
                $depense->setDocument($nouveauNomJustificatif);

            }
            $entityManager->persist($depense);
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
                            
                            

                            $message  = "⚠️ Alerte Modification Dépense ⚠️\n";
                            $message .= "La dépense n° " . $depense->getId() . " d'un montant de " . number_format($montant, 0, ',', ' ') . " a été modifiée.\n";
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
                                    ->setCommentaire('dépense numéro '.$depense->getId())
                                    ->setDateEnvoie(new \DateTime())
                                    ->setForfait($service->verifierForfaitDisponible());
                                $entityManager->persist($sms);
                                $entityManager->flush();

                            }
                            
                        }
                    }
                }
            }

            $this->addFlash("success", "Depense modifié avec succès :)");
            return $this->redirectToRoute('app_logescom_comptable_sorties_depense_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
            
        }

        return $this->render('logescom/comptable/sorties/depense/edit.html.twig', [
            'depense' => $depense,
            'form' => $form,
            'site' => $site
        ]);
    }

    #[Route('/confirm/delete/{id}', name: 'app_logescom_comptable_sorties_depense_confirm_delete', methods: ['GET', 'POST'])]
    public function confirmDelete(Depense $depense, Request $request): Response
    {
        $param = $request->request->get('param'); // Récupération du paramètre

        $route_suppression = $this->generateUrl('app_logescom_comptable_sorties_depense_delete', [
            'id' => $depense->getId(),
            'site' => $depense->getSite()->getId(),
        ]);
        

        return $this->render('logescom/comptable/sorties/depense/confirm_delete.html.twig', [
            'route_suppression' => $route_suppression,
            'param' => $param,
            'site' => $depense->getSite(),
            'entreprise' => $depense->getSite()->getEntreprise(),
            'operation' => $depense
        ]);
    }

    #[Route('/delete/{id}/{site}', name: 'app_logescom_comptable_sorties_depense_delete', methods: ['POST'])]
    public function delete(Request $request, Depense $depense, EntityManagerInterface $entityManager, Site $site, LogicielService $service, OrangeSmsService $orangeService, ConfigurationSmsRepository $configSmsRep, Filesystem $filesystem,): Response
    {
        if ($this->isCsrfTokenValid('delete'.$depense->getId(), $request->request->get('_token'))) {
            $justificatif = $depense->getDocument();
            $pdfPath = $this->getParameter("dossier_depense") . '/' . $justificatif;
            // Si le chemin du justificatif existe, supprimez également le fichier
            if ($justificatif && $filesystem->exists($pdfPath)) {
                $filesystem->remove($pdfPath);
            }

            $entityManager->remove($depense);

            $deleteReason = $request->request->get('delete_reason');
            $montant = $depense->getMontant();

            
            $dateOperation = $depense->getDateOperation() 
                ? $depense->getDateOperation()->format('d/m/Y H:i') 
                : 'Date non définie';

            $information = "Montant : {$montant} | Date : {$dateOperation}";

            $personnel = $this->getUser();
            $historiqueSup = new HistoriqueChangement();
            $historiqueSup->setSaisiePar($personnel)
                    ->setDateSaisie(new \DateTime())
                    ->setMotif($deleteReason)
                    ->setInformation($information)
                    ->setType('depense')
                    ->setSite($depense->getSite());
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
                            
                            $message  = "⚠️ Alerte Suppression Dépense ⚠️\n";
                            $message .= "La dépense n° " . $depense->getId() . " d'un montant de " . number_format($depense->getMontant(), 0, ',', ' ') . " a été supprimée.\n";
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
                                    ->setCommentaire($depense->getCommentaire())
                                    ->setDateEnvoie(new \DateTime())
                                    ->setForfait($service->verifierForfaitDisponible());
                                $entityManager->persist($sms);
                                $entityManager->flush();

                            }
                            
                        }
                    }
                }
            }

            $this->addFlash("success", "Depense supprimé avec succès :)");
        }

        return $this->redirectToRoute('app_logescom_comptable_sorties_depense_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/depense/pdf/{site}', name: 'app_logescom_comptable_sorties_depense_pdf')]
    public function depensePdf(Site $site, DepenseRepository $depenseRepository, CategorieDepenseRepository $categorieDepenseRep, Request $request ): Response
    {       
        $logoPath = $this->getParameter('kernel.project_dir') . '/public/images/img_logos/'.$site->getEntreprise()->getLogo();
        $logoBase64 = base64_encode(file_get_contents($logoPath));

        if ($request->get("categorie")){
            $categorie = $categorieDepenseRep->find($request->get("categorie"));
        }else{
            $categorie = "";
        }

        $firstOp = $depenseRepository->findOneBy([], ['dateOperation' => 'ASC']);
        $date1 = $request->get("date1") ? $request->get("date1") : ($firstOp ? $firstOp->getDateOperation()->format('Y-m-d') : $request->get("date1"));
        $date2 = $request->get("date2") ? $request->get("date2") : date("Y-m-d");


        if ($request->get("categorie")){
            $depenses = $depenseRepository->findDepenseSearch(site: $site, startDate: $date1, endDate: $date2, categorie: $categorie);

            $cumulDepenses = $depenseRepository->totalDepenses(site: $site, categorie: $categorie, startDate: $date1, endDate: $date2, alwaysGroupByDevise: true);

        }else{
            $depenses = $depenseRepository->findDepenseSearch(site: $site, startDate: $date1, endDate: $date2);

            $cumulDepenses = $depenseRepository->totalDepenses(site: $site, startDate: $date1, endDate: $date2, alwaysGroupByDevise: true);
        }
        // Grouper les dépenses par catégorie
        $depensesGroupeesParCategorie = [];
        foreach ($depenses['data'] as $dep) {
            $categorieDepense = $dep->getCategorieDepense()->getNom(); // Assume que getNom() retourne le nom de la catégorie
            if (!isset($depensesGroupeesParCategorie[$categorieDepense])) {
                $depensesGroupeesParCategorie[$categorieDepense] = [];
            }
            $depensesGroupeesParCategorie[$categorieDepense][] = $dep;
        }
        
        
        $html = $this->renderView('logescom/comptable/sorties/depense/depense_pdf.html.twig', [           
            'logoPath' => $logoBase64,
            'site' => $site,
            'depensesGroupeesParCategorie' => $depensesGroupeesParCategorie,
            'cumulDepenses' => $cumulDepenses,
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
            'Content-Disposition' => 'inline; filename=depenses_'.date("d/m/Y à H:i").'".pdf"',
        ]);
    }

    
}
