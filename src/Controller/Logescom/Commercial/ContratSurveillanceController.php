<?php

namespace App\Controller\Logescom\Commercial;

use App\Entity\Site;
use App\Repository\BienRepository;
use App\Entity\ContratSurveillance;
use App\Form\ContratSurveillanceType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\Common\Collections\ArrayCollection;
use App\Repository\ContratSurveillanceRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\ConfigZoneRattachementRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/logescom/commercial/contrat/surveillance')]
final class ContratSurveillanceController extends AbstractController
{
    #[Route('/index/{site}', name: 'app_logescom_commercial_contrat_surveillance_index', methods: ['GET'])]
    public function index(ContratSurveillanceRepository $contratRep, Site $site, Request $request, ConfigZoneRattachementRepository $zoneRep): Response
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
                    'nom' => ($contrat->getbien()->getClient()->getNomComplet()).' '.$contrat->getbien()->getNom().' '.$contrat->getbien()->getClient()->getTelephone(),
                    'id' => $contrat->getId()
                ]; // Mettez à jour avec le nom réel de votre propriété
            }
            return new JsonResponse($response);
        }


        if ($request->get("id_client_search")) {

            $contrats = $contratRep->findContratBySearch(id: $search);

        } else {

            $search = $request->get('search') ?? NULL;
            $pageEncours = $request->get('pageEnCours', 1);

            $contrats = $contratRep->findContratBySearch(
                site: $site,
                zones: $request->get('zone') ?? null,
                search: $search,
                pageEnCours: $pageEncours,
                limit: 1000
            );
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


        // 🔥 Envoi à Twig
        return $this->render('logescom/commercial/contrat_surveillance/index.html.twig', [
            'contratsGroup' => $contratsGroup,
            'nbrePages'     => $nbrePages,
            'pageEnCours'   => $pageEnCours,
            'limit'         => $limit,
            'total'         => $total,
            'site'          => $site,
            'zones' => $zoneRep->findAll()
        ]);

    }

    #[Route('/new/{site}', name: 'app_logescom_commercial_contrat_surveillance_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager, 
        Site $site,  
        SessionInterface $session, 
        BienRepository $bienRep
    ): Response
    {
        $contrat = new ContratSurveillance();
        $contrat->setStatut("actif");

        /* ------------------------------
        Pré-sélection du bien
        ------------------------------- */
        $bienId = $request->query->get('bien');

        if ($bienId) {
            $bien = $bienRep->find($bienId);

            // SÉCURITÉ : vérifier que le bien existe OR appartient au site
            if ($bien && $bien->getSite()->getId() === $site->getId()) {
                $contrat->setBien($bien);
                $session->set('last_bien_id', $bien->getId());
            }
        } elseif ($session->has('last_bien_id')) {
            $bien = $bienRep->find($session->get('last_bien_id'));

            if ($bien && $bien->getSite()->getId() === $site->getId()) {
                $contrat->setBien($bien);
            }
        }

        /* ------------------------------
        Création du formulaire
        ------------------------------- */
        // Charger uniquement les biens sans contrat
        $biensDisponibles = $bienRep->findBiensSansContrat($site);
        // Passer ces biens au formulaire
        $form = $this->createForm(ContratSurveillanceType::class, $contrat, [
            'biens_disponibles' => $biensDisponibles
        ]);
        $form->handleRequest($request);
        /* ------------------------------
        Traitement du formulaire
        ------------------------------- */
        if ($form->isSubmitted() && $form->isValid()) {
             // 🔐 Sécurité : au moins un type de surveillance obligatoire
            if ($contrat->getTypesSurveillance()->isEmpty()) {

                $this->addFlash(
                    'danger',
                    'Veuillez sélectionner au moins un type de surveillance pour ce contrat.'
                );

                // Retour à la page de création
                return $this->redirectToRoute(
                    'app_logescom_commercial_contrat_surveillance_new',
                    ['site' => $site->getId()]
                );
            }
            // ↓ Récupération des types de surveillance (collection)
            foreach ($contrat->getTypesSurveillance() as $type) {
                $type->setContrat($contrat); // relation inverse
            }
            $entityManager->persist($contrat);
            $entityManager->flush();

            $this->addFlash('success', 'Contrat de surveillance enregistré avec succès.');

            return $this->redirectToRoute(
                'app_logescom_commercial_contrat_surveillance_index', 
                ["site" => $site->getId()], 
                Response::HTTP_SEE_OTHER
            );
        }

        /* ------------------------------
        Affichage du formulaire
        ------------------------------- */
        return $this->render('logescom/commercial/contrat_surveillance/new.html.twig', [
            'contrat_surveillance' => $contrat,
            'form' => $form,
            'site' => $site,
        ]);
    }


    #[Route('/{id}/{site}', name: 'app_logescom_commercial_contrat_surveillance_show', methods: ['GET'])]
    public function show(ContratSurveillance $contratSurveillance, Site $site): Response
    {
        return $this->render('logescom/commercial/contrat_surveillance/show.html.twig', [
            'contrat_surveillance' => $contratSurveillance,
            'site' => $site,
        ]);
    }

    #[Route('/edit/{id}/{site}', name: 'app_logescom_commercial_contrat_surveillance_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        ContratSurveillance $contrat,
        EntityManagerInterface $entityManager,
        Site $site,
        BienRepository $bienRep,
    ): Response
    {

        /* -----------------------------------------------------
            SÉCURITÉ : Vérifier que le contrat appartient au site
        ----------------------------------------------------- */
        if ($contrat->getBien()->getSite()->getId() !== $site->getId()) {
            throw $this->createAccessDeniedException("Ce contrat n'appartient pas à ce site.");
        }

        /* -----------------------------------------------------
            Gestion de la collection avant modification
            (pour gérer la suppression des items)
        ----------------------------------------------------- */
        $originalTypes = [];

        foreach ($contrat->getTypesSurveillance() as $type) {
            $originalTypes[$type->getId()] = $type;
        }

        // Charger uniquement les biens sans contrat
        $biensDisponibles = $bienRep->findBiensSansContrat($site);
        // 2) Bien déjà lié à ce contrat
        $bienActuel = $contrat->getBien();

        if ($bienActuel && !in_array($bienActuel, $biensDisponibles, true)) {
            $biensDisponibles[] = $bienActuel;
        }
        // Passer ces biens au formulaire
        $form = $this->createForm(ContratSurveillanceType::class, $contrat, [
            'biens_disponibles' => $biensDisponibles
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Traitement des suppressions
            foreach ($originalTypes as $id => $oldType) {
                if (!$contrat->getTypesSurveillance()->contains($oldType)) {
                    $entityManager->remove($oldType);
                }
            }

            // Ré-assigner le contrat pour les nouveaux
            foreach ($contrat->getTypesSurveillance() as $type) {
                $type->setContrat($contrat);
                $entityManager->persist($type);
            }

            $entityManager->flush();

            $this->addFlash("success", "Contrat modifié avec succès.");
            return $this->redirectToRoute(
                'app_logescom_commercial_contrat_surveillance_index',
                ["site" => $site->getId()]
            );
        }

        return $this->render('logescom/commercial/contrat_surveillance/edit.html.twig', [
            'contrat_surveillance' => $contrat,
            'form'  => $form,
            'site'  => $site,
        ]);
    }


    #[Route('/{id}/{site}', name: 'app_logescom_commercial_contrat_surveillance_delete', methods: ['POST'])]
    public function delete(Request $request, ContratSurveillance $contratSurveillance, EntityManagerInterface $entityManager, Site $site): Response
    {
        if ($this->isCsrfTokenValid('delete'.$contratSurveillance->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($contratSurveillance);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_logescom_commercial_contrat_surveillance_index', ["site" => $site->getId()], Response::HTTP_SEE_OTHER);
    }
}
