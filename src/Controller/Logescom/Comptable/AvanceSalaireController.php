<?php

namespace App\Controller\Logescom\Comptable;

use App\Entity\AvanceSalaire;
use App\Entity\HistoriqueChangement;
use App\Entity\MouvementCaisse;
use App\Entity\MouvementCaisses;
use App\Entity\MouvementCollaborateur;
use App\Entity\site;
use App\Entity\SmsEnvoyes;
use App\Form\AvanceSalaireType;
use App\Repository\AvanceSalaireRepository;
use App\Repository\CaisseRepository;
use App\Repository\CategorieOperationRepository;
use App\Repository\CompteOperationRepository;
use App\Repository\ComptesDepotRepository;
use App\Repository\ConfigDeviseRepository;
use App\Repository\ConfigurationSmsRepository;
use App\Repository\ContratSurveillanceRepository;
use App\Repository\EntrepriseRepository;
use App\Repository\MouvementCaisseRepository;
use App\Repository\UserRepository;
use App\Service\LogicielService;
use App\Service\OrangeSmsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/logescom/comptable/avance/salaire')]
class AvanceSalaireController extends AbstractController
{
    #[Route('/{site}', name: 'app_logescom_comptable_avance_salaire_index', methods: ['GET'])]
    public function index(AvanceSalaireRepository $avanceSalaireRepository, Site $site): Response
    {
        
        $avances = $avanceSalaireRepository->findBy(['site' => $site], ['mois' => 'ASC']);
        // Regrouper par mois
        $avancesParMois = [];
        foreach ($avances as $avance) {
            $mois = $avance->getMois(); // ex: "08-2025"
            if (!isset($avancesParMois[$mois])) {
                $avancesParMois[$mois] = [];
            }
            $avancesParMois[$mois][] = $avance;
        }

        return $this->render('logescom/comptable/avance_salaire/index.html.twig', [
            'avancesParMois' => $avancesParMois,
            'site' => $site,
        ]);

    }

