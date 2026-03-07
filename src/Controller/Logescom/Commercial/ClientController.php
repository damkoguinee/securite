<?php

namespace App\Controller\Logescom\Commercial;


use App\Entity\Site;
use App\Entity\User;
use App\Entity\Client;
use App\Form\UserType;
use App\Form\ClientType;
use App\Entity\Locataire;
use App\Entity\LieuxVentes;
use App\Entity\DocumentUser;
use App\Form\ClientEditType;
use App\Entity\ConfigQuartier;
use App\Entity\AjustementSolde;
use App\Form\ConfigQuartierType;
use App\Entity\GroupeFacturation;
use App\Repository\BienRepository;
use App\Repository\UserRepository;
use App\Entity\DernierNumeroClient;
use App\Entity\ConfigDivisionLocale;
use App\Repository\ClientRepository;
use App\Repository\DeviseRepository;
use App\Entity\MouvementCollaborateur;
use App\Entity\DernierNumeroZoneClient;
use App\Repository\EntrepriseRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ConfigDeviseRepository;
use App\Repository\ConfigQuartierRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\ConfigZoneAdresseRepository;
use App\Repository\GroupeFacturationRepository;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Common\Collections\ArrayCollection;
use App\Repository\ConfigDivisionLocaleRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\String\Slugger\AsciiSlugger;
use App\Repository\ConfigZoneRattachementRepository;
use App\Repository\MouvementCollaborateurRepository;
use App\Repository\DernierNumeroZoneClientRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Repository\ConfigRegionAdministrativeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface as Hasher;

#[Route('/logescom/commercial/client')]
class ClientController extends AbstractController
{
    #[Route('/index/{site}', name: 'app_logescom_commercial_client_index', methods: ['GET'])]
    public function index(ClientRepository $clientRep, Site $site, Request $request, ConfigZoneRattachementRepository $zoneRep): Response
    {
        if ($request->get("id_client_search")){
            $search = $request->get("id_client_search");
        }else{
            $search = "";
        }
       
        if ($request->isXmlHttpRequest()) {
            $search = $request->query->get('search');
            $clients = $clientRep->findClientBySearch(search: $search, site: $site);    
            $response = [];
            foreach ($clients['data'] as $client) {
                $response[] = [
                    'nom' => ($client->getNomComplet()).' '.$client->getTelephone(),
                    'id' => $client->getId()
                ]; // Mettez à jour avec le nom réel de votre propriété
            }
            return new JsonResponse($response);
        }


        if ($request->get("id_client_search")){
            $clients = $clientRep->findClientBySearch(id: $search);
        }else{

            $pageEncours = $request->get('pageEnCours', 1);
            $clients = $clientRep->findClientBySearch(site: $site, pageEnCours: $pageEncours, limit: 100);
        } 
        return $this->render('logescom/commercial/client/index.html.twig', [
            'clients' => $clients,
            'site' => $site,
            'search' => $search,
            'zones' => $zoneRep->findAll()
        ]);
    }

    private function uploadFile($file): string
    {
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        $slugger = new \Symfony\Component\String\Slugger\AsciiSlugger();
        $safeName = $slugger->slug($originalName);

        $newName = $safeName . "_" . uniqid() . "." . $file->guessExtension();

        $file->move(
            $this->getParameter("dossier_img_clients"),
            $newName
        );

        return $newName;
    }

    

    #[Route('/new/{site}', name: 'app_logescom_commercial_client_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        Site $site,
        UserRepository $userRepo,
        ClientRepository $clientRepo,
        ConfigQuartierRepository $quartierRepo,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        
        /** 🔍 Recherche via AJAX */
        if ($request->isXmlHttpRequest()) {
            $search = $request->query->get('search');
            $quartiers = $quartierRepo->rechercheQuartier($search);

            $response = [];
            foreach ($quartiers as $q) {
                $response[] = [
                    'nom' => ucwords($q->getNom()) . ' ' . strtoupper($q->getDivisionLocale()->getNom()),
                    'id' => $q->getId()
                ];
            }
            return new JsonResponse($response);
        }

        /** 🏠 Adresse choisie */
        $quartier = $request->get("id_adresse_search")
            ? $quartierRepo->find($request->get("id_adresse_search"))
            : null;

        $client = new Client();
        $client->addSite($site);

