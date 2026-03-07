<?php

namespace App\Controller\Logescom\Comptable;

use App\Entity\HistoriqueChangement;
use App\Entity\PaiementSalairePersonnel;
use App\Entity\Site;
use App\Form\PaiementSalairePersonnelType;
use App\Repository\AbsencePersonnelRepository;
use App\Repository\AffectationAgentRepository;
use App\Repository\AvanceSalaireRepository;
use App\Repository\CaisseRepository;
use App\Repository\ConfigDeviseRepository;
use App\Repository\ConfigModePaiementRepository;
use App\Repository\ConfigSalaireRepository;
use App\Repository\ConfigurationSmsRepository;
use App\Repository\ConfigZoneRattachementRepository;
use App\Repository\ContratSurveillanceRepository;
use App\Repository\MouvementCaisseRepository;
use App\Repository\PaiementSalairePersonnelRepository;
use App\Repository\PenaliteRepository;
use App\Repository\PersonelRepository;
use App\Repository\PersonnelRepository;
use App\Repository\PrimePersonnelRepository;
use App\Repository\SiteRepository;
use App\Repository\UserRepository;
use App\Service\LogicielService;
use App\Service\OrangeSmsService;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

#[Route('/logescom/comptable/salaire/personnel')]
class PaiementSalairePersonnelController extends AbstractController
{
    #[Route('/accueil/{site}', name: 'app_logescom_comptable_salaire_personnel_index', methods: ['GET'])]
    public function index(PaiementSalairePersonnelRepository $paiementSalairePersonnelRep, Site $site, ContratSurveillanceRepository $contratRep, Request $request, ConfigZoneRattachementRepository $zoneRep): Response
    {
        if ($request->isXmlHttpRequest()) {
            $searchContrat = $request->query->get('searchContrat');
            $contrats = $contratRep->findContratBySearch(search: $searchContrat, site: $site);    
            $response = [];
            foreach ($contrats['data'] as $contrat) {
                $response[] = [
                    'nom' => ($contrat->getbien()->getClient()->getNomComplet()).' '.$contrat->getbien()->getNom().' '.$contrat->getbien()->getClient()->getTelephone(),
                    'id' => $contrat->getId()
                ]; // Mettez à jour avec le nom réel de votre propriété
            }
            return new JsonResponse($response);
        }
        $contratId = $request->query->get('id_contrat_search');
        $contrat = $contratId ? $contratRep->find($contratId) : null;

        $search = $request->get('search') ?? NULL;
        $periode_select = $request->get("periode");
        $contrat = $request->get('contrat') ? $contratRep->find($request->get('contrat')) : null;
        $salaires = $paiementSalairePersonnelRep->findSalaireSearch(date:$periode_select, site: $site, search: $search, zones: $request->get('zone') ?? null, fonctions: $request->get('fonction') ?? null, contrat: $contrat);

        // Regrouper par contrat et par mois
        $salairesParMois = [];

        foreach ($salaires as $salaire) {

            $periode = $salaire->getPeriode();
            $mois = $periode->format('m-Y');

            // récupérer le bien si contrat existe
            $bienKey = 'Paiement sans rattachement de site';

            if ($salaire->getContrat()) {
                $bien = $salaire->getContrat()->getBien();
                $bienKey = $bien ? $bien->getNom() : 'Contrat';
            }

            if (!isset($salairesParMois[$mois])) {
                $salairesParMois[$mois] = [];
            }

            if (!isset($salairesParMois[$mois][$bienKey])) {
                $salairesParMois[$mois][$bienKey] = [];
            }

            $salairesParMois[$mois][$bienKey][] = $salaire;
        }

        return $this->render('logescom/comptable/salaire_personnel/index.html.twig', [
            'salairesParMois' => $salairesParMois,
            'site' => $site,
            'zones' => $zoneRep->findAll(),
            'contrat' => $contrat
        ]);
    }

