<?php

namespace App\Controller\Logescom\Operation;

use App\Entity\Site;
use App\Entity\Presence;
use App\Form\PresenceType;
use App\Repository\PersonelRepository;
use App\Repository\PresenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\AffectationAgentRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\ConfigZoneRattachementRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/logescom/operation/presence')]
final class PresenceController extends AbstractController
{
    #[Route('/{site}',  name: 'app_logescom_operation_presence_index', methods: ['GET'])]
    public function index(
        PresenceRepository $presenceRepository,
        Site $site,
        AffectationAgentRepository $affectationAgentRep,
        Request $request,
        PersonelRepository $personnelRep,
        SessionInterface $session,
        ConfigZoneRattachementRepository $zoneRep,
    ): Response {
         $search = $request->get('id_client_search', '');

        // 🧩 Gestion des dates (session + GET)
        if ($request->query->get('date1')) {
            $date1 = $request->query->get('date1');
            // On sauvegarde en session
            $session->set('presence_date1', $date1);
        } else {
            // Si pas dans la requête, on tente depuis la session
            $date1 = $session->get('presence_date1', date('Y-m-d'));
        }

        // 🔍 Recherche AJAX
        if ($request->isXmlHttpRequest()) {
            $search = $request->query->get('search', '');
            $personnels = $personnelRep->findUserBySearch(search: $search, site: $site, fonction: ['agent'], statutPlanning:['actif']);


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
            $date1 = $session->get('presence_date1', date('Y-m-d'));
            // dd($date1, $date2);
            $affectations = $affectationAgentRep->findAffectation(
                personnel: $idClientSearch,
                startDate: $date1,
                endDate: $date1
            );
        } else {
            $affectations = $affectationAgentRep->findAffectation(
                site: $site,
                startDate: $date1,
                endDate: $date1,
                zones: $request->get('zone') ?? null,
                fonctions: $request->get('fonction') ?? null,
                statutAffectationDifferent: "inactif"
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

        return $this->render('logescom/operation/presence/index.html.twig', [
            'affectationsGroupes' => $affectationsGroupes,
            'site' => $site,
            'search' => $search,
            'date1' => $date1,
            'zones' => $zoneRep->findAll()
        ]);
    }

    #[Route('/presence/validate/{site}', name: 'app_logescom_operation_presence_batch_validate', methods: ['POST'])]
    public function batchValidate(
        Request $request,
        Site $site,
        AffectationAgentRepository $affectationRepo,
        EntityManagerInterface $em
    ): Response {
        $ids = $request->request->all('affectations');
        $action = $request->request->get('action', 'entree'); // entree ou sortie

        if (empty($ids)) {
            $this->addFlash('warning', 'Aucun agent sélectionné.');
            return $this->redirectToRoute('app_logescom_operation_presence_index', ['site' => $site->getId()]);
        }
        $typePointage = $action === 'sortie' ? 'sortie' : 'entree';
        foreach ($ids as $id) {
            $aff = $affectationRepo->find($id);
            if (!$aff) continue;

            $now = new \DateTime();

            $presence = new Presence();
            $presence->setAffectationAgent($aff)
                ->setDatePointage($now)
                ->setTypePointage($action)
                ->setMode('manuel')
                ->setSaisiePar($this->getUser())
                ->setStatut('present')
                ->setDateSaisie(new \DateTime())
                ->setCommentaire(
                $typePointage === 'sortie'
                    ? 'Fin de service confirmée par le superviseur.'
                    : 'Présence confirmée par le superviseur.'
            );

            $aff->setPresenceConfirme(true);
            // Si l’agent pointe à l’entree
            // if ($aff->getHeureDebut() && $now > $aff->getHeureDebut()) {
            //     $presence->setStatut('retard');
            // } else {
            //     $presence->setStatut('present');
            // }
           

            /**
             * 🕘 Calcul du décalage entre l’heure prévue et l’heure réelle
             */
            // if ($action === 'entree' && $aff->getHeureDebut()) {
            //     $datePrevue = (clone $aff->getDateOperation())->setTime(
            //         (int) $aff->getHeureDebut()->format('H'),
            //         (int) $aff->getHeureDebut()->format('i')
            //     );
            // } elseif ($action === 'sortie' && $aff->getHeureFin()) {
            //     $datePrevue = (clone $aff->getDateOperation())->setTime(
            //         (int) $aff->getHeureFin()->format('H'),
            //         (int) $aff->getHeureFin()->format('i')
            //     );

            //     // 🔧 si l’heure de fin est < heure de début, cela veut dire qu’on passe à minuit
            //     if ($aff->getHeureFin() < $aff->getHeureDebut()) {
            //         $datePrevue->modify('+1 day');
            //     }
            // } else {
            //     $datePrevue = null;
            // }


            // if ($datePrevue) {
            //     // $diff = $now->getTimestamp() - $datePrevue->getTimestamp();
            //     // $minutesDiff = (int) round($diff / 60);
            //     // $presence->setEcart($minutesDiff);
            //     $diff = $now->getTimestamp() - $datePrevue->getTimestamp();
            //     $hoursDiff = round($diff / 3600, 2); // arrondi à 2 décimales (ex : 1.25h = 1h15min)
            //     $presence->setEcart($hoursDiff)

            //     // Si l’agent pointe à l’entree
            //     ->setStatut($hoursDiff <= 0 ? "present" : "retard");

            // }

            $em->persist($presence);
            $em->persist($aff);
        }

        $em->flush();

        $this->addFlash(
            'success',
            ucfirst($action) . ' confirmée pour ' . count($ids) . ' agent(s).'
        );

        return $this->redirectToRoute('app_logescom_operation_presence_index', [
            'site' => $site->getId(),
            'zone' => $request->get('zone') ?? null,
            'fonction' => $request->get('fonction') ?? null,
        ]);
    }





    #[Route('/new/{site}', name: 'app_logescom_operation_presence_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, Site $site): Response
    {
        $presence = new Presence();
        $form = $this->createForm(PresenceType::class, $presence);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($presence);
            $entityManager->flush();

            return $this->redirectToRoute('app_logescom_operation_presence_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('logescom/operation/presence/new.html.twig', [
            'presence' => $presence,
            'form' => $form,
            'site' => $site,
        ]);
    }

    #[Route('/{id}/{site}', name: 'app_logescom_operation_presence_show', methods: ['GET'])]
    public function show(Presence $presence, Site $site): Response
    {
        return $this->render('logescom/operation/presence/show.html.twig', [
            'presence' => $presence,
            'site' => $site,
        ]);
    }

    #[Route('/edit/{id}/{site}', name: 'app_logescom_operation_presence_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Presence $presence, EntityManagerInterface $entityManager, Site $site): Response
    {
        $form = $this->createForm(PresenceType::class, $presence);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $presence->setSaisiePar($this->getUser())
                ->setDateSaisie(new \DateTime());

            /**
             * 🕘 Calcul du décalage entre l’heure prévue et l’heure réelle
             */
            // $aff = $presence->getAffectationAgent();
            // $datePointage = $presence->getDatePointage(); // ✅ la vraie date de pointage (pas now)
            // $datePrevue = null;

            // if ($presence->getTypePointage() === 'entree' && $aff->getHeureDebut()) {
            //     $datePrevue = (clone $aff->getDateOperation())->setTime(
            //         (int) $aff->getHeureDebut()->format('H'),
            //         (int) $aff->getHeureDebut()->format('i')
            //     );
            // } elseif ($presence->getTypePointage() === 'sortie' && $aff->getHeureFin()) {
            //     $datePrevue = (clone $aff->getDateOperation())->setTime(
            //         (int) $aff->getHeureFin()->format('H'),
            //         (int) $aff->getHeureFin()->format('i')
            //     );

            //     // 🔧 Cas des horaires de nuit (ex : 19:00 → 06:00)
            //     if ($aff->getHeureFin() < $aff->getHeureDebut()) {
            //         $datePrevue->modify('+1 day');
            //     }
            // }

            // if ($datePrevue && $datePointage) {
            //     $diff = $datePointage->getTimestamp() - $datePrevue->getTimestamp();
            //     $hoursDiff = round($diff / 3600, 2); // 1.25h = 1h15min
            //     $presence->setEcart($hoursDiff)
            //     ->setStatut($hoursDiff <= 0 ? "present" : "retard");
            // } else {
            //     $presence->setEcart(null);
            // }

            $entityManager->flush();

            return $this->redirectToRoute('app_logescom_operation_presence_index', [
                'site' => $site->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('logescom/operation/presence/edit.html.twig', [
            'presence' => $presence,
            'form' => $form,
            'site' => $site,
        ]);
    }


    #[Route('/{id}/{site}', name: 'app_logescom_operation_presence_delete', methods: ['POST'])]
    public function delete(Request $request, Presence $presence, EntityManagerInterface $entityManager, Site $site): Response
    {
        if ($this->isCsrfTokenValid('delete'.$presence->getId(), $request->getPayload()->getString('_token'))) {
            $aff = $presence->getAffectationAgent();
            $aff->setPresenceConfirme(false);
            $entityManager->remove($presence);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_logescom_operation_presence_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
    }
}
