<?php

namespace App\Controller\Logescom\Comptable;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\Site;
use App\Entity\Client;
use App\Entity\Facture;
use App\Form\FactureType;
use App\Entity\SmsEnvoyes;
use App\Service\LogicielService;
use App\Service\OrangeSmsService;
use App\Entity\ContratSurveillance;
use App\Entity\HistoriqueChangement;
use App\Repository\CaisseRepository;
use App\Repository\ClientRepository;
use App\Repository\FactureRepository;
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
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\DetailPaiementFactureRepository;
use App\Repository\ConfigZoneRattachementRepository;
use App\Repository\MouvementCollaborateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/logescom/comptable/facture')]
final class FactureController extends AbstractController
{
    #[Route('/index/{site}', name: 'app_logescom_comptable_facture_index', methods: ['GET'])]
    public function index(FactureRepository $factureRep, Site $site, Request $request, ClientRepository $clientRep, ConfigZoneRattachementRepository $zoneRep,): Response
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

        
        $pageEncours = $request->get('pageEnCours', 1);
        
        $factures = $factureRep->findFactureSearch(site: $site, zones: $request->get('zone') ?? null, search: $search, startDate: $date1, endDate: $date2, pageEnCours: $pageEncours, limit: 100);

        $facturesGroup = [];

        foreach ($factures['data'] as $facture) {
            $client = $facture->getContrat()->getBien()->getClient();
            $mois = $facture->getPeriodeDebut()->format('Y-m');

            $key = $client->getId() . '_' . $mois;

            if (!isset($facturesGroup[$key])) {
                $facturesGroup[$key] = [
                    'client' => $client,
                    'mois' => $mois,
                    'facturations' => [],
                    'total' => 0
                ];
            }

            $facturesGroup[$key]['facturations'][] = $facture;
            $facturesGroup[$key]['total'] += $facture->getMontantTotal();
        }

