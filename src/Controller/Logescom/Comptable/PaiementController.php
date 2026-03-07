<?php

namespace App\Controller\Logescom\Comptable;

use App\Entity\DetailPaiementFacture;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\Site;
use App\Entity\Facture;
use App\Entity\Paiement;
use App\Entity\SmsEnvoyes;
use App\Form\PaiementType;
use App\Service\LogicielService;
use App\Service\OrangeSmsService;
use App\Entity\HistoriqueChangement;
use App\Repository\ClientRepository;
use App\Entity\MouvementCollaborateur;
use App\Repository\PaiementRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ConfigDeviseRepository;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\ConfigurationSmsRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ContratSurveillanceRepository;
use App\Repository\FactureRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\String\Slugger\AsciiSlugger;
use App\Repository\MouvementCollaborateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/logescom/comptable/paiement')]
final class PaiementController extends AbstractController
{

    #[Route('/index/{site}', name: 'app_logescom_comptable_paiement_index', methods: ['GET'])]
    public function index(PaiementRepository $paiementRep, Site $site, Request $request, ClientRepository $clientRep): Response
    {
        if ($request->get("search")){
            $search = $request->get("search");
        }else{
            $search = "";
        }

        if ($request->get("date1")){
            $date1 = $request->get("date1");
            $date2 = $request->get("date2");

        }else{
            $date1 = date("Y-m-01");
            $date2 = date("Y-m-d");
        }

        // if ($request->isXmlHttpRequest()) {
        //     $search = $request->query->get('search');
        //     $utilisateurs = $clientRep->findClientBySearch(search: $search, site: $site);     
        //     $response = [];
        //     foreach ($utilisateurs['data'] as $client) {
        //         $response[] = [
        //             'nom' => ucwords($client->getPrenom())." ".strtoupper($client->getNom()),
        //             'id' => $client->getId()
        //         ]; // Mettez à jour avec le nom réel de votre propriété
        //     }
        //     return new JsonResponse($response);
        // }
        $pageEncours = $request->get('pageEnCours', 1);
        $paiements = $paiementRep->findPaiementSearch(site: $site, startDate: $date1, endDate: $date2, pageEnCours: $pageEncours, limit: 100);

        
        return $this->render('logescom/comptable/paiement/index.html.twig', [
            'paiements' => $paiements,
            'search' => $search,            
            'site' => $site,
            'date1' => $date1,
            'date2' => $date2,
        ]);
    }