    #[Route('/new/{site}', name: 'app_logescom_comptable_salaire_personnel_new', methods: ['GET', 'POST'])]
    public function new(Request $request, UserRepository $userRep, AbsencePersonnelRepository $absencesRep, PrimePersonnelRepository $primesRep, AvanceSalaireRepository $avanceRep, CaisseRepository $caisseRep, ConfigModePaiementRepository $modePaieRep, ConfigDeviseRepository $deviseRep, PersonelRepository $personnelRep, MouvementCaisseRepository $mouvementRep, EntityManagerInterface $entityManager, Site $site, PenaliteRepository $penaliteRep, ConfigZoneRattachementRepository $zoneRep, AffectationAgentRepository $affectationRep, ConfigSalaireRepository $configSalaireRep, ContratSurveillanceRepository $contratRep): Response
    {
        if ($request->isXmlHttpRequest()) {
            $searchContrat = $request->query->get('searchContrat');
            $contrats = $contratRep->findContratBySearch(search: $searchContrat, site: $site);    
            $response = [];
            foreach ($contrats['data'] as $contrat) {
                $response[] = [
                    'nom' => ($contrat->getbien()->getClient()->getNomComplet()).' '.$contrat->getbien()->getNom().' '.$contrat->getbien()->getClient()->getTelephone(),
                    'id' => $contrat->getId()
                ]; // Mettez à jour avec le nom réel de votre propriété
            }
            return new JsonResponse($response);
        }

        $periode = $request->query->get('periode');
        $contratId = $request->query->get('id_contrat_search');
        $contrat = $contratId ? $contratRep->find($contratId) : null;

        if ($periode and $contrat ) {
            $zone   = $request->get('zone');
            $fonction   = $request->get('fonction');

            
            if (
                $request->isMethod('POST')
                && $request->request->get('paiement_individuel') === '1'
                && $request->request->get('personnel')
                && $request->request->get('compte')
                && $request->request->get('modePaie')
            ) {
                $personnel = $userRep->find($request->request->get('personnel'));
                $periode   = $request->request->get('periode');
                $periode_select   = new \DateTime($request->request->get('periode'));
                
                if (!$personnel) {
                    $this->addFlash('danger', 'Personnel introuvable.');
                    return $this->redirectToRoute('app_logescom_comptable_salaire_personnel_new', [
                        'site' => $site->getId(),
                        'periode' => $periode->format('Y-m-d'),
                        'zone' => $zone,
                        'fonction' => $fonction
                    ]);
                }
                

                /* ===================== CALCULS SERVEUR ===================== */

                $salaireBase = (float) $personnel->getSalaireBase();

                $absencesHeures = (float) $absencesRep
                    ->findSumOfHoursForPersonnel($personnel, $periode);

                $montantAbsence = $absencesHeures * (float) $personnel->getTauxHoraire();

                $montantPrime = (float) $primesRep
                    ->findSumOfPrimeForPersonnel($personnel, $periode);

                $montantAvance = (float) $avanceRep
                    ->findSumOfAvanceForPersonnel($personnel, $periode, $contrat);

                
                $penaliteInput = (float) $request->request->get('penalite', 0);
                $reposInput    = (float) $request->request->get('repos', 0);
                $journeeInput  = (float) $request->request->get('journee', 0);

                // $montantPenalite = (float) $penaliteRep
                //     ->findSumOfPenaliteForPersonnel($personnel, $periode);

                $montantPenalite = $penaliteInput;

                // 🔁 Repos travaillé
                $nbReposTravaille = $affectationRep->findCountOfAffectationForPersonnel(
                    personnelId: $personnel->getId(),
                    date: $periode,
                    statutAffectation: ['repos_travaille']
                );

                $configReposTravaille = $configSalaireRep->findOneBy([
                    'code'  => 'REPOS_TRAVAILLE',
                    'actif' => true,
                ]);

                $montantJourReposTravaille = $configReposTravaille
                ? (float) $configReposTravaille->getMontant()
                : 0;

                
                // $montantReposTravaille = $nbReposTravaille * (float) $montantJourReposTravaille;
                $montantReposTravaille = $reposInput;

                // 🔁 Journée entière travaillée
                $nbJourneeEntiere = $affectationRep->findCountOfAffectationForPersonnel(
                    personnelId: $personnel->getId(),
                    date: $periode,
                    statutAffectation: ['journee_entiere']
                );

                $configJourneeEntiere = $configSalaireRep->findOneBy([
                    'code'  => 'JOURNEE_ENTIERE',
                    'actif' => true,
                ]);
                $montantJourJourneeEntiere = $configJourneeEntiere
                ? (float) $configJourneeEntiere->getMontant()
                : 0;

                // $montantJourneeEntiere = $nbJourneeEntiere * (float) $montantJourJourneeEntiere;
                $montantJourneeEntiere = $journeeInput;

                // 📅 Jours travaillés
                $joursTravailles = $affectationRep->findCountOfAffectationForPersonnel(
                    personnelId: $personnel->getId(),
                    date: $periode
                );

                // 💰 Salaire net FINAL
                $salaireNet =
                    $salaireBase
                    + $montantPrime
                    + $montantReposTravaille
                    + $montantJourneeEntiere
                    - $montantAvance
                    - $montantAbsence
                    - $montantPenalite;

                /* ===================== SÉCURITÉ CAISSE ===================== */

                $caisse = $caisseRep->find($request->request->get('compte'));
                $devise = $deviseRep->findOneBy([]);
                $soldeCaisse = $mouvementRep->findSoldeCaisse(caisse: $caisse, devise: $devise);

                if ($soldeCaisse < $salaireNet) {
                    $this->addFlash('warning', 'Solde de caisse insuffisant.');
                    return $this->redirectToRoute('app_logescom_comptable_salaire_personnel_new', [
                        'site' => $site->getId(),
                        'id_contrat_search' => $contrat->getId(),
                        'periode' => $periode_select->format('Y-m-d'),
                        'zone' => $zone,
                        'fonction' => $fonction
                    ]);
                }

                /* ===================== ENREGISTREMENT ===================== */

                $modePaie = $modePaieRep->find($request->request->get('modePaie'));

                $paiement = new PaiementSalairePersonnel();
                $paiement
                    ->setPersonnel($personnel)
                    ->setSite($site)
                    ->setContrat($contrat)
                    ->setPeriode($periode_select)
                    ->setDateOperation($periode_select)
                    ->setDateSaisie(new \DateTime())
                    ->setSaisiePar($this->getUser())
                    ->setSalaireBrut($salaireBase)
                    ->setPrime($montantPrime)
                    ->setAvanceSalaire($montantAvance)
                    ->setPenalite($montantPenalite)
                    ->setHeures($absencesHeures)
                    ->setMontant(-1 * $salaireNet) // 🔴 sortie caisse
                    ->setTypeMouvement('salaire')
                    ->setCaisse($caisse)
                    ->setModePaie($modePaie)
                    ->setDevise($devise)
                    ->setJourTravaille($joursTravailles)
                    ->setReposTravaille($montantReposTravaille)
                    ->setJourneeEntiere($montantJourneeEntiere)
                    ->setCommentaire($request->request->get('commentaire'));
                $entityManager->persist($paiement);
                // dd($paiement);
                $entityManager->flush();

                $this->addFlash('success', '💰 Salaire payé avec succès.');
                // dd($zone, $fonction, $request);
                return $this->redirectToRoute('app_logescom_comptable_salaire_personnel_new', [
                    'site' => $site->getId(),
                    'periode' => $periode_select->format('Y-m-d'),
                    'id_contrat_search' => $contrat->getId(),
                    'zone' => $zone,
                    'fonction' => $fonction
                ]);
            }

            /* ============================================================
            * 🔁 PAIEMENT GLOBAL DES SALAIRES
            * ============================================================ */
            // dd($request);
            if (
                $request->isMethod('POST')
                && $request->request->all('personnels')
                && $request->request->get('compte')
                && $request->request->get('modePaie')
            ) {

                $personnelIds = $request->request->all('personnels');
                $periodeStr   = $request->request->get('periode');
                $periode      = new \DateTime($periodeStr);

                $penalites = $request->request->all('penalite');
                $repos     = $request->request->all('repos');
                $journees  = $request->request->all('journee');
                $commentaires  = $request->request->all('commentaire');
                $caisse   = $caisseRep->find($request->request->get('compte'));
                $modePaie = $modePaieRep->find($request->request->get('modePaie'));
                $devise   = $deviseRep->findOneBy([]);

                if (!$caisse || !$modePaie) {
                    $this->addFlash('danger', 'Caisse ou mode de paiement invalide.');
                    return $this->redirectToRoute('app_logescom_comptable_salaire_personnel_new', [
                        'site' => $site->getId(),
                        'periode' => $periode->format('Y-m-d'),
                        'id_contrat_search' => $contrat->getId(),
                        'zone' => $zone,
                        'fonction' => $fonction
                    ]);
                }

                /* ===================== PRÉ-CALCUL GLOBAL ===================== */
                $totalGlobal = 0;
                $paiements   = [];
                foreach ($personnelIds as $personnelId) {

                    $personnel = $userRep->find($personnelId);
                    if (!$personnel) {
                        continue;
                    }

                    /* ===== CALCULS SERVEUR ===== */
                    $salaireBase = (float) $personnel->getSalaireBase();

                    $absencesHeures = (float) $absencesRep
                        ->findSumOfHoursForPersonnel($personnel, $periodeStr);

                    $montantAbsence = $absencesHeures * (float) $personnel->getTauxHoraire();

                    $montantPrime = (float) $primesRep
                        ->findSumOfPrimeForPersonnel($personnel, $periodeStr);

                    $montantAvance = (float) $avanceRep
                        ->findSumOfAvanceForPersonnel($personnel, $periodeStr);

                    // $montantPenalite = (float) $penaliteRep
                    //     ->findSumOfPenaliteForPersonnel($personnel, $periodeStr);
                    $montantPenalite = isset($penalites[$personnelId]) && $penalites[$personnelId] !== ''
                                        ? (float) $penalites[$personnelId]
                                        : 0.0;

                    // $commentaire = $commentaires[$personnelId];
                    $commentaire = 'paiement salaire';

                    /* 🔁 Repos travaillé */
                    $nbReposTravaille = $affectationRep->findCountOfAffectationForPersonnel(
                        personnelId: $personnel->getId(),
                        date: $periodeStr,
                        statutAffectation: ['repos_travaille']
                    );

                    $configReposTravaille = $configSalaireRep->findOneBy([
                        'code'  => 'REPOS_TRAVAILLE',
                        'actif' => true,
                    ]);

                    $montantJourReposTravaille = $configReposTravaille
                        ? (float) $configReposTravaille->getMontant()
                        : 0;

                    // $montantReposTravaille = $nbReposTravaille * $montantJourReposTravaille;
                    $montantReposTravaille = isset($repos[$personnelId]) && $repos[$personnelId] !== ''
                                            ? (float) $repos[$personnelId]
                                            : 0.0;

                     // 🔁 Journée entière travaillée
                    $nbJourneeEntiere = $affectationRep->findCountOfAffectationForPersonnel(
                        personnelId: $personnel->getId(),
                        date: $periode,
                        statutAffectation: ['journee_entiere']
                    );

                    $configJourneeEntiere = $configSalaireRep->findOneBy([
                        'code'  => 'JOURNEE_ENTIERE',
                        'actif' => true,
                    ]);
                    $montantJourJourneeEntiere = $configJourneeEntiere
                    ? (float) $configJourneeEntiere->getMontant()
                    : 0;

                    // $montantJourneeEntiere = $nbJourneeEntiere * (float) $montantJourJourneeEntiere;
                    $montantJourneeEntiere = isset($journees[$personnelId]) && $journees[$personnelId] !== ''
                                            ? (float) $journees[$personnelId]
                                            : 0.0;

                    /* 📅 Jours travaillés */
                    $joursTravailles = $affectationRep->findCountOfAffectationForPersonnel(
                        personnelId: $personnel->getId(),
                        date: $periodeStr
                    );

                    /* 💰 Salaire net */
                    $salaireNet =
                        $salaireBase
                        + $montantPrime
                        + $montantReposTravaille
                        + $montantJourneeEntiere
                        - $montantAvance
                        - $montantAbsence
                        - $montantPenalite;

                        // dd($salaireNet);

                    if ($salaireNet <= 0) {
                        continue;
                    }

                    $totalGlobal += $salaireNet;

                    $paiements[] = [
                        'personnel' => $personnel,
                        'salaireBase' => $salaireBase,
                        'prime' => $montantPrime,
                        'avance' => $montantAvance,
                        'penalite' => $montantPenalite,
                        'heures' => $absencesHeures,
                        'jours' => $joursTravailles,
                        'reposTravaille' => $montantReposTravaille,
                        'journeeEntiere' => $montantJourneeEntiere,
                        'net' => $salaireNet,
                        'commentaire' => $commentaire,
                    ];
                }

                /* ===================== CONTRÔLE CAISSE ===================== */
                $soldeCaisse = $mouvementRep->findSoldeCaisse(
                    caisse: $caisse,
                    devise: $devise
                );

                if ($soldeCaisse < $totalGlobal) {
                    $this->addFlash(
                        'warning',
                        sprintf(
                            'Solde insuffisant : %s requis, %s disponible.',
                            number_format($totalGlobal, 0, ',', ' '),
                            number_format($soldeCaisse, 0, ',', ' ')
                        )
                    );

                    return $this->redirectToRoute('app_logescom_comptable_salaire_personnel_new', [
                        'site' => $site->getId(),
                        'id_contrat_search' => $contrat->getId(),
                        'periode' => $periode->format('Y-m-d'),
                        'zone' => $zone,
                        'fonction' => $fonction
                    ]);
                }

                /* ===================== ENREGISTREMENT (TRANSACTION) ===================== */
                $entityManager->beginTransaction();

                try {

                    foreach ($paiements as $data) {

                        $paiement = new PaiementSalairePersonnel();
                        $paiement
                            ->setPersonnel($data['personnel'])
                            ->setSite($site)
                            ->setPeriode($periode)
                            ->setDateOperation($periode)
                            ->setDateSaisie(new \DateTime())
                            ->setSaisiePar($this->getUser())
                            ->setSalaireBrut($data['salaireBase'])
                            ->setPrime($data['prime'])
                            ->setAvanceSalaire($data['avance'])
                            ->setPenalite($data['penalite'])
                            ->setHeures($data['heures'])
                            ->setJourTravaille($data['jours'])
                            ->setReposTravaille($data['reposTravaille'])
                            ->setJourneeEntiere($data['journeeEntiere'])
                            ->setMontant(-1 * $data['net']) // 🔴 sortie caisse
                            ->setTypeMouvement('salaire')
                            ->setCaisse($caisse)
                            ->setModePaie($modePaie)
                            ->setDevise($devise)
                            ->setContrat($contrat)
                            ->setCommentaire($data['commentaire'] ? $data['commentaire'] : 'Paiement global des salaires');

                        $entityManager->persist($paiement);
                    }

                    $entityManager->flush();
                    $entityManager->commit();

                    $this->addFlash(
                        'success',
                        sprintf(
                            '💰 Paiement global effectué (%d employés – %s GNF)',
                            count($paiements),
                            number_format($totalGlobal, 0, ',', ' ')
                        )
                    );

                } catch (\Throwable $e) {

                    $entityManager->rollback();
                    $this->addFlash('danger', 'Erreur lors du paiement global.');
                }

                return $this->redirectToRoute('app_logescom_comptable_salaire_personnel_new', [
                    'site' => $site->getId(),
                    'id_contrat_search' => $contrat->getId(),
                    'periode' => $periode->format('Y-m-d'),
                    'zone' => $zone,
                    'fonction' => $fonction
                ]);
            }



            $search = $request->get('search') ?? NULL;
            $periode_select = $request->get("periode");
            $periode_select_format = new \DateTime($periode_select);
            $paiementsInfos = [];

            $personnels = $personnelRep->findPersonnelsNotInPaiementsForPeriod(date:$periode_select, site: $site, contrat: $contrat, search: $search, zones: $request->get('zone') ?? [1], fonctions: $request->get('fonction') ?? null );
            
            $configReposTravaille = $configSalaireRep->findOneBy([
                'code'  => 'REPOS_TRAVAILLE',
                'actif' => true,
            ]);
            $montantJourReposTravaille = $configReposTravaille
            ? (float) $configReposTravaille->getMontant()
            : 0;

            $configJourneeEntiere = $configSalaireRep->findOneBy([
                'code'  => 'JOURNEE_ENTIERE',
                'actif' => true,
            ]);
            $montantJourJourneeEntiere = $configJourneeEntiere
            ? (float) $configJourneeEntiere->getMontant()
            : 0;

            foreach ($personnels as $key => $personnel) {
                $salaire_base = $personnel->getSalaireBase();
                $absences = $absencesRep->findSumOfHoursForPersonnel($personnel, $periode_select);
                $montant_penalite = $penaliteRep->findSumOfPenaliteForPersonnel($personnel, $periode_select);
                $montant_prime = $primesRep->findSumOfPrimeForPersonnel($personnel, $periode_select);
                $montant_avance = $avanceRep->findSumOfAvanceForPersonnel($personnel, $periode_select);

                $nbreReposTravaille = $affectationRep->findCountOfAffectationForPersonnel(personnelId: $personnel, date: $periode_select, statutAffectation: ['repos_travaille']);
                $montant_repos_travaille = $nbreReposTravaille ? $nbreReposTravaille * $montantJourReposTravaille : 0; 

                $nbreJourneeEntiere = $affectationRep->findCountOfAffectationForPersonnel(personnelId: $personnel, date: $periode_select, statutAffectation: ['journee_entiere']);

                $montant_journee_entiere = $nbreJourneeEntiere ? $nbreJourneeEntiere * $montantJourJourneeEntiere : 0; 

                $nbreJourTravaille = $affectationRep->findCountOfAffectationForPersonnel(personnelId: $personnel, date: $periode_select);


                
                $paiementsInfos[] = [
                    'personnel' => $personnel,
                    'salaireBase' => $salaire_base,
                    'absences' => $absences,
                    'montant_prime' => $montant_prime,
                    'montant_avance' => $montant_avance,
                    'montant_penalite' => $montant_penalite,
                    'repos_travaille' => $montant_repos_travaille,
                    'journee_entiere' => $montant_journee_entiere,
                    'jour_travaille' => $nbreJourTravaille,
                ];
            }
        }else{
            $paiementsInfos = [];
            $periode_select = date("Y-m-d");
        }
        // dd($paiementsInfos);
        return $this->render('logescom/comptable/salaire_personnel/new.html.twig', [
            'paiementsInfos' => $paiementsInfos,
            'comptes' => $caisseRep->findCaisse(site: $site),
            'modePaies' => $modePaieRep->findAll(),            
            'site' => $site,
            'zones' => $zoneRep->findAll(),
            'contrat' => $contrat

        ]);
    }

