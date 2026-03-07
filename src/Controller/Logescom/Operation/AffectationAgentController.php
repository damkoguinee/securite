<?php

namespace App\Controller\Logescom\Operation;

use DateTime;
use App\Entity\Site;
use App\Entity\Penalite;
use App\Entity\Personel;
use App\Form\PenaliteType;
use App\Entity\AffectationAgent;
use App\Form\AffectationAgentType;
use App\Repository\BienRepository;
use App\Entity\HistoriqueChangement;
use App\Repository\PersonelRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\AffectationAgentPermutationType;
use Symfony\Component\HttpFoundation\Request;
use App\Form\AffectationAgentInterventionType;
use App\Form\AffectationAgentRemplacementType;
use App\Repository\AffectationAgentRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ConfigPenaliteTypeRepository;
use App\Repository\ContratSurveillanceRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\ConfigZoneRattachementRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/logescom/operation/affectation/agent')]
final class AffectationAgentController extends AbstractController
{
    #[Route('/{site}', name: 'app_logescom_operation_affectation_agent_index', methods: ['GET'])]
    public function index(
        AffectationAgentRepository $affectationAgentRep,
        Request $request,
        PersonelRepository $personnelRep,
        Site $site,
        SessionInterface $session,
        ConfigZoneRattachementRepository $zoneRep,
    ): Response {
        // 🧭 Gestion du client recherché
        $search = $request->get('id_client_search', '');

        // 🧩 Gestion des dates (session + GET)
        if ($request->query->get('date1')) {
            $date1 = $request->query->get('date1');
            $date2 = $request->query->get('date1');
            // On sauvegarde en session
            $session->set('affectation_date1', $date1);
            $session->set('affectation_date2', $date2);
        } else {
            // Si pas dans la requête, on tente depuis la session
            $date1 = $session->get('affectation_date1', date('Y-m-d'));
            $date2 = $session->get('affectation_date2', date('Y-m-d'));
        }

        // 🔍 Recherche AJAX
        if ($request->isXmlHttpRequest()) {
            $search = $request->query->get('search', '');
            $personnels = $personnelRep->findUserBySearch(search: $search, site: $site, fonction: ['agent']);


            $response = array_map(function ($p) {
                return [
                    'nom' => $p->getNomCompletUser() . ' (' . $p->getTelephone() . ')',
                    'id'  => $p->getId(),
                ];
            }, $personnels);

            return new JsonResponse(array_values($response));
        }
        // 🧑‍💼 Recherche agent spécifique
        $idClientSearch = $request->get('id_client_search');
        
        if ($idClientSearch) {
            // 🔁 On conserve les dates depuis la session
            $date1 = $session->get('affectation_date1', date('Y-m-d'));
            $date2 = $session->get('affectation_date2', date('Y-m-d'));
            $affectations = $affectationAgentRep->findAffectation(
                personnel: $idClientSearch,
                startDate: $date1,
                endDate: $date2
            );
        } else {
            $affectations = $affectationAgentRep->findAffectation(
                site: $site,
                startDate: $date1,
                endDate: $date2,
                zones: $request->get('zone') ?? null,
                fonctions: $request->get('fonction') ?? null
            );


        }
        $affectationsGroupes = [];
        foreach ($affectations as $affectation) {

            $bien = $affectation->getContrat()->getBien();
            $bienId = $bien->getId();
            $bienNom = $bien->getNom();

            if (!isset($affectationsGroupes[$bienId])) {
                $affectationsGroupes[$bienId] = [
                    'bien_id' => $bienId,
                    'bien_nom' => $bienNom,
                    'affectations' => []
                ];
            }
            

            $affectationsGroupes[$bienId]['affectations'][] = $affectation;
            
        }

        // dd($affectationsGroupes);

        return $this->render('logescom/operation/affectation_agent/index.html.twig', [
            'affectationsGroupes' => $affectationsGroupes,
            'site' => $site,
            'search' => $search,
            'date1' => $date1,
            'date2' => $date2,
            'zones' => $zoneRep->findAll()
        ]);
    }