        return $this->render('logescom/comptable/facture/index.html.twig', [
            'facturesGroup' => $facturesGroup,
            'factures' => $factures,
            'search' => $search,            
            'site' => $site,
            'date1' => $date1,
            'date2' => $date2,
            'zones' => $zoneRep->findAll()
        ]);
    }

    #[Route('/new/{site}', name: 'app_logescom_comptable_facture_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        Site $site,
        ContratSurveillanceRepository $contratRepo,
        FactureRepository $factureRepo,
        ConfigDeviseRepository $deviseRep,
        ClientRepository $clientRep,
    ): Response {

        if ($request->get('mois') and $request->get('annee')) {

            /* ================================
            1️⃣ RÉCUPÉRATION DU MOIS
            ================================= */
            $mois = (int)$request->get('mois');
            $annee = (int)$request->get('annee');
            if (!$mois || !$annee) {
                $this->addFlash('danger', 'Veuillez sélectionner un mois et une année.');
                return $this->redirectToRoute('app_logescom_comptable_facture_new', ['site' => $site->getId()]);
            }

            /* ================================
            2️⃣ CALCUL PÉRIODE
            ================================= */
            $periodeDebut = new \DateTime("$annee-$mois-01");
            $periodeFin   = (clone $periodeDebut)->modify('last day of this month');
            $nbJoursMois  = (int)$periodeFin->format('d');  // nombre de jours dans le mois
            
            /* ================================
            3️⃣ CONTRATS ACTIFS
            ================================= */
            if ($request->get('client')) {
                $client = $clientRep->find($request->get('client'));
                $contrats = $contratRepo->findContrat(site: $site, client: $client, statut: ['actif']);

            }else{

                $contrats = $contratRepo->findContrat(site: $site, statut: ['actif']);
            }
            $nbFacturesCreees = 0;

            foreach ($contrats as $contrat) {
            
                /* ================================
                4️⃣ CONDITIONS D’ÉLIGIBILITÉ
                ================================= */
                
                if ($contrat->getDateDebut() > $periodeFin) continue;

                if ($contrat->getDateFin() !== null && $contrat->getDateFin() < $periodeDebut) continue;

                if (!in_array($contrat->getModeFacturation(), ['mensuel', 'mensuel_agent', 'horaire'])) continue;

                $factureExistante = $factureRepo->findFactureForContratAndPeriod(
                    $contrat,
                    $periodeDebut,
                    $periodeFin
                );

                if ($factureExistante) continue;


                /* ================================================
                5️⃣ CALCUL PRORATA DU CONTRAT (correct)
                ================================================= */

                $dateDebutContrat = max($contrat->getDateDebut(), $periodeDebut);
                $dateFinContrat   = $contrat->getDateFin()
                    ? min($contrat->getDateFin(), $periodeFin)
                    : $periodeFin;

                // Détection si premier ou dernier mois
                // $estPremierMois = $contrat->getDateDebut() > $periodeDebut;
                $estPremierMois = $contrat->getDateDebut()->format('Y-m') === $periodeDebut->format('Y-m')
                 && $contrat->getDateDebut()->format('d') !== '01';
                // $estDernierMois = $contrat->getDateFin() !== null && $contrat->getDateFin() < $periodeFin;
                $estDernierMois = $contrat->getDateFin() !== null
                    && $contrat->getDateFin()->format('Y-m') === $periodeDebut->format('Y-m')
                    && $contrat->getDateFin()->format('d') !== $periodeFin->format('d');

                // Si c'est un mois plein → pas de prorata
                if (!$estPremierMois && !$estDernierMois) {
                    $tauxProrata = 1;
                } else {
                    $nbJoursActifs = $dateDebutContrat->diff($dateFinContrat)->days + 1;
                    $tauxProrata = $nbJoursActifs / $nbJoursMois; 
                }


                /* ================================================
                6️⃣ CALCUL HT (TYPE PRINCIPAL)
                ================================================= */
                $montantHTInitial = 0;

                foreach ($contrat->getTypesSurveillance() as $type) {
                    $tarifJournalier = $type->getTarifHoraire();
                    $tarifMensuel = $type->getTarifMensuel();

                    /* ====== 1️⃣ FACTURATION MENSUELLE ====== */
                    if ($contrat->getModeFacturation() === 'mensuel') {
                        if ($tarifJournalier and $tauxProrata != 1) {
                            // ✅ pas de prorata, calcul au jour ET par agent
                            $montantHTInitial += ($tarifJournalier * $nbJoursActifs);
                        }else{
                            $montantHTInitial += $tarifMensuel ?? 0;

                        }

                        continue;
                    }

                    /* ====== 2️⃣ FACTURATION PAR AGENT ====== */
                    if ($contrat->getModeFacturation() === 'mensuel_agent') {

                        $nbJour = $type->getNbAgentsJour() ?? 0;
                        $nbNuit = $type->getNbAgentsNuit() ?? 0;
                        $nbTotal = $nbJour + $nbNuit;

                        if ($nbTotal > 0) {
                            if ($tarifJournalier and $tauxProrata != 1) {
                                // ✅ pas de prorata, calcul au jour ET par agent
                                $montantHTInitial += ($tarifJournalier * $nbJoursActifs) * $nbTotal;
                            }else{
                                $montantHTInitial += ($tarifMensuel * $nbTotal) * $tauxProrata;
                            }
                        }

                        continue;
                    }


                    /* ====== 3️⃣ FACTURATION HORAIRE ====== */
                    if ($contrat->getModeFacturation() === 'horaire') {

                        $tarifHoraire = $type->getTarifHoraire() ?? 0;
                        $totalHeures = 0;

                        foreach ($contrat->getAffectationAgents() as $aff) {

                            $dateOp = $aff->getDateOperation();
                            if ($dateOp < $periodeDebut || $dateOp > $periodeFin) continue;

                            if (!$aff->isPresenceConfirme()) continue;

                            $debut = $aff->getHeureDebut();
                            $fin = $aff->getHeureFin();

                            if ($debut && $fin) {
                                $diff = $fin->getTimestamp() - $debut->getTimestamp();
                                $heures = $diff / 3600;
                                $totalHeures += $heures;
                            }
                        }

                        $montantHTInitial += $totalHeures * $tarifHoraire;

                        continue;
                    }
                }

                // /* Application du prorata UNIQUEMENT pour mensuel/mensuel_agent */
                // if (in_array($contrat->getModeFacturation(), ['mensuel', 'mensuel_agent'])) {
                //     $montantHTInitial = $montantHTInitial * $tauxProrata;
                // }

                /* ================================================
                7️⃣ CONTRATS COMPLÉMENTAIRES — PRORATA INDÉPENDANT
                ================================================ */

                foreach ($contrat->getContratComplementaires() as $cc) {

                    // Vérifier chevauchement avec la période
                    if ($cc->getDateDebut() > $periodeFin) continue;
                    if ($cc->getDateFin() !== null && $cc->getDateFin() < $periodeDebut) continue;
                    
                    /* ============================
                    🔁 CALCUL PRORATA DU CC
                    ============================ */

                    $dateDebutCC = max($cc->getDateDebut(), $periodeDebut);
                    $dateFinCC   = $cc->getDateFin()
                        ? min($cc->getDateFin(), $periodeFin)
                        : $periodeFin;

                    // Sécurité si jamais
                    if ($dateFinCC < $dateDebutCC) continue;

                    $nbJoursActifsCC = $dateDebutCC->diff($dateFinCC)->days + 1;
                    $tauxProrataCC   = $nbJoursActifsCC / $nbJoursMois;

                    foreach ($cc->getComplementTypeSurveillances() as $cts) {

                        $tarif = $cts->getTarif() ?? 0;
                        $tarifJournalier = $tarif/30;

                        /* ====== 1️⃣ FACTURATION MENSUELLE ====== */
                        if ($contrat->getModeFacturation() === 'mensuel') {
                            if ($tarifJournalier and $tauxProrataCC != 1) {
                                // ✅ pas de prorata, calcul au jour ET par agent
                                $montantHTInitial += ($tarifJournalier * $nbJoursActifsCC);
                            }else{
                                $montantHTInitial += $tarif * $tauxProrataCC;

                            }

                            
                            continue;
                        }

                        /* ====== 2️⃣ FACTURATION PAR AGENT ====== */
                        if ($contrat->getModeFacturation() === 'mensuel_agent') {

                            $nbAgents = $cts->getNbAgent() ?? 0;

                            if ($nbAgents > 0) {
                                if ($tarifJournalier and $tauxProrataCC != 1) {
                                    // ✅ pas de prorata, calcul au jour ET par agent
                                    $montantHTInitial += ($tarifJournalier * $nbJoursActifsCC) * $nbAgents;

                                }else{
                                    $montantHTInitial += ($tarif * $nbAgents) * $tauxProrataCC;

                                }
                                
                            }

                            continue;
                        }

                        /* ====== 3️⃣ FACTURATION HORAIRE ====== */
                        if ($contrat->getModeFacturation() === 'horaire') {

                            $totalHeuresCC = 0;

                            foreach ($contrat->getAffectationAgents() as $aff) {

                                $dateOp = $aff->getDateOperation();
                                if ($dateOp < $periodeDebut || $dateOp > $periodeFin) continue;

                                if (!$aff->isPresenceConfirme()) continue;

                                $debut = $aff->getHeureDebut();
                                $fin   = $aff->getHeureFin();

                                if ($debut && $fin) {
                                    $diff = $fin->getTimestamp() - $debut->getTimestamp();
                                    $totalHeuresCC += $diff / 3600;
                                }
                            }

                            $montantHTInitial += $totalHeuresCC * $tarif;
                        }
                    }
                }



                /* ================================================
                8️⃣ REMISE & TVA
                ================================================= */

                $montantHT = $montantHTInitial;

                $remisePourcentage = $contrat->getRemise() ?? 0;
                $remiseMontant = 0;

                if ($remisePourcentage > 0) {
                    $remiseMontant = $montantHT * ($remisePourcentage / 100);
                    $montantHT -= $remiseMontant;
                }

                $tauxTVA = $contrat->getTva() ?? 0;
                $montantTVA = 0;

                if ($tauxTVA > 0) {
                    $montantTVA = $montantHT * ($tauxTVA / 100);
                }

                $montantTTC = round($montantHT + $montantTVA, 2);


                /* ================================================
                9️⃣ CRÉATION DE LA FACTURE
                ================================================= */

                $facture = new Facture();

                $facture->setContrat($contrat)
                        ->setSite($site)
                        ->setPeriodeDebut($periodeDebut)
                        ->setPeriodeFin($periodeFin)
                        ->setDateEmission(new \DateTime())
                        ->setDateEcheance((new \DateTime())->modify('+10 days'))
                        ->setStatut("en_attente")
                        ->setDevise($deviseRep->findOneBy([]))
                        ->setDateSaisie(new \DateTime())
                        ->setSaisiePar($this->getUser());

                // Référence
                $reference = $factureRepo->generateReference(periodeDebut: $periodeDebut);
                $facture->setReference($reference);
                
                // TTC arrondi à l'entier
                $montantTTC = round($montantTTC);
                // Montants
                $facture->setMontantHT($montantHTInitial)
                        ->setRemisePourcentage($remisePourcentage)
                        ->setRemiseMontant(round($remiseMontant, 2))
                        ->setBaseTVA($montantHT)
                        ->setTauxTVA($tauxTVA)
                        ->setMontantTVA(round($montantTVA, 2))
                        ->setMontantTotal($montantTTC)
                        ->setMontantPaye(0);


                /* ================================================
                🔟 MOUVEMENT COLLABORATEUR
                ================================================= */

                $mouv = new MouvementCollaborateur();
                $devise = $deviseRep->findOneBy([]);

                $mouv->setCollaborateur($contrat->getBien()->getClient())
                    ->setOrigine("facturation")
                    ->setMontant(-$montantTTC)
                    ->setDevise($devise)
                    ->setSite($site)
                    ->setDateOperation(new \DateTime())
                    ->setDateSaisie(new \DateTime());

                $facture->addMouvementCollaborateur($mouv);


                $entityManager->persist($facture);
                $nbFacturesCreees++;
            }

            $entityManager->flush();

            /* ================================
            🔔 MESSAGE FINAL
            ================================= */
            if ($nbFacturesCreees > 0) {
                $this->addFlash('success',
                    "$nbFacturesCreees facture(s) générée(s) pour " . $periodeDebut->format('F Y')
                );
            } else {
                $this->addFlash('warning',
                    "Aucune facture à générer pour cette période."
                );
            }

            $referer = $request->headers->get('referer');

            if ($referer) {
                return $this->redirect($referer);
            }

            // fallback si jamais aucun referer n’est disponible
            return $this->redirectToRoute('app_logescom_comptable_facture_index', [
                'site' => $site->getId()
            ]);

        }

        $clients = $clientRep->findClientBySearch(site: $site, limit: 1000)['data'];
        // Page formulaire
        return $this->render('logescom/comptable/facture/new.html.twig', [
            'site' => $site,
            'clients' => $clients
        ]);
    }




    #[Route('/show/{id}/{site}', name: 'app_logescom_comptable_facture_show', methods: ['GET'])]
    public function show(Facture $facture, Site $site): Response
    {
        return $this->render('logescom/comptable/facture/show.html.twig', [
            'facture' => $facture,
            'site' => $site,
        ]);
    }


    #[Route('/confirm/delete/{id}', name: 'app_logescom_comptable_facture_confirm_delete', methods: ['GET', 'POST'])]
    public function confirmDelete(Facture $facture, Request $request): Response
    {
        $param = $request->request->get('param'); // Récupération du paramètre

        $route_suppression = $this->generateUrl('app_logescom_comptable_facture_delete', [
            'id' => $facture->getId(),
            'site' => $facture->getSite()->getId(),
        ]);
        

        return $this->render('logescom/comptable/facture/confirm_delete.html.twig', [
            'route_suppression' => $route_suppression,
            'param' => $param,
            'site' => $facture->getSite(),
            'entreprise' => $facture->getSite()->getEntreprise(),
            'operation' => $facture
        ]);
    }

    #[Route('/delete/{id}/{site}', name: 'app_logescom_comptable_facture_delete', methods: ['POST'])]
    public function delete(Request $request, Facture $facture, EntityManagerInterface $entityManager, Site $site, LogicielService $service, OrangeSmsService $orangeService, ConfigurationSmsRepository $configSmsRep): Response
    {
        if ($this->isCsrfTokenValid('delete'.$facture->getId(), $request->request->get('_token'))) {
            if ($facture->getPaiements()->first()) {
                $this->addFlash("warning", "impossible de supprimer cette facture car elle contient des paiements");
                return $this->redirectToRoute('app_logescom_comptable_facture_show', ['id' => $facture ->getId(), 'site' => $site->getId()], Response::HTTP_SEE_OTHER);
            }
            $entityManager->remove($facture);

            $deleteReason = $request->request->get('delete_reason');
            $reference = $facture->getReference();
            $montant = $facture->getMontantTotal();

            
            // Format période facturée
            $periodeDebut = $facture->getPeriodeDebut()
                ? $facture->getPeriodeDebut()->format('d/m/Y')
                : 'N/A';

            $periodeFin = $facture->getPeriodeFin()
                ? $facture->getPeriodeFin()->format('d/m/Y')
                : 'N/A';

            $periode = "{$periodeDebut} → {$periodeFin}";

            // Informations
            $clientNom = $facture->getContrat()->getBien()->getClient()->getNomComplet();
            $montant = number_format($facture->getMontantTotal(), 0, '.', ' ');

            $information = "Référence {$reference} | Client : {$clientNom} | Montant : {$montant} GNF | Période : {$periode}";


            $personnel = $this->getUser();
            $historiqueSup = new HistoriqueChangement();
            $historiqueSup->setSaisiePar($personnel)
                    ->setDateSaisie(new \DateTime())
                    ->setMotif($deleteReason)
                    ->setUser($facture->getContrat()->getBien()->getClient())
                    ->setInformation($information)
                    ->setType('facture')
                    ->setSite($facture->getSite());
            $entityManager->persist($historiqueSup);
           
            $entityManager->flush();

            # gestion envoi sms alert
            if ($service->estConnecteInternet()) {// vérifie si il ya une connexion internet
                # on verifie si l'envoi de notification est actif pour la facture
                $etat_notification = $configSmsRep->findOneBy(['nom' => 'suppression_modification', 'etat' => 'actif']);

                if ($etat_notification) {                
                    if ($service->verifierForfaitDisponible()) {// verifie si il ya un forfait disponible
                        // Forfait disponible : envoyer le SMS
                        $telephone = $site->getEntreprise()->getTelephone();
                        $telephone = $service->normaliserTelephone($telephone);
                        if ($telephone and strlen($telephone) >= 9) {

                            $recipientPhoneNumber = $telephone;
                            $countrySenderNumber = 'tel:+2240000'; 
                            
                            

                            $message  = "⚠️ Alerte Suppression facture ⚠️\n";
                            $message .= "la facture n° " . $facture->getReference() . " de " . $facture->getContrat()->getBien()->getClient()->getNomComplet() . " d'un montant de " . number_format($facture->getMontantTotal(), 0, ',', ' ') . " a été supprimé.\n";
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
                                    ->setCommentaire($facture->getCommentaire())
                                    ->setDateEnvoie(new \DateTime())
                                    ->setForfait($service->verifierForfaitDisponible());
                                $entityManager->persist($sms);
                                $entityManager->flush();

                            }
                            
                        }
                    }
                }
            }

            $this->addFlash("success", "facture supprimé avec succès :)");
        }

        return $this->redirectToRoute('app_logescom_comptable_facture_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
    }


    #[Route('/pdf/facture/{site}', name: 'app_logescom_comptable_facture_pdf', methods: ['GET'])]
    public function facturePdf(
        Site $site,
        MouvementCollaborateurRepository $mouvementCollabRep,
        CaisseRepository $caisseRepository,
        Request $request,
        FactureRepository $factureRep,
    ) {
        /** LOGO ENTREPRISE */
        $entreprise = $site->getEntreprise();
        $logoPath = $this->getParameter('kernel.project_dir') . '/public/images/img_logos/' . $entreprise->getLogo();
        $logoBase64 = base64_encode(file_get_contents($logoPath));

        /** FILIGRANE LOGESCOM (PNG transparent) */
        $filigranePath = $this->getParameter('kernel.project_dir') . '/public/images/watermark/logescom_filigrane.png';
        $filigraneBase64 = base64_encode(file_get_contents($filigranePath));

        if ($request->get('periode')) {
            $periode = new \DateTime($request->get('periode'));
             // 📌 1er jour du mois
            $date1 = (clone $periode)->modify('first day of this month')->setTime(0, 0, 0);
            // 📌 Dernier jour du mois
            $date2 = (clone $periode)->modify('last day of this month')->setTime(23, 59, 59);

            $factures = $factureRep->findFacture(site: $site, startDate: $date1, endDate: $date2, zones: $request->get('zone') ?? null,);
        }elseif ($request->get('facture')) {
            $factures = $factureRep->findFacture(id: $request->get('facture'));
        }
        // Regrouper factures par client
        $facturesRegroupees = [];

        foreach ($factures as $facture) {

            $contrat = $facture->getContrat();
            $bien    = $contrat->getBien();
            $client  = $bien->getClient();
            $clientId = $client->getId();

            $groupeFacturation = $bien->getGroupeFacturation();

                    // 🔑 clé de regroupement
            if ($groupeFacturation) {
                $key = 'CLIENT_'.$client->getId().'_GROUPE_'.$groupeFacturation->getId();
                $libelle = $groupeFacturation->getNom();
            } else {
                $key = 'CLIENT_'.$client->getId().'_BIEN_'.$bien->getId();
                $libelle = $bien->getNom();
            }

            if (!isset($facturesRegroupees[$key])) {

                $dateOp = $facture->getDateEmission();

                $facturesRegroupees[$key] = [
                    'client'        => $client,
                    'groupe'        => $groupeFacturation,
                    'libelle'       => $libelle,
                    'biens'         => [],
                    'factures'      => [],
                    'solde_actuel'  => $mouvementCollabRep->findSoldeCollaborateur($client),
                    'ancien_solde'  => $mouvementCollabRep->findAncienSoldeCollaborateur($client, $dateOp),
                    'mode'            => $facture->getContrat()->getModeFacturation(),
                    'modeFacturation'            => $client->getModeFacturation(),
                ];
            }

            

            $facturesRegroupees[$key]['factures'][] = $facture;
            $facturesRegroupees[$key]['biens'][$bien->getId()] = $bien;
        }
        // dd($facturesRegroupees);
        /** BANQUES */
        $banques = $caisseRepository->findBy(['type' => 'banque']);

       
        $template = 'logescom/comptable/facture/facture_pdf.html.twig';
        
        /** RENDER HTML */
        $html = $this->renderView($template, [
            'facturesRegroupees'   => $facturesRegroupees,
            'logoPath'             => $logoBase64,
            'filigrane'            => $filigraneBase64, 
            'site'                 => $site,
            'banques'              => $banques,
        ]);

        /** DOMPDF CONFIG */
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="facture.pdf"',
        ]);
    }

    #[Route('/contrat/{site}', name: 'app_logescom_comptable_contrat', methods: ['GET'])]
    public function contrat(ContratSurveillanceRepository $contratRep, Site $site, Request $request, ConfigZoneRattachementRepository $zoneRep): Response
    {
         if ($request->get("id_client_search")){
            $search = $request->get("id_client_search");
        }else{
            $search = "";
        }
       
        if ($request->isXmlHttpRequest()) {
            $search = $request->query->get('search');
            $contrats = $contratRep->findContratBySearch(search: $search, site: $site);    
            $response = [];
            foreach ($contrats['data'] as $contrat) {
                $response[] = [
                    'nom' => ($contrat->getbien()->getClient()->getNomComplet()).' '.$contrat->getbien()->getClient()->getTelephone(),
                    'id' => $contrat->getId()
                ]; // Mettez à jour avec le nom réel de votre propriété
            }
            return new JsonResponse($response);
        }


        if ($request->get("id_client_search")){
            $contrats = $contratRep->findContratBySearch(id: $search);
        }else{

            $pageEncours = $request->get('pageEnCours', 1);
            $contrats = $contratRep->findContratBySearch(site: $site, statut:['actif'], zones: $request->get('zone') ?? null, pageEnCours: $pageEncours, limit: 100);
        }

        // 🔥 Récupération pagination
        $contratsData = $contrats['data'];
        $nbrePages    = $contrats['nbrePages'];
        $pageEnCours  = $contrats['pageEnCours'];
        $limit        = $contrats['limit'];
        $total        = $contrats['total'];
        
        // 🔥 Groupement par client
        $contratsGroup = [];

        foreach ($contratsData as $contrat) {

            // On récupère le client via le bien
            $client = $contrat->getBien()->getClient();
            $clientId = $client ? $client->getId() : 'sans_client';

            if (!isset($contratsGroup[$clientId])) {
                $contratsGroup[$clientId] = [
                    'client'   => $client,
                    'contrats' => []
                ];
            }

            $contratsGroup[$clientId]['contrats'][] = $contrat;
        }

        return $this->render('logescom/comptable/facture/contrat.html.twig', [
            'contratsGroup' => $contratsGroup,
            'nbrePages'     => $nbrePages,
            'pageEnCours'   => $pageEnCours,
            'limit'         => $limit,
            'total'         => $total,
            'site' => $site,
            'zones' => $zoneRep->findAll()
        ]);
    }

    // #[Route('/contrat/historique/paiement/{client}/{site}', name: 'app_logescom_comptable_contrat_historique_paiement', methods: ['GET'])]
    // public function historiquePaiementClient(
    //     Client $client,
    //     Site $site,
    //     DetailPaiementFactureRepository $detailPaiementRep
    // ): Response {

    //     $moisListe = range(1, 12);
    //     $paiementsParAnnee = [];

    //     // Paiements du client sur ce site
    //     $detailPaiements = $detailPaiementRep->findDetailPaiement(site: $site, client: $client);

    //     foreach ($detailPaiements as $detail) {
    //         $bien = $detail->getFacture()->getContrat();
    //         dd($bien);
    //         // ➜ Utilisation de la période de la facture
    //         $annee = $detail->getFacture()->getPeriodeDebut()->format('Y');
    //         $mois  = $detail->getFacture()->getPeriodeDebut()->format('n');

    //         // Initialise l'année si non existante
    //         if (!isset($paiementsParAnnee[$annee])) {
    //             foreach ($moisListe as $m) {
    //                 $paiementsParAnnee[$annee][$m] = [];
    //             }
    //         }
    //         // Ajouter entrée
    //         $paiementsParAnnee[$annee][$mois][] = [
    //             'paiement' => $detail->getPaiement(),
    //             'facture'  => $detail->getFacture(),
    //             'montant'  => $detail->getMontant(), // Tu peux remplacer par montant affecté si tu gères ça
    //         ];
            
    //     }
    //     // Trie décroissant des années
    //     krsort($paiementsParAnnee);
    //     return $this->render('logescom/comptable/facture/contrat_facture.html.twig', [
    //         'client' => $client,
    //         'site' => $site,
    //         'paiementsParAnnee' => $paiementsParAnnee,
    //     ]);
    // }

    #[Route(
    '/contrat/historique/paiement/{client}/{site}', 
    name: 'app_logescom_comptable_contrat_historique_paiement', 
    methods: ['GET']
    )]
    public function historiquePaiementClient(
        Client $client,
        Site $site,
        DetailPaiementFactureRepository $detailPaiementRep,
        ContratSurveillanceRepository $contratRep,
    ): Response {

        $moisListe = range(1, 12);
        $paiementsParAnnee = [];

        // Paiements du client
        $detailPaiements = $detailPaiementRep->findDetailPaiement(site: $site, client: $client);

        foreach ($detailPaiements as $detail) {

            $facture  = $detail->getFacture();
            $contrat  = $facture->getContrat();
            $paiement = $detail->getPaiement();
            $montant  = $detail->getMontant(); // montant affecté à cette facture

            // Année et mois basés sur la facture
            $annee = $facture->getPeriodeDebut()->format('Y');
            $mois  = $facture->getPeriodeDebut()->format('n');
            $contratId = $contrat->getId();

            /** 1️⃣ Crée année si vide */
            if (!isset($paiementsParAnnee[$annee])) {
                $paiementsParAnnee[$annee] = [];
            }

            /** 2️⃣ Crée contrat si vide */
            if (!isset($paiementsParAnnee[$annee][$contratId])) {
                $paiementsParAnnee[$annee][$contratId] = [];

                // Initialise les 12 mois du contrat
                foreach ($moisListe as $m) {
                    $paiementsParAnnee[$annee][$contratId][$m] = [];
                }
            }

            /** 3️⃣ Ajoute l'entrée */
            $paiementsParAnnee[$annee][$contratId][$mois][] = [
                'paiement' => $paiement,
                'facture'  => $facture,
                'montant'  => $montant,
                'contrat' => $contrat
            ];
        }

        // Trie années décroissantes
        krsort($paiementsParAnnee);

        $contrats = $contratRep->findContrat(site: $site, client: $client);
        $contratsById = [];
        foreach ($contrats as $contrat) {
            $contratsById[$contrat->getId()] = $contrat;
        }
        return $this->render('logescom/comptable/facture/contrat_facture.html.twig', [
            'client' => $client,
            'site'   => $site,
            'paiementsParAnnee' => $paiementsParAnnee,
            'contratClients' => $contratsById
        ]);
    }



    #[Route('/facture/impaye/{site}', name: 'app_logescom_comptable_facture_impaye', methods: ['GET'])]
    public function factureImpaye(
        Site $site,
        ClientRepository $clientRep,
        ContratSurveillanceRepository $contratRep,
        FactureRepository $factureRep,
        DetailPaiementFactureRepository $detailPaiementRep,
        Request $request
    ): Response {

        $filtreContratId = $request->query->get('contratId');
        $filtreContrat = $filtreContratId ? $contratRep->find($filtreContratId) : null;

        $search = $request->get('search') ?? NULL ;
        /** 🔎 1. Récupération des contrats du site (filtré par client si besoin) */
        $contrats = $contratRep->findContrat(site: $site, id: $filtreContrat, search: $search);

        /** Conteneurs */
        $impayes = [];
        $moisLabels = [];

        $formatter = new \IntlDateFormatter(
            'fr_FR',
            \IntlDateFormatter::NONE,
            \IntlDateFormatter::NONE,
            null,
            null,
            'LLLL yyyy'
        );

        /** Aujourd’hui : limite de facturation */
        $moisActuel = new \DateTimeImmutable('first day of this month');

        foreach ($contrats as $contrat) {

            $dateDebut = $contrat->getDateDebut();
            $dateFin = $contrat->getDateFin() ?? $moisActuel;

            if ($dateDebut > $moisActuel) continue; // contrat futur → skip

            /** 🗓️ Génération des mois du contrat */
            $start = new \DateTimeImmutable($dateDebut->format('Y-m-01'));
            $end = new \DateTimeImmutable($dateFin->format('Y-m-01'));

            $period = new \DatePeriod($start, new \DateInterval('P1M'), $end->modify('+1 month'));

            /** 🔎 Paiements du contrat : groupés par "YYYY-MM" */
            $details = $detailPaiementRep->findDetailPaiement(contrat:$contrat);
            
            $paiementsParMois = [];
            foreach ($details as $d) {
                $fact = $d->getFacture();
                $key = $fact->getPeriodeDebut()->format("Y-m");

                $paiementsParMois[$key] = ($paiementsParMois[$key] ?? 0) + floatval($d->getMontant());
            }

            /** 🔍 Récupération des factures du contrat */
            $factures = $factureRep->findBy(['contrat' => $contrat]);

            /** Regroupe les factures par mois */
            $facturesParMois = [];
            foreach ($factures as $fact) {
                $key = $fact->getPeriodeDebut()->format("Y-m");

                $facturesParMois[$key] = ($facturesParMois[$key] ?? 0) + floatval($fact->getMontantTotal());
            }

            /** 💡 Calcul impayés par mois */
            foreach ($period as $month) {

                $key = $month->format("Y-m");

                if (!isset($moisLabels[$key])) {
                    $moisLabels[$key] = ucfirst($formatter->format($month));
                }

                $montantFacture = $facturesParMois[$key] ?? 0;
                $montantPaye    = $paiementsParMois[$key] ?? 0;

                $reste = $montantFacture - $montantPaye;

                if ($montantFacture > 0 && $reste > 0) {
                    $impayes[$key][] = [
                        'contrat' => $contrat,
                        'mois'    => $month->format('m'),
                        'annee'   => $month->format('Y'),
                        'facture' => $montantFacture,
                        'paye'    => $montantPaye,
                        'reste'   => $reste,
                    ];
                }
            }
        }
        /** Trie du plus récent au plus ancien */
        krsort($impayes);

        /** 🔎 Recherche des factures non générées */
        foreach ($contrats as $contrat) {

            // $dateDebut = $contrat->getDateDebut();
            $dateDebut = new \DateTime(date("Y-10-01"));
            $dateFinContrat = $contrat->getDateFin();
            $dateFin = $dateFinContrat ? min($dateFinContrat, $moisActuel) : $moisActuel;

            // Contrat futur → aucune facture attendue
            if ($dateDebut > $moisActuel) {
                continue;
            }

            /** 🗓️ Génération de tous les mois depuis le début du contrat */
            $start = new \DateTimeImmutable($dateDebut->format('Y-m-01'));
            $end   = new \DateTimeImmutable($dateFin->format('Y-m-01'));

            $period = new \DatePeriod($start, new \DateInterval('P1M'), $end->modify('+1 month'));

            /** 🔍 Récupérer toutes les factures existantes du contrat */
            $factures = $factureRep->findBy(['contrat' => $contrat]);

            /** Indexer les factures par mois (YYYY-MM) */
            $facturesParMois = [];
            foreach ($factures as $fact) {
                $key = $fact->getPeriodeDebut()->format("Y-m");
                $facturesParMois[$key] = true; // bool suffit
            }

            /** 🧮 Détection des mois sans facture */
            foreach ($period as $month) {

                $key = $month->format('Y-m');

                if (!isset($facturesParMois[$key])) {
                    // FACTURE NON GÉNÉRÉE POUR CE MOIS
                    $facturesNonGenerees[$contrat->getId()][] = [
                        'contrat' => $contrat,
                        'mois'    => (int)$month->format('m'),
                        'annee'   => (int)$month->format('Y'),
                        'label'   => $month->format('F Y'),
                    ];
                }
            }
        }

        return $this->render('logescom/comptable/facture/facture_impaye.html.twig', [
            'site'       => $site,
            'impayes'    => $impayes,
            'moisLabels' => $moisLabels,
            'contrats'    => $filtreContrat ? [$filtreContrat] : $contrats,
            'selectedContrat' => $filtreContratId,
            'facturesNonGenerees' => $facturesNonGenerees ?? NULL,
        ]);
    }




    


}