    #[Route('/show/{id}/{site}', name: 'app_logescom_comptable_salaire_personnel_show', methods: ['GET'])]
    public function show(PaiementSalairePersonnel $paiementSalairePersonnel, Site $site): Response
    {
        return $this->render('logescom/comptable/salaire_personnel/show.html.twig', [
            'salaire_personnel' => $paiementSalairePersonnel,
            
            'site' => $site,
        ]);
    }

    #[Route('/edit/{id}/{site}', name: 'app_logescom_comptable_salaire_personnel_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, PaiementSalairePersonnel $paiementSalairePersonnel, ContratSurveillanceRepository $contratRep, EntityManagerInterface $entityManager, Site $site): Response
    {
        if ($request->isXmlHttpRequest()) {
            $searchContrat = $request->query->get('searchContrat');
            $contrats = $contratRep->findContratBySearch(search: $searchContrat, site: $site);    
            $response = [];
            foreach ($contrats['data'] as $contrat) {
                $response[] = [
                    'nom' => ($contrat->getbien()->getClient()->getNomComplet()).' '.$contrat->getbien()->getNom().' '.$contrat->getbien()->getClient()->getTelephone(),
                    'id' => $contrat->getId()
                ]; // Mettez à jour avec le nom réel de votre propriété
            }
            return new JsonResponse($response);
        }
        $contratId = $request->query->get('id_client_search');
        $contrat = $contratId ? $contratRep->find($contratId) : null;
        $paiementSalairePersonnel->setContrat($contrat);
        // dd($paiementSalairePersonnel);
        $form = $this->createForm(PaiementSalairePersonnelType::class, $paiementSalairePersonnel, ['site' => $site]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $paiementSalairePersonnel->setDateSaisie(new \Datetime())
                                ->setSaisiePar($this->getUser());
            $entityManager->flush();

            return $this->redirectToRoute('app_logescom_comptable_salaire_personnel_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('logescom/comptable/salaire_personnel/edit.html.twig', [
            'paiements_salaires_personnel' => $paiementSalairePersonnel,
            'form' => $form,            
            'site' => $site,

        ]);
    }