    #[Route('/new/{site}', name: 'app_logescom_operation_affectation_agent_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        ContratSurveillanceRepository $contratRepo,
        PersonelRepository $personnelRep,
        AffectationAgentRepository $affectationRep,
        Site $site,
        SessionInterface $session,
        ConfigZoneRattachementRepository $zoneRep,
    ): Response {
        // 🧭 Récupération des paramètres GET ou Session
        $contratId = $request->query->get('id_contrat_search') ?? $session->get('contrat_actif');
        $jour = $request->query->get('jour') ?? $session->get('jour_actif') ?? date('Y-m-d');

        // 🧩 Mise à jour de la session
        if ($request->query->get('id_contrat_search')) {
            $session->set('contrat_actif', $request->query->get('id_contrat_search'));
        }
        if ($request->query->get('jour')) {
            $session->set('jour_actif', $request->query->get('jour'));
        }

        if ($request->get("id_client_search")){
            $search = $request->get("id_client_search");
        }else{
            $search = "";
        }
       
        if ($request->isXmlHttpRequest()) {
            $searchContrat = $request->query->get('searchContrat');

            if ($searchContrat) {
                # code...
                $contrats = $contratRepo->findContratBySearch(search: $searchContrat, site: $site);    
                $response = [];
                foreach ($contrats['data'] as $contrat) {
                    $response[] = [
                        'nom' => ($contrat->getbien()->getClient()->getNomComplet()).' '.$contrat->getbien()->getNom().' '.$contrat->getbien()->getClient()->getTelephone(),
                        'id' => $contrat->getId()
                    ]; // Mettez à jour avec le nom réel de votre propriété
                }
                return new JsonResponse($response);

            }else{

                $search = $request->query->get('search', '');
                $personnels = $personnelRep->findPersonnelBySite(site: $site, typePersonnel: ['agent']);
    
                // Filtrage local si besoin
                if ($search) {
                    $personnels = array_filter($personnels, function ($p) use ($search) {
                        return stripos($p->getNomCompletUser(), $search) !== false
                            || stripos($p->getTelephone(), $search) !== false;
                    });
                }
                $response = array_map(function ($p) {
                    return [
                        'nom' => $p->getNomCompletUser() . ' (' . $p->getTelephone() . ')',
                        'id'  => $p->getId(),
                    ];
                }, $personnels);
    
                return new JsonResponse(array_values($response));
            }



        }

        $contrat = $contratId ? $contratRepo->find($contratId) : null;
    
        // 🧑‍💼 Recherche agent spécifique
        $idClientSearch = $request->get('id_client_search');
        if ($idClientSearch) {
            $contratId = $session->get('contrat_actif');
            $jour = $session->get('jour_actif', date('Y-m-d'));
            $personnels = [$personnelRep->find($idClientSearch)];
        }elseif ($request->get("zone")) {
            $personnels = $personnelRep->findPersonnelBySite(site: $site, typePersonnel: ['agent'], zones: [$request->get("zone")]);

        } else {
            $personnels = $personnelRep->findPersonnelBySite(site: $site, typePersonnel: ['agent'], bienAffecte: ($contrat ? $contrat->getBien() : NULL));
        }

        $personnelsGroupes = [];
        foreach ($personnels as $personel) {

            $zones = $personel->getZoneRattachement();

            // 🔹 Cas : aucune zone
            if ($zones->isEmpty()) {

                $zoneId = 'sans_zone';

                if (!isset($personnelsGroupes[$zoneId])) {
                    $personnelsGroupes[$zoneId] = [
                        'zone_id' => $zoneId,
                        'zone_nom' => 'Sans zone de rattachement',
                        'personnels' => []
                    ];
                }

                $personnelsGroupes[$zoneId]['personnels'][] = $personel;
                continue;
            }

            // 🔹 Cas : une ou plusieurs zones
            foreach ($zones as $zone) {

                $zoneId = $zone->getId();
                $zoneNom = $zone->getNom();

                if (!isset($personnelsGroupes[$zoneId])) {
                    $personnelsGroupes[$zoneId] = [
                        'zone_id' => $zoneId,
                        'zone_nom' => $zoneNom,
                        'personnels' => []
                    ];
                }

                $personnelsGroupes[$zoneId]['personnels'][] = $personel;
            }
        }

        $contrat = $contratId ? $contratRepo->find($contratId) : null;
        // dd($personnels, $request);
        // 🧾 Soumission du formulaire
        if ($request->isMethod('POST') and $request->get('action')) {
            $data = $request->request->all();
            $groupeCode = null;
            if (!empty($data['affectations'])) {
                $jour = new \DateTime($data['jour']);
                $contrat = $contratRepo->find($data['id_contrat_search']);

                $isPeriodique = isset($data['periodique']);
                $joursSemaine = $data['jours'] ?? [];
                $duree = $data['duree'] ?? null;

                // 🔧 Calcul automatique de la date de fin
                $dateFin = clone $jour;
                if ($isPeriodique && $duree) {
                    $groupeCode = uniqid('grp_'); // identifiant unique du groupe
                    switch ($duree) {
                        case '1m': $dateFin->modify('+1 month'); break;
                        case '2m': $dateFin->modify('+2 months'); break;
                        case '3m': $dateFin->modify('+3 months'); break;
                        case '6m': $dateFin->modify('+6 months'); break;
                        case '12m': $dateFin->modify('+12 months'); break;
                    }
                }

                $nbCree = 0;
                $joursTrad = [
                    'Mon' => 'Lun', 'Tue' => 'Mar', 'Wed' => 'Mer',
                    'Thu' => 'Jeu', 'Fri' => 'Ven', 'Sat' => 'Sam', 'Sun' => 'Dim'
                ];

                foreach ($data['affectations'] as $aff) {
                    $personnel = $personnelRep->find($aff['personnel']);
                    if (!$personnel || empty($aff['creneaux'])) continue;

                    foreach ($aff['creneaux'] as $creneau) {
                        if (empty($creneau['poste'])) continue;

                        $heureDebut = !empty($creneau['heureDebut']) ? new \DateTime($creneau['heureDebut']) : null;
                        $heureFin   = !empty($creneau['heureFin']) ? new \DateTime($creneau['heureFin']) : null;

                        // 🔁 Cas périodique
                        if ($isPeriodique && count($joursSemaine) > 0) {
                            $periode = clone $jour;
                            while ($periode <= $dateFin) {
                                $jourCourt = $periode->format('D');
                                $jourFr = $joursTrad[$jourCourt] ?? null;

                                if ($jourFr && in_array($jourFr, $joursSemaine)) {
                                    // 🛑 Vérification conflit
                                    $conflit = $affectationRep->hasConflitHoraire($personnel, clone $periode, $heureDebut, $heureFin);
                                    if ($conflit) {
                                        $this->addFlash('warning', sprintf(
                                            "⚠️ L’agent %s %s a déjà une affectation le %s (%s-%s).",
                                            $personnel->getPrenom(),
                                            strtoupper($personnel->getNom()),
                                            $periode->format('d/m/Y'),
                                            $heureDebut?->format('H:i'),
                                            $heureFin?->format('H:i')
                                        ));
                                        $periode->modify('+1 day');
                                        continue;
                                    }

                                    // 🔍 Évite doublons exacts
                                    $existe = $affectationRep->findOneBy([
                                        'personnel' => $personnel,
                                        'contrat' => $contrat,
                                        'dateOperation' => $periode,
                                        'poste' => $creneau['poste'],
                                    ]);

                                    if (!$existe) {
                                        $affectation = (new AffectationAgent())
                                            ->setContrat($contrat)
                                            ->setPersonnel($personnel)
                                            ->setDateOperation(clone $periode)
                                            ->setPoste($creneau['poste'])
                                            ->setHeureDebut($heureDebut)
                                            ->setHeureFin($heureFin)
                                            ->setCommentaire($creneau['commentaire'] ?? null)
                                            ->setPresenceConfirme(false)
                                            ->setTypeAffectation('normale')
                                            ->setSaisirPar($this->getUser())
                                            ->setDateSaisie(new \DateTime())
                                            ->setGroupeAffectation($isPeriodique ? $groupeCode : null);
                                        $em->persist($affectation);
                                        $nbCree++;
                                    }
                                }
                                $periode->modify('+1 day');
                            }
                        }
                        // ✅ Cas ponctuel
                        else {
                            $conflit = $affectationRep->hasConflitHoraire($personnel, clone $jour, $heureDebut, $heureFin);
                            if ($conflit) {
                                $this->addFlash('warning', sprintf(
                                    "⚠️ L’agent %s %s a déjà une affectation le %s (%s-%s).",
                                    $personnel->getPrenom(),
                                    strtoupper($personnel->getNom()),
                                    $jour->format('d/m/Y'),
                                    $heureDebut?->format('H:i'),
                                    $heureFin?->format('H:i')
                                ));
                                continue;
                            }

                            $existe = $em->getRepository(AffectationAgent::class)->findOneBy([
                                'personnel' => $personnel,
                                'contrat' => $contrat,
                                'dateOperation' => $jour,
                                'poste' => $creneau['poste'],
                            ]);

                            if (!$existe) {
                                $affectation = (new AffectationAgent())
                                    ->setContrat($contrat)
                                    ->setPersonnel($personnel)
                                    ->setDateOperation(clone $jour)
                                    ->setPoste($creneau['poste'])
                                    ->setHeureDebut($heureDebut)
                                    ->setHeureFin($heureFin)
                                    ->setCommentaire($creneau['commentaire'] ?? null)
                                    ->setPresenceConfirme(false)
                                    ->setSaisirPar($this->getUser())
                                    ->setDateSaisie(new \DateTime());
                                $em->persist($affectation);
                                $nbCree++;
                            }
                        }
                    }
                }

                $em->flush();
                $this->addFlash('success', "✅ $nbCree affectations enregistrées avec succès !");

                return $this->redirectToRoute('app_logescom_operation_affectation_agent_new', [
                    'site' => $site->getId(),
                    'id_contrat_search' => $contrat->getId(),
                    'jour' => $jour->format('Y-m-d'),
                ]);
            }
        }

        return $this->render('logescom/operation/affectation_agent/new.html.twig', [
            'contrat' => $contrat,
            'personnelsGroupes' => $personnelsGroupes,
            'site' => $site,
            'zones' => $zoneRep->findAll()
        ]);
    }


    #[Route('/show/{id}/{site}', name: 'app_logescom_operation_affectation_agent_show', methods: ['GET'])]
    public function show(AffectationAgent $affectationAgent, AffectationAgentRepository $affectationRep, Site $site): Response
    {
        $groupeAffectations = $affectationRep->findBy(['groupeAffectation' => $affectationAgent->getGroupeAffectation()]);
        return $this->render('logescom/operation/affectation_agent/show.html.twig', [
            'affectation_agent' => $affectationAgent,
            'groupe_affectations' => $groupeAffectations,
            'site' => $site,
        ]);
    }

    #[Route('/edit/{id}/{site}', name: 'app_logescom_operation_affectation_agent_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        AffectationAgent $affectationAgent,
        AffectationAgentRepository $affectationRep,
        EntityManagerInterface $em,
        Site $site,
        ContratSurveillanceRepository $contratRep
    ): Response {

        // dd($affectationAgent);
        /** ===================== ÉTAT ORIGINAL (AVANT FORM) ===================== */
        $originalContrat       = $affectationAgent->getContrat();
        $originalPersonnel     = $affectationAgent->getPersonnel();
        $originalDateOperation = $affectationAgent->getDateOperation();
        $originalGroupe        = $affectationAgent->getGroupeAffectation();


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


        $contratCible = $request->get('id_client_search')
            ? $contratRep->find($request->get('id_client_search'))
            : $affectationAgent->getContrat();


        $affectationAgent->setContrat($contratCible);
        
        $form = $this->createForm(
            AffectationAgentType::class,
            $affectationAgent,
            ['site' => $site]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** ===================== INTENTIONS ===================== */
            $editGroup = (bool) $request->request->get('edit_group');
            $scope     = $request->request->get('scope'); // ⚠️ PAS de valeur par défaut
            $startDate = $request->request->get('period_start');
            $endDate   = $request->request->get('period_end');

            /** ===================== SÉLECTION DES CIBLES ===================== */
            $targets = [];

            if ($editGroup) {
                // 🔥 MODE GROUPE : scope ignoré
                if (!$originalGroupe) {
                    throw new \LogicException('Aucun groupe associé à cette affectation.');
                }

                $targets = $affectationRep->findByScopeFromContext(
                    scope: 'group',
                    editGroup: true,
                    dateOperation: $originalDateOperation,
                    startDate: null,
                    endDate: null,
                    contrat: $originalContrat,
                    personnel: $originalPersonnel,
                    groupeAffectation: $originalGroupe
                );

            } else {
                // 🔥 MODE CLASSIQUE : single / future / period
                $scope = $scope ?? 'single';

                if ($scope === 'single') {
                    $targets = [$affectationAgent];
                } else {
                    $targets = $affectationRep->findByScopeFromContext(
                        scope: $scope,
                        editGroup: false,
                        dateOperation: $originalDateOperation,
                        startDate: $startDate,
                        endDate: $endDate,
                        contrat: $originalContrat,
                        personnel: $originalPersonnel,
                        groupeAffectation: null
                    );
                }

            }

            /** ===================== APPLICATION DES MODIFICATIONS ===================== */
            $nb = 0;
            foreach ($targets as $a) {

                // 👉 RÈGLE MÉTIER ACTUELLE :
                // le contrat est appliqué aux cibles sélectionnées
                $a->setContrat($affectationAgent->getContrat());

                $a->setPoste($affectationAgent->getPoste());
                $a->setHeureDebut($affectationAgent->getHeureDebut());
                $a->setHeureFin($affectationAgent->getHeureFin());
                $a->setCommentaire($affectationAgent->getCommentaire());
                $a->setPresenceConfirme($affectationAgent->isPresenceConfirme());
                $a->setSaisirPar($this->getUser());
                $a->setDateSaisie(new \DateTime());

                $nb++;
            }

            $em->flush();

            $this->addFlash(
                'success',
                sprintf('✅ %d affectation(s) modifiée(s) avec succès.', $nb)
            );

            return $this->redirectToRoute(
                'app_logescom_operation_affectation_agent_index',
                ['site' => $site->getId()],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render('logescom/operation/affectation_agent/edit.html.twig', [
            'form' => $form->createView(),
            'affectation_agent' => $affectationAgent,
            'site' => $site,
        ]);
    }

    #[Route('/remplacement/{id}/{site}', name: 'app_logescom_affectation_remplacement_new', methods: ['GET','POST'])]
    public function remplacement(
        Request $request,
        AffectationAgent $affectationSource,
        AffectationAgentRepository $affectationRep,
        EntityManagerInterface $em,
        Site $site,
        PersonelRepository $personnelRep
    ): Response {

        // 🔍 Recherche AJAX
        if ($request->isXmlHttpRequest()) {
            $search = $request->query->get('search', '');
            $personnels = $personnelRep->findUserBySearch(search: $search, site: $site, typePersonnel: ['agent'], statutPlanning: ['actif']);

            $response = array_map(function ($p) {
                return [
                    'nom' => $p->getNomCompletUser() . ' (' . $p->getTelephone() . ')',
                    'id'  => $p->getId(),
                ];
            }, $personnels);

            return new JsonResponse(array_values($response));
        }

        /**
        * ===================== CONTEXTE SOURCE =====================
        */
        $dateDebutRemplacement = $affectationSource->getDateOperation();
        $contrat               = $affectationSource->getContrat();
        $ancienAgent           = $affectationSource->getPersonnel();
        $groupe                = $affectationSource->getGroupeAffectation();

        /**
        * ===================== FORMULAIRE : CHOIX DU NOUVEL AGENT =====================
        * On utilise un prototype uniquement pour sélectionner le remplaçant
        */
        $prototype = new AffectationAgent();

        // 🧑‍💼 Recherche agent spécifique
        $idClientSearch = $request->get('id_client_search');
        
        if ($idClientSearch) {
            $prototype->setPersonnel($personnelRep->find($idClientSearch));
        }
        $prototype
            ->setContrat($contrat)
            ->setDateOperation($dateDebutRemplacement)
            ->setPoste($affectationSource->getPoste());

        $form = $this->createForm(AffectationAgentRemplacementType::class, $prototype, [
            'site' => $site
        ]);

        // 🔒 la date est imposée par la source
        $form->remove('dateOperation');
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $nouvelAgent = $prototype->getPersonnel();

            // 🛑 Sécurité : même agent
            if ($nouvelAgent === $ancienAgent) {
                $this->addFlash('danger', '❌ Impossible de remplacer un agent par lui-même.');
                return $this->redirectToRoute(
                    'app_logescom_operation_affectation_agent_index',
                    ['site' => $site->getId()]
                );
            }

            /**
            * ===================== 1️⃣ RÉCUPÉRER LES CRÉNEAUX À VENIR =====================
            */
            $creaneauxAVenir = $affectationRep->findCreneauxAVenir(
                contrat: $contrat,
                personnel: $ancienAgent,
                dateDebut: $dateDebutRemplacement,
                groupeAffectation: $groupe
            );

            if (!$creaneauxAVenir) {
                $this->addFlash('warning', '⚠️ Aucun créneau futur à remplacer.');
                return $this->redirectToRoute(
                    'app_logescom_operation_affectation_agent_index',
                    ['site' => $site->getId()]
                );
            }

            /**
            * ===================== 2️⃣ REMPLACEMENT RÉEL DES CRÉNEAUX =====================
            */
            $nb = 0;

            foreach ($creaneauxAVenir as $old) {

                // 🛑 Sécurité conflit horaire pour le nouvel agent
                if ($affectationRep->hasConflitHoraire(
                    $nouvelAgent,
                    $old->getDateOperation(),
                    $old->getHeureDebut(),
                    $old->getHeureFin()
                )) {
                    continue;
                }

                // 🔁 REMPLACEMENT DIRECT (PAS DE CRÉATION)
                $old
                    ->setAgentInitial($ancienAgent)              // traçabilité
                    ->setPersonnel($nouvelAgent)                 // 🔥 remplacement réel
                    ->setTypeAffectation('remplacement')
                    ->setPresenceConfirme(false)
                    ->setCommentaire(
                        trim(
                            ($old->getCommentaire() ?? '') .
                            ' | Remplacement de ' . $ancienAgent->getNomCompletUser()
                        )
                    )
                    ->setSaisirPar($this->getUser())
                    ->setDateSaisie(new \DateTime());

                $nb++;
            }

            $em->flush();

            $this->addFlash(
                'success',
                sprintf(
                    '🔁 %d créneau(x) remplacé(s) à partir du %s.',
                    $nb,
                    $dateDebutRemplacement->format('d/m/Y')
                )
            );

            return $this->redirectToRoute(
                'app_logescom_operation_affectation_agent_index',
                ['site' => $site->getId()]
            );
        }

        /**
        * ===================== VUE =====================
        */
        return $this->render('logescom/operation/affectation_agent/remplacement.html.twig', [
            'form' => $form->createView(),
            'affectation_source' => $affectationSource,
            'site' => $site,
        ]);
    }

    #[Route('/intervention/{id}/{site}', name: 'app_logescom_affectation_intervention_new', methods: ['GET','POST'])]
    public function intervention(
        Request $request,
        AffectationAgent $affectationSource,
        AffectationAgentRepository $affectationRep,
        EntityManagerInterface $em,
        Site $site,
        ContratSurveillanceRepository $contratRep,
    ): Response {

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

        /**
         * ===================== CONTEXTE SOURCE =====================
         */
        $agent          = $affectationSource->getPersonnel();
        $contratInitial = $affectationSource->getContrat();
        $dateDebut      = $affectationSource->getDateOperation();

        /**
         * ===================== PROTOTYPE INTERVENTION =====================
         */
        $intervention = new AffectationAgent();

        $contratCible = $request->get('id_client_search')
            ? $contratRep->find($request->get('id_client_search'))
            : $contratInitial;

        $intervention
            ->setPersonnel($agent)
            ->setContrat($contratCible)
            ->setContratInitial($contratInitial)
            ->setDateOperation($dateDebut)
            ->setHeureDebut($affectationSource->getHeureDebut())
            ->setHeureFin($affectationSource->getHeureFin())
            ->setPoste($affectationSource->getPoste())
            ->setTypeAffectation('intervention')
            ->setPresenceConfirme(false);

        /**
         * ===================== FORMULAIRE =====================
         */
        $form = $this->createForm(AffectationAgentInterventionType::class, $intervention, [
            'site' => $site,
            'dateOperation' => $dateDebut,
        ]);

        $form->remove('personnel');
        $form->remove('dateOperation');

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var \DateTimeInterface $dateFin */
            $dateFin = $form->get('dateFinIntervention')->getData();
            $jours   = $form->get('joursIntervention')->getData(); // 🔑 NOUVEAU

            if ($dateFin < $dateDebut) {
                $this->addFlash('danger', '❌ La date de fin ne peut pas être antérieure à la date de début.');
                return $this->redirectToRoute(
                    'app_logescom_affectation_intervention_new',
                    ['id' => $affectationSource->getId(), 'site' => $site->getId()]
                );
            }

            if (empty($jours)) {
                $this->addFlash('danger', '❌ Veuillez sélectionner au moins un jour d’intervention.');
                return $this->redirectToRoute(
                    'app_logescom_affectation_intervention_new',
                    ['id' => $affectationSource->getId(), 'site' => $site->getId()]
                );
            }

           
            $joursAutorises = in_array('ALL', $jours, true)
                ? ['Mon','Tue','Wed','Thu','Fri','Sat','Sun']
                : $jours;

             /**
             * ===================== mise a jour des affectations initiales  =====================
             */

            $affectationsSources = $affectationRep->findAffectation(
                personnel: $agent,
                contrat: $contratInitial,
                startDate: $dateDebut,
                endDate: $dateFin
            );

            // dd($affectationsSources);
            
            foreach ($affectationsSources as $source) {

                if (!in_array($source->getDateOperation()->format('D'), $joursAutorises, true)) {
                    continue;
                }

                $commentaire = $source->getCommentaire() ?? '';

                if (!str_contains($commentaire, 'Intervention')) {
                    $source->setCommentaire(
                        trim(
                            $commentaire .
                            ' | ⚠️ Agent en intervention sur le site '.$intervention->getContrat()->getBien()->getNom()
                        )
                    );
                }
            }
                

            /**
             * ===================== CRÉATION DES INTERVENTIONS =====================
             */
            $nb = 0;
            $periode = clone $dateDebut;

            while ($periode <= $dateFin) {

                if (in_array($periode->format('D'), $joursAutorises, true)) {

                    $new = (new AffectationAgent())
                        ->setPersonnel($agent)
                        ->setContrat($intervention->getContrat())
                        ->setContratInitial($contratInitial)
                        ->setDateOperation(clone $periode)
                        ->setHeureDebut($intervention->getHeureDebut())
                        ->setHeureFin($intervention->getHeureFin())
                        ->setPoste($intervention->getPoste())
                        ->setTypeAffectation('intervention')
                        ->setCommentaire(
                            trim('Intervention temporaire | '.($intervention->getCommentaire() ?? ''))
                        )
                        ->setPresenceConfirme(false)
                        ->setSaisirPar($this->getUser())
                        ->setDateSaisie(new \DateTime());

                    $em->persist($new);
                    $nb++;
                    
                }

                $periode->modify('+1 day');
            }

            $em->flush();

            $this->addFlash(
                'success',
                sprintf(
                    '🚑 Intervention planifiée du %s au %s (%d jour(s)).',
                    $dateDebut->format('d/m/Y'),
                    $dateFin->format('d/m/Y'),
                    $nb
                )
            );

            return $this->redirectToRoute(
                'app_logescom_operation_affectation_agent_index',
                ['site' => $site->getId()]
            );
        }

        return $this->render('logescom/operation/affectation_agent/intervention.html.twig', [
            'form' => $form->createView(),
            'affectation_source' => $affectationSource,
            'site' => $site,
        ]);
    }


    #[Route('/permutation/{id}/{site}', name: 'app_logescom_affectation_permutation_new', methods: ['GET','POST'])]
    public function permutation(
        Request $request,
        AffectationAgent $affectationSource,
        AffectationAgentRepository $affectationRep,
        EntityManagerInterface $em,
        Site $site,
        PersonelRepository $personnelRep
    ): Response {

        /**
         * ===================== AJAX : RECHERCHE AGENT =====================
         */
        if ($request->isXmlHttpRequest()) {
            $search = $request->query->get('search', '');
            $personnels = $personnelRep->findUserBySearch(
                search: $search,
                site: $site,
                typePersonnel: ['agent'],
                statutPlanning: ['actif']
            );

            $response = array_map(fn ($p) => [
                'nom' => $p->getNomCompletUser().' ('.$p->getTelephone().')',
                'id'  => $p->getId(),
            ], $personnels);

            return new JsonResponse(array_values($response));
        }

        /**
         * ===================== CONTEXTE SOURCE =====================
         */
        $agentA        = $affectationSource->getPersonnel();
        $dateReference = $affectationSource->getDateOperation();

        /**
         * ===================== FORMULAIRE =====================
         */
        $prototype = new AffectationAgent();

        // 🧑‍💼 Recherche agent spécifique
        $idClientSearch = $request->get('id_client_search');
        
        if ($idClientSearch) {
            $prototype->setPersonnel($personnelRep->find($idClientSearch));
        }

        $form = $this->createForm(AffectationAgentPermutationType::class, $prototype, [
            'site' => $site,
            'dateOperation' => $dateReference,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var Personel $agentB */
            $agentB     = $form->get('personnel')->getData();
            $dateDebut  = $form->get('dateDebutPermutation')->getData();
            $dateFin    = $form->get('dateFinPermutation')->getData();
            $commentaire = $form->get('commentaire')->getData();

            /**
             * ===================== VALIDATIONS =====================
             */
            if ($agentA === $agentB) {
                $this->addFlash('danger', '❌ Impossible de permuter un agent avec lui-même.');
                return $this->redirectToRoute(
                    'app_logescom_affectation_permutation_new',
                    ['id' => $affectationSource->getId(), 'site' => $site->getId()]
                );
            }

            if ($dateFin < $dateDebut) {
                $this->addFlash('danger', '❌ La date de fin ne peut pas être antérieure à la date de début.');
                return $this->redirectToRoute(
                    'app_logescom_affectation_permutation_new',
                    ['id' => $affectationSource->getId(), 'site' => $site->getId()]
                );
            }

            /**
             * ===================== RÉCUPÉRATION DES AFFECTATIONS =====================
             */
            $affectationsA = $affectationRep->findAffectation(
                personnel: $agentA,
                startDate: $dateDebut,
                endDate: $dateFin
            );

            $affectationsB = $affectationRep->findAffectation(
                personnel: $agentB,
                startDate: $dateDebut,
                endDate: $dateFin
            );

            /**
             * ===================== SÉCURITÉ =====================
             */
            if (!$affectationsA) {
                $this->addFlash(
                    'warning',
                    '⚠️ Aucun créneau trouvé pour les agents sur la période sélectionnée.'
                );
                return $this->redirectToRoute(
                    'app_logescom_operation_affectation_agent_index',
                    ['site' => $site->getId()]
                );
            }

            /**
             * ===================== PERMUTATION / REMPLACEMENT =====================
             */
            $nb = 0;

            /**
             * 🔁 A → B (toujours)
             */
            foreach ($affectationsA as $a) {
                $a->setAgentInitial($agentA);
                $a->setPersonnel($agentB);
                $a->setTypeAffectation('permutation');
                $a->setCommentaire(
                    trim(
                        ($a->getCommentaire() ?? '') .
                        ' | Permutation avec ' . $agentB->getNomCompletUser() .
                        ($affectationsB ? '' : ' (agent de relève)')
                    )
                );
                $nb++;
            }

            /**
             * 🔁 B → A (UNIQUEMENT si B a un planning)
             */
            if ($affectationsB) {
                foreach ($affectationsB as $b) {
                    $b->setAgentInitial($agentB);
                    $b->setPersonnel($agentA);
                    $b->setTypeAffectation('permutation');
                    $b->setCommentaire(
                        trim(
                            ($b->getCommentaire() ?? '') .
                            ' | Permutation avec ' . $agentA->getNomCompletUser()
                        )
                    );
                    $nb++;
                }
            }

            $em->flush();

            /**
             * ===================== FEEDBACK =====================
             */
            $this->addFlash(
                'success',
                sprintf(
                    '🔁 Permutation effectuée du %s au %s (%d créneau(x)).',
                    $dateDebut->format('d/m/Y'),
                    $dateFin->format('d/m/Y'),
                    $nb
                )
            );

            return $this->redirectToRoute(
                'app_logescom_operation_affectation_agent_index',
                ['site' => $site->getId()]
            );
        }

        return $this->render('logescom/operation/affectation_agent/permutation.html.twig', [
            'form' => $form->createView(),
            'affectation_source' => $affectationSource,
            'site' => $site,
        ]);
    }

    #[Route('/repos/{id}/{site}', name: 'app_logescom_affectation_repos_new', methods: ['GET'])]
    public function repos(
        AffectationAgent $affectation,
        Site $site,
        Request $request,
        EntityManagerInterface $em
    ): Response {

        // Action envoyée : repos | repos_travaille | journee_entiere
        $action = $request->get('action');

        // Actions autorisées
        $labels = [
            'repos' => 'Repos',
            'repos_travaille' => 'Repos travaillé',
            'journee_entiere' => 'Journée entière',
        ];

        if (!array_key_exists($action, $labels)) {
            throw $this->createNotFoundException();
        }

        $statutActuel = $affectation->getStatutAffectation();

        // 🔁 CAS 1 : même action → annulation
        if ($statutActuel === $action) {

            $affectation
                ->setStatutAffectation(null)
                ->setCommentaire(
                    trim(
                        ($affectation->getCommentaire() ?? '') .
                        ' | Annulation ' .
                        strtolower($labels[$action])
                    )
                )
                ->setDateSaisie(new \DateTime())
                ->setSaisirPar($this->getUser());

            $em->flush();

            $this->addFlash(
                'info',
                '↩️ ' . $labels[$action] . ' annulée.'
            );

            return $this->redirectToRoute(
                'app_logescom_operation_affectation_agent_index',
                ['site' => $site->getId()]
            );
        }

        // 🔄 CAS 2 : nouvelle action ou remplacement

        $affectation
            ->setStatutAffectation($action)
            ->setCommentaire(
                trim(
                    ($affectation->getCommentaire() ?? '') .
                    ' | ' .
                    $labels[$action]
                )
            )
            ->setDateSaisie(new \DateTime())
            ->setSaisirPar($this->getUser());

        $em->flush();

        $this->addFlash(
            'success',
            match ($action) {
                'repos' => '🛑 L’agent est marqué en repos.',
                'repos_travaille' => '✅ Repos travaillé enregistré.',
                'journee_entiere' => '📅 Journée entière enregistrée.',
            }
        );

        return $this->redirectToRoute(
            'app_logescom_operation_affectation_agent_index',
            ['site' => $site->getId()]
        );
    }

    #[Route('/desactivation/{id}/{site}', name: 'app_logescom_affectation_desactivation_new', methods: ['GET'])]
    public function desactivationAgentPlanning(
        AffectationAgent $affectation,
        Site $site,
        Request $request,
        AffectationAgentRepository $affectationAgentRep,
        EntityManagerInterface $em
    ): Response {

        // Action envoyée : repos | repos_travaille | journee_entiere
        $action = $request->get('action');

        // Actions autorisées
        $labels = [
            'inactif' => 'Desactiver',
        ];

        if (!array_key_exists($action, $labels)) {
            throw $this->createNotFoundException();
        }

        $personnel = $affectation->getPersonnel();
        $affectations = $affectationAgentRep->findCreneauxAVenir(personnel: $personnel, dateDebut: $affectation->getDateOperation());
        $annulation = false;

        foreach ($affectations as $affectation) {

            $statutActuel = $affectation->getStatutAffectation();

            // CAS 1 : même action → annulation
            if ($statutActuel === $action) {

                $affectation
                    ->setStatutAffectation(null)
                    ->setCommentaire(
                        trim(
                            ($affectation->getCommentaire() ?? '') .
                            ' | Annulation ' .
                            strtolower($labels[$action])
                        )
                    )
                    ->setDateSaisie(new \DateTime())
                    ->setSaisirPar($this->getUser());

                $annulation = true;
                $personnel->setStatutPlanning(null);
                continue; // on passe au suivant
            }

            // CAS 2 : nouvelle action
            $affectation
                ->setStatutAffectation($action)
                ->setCommentaire(
                    trim(
                        ($affectation->getCommentaire() ?? '') .
                        ' | ' .
                        $labels[$action]
                    )
                )
                ->setDateSaisie(new \DateTime())
                ->setSaisirPar($this->getUser());

            $personnel->setStatutPlanning('inactif');
            
        }


        $em->flush();

        $this->addFlash(
            $annulation ? 'info' : 'success',
            $annulation
                ? '↩️ Désactivation annulée.'
                : '🛑 L’agent est marqué en inactif.'
        );


        return $this->redirectToRoute(
            'app_logescom_operation_affectation_agent_index',
            ['site' => $site->getId()]
        );
    }




    #[Route('/penalite/new/{site}/{affectation}', name: 'app_logescom_operation_affectation_agent_penalite_new', methods: ['GET', 'POST'])]
    public function penaliteAgentNew(
        AffectationAgent $affectation,
        Site $site,
        ConfigPenaliteTypeRepository $penaliteTypeRep,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {

        $penaliteTypes = $penaliteTypeRep->findAll();

        if ($request->isMethod('POST')) {

            // Récupération
            $ids = $request->request->all('penalites') ?? [];
            $commentaire = trim($request->request->get('commentaire', ''));
            $montantInput = $request->request->get('montant', '');

            // Nettoyage montant saisi
            $montantSaisi = floatval(str_replace(' ', '', preg_replace('/[^0-9 ]/', '', $montantInput)));

            // Validation
            if (empty($ids)) {
                $this->addFlash('danger', "Veuillez sélectionner au moins une pénalité.");
                return $this->redirectToRoute('app_logescom_operation_affectation_agent_penalite_new', [
                    'site' => $site->getId(),
                    'affectation' => $affectation->getId(),
                ]);
            }

            // Récup pénalités sélectionnées
            $penalitesValides = $penaliteTypeRep->findBy(['id' => $ids]);

            if (count($penalitesValides) !== count($ids)) {
                $this->addFlash('danger', "Certaines pénalités sélectionnées sont invalides.");
                return $this->redirectToRoute('app_logescom_operation_affectation_agent_penalite_new', [
                    'site' => $site->getId(),
                    'affectation' => $affectation->getId(),
                ]);
            }

            // -----------------------------------------
            // 🔥 LOGIQUE DU MONTANT
            // -----------------------------------------

            $plusieurs = count($penalitesValides) > 1;

            foreach ($penalitesValides as $type) {

                // Montant final pour CETTE pénalité
                if ($plusieurs) {
                    // CAS 1 : plusieurs pénalités → montantDefaut
                    $montantFinal = floatval($type->getMontantDefaut());
                } else {
                    // CAS 2 : une seule pénalité
                    if ($montantSaisi > 0) {
                        $montantFinal = $montantSaisi;
                    } else {
                        $montantFinal = floatval($type->getMontantDefaut());
                    }
                }

                // Sécurisation
                if ($montantFinal <= 0) {
                    $montantFinal = floatval($type->getMontantDefaut());
                }

                // -----------------------------------------
                // 🔥 Création de l'entité
                // -----------------------------------------
                $penalite = new Penalite();
                $penalite
                    ->setAffectationAgent($affectation)
                    ->setPenaliteType($type)
                    ->setMontant($montantFinal)
                    ->setCommentaire($commentaire)
                    ->setDateSaisie(new \DateTime())
                    ->setPeriode($affectation->getDateOperation())
                    ->setSaisiePar($this->getUser());

                $entityManager->persist($penalite);
            }

            $entityManager->flush();

            $this->addFlash("success", "Pénalité(s) enregistrée(s) avec succès.");
            return $this->redirectToRoute(
                'app_logescom_operation_affectation_agent_show',
                ['id' => $affectation->getId(), 'site' => $site->getId()]
            );
        }

        return $this->render('logescom/operation/affectation_agent/penalite_new.html.twig', [
            'site' => $site,
            'affectation' => $affectation,
            'penalites' => $penaliteTypes
        ]);
    }

    #[Route('/delete/penalite/{id}/{site}', name: 'app_logescom_operation_affectation_agent_penalite_delete', methods: ['POST'])]
    public function deletePenalite(Request $request, Penalite $penalite, EntityManagerInterface $entityManager, Site $site): Response
    {
        if ($this->isCsrfTokenValid('delete_penalite'.$penalite->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($penalite);

            $deleteReason = $request->request->get('delete_reason');
            
            $dateOperation = $penalite->getPeriode() 
                ? $penalite->getPeriode()->format('d/m/Y H:i') 
                : 'Date non définie';

            $information = "Site : {$penalite->getAffectationAgent()->getContrat()->getBien()->getNom()} | Date : {$dateOperation}";

            $personnel = $this->getUser();
            $historiqueSup = new HistoriqueChangement;
            $historiqueSup->setSaisiePar($personnel)
                    ->setDateSaisie(new \DateTime())
                    ->setMotif($deleteReason ? $deleteReason : 'penalité supprimée')
                    ->setInformation($information)
                    ->setType('affectation')
                    ->setSite($penalite->getAffectationAgent()->getContrat()->getBien()->getSite());
            $entityManager->persist($historiqueSup);
            
            $entityManager->flush();
        }
        return $this->redirectToRoute('app_logescom_operation_affectation_agent_show', ['id' => $penalite->getAffectationAgent()->getId(), 'site' => $site->getId()], Response::HTTP_SEE_OTHER);
    }



    #[Route('/planning/{site}/{personel}', name: 'app_logescom_operation_affectation_agent_planning')]
    public function planning(
        Site $site,
        Personel $personel,
        PersonelRepository $personnelRep,
        ContratSurveillanceRepository $contratRepo,
        Request $request
    ): Response {
        $personnels = $personnelRep->findPersonnelBySite(site: $site, fonction:['agent']);
        $contrats = $contratRepo->findContrat(site: $site);

        $personnelId = $request->query->get('searchpersonnel');
        $contratId = $request->query->get('searchcontrat');

        $personnelActive = $personnelId ? $personnelRep->find($personnelId) : null;
        $contratActive = $contratId ? $contratRepo->find($contratId) : null;

        return $this->render('logescom/operation/affectation_agent/planning.html.twig', [
            'site' => $site,
            'personnels' => $personnels,
            'contrats' => $contrats,
            'personnelActive' => $personnelActive,
            'contratActive' => $contratActive,
            'personnel' => $personel,
        ]);
    }




    #[Route('/api/{site}/{personnel}', name: 'app_logescom_operation_affectation_agent_planning_api')]
    public function events(
        Site $site,
        Personel $personnel,
        PersonelRepository $personnelRep,
        ContratSurveillanceRepository $contratRepo,
        AffectationAgentRepository $affectationRep,
        Request $request
    ): JsonResponse {
        $personnelId = $request->query->get('searchpersonnel');
        $contratId = $request->query->get('searchcontrat');

        $personnelSearch = $personnelId ? $personnelRep->find($personnelId) : null;
        $contratSearch = $contratId ? $contratRepo->find($contratId) : null;

        // 📦 Filtrage
        if ($personnelSearch && $contratSearch) {
            $events = $affectationRep->findBy(['personnel' => $personnelSearch, 'contrat' => $contratSearch]);
        } elseif ($personnelSearch) {
            $events = $affectationRep->findBy(['personnel' => $personnelSearch]);
        } elseif ($contratSearch) {
            $events = $affectationRep->findBy(['contrat' => $contratSearch]);
        } else {
            $events = $affectationRep->findAffectation(site: $site);
        }

        // 🧩 Formatage des données (identique à ta version précédente)
        $formattedEvents = [];
        foreach ($events as $event) {
            $personnel = $event->getPersonnel();
            $contrat = $event->getContrat();
            $bien = $contrat->getBien();
            $site = $bien->getSite();

            $startDate = clone $event->getDateOperation();
            $endDate = clone $event->getDateOperation();
            $startTime = $event->getHeureDebut()?->format('H:i:s') ?? '00:00:00';
            $endTime = $event->getHeureFin()?->format('H:i:s') ?? '23:59:59';

            if ($event->getHeureFin() && $event->getHeureDebut() && $event->getHeureFin() < $event->getHeureDebut()) {
                $endDate->modify('+1 day');
            }

            $poste = strtolower($event->getPoste() ?? '');
            $color = match (true) {
                str_contains($poste, 'jour') => '#0d6efd',
                str_contains($poste, 'nuit') => '#6f42c1',
                str_contains($poste, 'chef') => '#198754',
                default => '#adb5bd',
            };

            $formattedEvents[] = [
                'id' => $event->getId(),
                'title' => sprintf('%s — %s (%s)', $personnel->getNomComplet(), ucfirst($event->getPoste()), ucfirst($bien->getNom())),
                'start' => $startDate->format('Y-m-d') . 'T' . $startTime,
                'end' => $endDate->format('Y-m-d') . 'T' . $endTime,
                'backgroundColor' => $color,
                'textColor' => '#fff',
                'personnel' => $personnel->getNomComplet(),
                'poste' => $event->getPoste(),
                'commentaire' => $event->getCommentaire(),
                'site' => $site->getId(),
                'siteNom' => $bien->getNom(),
                'presence' => $event->isPresenceConfirme() ? 'Présent' : 'Non confirmé',
                'url' => 'edit/' . $event->getId(),
            ];
        }

        return new JsonResponse($formattedEvents);
    }


  #[Route('/liste/agent/{site}', name: 'app_logescom_operation_affectation_agent_liste', methods: ['GET', 'POST'])]
    public function listeAgent(
        Request $request,
        PersonelRepository $personnelRep,
        Site $site,
        BienRepository $bienRep,
        EntityManagerInterface $em,
        ConfigZoneRattachementRepository $zoneRep,
    ): Response {

        /* ============================
        * 🟢 TRAITEMENT POST (1 BIEN / AGENT)
        * ============================ */
        if ($request->isMethod('POST')) {

            $personelId = $request->request->get('personel');
            $bienId     = $request->request->get('bien'); // ✅ UN SEUL
            $token      = $request->request->get('_token');

            if (!$this->isCsrfTokenValid('affecter_agent_' . $personelId, $token)) {
                throw $this->createAccessDeniedException('Token CSRF invalide');
            }

            $personel = $personnelRep->find($personelId);
            if (!$personel) {
                throw $this->createNotFoundException('Agent introuvable');
            }

            // 🔥 Affectation simple
            if ($bienId) {
                $bien = $bienRep->find($bienId);
                if (!$bien) {
                    throw $this->createNotFoundException('Bien introuvable');
                }

                $personel->setBienAffecte($bien);
            } else {
                // possibilité de désaffecter
                $personel->setBienAffecte(null);
            }

            $em->persist($personel);

            $em->flush();

            $this->addFlash('success', 'Affectation mise à jour avec succès');

            return $this->redirectToRoute(
                'app_logescom_operation_affectation_agent_liste',
                ['site' => $site->getId()]
            );
        }

        /* ============================
        * 🔵 TRAITEMENT GET (AFFICHAGE)
        * ============================ */

        // 🔍 Recherche AJAX
        if ($request->isXmlHttpRequest()) {
            $search = $request->query->get('search', '');
            $personnels = $personnelRep->findUserBySearch(
                search: $search,
                site: $site,
                fonction: ['agent']
            );

            return new JsonResponse(array_map(fn ($p) => [
                'nom' => $p->getNomCompletUser() . ' (' . $p->getTelephone() . ')',
                'id'  => $p->getId(),
            ], $personnels));
        }

        // 🧑‍💼 Recherche agent spécifique
        $idClientSearch = $request->get('id_client_search');
        if ($idClientSearch) {
            $agents = $personnelRep->findPersonnelBySite(
                id: $idClientSearch
            );
        }elseif ($request->get("zone")) {
            $agents = $personnelRep->findPersonnelBySite(
                zones: $request->get("zone")
            );

        } else {
            // 📋 Liste agents
            $agents = $personnelRep->findPersonnelBySite(
                site: $site,
                fonction: ['agent']
            );
        }

    

        $biens = $bienRep->findBiens(site: $site, zoneRattachement: $request->get("zone"),  statut: 'actif');

        return $this->render('logescom/operation/affectation_agent/agent.html.twig', [
            'personels' => $agents,
            'biens'     => $biens,
            'site'      => $site,
            'zones' => $zoneRep->findAll()
        ]);
    }



    #[Route('/liste/Agent/show/{id}/{site}', name: 'app_logescom_operation_affectation_agent_liste_show', methods: ['GET'])]
    public function listeAgentShow(Personel $personel, Site $site): Response
    {
        return $this->render('logescom/operation/affectation_agent/agent_show.html.twig', [
            'personel' => $personel,
            'site' => $site,
        ]);
    }





    #[Route('/confirm/delete/{id}/{site}', name: 'app_logescom_operation_affectation_agent_confirm_delete', methods: ['GET', 'POST'])]
    public function confirmDelete(AffectationAgent $affectationAgent, Site $site, Request $request): Response
    {
        $param = $request->request->get('param'); // Récupération du paramètre
        
        if ($param =='group') {
            $route_suppression = $this->generateUrl('app_logescom_operation_affectation_agent_delete_group', [
                'groupe' => $affectationAgent->getGroupeAffectation(),
                'site' => $site->getId(),
            ]);
        }elseif ($param =='agent') {
            $route_suppression = $this->generateUrl('app_logescom_operation_affectation_agent_delete_planning_agent', [
                'id' => $affectationAgent->getId(),
                'site' => $site->getId(),
            ]);
        }else{
            $route_suppression = $this->generateUrl('app_logescom_operation_affectation_agent_delete', [
            'id' => $affectationAgent->getId(),
            'site' => $site->getId(),
        ]);
        }

        
        

        return $this->render('logescom/operation/affectation_agent/confirm_delete.html.twig', [
            'route_suppression' => $route_suppression,
            'param' => $param,
            'site' => $site,
            'entreprise' => $site->getEntreprise(),
            'operation' => $affectationAgent
        ]);
    }

    #[Route('/delete/{id}/{site}', name: 'app_logescom_operation_affectation_agent_delete', methods: ['POST'])]
    public function delete(Request $request, AffectationAgent $affectationAgent, EntityManagerInterface $entityManager, Site $site): Response
    {
        if ($this->isCsrfTokenValid('delete'.$affectationAgent->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($affectationAgent);

            $deleteReason = $request->request->get('delete_reason');
            
            $dateOperation = $affectationAgent->getDateOperation() 
                ? $affectationAgent->getDateOperation()->format('d/m/Y H:i') 
                : 'Date non définie';

            $information = "Site : {$affectationAgent->getContrat()->getBien()->getNOm()} | Date : {$dateOperation}";

            $personnel = $this->getUser();
            $historiqueSup = new HistoriqueChangement;
            $historiqueSup->setSaisiePar($personnel)
                    ->setDateSaisie(new \DateTime())
                    ->setMotif($deleteReason)
                    ->setInformation($information)
                    ->setType('affectation')
                    ->setSite($affectationAgent->getContrat()->getBien()->getSite());
            $entityManager->persist($historiqueSup);

            $entityManager->flush();
        }
        return $this->redirectToRoute('app_logescom_operation_affectation_agent_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/delete-group/{groupe}/{site}', name: 'app_logescom_operation_affectation_agent_delete_group', methods: ['POST'])]
    public function deleteGroup(string $groupe, Request $request, Site $site, AffectationAgentRepository $repo, EntityManagerInterface $em): Response
    {
        $affectations = $repo->findBy(['groupeAffectation' => $groupe]);
        if (!$affectations) {
            $this->addFlash('warning', 'Aucune affectation trouvée pour ce groupe.');
            return $this->redirectToRoute('app_logescom_operation_affectation_agent_index');
        }

        foreach ($affectations as $a) {
            $em->remove($a);

            $deleteReason = $request->request->get('delete_reason');
            
            $dateOperation = $a->getDateOperation() 
                ? $a->getDateOperation()->format('d/m/Y H:i') 
                : 'Date non définie';

            $information = "Site : {$a->getContrat()->getBien()->getNOm()} | Date : {$dateOperation}";

            $personnel = $this->getUser();
            $historiqueSup = new HistoriqueChangement;
            $historiqueSup->setSaisiePar($personnel)
                    ->setDateSaisie(new \DateTime())
                    ->setMotif($deleteReason)
                    ->setInformation($information)
                    ->setType('affectation')
                    ->setSite($a->getContrat()->getBien()->getSite());
            $em->persist($historiqueSup);
        }
        $em->flush();

        $this->addFlash('success', sprintf('✅ %d affectations supprimées avec succès.', count($affectations)));

        return $this->redirectToRoute('app_logescom_operation_affectation_agent_index', [
            'site' => $site->getId()
        ]);
    }


    #[Route('/delete/planning/agent/{id}/{site}', name: 'app_logescom_operation_affectation_agent_delete_planning_agent', methods: ['POST'])]
    public function deletePlanningAgent(AffectationAgent $affectationAgent, Request $request, Site $site, AffectationAgentRepository $repo, EntityManagerInterface $em): Response
    {
        $affectations = $repo->findBy(['personnel' => $affectationAgent->getPersonnel(), 'contrat' => $affectationAgent->getContrat()]);
        if (!$affectations) {
            $this->addFlash('warning', 'Aucun créneau trouvé pour cet agent.');
            return $this->redirectToRoute('app_logescom_operation_affectation_agent_index');
        }

        // dd($affectationAgent);
        foreach ($affectations as $a) {
            $em->remove($a);

            $deleteReason = $request->request->get('delete_reason');
            
            $dateOperation = $a->getDateOperation() 
                ? $a->getDateOperation()->format('d/m/Y H:i') 
                : 'Date non définie';

            $information = "Site : {$a->getContrat()->getBien()->getNOm()} | Date : {$dateOperation}";

            $personnel = $this->getUser();
            $historiqueSup = new HistoriqueChangement;
            $historiqueSup->setSaisiePar($personnel)
                    ->setDateSaisie(new \DateTime())
                    ->setMotif($deleteReason)
                    ->setInformation($information)
                    ->setType('affectation')
                    ->setSite($a->getContrat()->getBien()->getSite());
            $em->persist($historiqueSup);
        }
        $em->flush();

        $this->addFlash('success', sprintf('✅ %d créneaux supprimés avec succès.', count($affectations)));

        return $this->redirectToRoute('app_logescom_operation_affectation_agent_index', [
            'site' => $site->getId()
        ]);
    }

}