        $form = $this->createForm(ClientType::class, $client, [
            'sites' => new ArrayCollection([$site])
        ]);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {

            if (!$client->getReference()) {
                /** --------------------------------
                 * 🔢 Référence automatique
                 * -------------------------------- */
                $dateToday     = (new \DateTime())->format('ymd');
                $nextUserId    = $userRepo->findMaxId() + 1;

                $reference     = "c" . $dateToday . sprintf('%04d', $nextUserId);
                $client->setReference($reference);
            }

            /** --------------------------------
             * 👤 Username automatique
             * -------------------------------- */
            if (!$client->getUsername()) {
                $base = strtolower($client->getNom() . substr($client->getTelephone(), -4));
                $client->setUsername($base);
            }

            /** --------------------------------
             * 🔐 Mot de passe auto (tel + @)
             * -------------------------------- */
            $plainPassword = $client->getTelephone() . "@";
            $hashedPassword = $passwordHasher->hashPassword($client, $plainPassword);
            $client->setPassword($hashedPassword);


            /** --------------------------------
             * 🏠 Adresse
             * -------------------------------- */
            if ($quartier) {
                $client->setAdresse($quartier);
            }

            $client->setTypeUser('client')
                ->setStatut('actif')
                ->setDateCreation(new \DateTime())
                ->setRoles(['ROLE_CLIENT']);


            /** --------------------------------
             * 🖾 Upload PHOTO
             * -------------------------------- */
            $photo = $form->get("photo")->getData();
            if ($photo) {
                $fileName = $this->uploadFile($photo, $this->getParameter("dossier_img_clients"));
                $client->setPhoto($fileName);
            }

            /** ------------------------
             *  📄 DOCUMENTS MULTIPLES
             * ------------------------ */
            foreach ($form->get('documentUsers') as $docForm) {

                $uploaded = $docForm->get('fichier')->getData();
                $titre    = $docForm->get('titre')->getData();

                if ($uploaded) {
                    $fileName = $this->uploadFile($uploaded);

                    $doc = new DocumentUser();
                    $doc->setTitre($titre);
                    $doc->setFichier($fileName);
                    $doc->setUser($client);

                    $em->persist($doc);
                    $client->addDocumentUser($doc);
                }
            }

            /** 💾 SAVE */
            $em->persist($client);
            $em->flush();

            $this->addFlash("success", "Client ajouté avec succès 🙂");

            return $this->redirectToRoute('app_logescom_commercial_client_show', [
                'id' => $client->getId(),
                'site'   => $site->getId(),
            ]);
        }