    #[Route('/confirm/delete/{id}', name: 'app_logescom_comptable_salaire_personnel_confirm_delete', methods: ['GET', 'POST'])]
    public function confirmDelete(PaiementSalairePersonnel $paiement, Request $request): Response
    {
        $param = $request->request->get('param'); // Récupération du paramètre

        $route_suppression = $this->generateUrl('app_logescom_comptable_salaire_personnel_delete', [
            'id' => $paiement->getId(),
            'site' => $paiement->getSite()->getId(),
        ]);
        

        return $this->render('logescom/comptable/salaire_personnel/confirm_delete.html.twig', [
            'route_suppression' => $route_suppression,
            'param' => $param,
            'site' => $paiement->getSite(),
            'entreprise' => $paiement->getSite()->getEntreprise(),
            'operation' => $paiement
        ]);
    }

    #[Route('/delete/{id}/{site}', name: 'app_logescom_comptable_salaire_personnel_delete', methods: ['POST' , 'GET'])]
    public function delete(Request $request, PaiementSalairePersonnel $paiementSalairePersonnel, EntityManagerInterface $entityManager, Site $site, LogicielService $service, OrangeSmsService $orangeService, ConfigurationSmsRepository $configSmsRep): Response
    {
        if ($this->isCsrfTokenValid('delete'.$paiementSalairePersonnel->getId(), $request->request->get('_token'))) {
            $entityManager->remove($paiementSalairePersonnel);

            $deleteReason = $request->request->get('delete_reason');
            $reference = $paiementSalairePersonnel->getReference();
            $montant = $paiementSalairePersonnel->getMontant();

            
            // Format période facturée
            $dateOperation = $paiementSalairePersonnel->getDateOperation()
                ? $paiementSalairePersonnel->getDateOperation()->format('d/m/Y')
                : 'N/A';

            // Informations
            $clientNom = $paiementSalairePersonnel->getPersonnel()->getNomCompletUser();
            $montant = number_format($paiementSalairePersonnel->getMontant(), 0, '.', ' ');

            $information = "Référence {$reference} | Client : {$clientNom} | Montant : {$montant} GNF | Période : {$dateOperation}";

            // dd($paiementSalairePersonnel->getPersonnel());
            // $personnel = $this->getUser();
            
            // dd($personnel);
            // $historiqueSup = new HistoriqueChangement();
            // $historiqueSup->setSaisiePar($personnel)
            //         ->setDateSaisie(new \DateTime())
            //         ->setMotif($deleteReason)
            //         ->setUser($paiementSalairePersonnel->getPersonnel())
            //         ->setInformation($information)
            //         ->setType('paiement salaire')
            //         ->setSite($paiementSalairePersonnel->getSite());
            // $entityManager->persist($historiqueSup);

            $entityManager->flush();
        }

        return $this->redirectToRoute('app_logescom_comptable_salaire_personnel_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
    }


    #[Route('/pdf/fichepaie/{id}/{site}', name: 'app_logescom_comptable_salaire_personnel_fiche_paie', methods: ['GET'])]
    public function genererPdfAction(PaiementSalairePersonnel $paiementSalaire, Site $site, PrimePersonnelRepository $personnelRep, SiteRepository $siteRep)
    {
        $entreprise = $entrepriseRep->findOneBy(['id' => 1]);
        $logoPath = $this->getParameter('kernel.project_dir') . '/public/images/img-logos/'.$entreprise->getLogo();
        $logoBase64 = base64_encode(file_get_contents($logoPath));

        $html = $this->renderView('logescom/comptable/salaire_personnel/fiche_paie.html.twig', [
            'paiement_salaire' => $paiementSalaire,
            'personnel' => $personnelRep->findOneBy(['user' => $paiementSalaire->getPersonnel()]),
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
            'Content-Disposition' => 'inline; filename="fiche_paie.pdf"',
        ]);
    }
}