    #[Route('/new/{id}/{site}', name: 'app_logescom_comptable_paiement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, Facture $facture, Site $site, PaiementRepository $paiementRep, ConfigurationSmsRepository $configSmsRep, FactureRepository $factureRep, LogicielService $service, OrangeSmsService $orangeService): Response
    {
        // recupération des factures non entierement soldé du contrat
        $contrat = $facture->getContrat();
        $factures = $factureRep->findFacture(client: $contrat->getBien()->getClient(), statut:['en_attente', 'partielle','en_retard']);
        $paiement = new Paiement();
        $montantTotal = 0;
        $totalPaiement = 0;
        if (isset($factures)) {
            // logique pour mettre par defaut le montant à payer
            foreach ($factures as $facture) {
                $montantTotal += $facture->getMontantTotal();
                $paiement->addFacture($facture);
                foreach ($facture->getDetailPaiementFactures() as  $detailPaiment) {
                    $totalPaiement = $totalPaiement + $detailPaiment->getMontant();
                }

                $totalPaiement = $totalPaiement + $facture->getMontantPaye();
            }
            $reste = $montantTotal - $totalPaiement;
            // On formate le montant total avec 0 décimales pour affichage
            $paiement->setMontant(number_format($reste, 0, '', ' '));
        }

        $form = $this->createForm(PaiementType::class, $paiement, ['site' => $site, 'paiement' => $paiement]);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $reference = $paiementRep->generateReference();
            // 🔵 Nettoyage du montant
            $montant = floatval(preg_replace('/[^0-9,.]/', '', $form->get('montant')->getData())); 

            $paiement
                ->setSite($site)
                ->setSaisiePar($this->getUser())
                ->setMontant($montant)
                ->setDevise($form->getData()->getDevise())
                ->setCaisse($form->getData()->getCaisse())
                ->setModePaie($form->getData()->getModePaie())
                ->setTaux(1)
                ->setReference($reference)
                ->setTypeMouvement("paiement")
                ->setDateOperation($form->getData()->getDateOperation())
                ->setDateSaisie(new \DateTime());

            // 🔵 Upload fichier
            $fichier = $form->get("document")->getData();
            if ($fichier) {
                $nomFichier = pathinfo($fichier->getClientOriginalName(), PATHINFO_FILENAME);
                $slugger = new AsciiSlugger();
                $nouveauNom = $slugger->slug($nomFichier) . "_" . uniqid() . "." . $fichier->guessExtension();
                $fichier->move($this->getParameter("dossier_paiement"), $nouveauNom);
                $paiement->setDocument($nouveauNom);
            }

            $factures = $paiement->getFacture();
            $nbFactures = $factures->count();

            // ------------------------------------------------------
            // 🔥 CAS 1 : PAIEMENT MULTIPLE → tous doivent être payés
            // ------------------------------------------------------
            if ($nbFactures > 1) {

                /** @var Facture $fact */
                foreach ($factures as $fact) {
                    $montantTotal = (float) $fact->getMontantTotal();

                    // Paiement restant
                    $reste = $montantTotal - $fact->getMontantPaye();
                    // 👉 on force le paiement pour solder la facture
                    if ($reste > 0) {
                        $paiement->setMontant($paiement->getMontant()); // global, pas par facture
                        $fact->setMontantPaye($fact->getMontantPaye() + $reste);
                        $fact->setStatut("payee");
                    }

                    $detailPaiment = new DetailPaiementFacture();
                    $detailPaiment->setFacture($fact)
                            ->setMontant($reste);
                    $paiement->addDetailPaiementFacture($detailPaiment);
                    $entityManager->persist($fact);
                }
            }

            // ------------------------------------------------------
            // 🔥 CAS 2 : PAIEMENT SIMPLE → partiel autorisé
            // ------------------------------------------------------
            if ($nbFactures === 1) {
                /** @var Facture $fact */
                $fact = $factures->first();

                $montantTotal = (float) $fact->getMontantTotal();

                $nouveauTotalPaye = $fact->getMontantPaye() + $montant;

                // Ne pas dépasser le total
                if ($nouveauTotalPaye > $montantTotal) {
                    $nouveauTotalPaye = $montantTotal;
                }

                $reste = $montantTotal - $nouveauTotalPaye;

                $fact->setMontantPaye($nouveauTotalPaye);

                if ($reste <= 0) {
                    $fact->setStatut("payee");
                } elseif ($nouveauTotalPaye > 0) {
                    $fact->setStatut("partielle");
                }

                $detailPaiment = new DetailPaiementFacture();
                $detailPaiment->setFacture($fact)
                        ->setMontant($paiement->getMontant());
                $paiement->addDetailPaiementFacture($detailPaiment);

                $entityManager->persist($fact);
            }

            $mouvement_collab = new MouvementCollaborateur();
                $mouvement_collab->setCollaborateur($facture->getContrat()->getBien()->getClient())
                    ->setOrigine("paiement")
                    ->setMontant($paiement->getMontant())
                    ->setDevise($form->getViewData()->getDevise())
                    ->setSite($site)
                    ->setDateOperation(new \DateTime())
                    ->setDateSaisie(new \DateTime("now"));

                $paiement->addMouvementPaiement($mouvement_collab);
            $entityManager->persist($paiement);
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

                            $message  = "💡 paiement enregistré 💡\n";
                            $message .= "Motif : " . $paiement->getCommentaire() . "\n";
                            $message .= "Montant : " . number_format($paiement->getMontant(), 0, ',', ' ') . " F CFA\n";
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
                                    ->setCommentaire('motif '.$paiement->getCommentaire())
                                    ->setDateEnvoie(new \DateTime())
                                    ->setForfait($service->verifierForfaitDisponible());
                                $entityManager->persist($sms);
                                $entityManager->flush();

                            }
                            
                        }
                    }
                }
            }

            $this->addFlash("success", "paiement enregistré avec succès :)");
            return $this->redirectToRoute('app_logescom_comptable_paiement_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
        }
        

        // Page du formulaire
        return $this->render('logescom/comptable/paiement/new.html.twig', [
            'site' => $site,
            'form' => $form,
            'paiement' => $paiement,
            'facture' => $facture,
        ]);
    }

    #[Route('/edit/{id}/{site}', name: 'app_logescom_comptable_paiement_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Paiement $paiement,
        Site $site,
        PaiementRepository $paiementRep,
        FactureRepository $factureRep,
        EntityManagerInterface $entityManager
    ): Response {

        $oldMontant = $paiement->getMontant();

        /** @var Facture[] $oldFactures */
        $oldFactures = clone $paiement->getFacture();  // 🔥 on capture l’ancien état

        // Formulaire
        $form = $this->createForm(PaiementType::class, $paiement, [
            'site' => $site,
            'paiement' => $paiement
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // 🔥 Nettoyage montant
            $nouveauMontant = floatval(
                preg_replace('/[^0-9,.]/', '', $form->get('montant')->getData())
            );

            /** @var Facture[] $newFactures */
            $newFactures = $paiement->getFacture();

            // -----------------------------------------------------
            // 1️⃣ TRAITER LES FACTURES RETIRÉES DU PAIEMENT
            // -----------------------------------------------------
            foreach ($oldFactures as $oldFact) {
                if (!$newFactures->contains($oldFact)) {

                    // Total paiements hors ce paiement
                    $paiementsHors = array_sum(
                        array_map(
                            fn($p) => $p->getId() !== $paiement->getId() ? $p->getMontant() : 0,
                            $oldFact->getPaiements()->toArray()
                        )
                    );

                    $oldFact->setMontantPaye($paiementsHors);

                    // Nouveau statut
                    if ($paiementsHors <= 0) {
                        $oldFact->setStatut("en_attente");
                    } elseif ($paiementsHors < $oldFact->getMontantTotal()) {
                        $oldFact->setStatut("partielle");
                    }

                    $entityManager->persist($oldFact);
                }
            }


            // -----------------------------------------------------
            // 2️⃣ MISE À JOUR DES NOUVELLES FACTURES LIÉES
            // -----------------------------------------------------
            foreach ($newFactures as $fact) {

                $montantTotal = (float) $fact->getMontantTotal();

                // Paiements hors paiement actuel
                $paiementsHorsActuel = array_sum(
                    array_map(
                        fn($p) => $p->getId() !== $paiement->getId() ? $p->getMontant() : 0,
                        $fact->getPaiements()->toArray()
                    )
                );

                // Nouveau montant payé
                $nouveauTotalPaye = $paiementsHorsActuel + $nouveauMontant;

                if ($nouveauTotalPaye > $montantTotal) {
                    $nouveauTotalPaye = $montantTotal;
                }

                $fact->setMontantPaye($nouveauTotalPaye);

                // Nouveau statut
                if ($nouveauTotalPaye >= $montantTotal) {
                    $fact->setStatut("payee");
                } elseif ($nouveauTotalPaye > 0) {
                    $fact->setStatut("partielle");
                } else {
                    $fact->setStatut("en_attente");
                }

                $entityManager->persist($fact);
            }


            // -----------------------------------------------------
            // 3️⃣ METTRE À JOUR LE MOUVEMENT COLLABORATEUR
            // -----------------------------------------------------
            if ($paiement->getMouvementPaiements()->count() > 0) {
                $mvt = $paiement->getMouvementPaiements()->first();
                $mvt->setMontant($nouveauMontant);
                $mvt->setDateSaisie(new \DateTime("now"));
            }

            // -----------------------------------------------------
            // 4️⃣ MISE À JOUR PAIEMENT
            // -----------------------------------------------------
            $paiement
                ->setMontant($nouveauMontant)
                ->setDateOperation($form->get('dateOperation')->getData());

            // -----------------------------------------------------
            // 5️⃣ GESTION DOCUMENT
            // -----------------------------------------------------
            $fichier = $form->get("document")->getData();
            if ($fichier) {

                if ($paiement->getDocument()) {
                    $ancien = $this->getParameter("dossier_paiement") . '/' . $paiement->getDocument();
                    if (file_exists($ancien)) unlink($ancien);
                }

                $nomFichier = pathinfo($fichier->getClientOriginalName(), PATHINFO_FILENAME);
                $slugger = new AsciiSlugger();
                $nouveauNom = $slugger->slug($nomFichier) . "_" . uniqid() . "." . $fichier->guessExtension();
                $fichier->move($this->getParameter("dossier_paiement"), $nouveauNom);
                $paiement->setDocument($nouveauNom);
            }

            $entityManager->flush();

            $this->addFlash("success", "Paiement modifié avec succès !");
            return $this->redirectToRoute('app_logescom_comptable_paiement_index', [
                'site' => $site->getId()
            ]);
        }

        return $this->render('logescom/comptable/paiement/edit.html.twig', [
            'site' => $site,
            'paiement' => $paiement,
            'form' => $form,
        ]);
    }





    #[Route('/show/{id}/{site}', name: 'app_logescom_comptable_paiement_show', methods: ['GET'])]
    public function show(Paiement $paiement, Site $site): Response
    {
        return $this->render('logescom/comptable/paiement/show.html.twig', [
            'paiement' => $paiement,
            'site' => $site,
        ]);
    }




    #[Route('/confirm/delete/{id}', name: 'app_logescom_comptable_paiement_confirm_delete', methods: ['GET', 'POST'])]
    public function confirmDelete(Paiement $paiement, Request $request): Response
    {
        $param = $request->request->get('param'); // Récupération du paramètre

        $route_suppression = $this->generateUrl('app_logescom_comptable_paiement_delete', [
            'id' => $paiement->getId(),
            'site' => $paiement->getSite()->getId(),
        ]);
        

        return $this->render('logescom/comptable/paiement/confirm_delete.html.twig', [
            'route_suppression' => $route_suppression,
            'param' => $param,
            'site' => $paiement->getSite(),
            'entreprise' => $paiement->getSite()->getEntreprise(),
            'operation' => $paiement
        ]);
    }

    #[Route('/delete/{id}/{site}', name: 'app_logescom_comptable_paiement_delete', methods: ['POST'])]
    public function delete(Request $request, Paiement $paiement, EntityManagerInterface $entityManager, Site $site, LogicielService $service, OrangeSmsService $orangeService, ConfigurationSmsRepository $configSmsRep): Response
    {
        if ($this->isCsrfTokenValid('delete'.$paiement->getId(), $request->request->get('_token'))) {
            
            foreach ($paiement->getFacture() as $facture) {

                // Récupérer montant payé avant suppression
                $paiementsAnterieurs = 0;
                foreach ($facture->getPaiements() as $pf) {
                    if ($pf !== $paiement) {   // on exclut le paiement supprimé
                        $paiementsAnterieurs += $pf->getMontant();
                    }
                }

                $montantTotal = (float) $facture->getMontantTotal();
                $montantPaye = $paiementsAnterieurs;
                $reste = $montantTotal - $montantPaye;

                // 🔘 Mise à jour de la facture
                $facture->setMontantPaye($montantPaye);

                // 🔘 Statut en fonction du reste
                if ($reste <= 0) {
                    $facture->setStatut('payee');
                } elseif ($montantPaye > 0) {
                    $facture->setStatut('partielle');
                } else {
                    $facture->setStatut('en_attente');
                }

                $entityManager->persist($facture);
            }
            // 🔥 2) Suppression du paiement
            $entityManager->remove($paiement);

            $deleteReason = $request->request->get('delete_reason');
            $reference = $paiement->getReference();
            $montant = $paiement->getMontant();

            
            // Format période facturée
            $dateOperation = $paiement->getDateOperation()
                ? $paiement->getDateOperation()->format('d/m/Y')
                : 'N/A';

            // Informations
            $clientNom = $paiement->getFacture()->first()->getContrat()->getBien()->getClient()->getNomComplet();
            $montant = number_format($paiement->getMontant(), 0, '.', ' ');

            $information = "Référence {$reference} | Client : {$clientNom} | Montant : {$montant} GNF | Période : {$dateOperation}";


            $personnel = $this->getUser();
            $historiqueSup = new HistoriqueChangement();
            $historiqueSup->setSaisiePar($personnel)
                    ->setDateSaisie(new \DateTime())
                    ->setMotif($deleteReason)
                    ->setUser($paiement->getFacture()->first()->getContrat()->getBien()->getClient())
                    ->setInformation($information)
                    ->setType('paiement')
                    ->setSite($paiement->getSite());
            $entityManager->persist($historiqueSup);

            $entityManager->flush();

            # gestion envoi sms alert
            if ($service->estConnecteInternet()) {// vérifie si il ya une connexion internet
                # on verifie si l'envoi de notification est actif pour la Paiement
                $etat_notification = $configSmsRep->findOneBy(['nom' => 'suppression_modification', 'etat' => 'actif']);

                if ($etat_notification) {                
                    if ($service->verifierForfaitDisponible()) {// verifie si il ya un forfait disponible
                        // Forfait disponible : envoyer le SMS
                        $telephone = $site->getEntreprise()->getTelephone();
                        $telephone = $service->normaliserTelephone($telephone);
                        if ($telephone and strlen($telephone) >= 9) {

                            $recipientPhoneNumber = $telephone;
                            $countrySenderNumber = 'tel:+2240000'; 
                            
                            

                            $message  = "⚠️ Alerte Suppression Paiement ⚠️\n";
                            $message .= "la Paiement n° " . $paiement->getReference() . " de " . $paiement->getFacture()->first()->getContrat()->getBien()->getClient()->getNomComplet() . " d'un montant de " . number_format($paiement->getMontant(), 0, ',', ' ') . " a été supprimé.\n";
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
                                    ->setCommentaire($paiement->getCommentaire())
                                    ->setDateEnvoie(new \DateTime())
                                    ->setForfait($service->verifierForfaitDisponible());
                                $entityManager->persist($sms);
                                $entityManager->flush();

                            }
                            
                        }
                    }
                }
            }

            $this->addFlash("success", "Paiement supprimé avec succès :)");
        }

        return $this->redirectToRoute('app_logescom_comptable_paiement_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
    }


    #[Route('/pdf/recu/{id}/{site}', name: 'app_logescom_comptable_paiement_recu_pdf', methods: ['GET'])]
    public function PaiementPdf(Paiement $paiement, Site $site, MouvementCollaborateurRepository $mouvementCollabRep,)
    {
        $entreprise = $site->getEntreprise();
        $logoPath = $this->getParameter('kernel.project_dir') . '/public/images/img_logos/'.$entreprise->getLogo();
        $logoBase64 = base64_encode(file_get_contents($logoPath));

        $soleCollaborateur = $mouvementCollabRep->findSoldeCollaborateur($paiement->getFacture()->first()->getContrat()->getBien()->getClient());

        $collaborateur = $paiement->getFacture()->first()->getContrat()->getBien()->getClient();
        $dateOp = $paiement->getDateOperation();

        $ancienSoleCollaborateur = $mouvementCollabRep->findAncienSoldeCollaborateur($collaborateur, $dateOp);
        
        $html = $this->renderView('logescom/comptable/paiement/recu_pdf.html.twig', [
            'paiement' => $paiement,
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
            'Content-Disposition' => 'inline; filename="recu.pdf"',
        ]);
    }
    }