    #[Route('/new/{site}', name: 'app_logescom_comptable_avance_salaire_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Site $site, ContratSurveillanceRepository $contratRep, UserRepository $userRep, ConfigDeviseRepository $deviseRep, CaisseRepository $caisseRep, EntityManagerInterface $entityManager, MouvementCaisseRepository $mouvementRep, LogicielService $service, OrangeSmsService $orangeService, ConfigurationSmsRepository $configSmsRep): Response
    {
        /**
         * ===================== AJAX : RECHERCHE CONTRAT =====================
         */
        if ($request->isXmlHttpRequest()) {
            $search = $request->query->get('search');
            $contrats = $contratRep->findContratBySearch(search: $search, site: $site);

            $response = [];
            foreach ($contrats['data'] as $contrat) {
                $response[] = [
                    'nom' => $contrat->getBien()->getClient()->getNomComplet().' '
                        . $contrat->getBien()->getNom().' '
                        . $contrat->getBien()->getClient()->getTelephone(),
                    'id' => $contrat->getId()
                ];
            }

            return new JsonResponse($response);
        }

        $avanceSalaire = new AvanceSalaire(); 
        
        $contrat = $request->get('id_client_search')
            ? $contratRep->find($request->get('id_client_search'))
            : NULL;

        $avanceSalaire->setContrat($contrat);

        $form = $this->createForm(AvanceSalaireType::class, $avanceSalaire, ['site' => $site]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $caisse = $avanceSalaire->getCaisse();
            $solde_caisse = $mouvementRep->findSoldeCaisse($caisse, $avanceSalaire->getDevise());
            $montant = floatval(preg_replace('/[^0-9,.]/', '', $avanceSalaire->getMontant()));

            if ($solde_caisse >= $montant) {
                $avanceSalaire->setSaisiePar($this->getUser())
                            ->setDateOperation($avanceSalaire->getPeriode())
                            ->setMontant(- $montant)
                            ->setDateSaisie(new \DateTime("now"))
                            ->setSite($site)
                            ->setTypeMouvement("avance salaire");
                            
                $date_periode_format = $avanceSalaire->getPeriode()->format('m-Y');
                $avanceSalaire->setMois($date_periode_format);
                
                $entityManager->persist($avanceSalaire);
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

                                $message  = "💡 Avance sur Salaire enregistrée 💡\n";
                                $message .= "Bénéficiaire : " . $avanceSalaire->getPersonnel()->getNomComplet() . "\n";
                                $message .= "Montant : " . number_format($avanceSalaire->getMontant(), 0, ',', ' ') . " F CFA\n";
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
                                        ->setCommentaire('avance sur salaire '.$avanceSalaire->getPersonnel()->getNomComplet())
                                        ->setDateEnvoie(new \DateTime())
                                        ->setForfait($service->verifierForfaitDisponible());
                                    $entityManager->persist($sms);
                                    $entityManager->flush();

                                }
                                
                            }
                        }
                    }
                }

                $this->addFlash("success", "Avance sur salaire enrgistrée avec succès :)");
                return $this->redirectToRoute('app_logescom_comptable_avance_salaire_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
            }else{
                $this->addFlash("warning", "Le montant disponible en caisse est insuffisant");
                // Récupérer l'URL de la page précédente
                $referer = $request->headers->get('referer');
                if ($referer) {
                    $formView = $form->createView();
                    $formDecView = $form->createView();
                    return $this->render('logescom/comptable/avance_salaire/new.html.twig', [
                        
                        'site' => $site,
                        'avance_salaire' => $avanceSalaire,
                        'form' => $formView,
                        'referer' => $referer,
                    ]);
                }
            }
        }

        return $this->render('logescom/comptable/avance_salaire/new.html.twig', [
            'avance_salaire' => $avanceSalaire,
            'form' => $form,
            
            'site' => $site,
        ]);
    }

    #[Route('/show/{id}/{site}', name: 'app_logescom_comptable_avance_salaire_show', methods: ['GET'])]
    public function show(AvanceSalaire $avanceSalaire, Site $site): Response
    {
        return $this->render('logescom/comptable/avance_salaire/show.html.twig', [
            'avance_salaire' => $avanceSalaire,
            'site' => $site,
        ]);
    }

    #[Route('/edit/{id}/{site}', name: 'app_logescom_comptable_avance_salaire_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, AvanceSalaire $avanceSalaire, ContratSurveillanceRepository $contratRep, EntityManagerInterface $entityManager, Site $site, MouvementCaisseRepository $mouvementRep,ConfigDeviseRepository $deviseRep, CaisseRepository $caisseRep, LogicielService $service, OrangeSmsService $orangeService, ConfigurationSmsRepository $configSmsRep): Response
    {
         /**
         * ===================== AJAX : RECHERCHE CONTRAT =====================
         */
        if ($request->isXmlHttpRequest()) {
            $search = $request->query->get('search');
            $contrats = $contratRep->findContratBySearch(search: $search, site: $site);

            $response = [];
            foreach ($contrats['data'] as $contrat) {
                $response[] = [
                    'nom' => $contrat->getBien()->getClient()->getNomComplet().' '
                        . $contrat->getBien()->getNom().' '
                        . $contrat->getBien()->getClient()->getTelephone(),
                    'id' => $contrat->getId()
                ];
            }

            return new JsonResponse($response);
        }

        $avanceSalaire = new AvanceSalaire(); 
        
        $contrat = $request->get('id_client_search')
            ? $contratRep->find($request->get('id_client_search'))
            : NULL;

        $avanceSalaire->setContrat($contrat);

        $avanceSalaire->setMontant(-$avanceSalaire->getMontant());
        $form = $this->createForm(AvanceSalaireType::class, $avanceSalaire, ['site' => $site]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $periode = $avanceSalaire->getPeriode();
            $caisse = $avanceSalaire->getCaisse();
            $montant = floatval(preg_replace('/[^0-9,.]/', '', $avanceSalaire->getMontant()));
            $devise = $avanceSalaire->getDevise();
            $solde_caisse = $mouvementRep->findSoldeCaisse($caisse, $devise);

            if ($solde_caisse >= $montant) {
                $avanceSalaire->setSaisiePar($this->getUser())
                            ->setDateOperation($avanceSalaire->getPeriode())
                            ->setMontant(- $montant)
                            ->setDateSaisie(new \DateTime("now"));

                $entityManager->persist($avanceSalaire);
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
                                $message .= "L'avance sur salaire de " . $avanceSalaire->getPersonnel()->getNomComplet() . " d'un montant de " . number_format($montant, 0, ',', ' ') . " a été modifiée.\n";
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
                                        ->setCommentaire('avance sur salaire '.$avanceSalaire->getId())
                                        ->setDateEnvoie(new \DateTime())
                                        ->setForfait($service->verifierForfaitDisponible());
                                    $entityManager->persist($sms);
                                    $entityManager->flush();

                                }
                                
                            }
                        }
                    }
                }

                $this->addFlash("success", "avance sur salaire modifiée avec succès :)");
                return $this->redirectToRoute('app_logescom_comptable_avance_salaire_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
            }else{
                $this->addFlash("warning", "Le montant disponible en caisse est insuffisant");
                // Récupérer l'URL de la page précédente
                $referer = $request->headers->get('referer');
                if ($referer) {
                    $formView = $form->createView();
                    $formDecView = $form->createView();
                    return $this->render('logescom/comptable/avance_salaire/new.html.twig', [
                        
                        'site' => $site,
                        'avance_salaire' => $avanceSalaire,
                        'form' => $formView,
                        'referer' => $referer,
                    ]);
                }
            }
        }

        return $this->render('logescom/comptable/avance_salaire/edit.html.twig', [
            'avance_salaire' => $avanceSalaire,
            'form' => $form,
            'site' => $site,

        ]);
    }

    #[Route('/confirm/delete/{id}', name: 'app_logescom_comptable_avance_salaire_confirm_delete', methods: ['GET', 'POST'])]
    public function confirmDelete(AvanceSalaire $avanceSalaire, Request $request): Response
    {
        $param = $request->request->get('param'); // Récupération du paramètre

        $route_suppression = $this->generateUrl('app_logescom_comptable_avance_salaire_delete', [
            'id' => $avanceSalaire->getId(),
            'site' => $avanceSalaire->getSite()->getId(),
        ]);
        

        return $this->render('logescom/comptable/avance_salaire/confirm_delete.html.twig', [
            'route_suppression' => $route_suppression,
            'param' => $param,
            'site' => $avanceSalaire->getSite(),
            'entreprise' => $avanceSalaire->getSite()->getEntreprise(),
            'operation' => $avanceSalaire
        ]);
    }

    #[Route('/delete/{id}/{site}', name: 'app_logescom_comptable_avance_salaire_delete', methods: ['POST'])]
    public function delete(Request $request, AvanceSalaire $avanceSalaire, EntityManagerInterface $entityManager, Site $site, LogicielService $service, OrangeSmsService $orangeService, ConfigurationSmsRepository $configSmsRep): Response
    {
        if ($this->isCsrfTokenValid('delete'.$avanceSalaire->getId(), $request->request->get('_token'))) {

            $entityManager->remove($avanceSalaire);

            $deleteReason = $request->request->get('delete_reason');
            $montant = $avanceSalaire->getMontant();

            
            $dateOperation = $avanceSalaire->getDateOperation() 
                ? $avanceSalaire->getDateOperation()->format('d/m/Y H:i') 
                : 'Date non définie';

            $information = "Montant : {$montant} | Date : {$dateOperation}";

            $personnel = $this->getUser();
            $historiqueSup = new HistoriqueChangement();
            $historiqueSup->setSaisiePar($personnel)
                    ->setDateSaisie(new \DateTime())
                    ->setMotif($deleteReason)
                    ->setInformation($information)
                    ->setType('avance salaire')
                    ->setSite($avanceSalaire->getSite());
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

                            $message  = "⚠️ Alerte Suppression Avance sur salaire ⚠️\n";
                            $message .= "L'avance sur salaire de " . $avanceSalaire->getPersonnel()->getNomComplet() . " d'un montant de " . number_format($avanceSalaire->getMontant(), 0, ',', ' ') . " a été supprimée.\n";
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
                                    ->setCommentaire($avanceSalaire->getCommentaire())
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

        return $this->redirectToRoute('app_logescom_comptable_avance_salaire_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
    }
}