        return $this->render('logescom/commercial/client/new.html.twig', [
            'client'   => $client,
            'form'     => $form,
            'site'     => $site,
            'quartier' => $quartier,
            'referer'  => $request->headers->get('referer')
        ]);
    }

    #[Route('/adresse/ajax/{site}', name: 'app_logescom_commercial_client_adresse_ajax', methods: ['GET'])]
    public function adresseAjax(Request $request, ConfigQuartierRepository $quartierRep, Site $site): JsonResponse
    {
        $search = $request->query->get('search');

        if (!$search) {
            return new JsonResponse([]);
        }

        $quartiers = $quartierRep->rechercheQuartier($search);

        $response = [];

        foreach ($quartiers as $quartier) {
            $response[] = [
                'nom' => ucwords($quartier->getNom()) . ' ' .
                        ($quartier->getDivisionLocale() ? strtoupper($quartier->getDivisionLocale()->getNom()) : ''),
                'id'  => $quartier->getId()
            ];
        }

        return new JsonResponse($response);
    }





    #[Route('/show/{id}/{site}', name: 'app_logescom_commercial_client_show', methods: ['GET', 'POST'])]
    public function show(Client $client, Site $site, Request $request, EntityManagerInterface $em, GroupeFacturationRepository $groupeFacturationRep, BienRepository $bienRep): Response
    {
        if ($request->get('groupeFacturation')) {
            $nom = $request->get('nom');
            $verifGroup = $groupeFacturationRep->findOneBy(['nom' => $nom, 'client' => $client]);
            if ($verifGroup) {
                $this->addFlash("warning", "Ce nom de groupe est déjà attribue 🙂");

                return $this->redirectToRoute('app_logescom_commercial_client_show', [
                    'id' => $client->getId(),
                    'site' => $site->getId()
                ]);
            }
            $groupe = (new GroupeFacturation())
            ->setNom($nom)
            ->setClient($client);
    
            $em->persist($groupe);
            $em->flush();
    
            $this->addFlash(
                'success',
                '✅ Groupe de facturation créé avec succès.'
            );
        }

        if ($request->get('delete')) {

            $idGroupe = $request->get('delete');
            $groupeFacturation = $groupeFacturationRep->find($idGroupe);

            if (!$groupeFacturation) {
                $this->addFlash('danger', '❌ Groupe introuvable.');
                return $this->redirectToRoute('app_logescom_commercial_client_show', [
                    'id' => $client->getId(),
                    'site' => $site->getId()
                ]);
            }

            // 🔐 Sécurité métier : pas de suppression si des biens sont rattachés
            if (!$groupeFacturation->getBiens()->isEmpty()) {
                $this->addFlash(
                    'warning',
                    '⚠️ Impossible de supprimer ce groupe : des biens y sont rattachés.'
                );
                return $this->redirectToRoute('app_logescom_commercial_client_show', [
                    'id' => $client->getId(),
                    'site' => $site->getId()
                ]);
            }

            // ✅ Suppression autorisée
            $em->remove($groupeFacturation);
            $em->flush();

            $this->addFlash(
                'success',
                '🗑️ Groupe de facturation supprimé avec succès.'
            );

            return $this->redirectToRoute('app_logescom_commercial_client_show', [
                'id' => $client->getId(),
                'site' => $site->getId()
            ]);
        }

        // =====================
        // ➕ RATTACHER UN BIEN
        // =====================
        if ($request->isMethod('POST') && $request->get('groupe_id') && $request->get('bien_id')) {

            $groupe = $groupeFacturationRep->find($request->get('groupe_id'));
            $bien   = $bienRep->find($request->get('bien_id'));

            if ($groupe && $bien) {
                $bien->setGroupeFacturation($groupe);
                $em->flush();

                $this->addFlash('success', '🏢 Site rattaché au groupe.');
            }

            return $this->redirectToRoute('app_logescom_commercial_client_show', [
                'id' => $client->getId(),
                'site' => $site->getId()
            ]);
        }
        
        // =====================
        // ➖ DÉTACHER UN BIEN
        // =====================
        if ($request->get('detach')) {

            $bien = $bienRep->find($request->get('detach'));
            if ($bien) {
                $bien->setGroupeFacturation(null);
                $em->flush();

                $this->addFlash('info', '🔗 Site retiré du groupe.');
            }

            return $this->redirectToRoute('app_logescom_commercial_client_show', [
                'id' => $client->getId(),
                'site' => $site->getId()
            ]);
        }


        return $this->render('logescom/commercial/client/show.html.twig', [
            'client' => $client,
            'site' => $site,
        ]);
    }

    #[Route('/edit/{id}/{site}', name: 'app_logescom_commercial_client_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Client $client,
        EntityManagerInterface $em,
        ConfigQuartierRepository $quartierRepo,
        Site $site,
        UserPasswordHasherInterface $passwordHasher
    ): Response {

        /** ------------------------------------
         * 🔍 Recherche AJAX adresse
         * ------------------------------------ */
        if ($request->isXmlHttpRequest()) {
            $search = $request->query->get('search');
            $quartiers = $quartierRepo->rechercheQuartier($search);

            $response = [];
            foreach ($quartiers as $q) {
                $response[] = [
                    'nom' => ucwords($q->getNom()) . ' ' . strtoupper($q->getDivisionLocale()->getNom()),
                    'id'  => $q->getId()
                ];
            }
            return new JsonResponse($response);
        }

        /** ------------------------------------
         * 🏠 Adresse sélectionnée
         * ------------------------------------ */
        $quartier = $request->get("id_adresse_search")
            ? $quartierRepo->find($request->get("id_adresse_search"))
            : $client->getAdresse();

        /** ------------------------------------
         * 📌 Formulaire
         * ------------------------------------ */
        $form = $this->createForm(ClientType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** ------------------------------------
             * 🏠 Mise à jour adresse si modifiée
             * ------------------------------------ */
            if ($quartier) {
                $client->setAdresse($quartier);
            }

            /** ------------------------------------
             * 🖼 PHOTO
             * ------------------------------------ */
            $photo = $form->get("photo")->getData();
            if ($photo) {

                if ($client->getPhoto()) {
                    $ancien = $this->getParameter("dossier_img_clients") . "/" . $client->getPhoto();
                    if (file_exists($ancien)) unlink($ancien);
                }

                $newName = $this->uploadFile($photo, $this->getParameter("dossier_img_clients"));
                $client->setPhoto($newName);
            }

            /** ------------------------------------
             * 📄 DOCUMENTS MULTIPLES
             * ------------------------------------ */
            foreach ($form->get('documentUsers') as $docForm) {

                $uploaded = $docForm->get('fichier')->getData();
                $titre    = $docForm->get('titre')->getData();

                if ($uploaded) {

                    $newName = $this->uploadFile($uploaded);

                    $doc = new DocumentUser();
                    $doc->setTitre($titre);
                    $doc->setFichier($newName);
                    $doc->setUser($client);

                    $em->persist($doc);
                    $client->addDocumentUser($doc);
                }
            }

            /** ------------------------------------
             * 🔐 Mise à jour du mot de passe
             * ------------------------------------ */
            $plainPassword = $form->get("password")->getData();
            if ($plainPassword) {
                $hashed = $passwordHasher->hashPassword($client, $plainPassword);
                $client->setPassword($hashed);
            }

            /** ------------------------------------
             * 🔗 Garantir le rattachement au site
             * ------------------------------------ */
            if (!$client->getSite()->contains($site)) {
                $client->addSite($site);
            }

            /** ------------------------------------
             * 💾 Enregistrement
             * ------------------------------------ */
            $em->flush();

            $this->addFlash("success", "Client modifié avec succès 🙂");

            return $this->redirectToRoute('app_logescom_commercial_client_index', [
                'site' => $site->getId()
            ]);
        }

        return $this->render('logescom/commercial/client/edit.html.twig', [
            'client'   => $client,
            'form'     => $form,
            'site'     => $site,
            'quartier' => $quartier,
            'referer'  => $request->headers->get('referer')
        ]);
    }


    #[Route('/adresse/{site}', name: 'app_logescom_commercial_client_adresse', methods: ['GET', 'POST'])]
    public function newAdresseClient(Request $request, Site $site, EntityManagerInterface $entityManager, ConfigDivisionLocaleRepository $divisionRep, ConfigQuartierRepository $quartierRep, EntrepriseRepository $entrepriseRep): Response
    {

        if ($request->isXmlHttpRequest()) {
            $search = $request->query->get('search');
            $divisions = $divisionRep->rechercheDivisionParTypes($search, ['sous préfecture', 'commune urbaine']);    
            $response = [];
            foreach ($divisions as $division) {
                $response[] = [
                    'nom' => ucwords($division->getNom())." ".ucwords($division->getRegion()->getNom()),
                    'id' => $division->getId()
                ]; 
            }
            return new JsonResponse($response);
        }

        if ($request->get("id_adresse_search")){
            $division = $divisionRep->find($request->get("id_adresse_search"));
        }else{
            $division = array();
        }


        if ($request->get('division') and $request->get('nom')) {
            $nom = $request->get('nom');
            $code = $request->get('code');
            $longitude = $request->get('longitude');
            $latitude = $request->get('latitude');
            $division = $request->get('division');
            $division = $divisionRep->find($division);

            $configQuartier = new ConfigQuartier();
            $configQuartier->setCode($code)
                    ->setNom($nom)
                    ->setLongitude($longitude)
                    ->setLatitude($latitude)
                    ->setDivisionLocale($division);
            
           
            $entityManager->persist($configQuartier);
            $entityManager->flush();

            $quartier = $quartierRep->findOneBy([], ['id' => 'DESC']);
            

            return $this->redirectToRoute('app_logescom_commercial_client_new', ['site' => $site->getId(), 'id_adresse_search' => $quartier->getId() ], Response::HTTP_SEE_OTHER);
        }
        return $this->render('logescom/commercial/client/new_adresse.html.twig', [
            'entreprise' => $entrepriseRep->findOneBy([]),
            'site' => $site,
            'division_locale' => $division
        ]);
    }

    #[Route('/delete/{id}/{site}', name: 'app_logescom_commercial_client_delete', methods: ['POST'])]
    public function delete(Request $request, Client $client, UserRepository $userRep, EntityManagerInterface $entityManager, Site $site, EntrepriseRepository $entrepriseRep): Response
    {
        if ($this->isCsrfTokenValid('delete'.$client->getId(), $request->request->get('_token'))) {
            $entityManager->remove($client);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_logescom_commercial_client_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
    }
}
