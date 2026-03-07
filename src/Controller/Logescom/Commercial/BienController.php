<?php

namespace App\Controller\Logescom\Commercial;

use App\Entity\Bien;
use App\Entity\Site;
use App\Form\BienForm;
use App\Form\BienType;
use App\Entity\UniteBien;
use App\Entity\Appartement;
use App\Form\UniteBienType;
use App\Form\AppartementType;
use App\Repository\BienRepository;
use App\Repository\ClientRepository;
use App\Repository\EntrepriseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\PaiementLocationRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\OccupationAppartementRepository;
use App\Repository\ConfigZoneRattachementRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/logescom/commercial/bien')]
final class BienController extends AbstractController
{
    #[Route('/{site}', name: 'app_logescom_commercial_bien_index', methods: ['GET'])]
    public function index(
        BienRepository $bienRep,
        Site $site,
        Request $request, 
        ConfigZoneRattachementRepository $zoneRep
    ): Response {

        if ($request->get("id_client_search")){
            $search = $request->get("id_client_search");
        }else{
            $search = "";
        }
       
        if ($request->isXmlHttpRequest()) {
            $search = $request->query->get('search');
            $biens = $bienRep->findBienBySearch(search: $search, site: $site);    
            $response = [];
            foreach ($biens['data'] as $bien) {
                $response[] = [
                    'nom' => ($bien->getClient()->getNomComplet()).' '.$bien->getClient()->getTelephone(),
                    'id' => $bien->getId()
                ]; // Mettez à jour avec le nom réel de votre propriété
            }
            return new JsonResponse($response);
        }


        if ($request->get("id_client_search")){
            $biens = $bienRep->findBienBySearch(id: $search);
        }else{
            $search = $request->get('search') ?? NULL ;
            $pageEncours = $request->get('pageEnCours', 1);
            $biens = $bienRep->findBienBySearch(site: $site, zones: $request->get('zone') ?? null, search: $search, pageEnCours: $pageEncours, limit: 100);
        }
        
        $biensData   = $biens['data'];
        $nbrePages   = $biens['nbrePages'];
        $pageEnCours = $biens['pageEnCours'];
        $limit       = $biens['limit'];
        $total       = $biens['total'];

        $biensGroup = [];

        foreach ($biensData as $bien) {
            $client = $bien->getClient();
            $clientId = $client ? $client->getId() : 'sans_client';

            if (!isset($biensGroup[$clientId])) {
                $biensGroup[$clientId] = [
                    'client' => $client,
                    'biens' => []
                ];
            }

            $biensGroup[$clientId]['biens'][] = $bien;
        }

        return $this->render('logescom/commercial/bien/index.html.twig', [
            'biensGroup'   => $biensGroup,
            'nbrePages'    => $nbrePages,
            'pageEnCours'  => $pageEnCours,
            'limit'        => $limit,
            'total'        => $total,
            'site'         => $site,
            'zones' => $zoneRep->findAll()
        ]);
    }

    #[Route('/ajax/biens-by-zones', name: 'ajax_biens_by_zones', methods: ['GET'])]
    public function biensByZones(
        Request $request,
        BienRepository $bienRepository
    ): JsonResponse {
        $zones = $request->query->all('zones');

        if (!$zones) {
            return $this->json([]);
        }

        $biens = $bienRepository->findBiens(zoneRattachement: $zones);

        $data = [];
        foreach ($biens as $bien) {
            $data[] = [
                'id' => $bien->getId(),
                'label' => $bien->getClient()->getNomComplet() . ' — ' . $bien->getNom()
            ];
        }

        return $this->json($data);
    }


    #[Route('/new/{site}', name: 'app_logescom_commercial_bien_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        Site $site,
        BienRepository $bienRepository,
        EntityManagerInterface $entityManager,
        ClientRepository $clientRep,
        SessionInterface $session
    ): Response {
        $bien = new Bien();

        if ($request->get('client')) {
            $client = $clientRep->find($request->query->get('client'));
            if ($client) {
                $bien->setClient($client);
                $session->set('last_client_id', $client->getId());
            }
        } elseif ($session->has('last_client_id')) {
            $client = $clientRep->find($session->get('last_client_id'));
            if ($client) {
                $bien->setClient($client);
            }
        }

        $form = $this->createForm(BienType::class, $bien);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $bien->setCreatedAt(new \DateTimeImmutable());
            $entityManager->persist($bien);
            $entityManager->flush();

            $this->addFlash('success', 'Le bien a été ajouté avec succès :)');

             return $this->redirectToRoute('app_logescom_commercial_contrat_surveillance_new', ['site' => $site->getId(), 'bien' => $bien->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('logescom/commercial/bien/new.html.twig', [
            'bien' => $bien,
            'form' => $form,
            'site' => $site,
        ]);
    }



    #[Route('/show/{id}/{site}', name: 'app_logescom_commercial_bien_show', methods: ['GET'])]
    public function show(Bien $bien, Site $site, EntrepriseRepository $entrepriseRep): Response
    {
        return $this->render('logescom/commercial/bien/show.html.twig', [
            'bien' => $bien,            
            'site' => $site,            
        ]);
    }

    #[Route('/{id}/{site}/edit', name: 'app_logescom_commercial_bien_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Bien $bien, Site $site, EntityManagerInterface $entityManager, EntrepriseRepository $entrepriseRep): Response
    {
        $form = $this->createForm(BienType::class, $bien);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Le bien a été modifié avec succès :)');

            return $this->redirectToRoute('app_logescom_commercial_bien_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('logescom/commercial/bien/edit.html.twig', [
            'bien' => $bien,
            'form' => $form,
            'site' => $site,
            
        ]);
    }

    #[Route('/delete/{id}/{site}', name: 'app_logescom_commercial_bien_delete', methods: ['POST'])]
    public function delete(Request $request, Bien $bien, Site $site, EntityManagerInterface $entityManager, EntrepriseRepository $entrepriseRep): Response
    {
        if ($this->isCsrfTokenValid('delete'.$bien->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($bien);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_logescom_commercial_bien_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
    }
}
