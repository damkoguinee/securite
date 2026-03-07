<?php

namespace App\Controller\Logescom\Rh;

use App\Entity\Bien;
use App\Entity\Site;
use App\Entity\Personel;
use App\Form\PersonelType;
use App\Entity\DocumentUser;
use App\Repository\BienRepository;
use App\Repository\SiteRepository;
use App\Repository\UserRepository;
use App\Repository\AgentRepository;
use App\Repository\PersonelRepository;
use App\Repository\EntrepriseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\String\Slugger\AsciiSlugger;
use App\Repository\ConfigZoneRattachementRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/logescom/rh/personel')]
final class PersonelController extends AbstractController
{
    #[Route('/{site}', name: 'app_logescom_rh_personel_index', methods: ['GET'])]
    public function index(PersonelRepository $personnelRep,  Site $site, SessionInterface $session, Request $request, ConfigZoneRattachementRepository $zoneRep): Response
    {
        // Récupération du paramètre type
        $typePersonnel = $request->query->get('type');

        // Mise en session (uniquement s'il existe)
        if ($typePersonnel !== null) {
            $session->set('type_personnel', $typePersonnel);
        }

        // (optionnel) récupération depuis la session si absent dans l'URL
        $typePersonnel = $typePersonnel ?? $session->get('type_personnel');
        $search = $request->get('id_client_search', '');
        // 🔍 Recherche AJAX
        if ($request->isXmlHttpRequest()) {
            $search = $request->query->get('search', '');
            $personnels = $personnelRep->findUserBySearch(search: $search, site: $site, typePersonnel: [$typePersonnel]);

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
            $personels = $personnelRep->findPersonnelBySite( 
                id: $idClientSearch
            );
        }elseif ($request->get("zone")) {
            $personels = $personnelRep->findPersonnelBySite(
                zones: $request->get("zone")
            );

        } else {
                $personels = $personnelRep->findPersonnelBySite(
                    site: $site,
                    typePersonnel: [$typePersonnel]
                );
        }

        $personnelsGroupes = [];
        foreach ($personels as $personel) {

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

        // dd($personnelsGroupes);

        return $this->render('logescom/rh/personel/index.html.twig', [
            'personnelsGroupes' => $personnelsGroupes,
            'site' => $site,
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
            $this->getParameter("dossier_img_personnels"),
            $newName
        );

        return $newName;
    }




    #[Route('/new/{site}', name: 'app_logescom_rh_personel_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $em, 
        Site $site, 
        UserPasswordHasherInterface $passwordHasher, 
        UserRepository $userRepo
    ): Response 
    {
        $personel = new Personel();
        $personel->addSite($site);
        $form = $this->createForm(PersonelType::class, $personel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            # code...
            /** ------------------------
             *  🔢 GENERATION ID + REF
             * ------------------------ */
            $lastUser = $userRepo->findOneBy([], ['id' => 'DESC']);
            $nextId = $lastUser ? $lastUser->getId() + 1 : 1;
            if (!$personel->getReference()) {
                $reference = $site->getInitial().$nextId;
                $personel->setReference($reference);
            }

            /** ------------------------
             *  👤 USERNAME AUTO
             * ------------------------ */
            if (empty($personel->getUsername())) {

                $username = strtolower(
                    preg_replace('/\s+/', '', $personel->getPrenom())
                    . '.' .
                    preg_replace('/\s+/', '', $personel->getNom())
                );

                $username .= $nextId;

                $personel->setUsername($username);
            }

            /** ------------------------
             *  🔐 PASSWORD AUTO
             * ------------------------ */
            $plainPassword = $form->get('password')->getData();

            if (!$plainPassword || trim($plainPassword) === '') {
                $plainPassword = 'Pers' . $nextId . '!';
            }

            $hashedPassword = $passwordHasher->hashPassword($personel, $plainPassword);
            $personel->setPassword($hashedPassword);

            $personel->setTypeUser('personnel');

            /** ------------------------
             *  🖋 SIGNATURE
             * ------------------------ */
            $signature = $form->get('signature')->getData();
            if ($signature) {
                $newName = $this->uploadFile($signature);
                $personel->setSignature($newName);
            }

            /** ------------------------
             *  🖼 PHOTO
             * ------------------------ */
            $photo = $form->get('photo')->getData();
            if ($photo) {
                $newName = $this->uploadFile($photo);
                $personel->setPhoto($newName);
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
                    $doc->setUser($personel);

                    $em->persist($doc);
                    $personel->addDocumentUser($doc);
                }
            }

            /** ------------------------
             *  💾 SAVE
             * ------------------------ */
            $em->persist($personel);
            $em->flush();

            $this->addFlash('success', "Le personnel a été créé avec succès !");
            return $this->redirectToRoute('app_logescom_rh_personel_index', ['site' => $site->getId()]);
        }

        return $this->render('logescom/rh/personel/new.html.twig', [
            'form' => $form,
            'personel' => $personel,
            'site' => $site,
            
        ]);
    }

    #[Route('/ajax/biens-by-zones/personnel', name: 'ajax_biens_by_zones_personnel', methods: ['GET'])]
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



    #[Route('/{id}/{site}', name: 'app_logescom_rh_personel_show', methods: ['GET'])]
    public function show(Personel $personel, Site $site): Response
    {
        return $this->render('logescom/rh/personel/show.html.twig', [
            'personel' => $personel,
            'site' => $site,
        ]);
    }

    #[Route('/{id}/edit/{site}', name: 'app_logescom_rh_personel_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Personel $personel,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $userPass,
        Site $site
    ): Response
    {
        $form = $this->createForm(PersonelType::class, $personel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /* ------ 🔐 Mot de passe --------- */
            if ($form->get('password')->getData()) {
                $personel->setPassword(
                    $userPass->hashPassword($personel, $form->get('password')->getData())
                );
            }

            /* ------ 🖼 PHOTO -------- */
            $photo = $form->get("photo")->getData();
            if ($photo) {

                // Supprimer l'ancienne photo
                if ($personel->getPhoto()) {
                    $oldPath = $this->getParameter("dossier_img_personnels") . "/" . $personel->getPhoto();
                    if (file_exists($oldPath)) unlink($oldPath);
                }

                $personel->setPhoto($this->uploadFile($photo));
            }

            /* ------ ✒ SIGNATURE -------- */
            $signature = $form->get("signature")->getData();
            if ($signature) {

                if ($personel->getSignature()) {
                    $oldPath = $this->getParameter("dossier_img_personnels") . "/" . $personel->getSignature();
                    if (file_exists($oldPath)) unlink($oldPath);
                }

                $personel->setSignature($this->uploadFile($signature));
            }


            /* ------ 📎 DOCUMENTS MULTIPLES -------- */
            foreach ($form->get('documentUsers')->getData() as $index => $doc) {

                $fichier = $form->get('documentUsers')[$index]->get('fichier')->getData();

                if ($fichier) {

                    // Supprimer ancien fichier si existant
                    if ($doc->getFichier()) {
                        $oldPath = $this->getParameter("dossier_img_personnels") . "/" . $doc->getFichier();
                        if (file_exists($oldPath)) unlink($oldPath);
                    }

                    $doc->setFichier($this->uploadFile($fichier));
                }

                $doc->setUser($personel);
                $entityManager->persist($doc);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Le personnel a été modifié avec succès !');

            return $this->redirectToRoute('app_logescom_rh_personel_index', [
                'site' => $site->getId()
            ]);
        }

        return $this->render('logescom/rh/personel/edit.html.twig', [
            'personel'   => $personel,
            'form'       => $form,
            'site'       => $site,
        ]);
    }



    #[Route('/{id}/{site}', name: 'app_logescom_rh_personel_delete', methods: ['POST'])]
    public function delete(Request $request, Personel $personel, Site $site, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$personel->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($personel);
            $entityManager->flush();

            $this->addFlash('success', 'Le personnel a été supprimer avec succès :)');
        } else {
            $this->addFlash('warning', 'Le personnel n\'a pas été supprimer :)');
        }

        return $this->redirectToRoute('app_logescom_rh_personel_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
    }



    #[Route('/personnel/inactif/{site}', name: 'app_logescom_rh_personel_inactif', methods: ['GET'])]
    public function personnelInactif(PersonelRepository $personnelRep,  Site $site, SessionInterface $session, Request $request, ConfigZoneRattachementRepository $zoneRep): Response
    {
        // Récupération du paramètre type
        $typePersonnel = $request->query->get('type');
            

        // Mise en session (uniquement s'il existe)
        if ($typePersonnel !== null) {
            $session->set('type_personnel', $typePersonnel);
        }

        // (optionnel) récupération depuis la session si absent dans l'URL
        $typePersonnel = $typePersonnel ?? $session->get('type_personnel');
        
        $search = $request->get('id_client_search', '');
        // 🔍 Recherche AJAX
        if ($request->isXmlHttpRequest()) {
            $search = $request->query->get('search', '');
            $personnels = $personnelRep->findUserBySearch(search: $search, site: $site, typePersonnel: [$typePersonnel]);

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
            $personels = $personnelRep->findPersonnelBySite( 
                id: $idClientSearch
            );
        }elseif ($request->get("zone")) {
            $personels = $personnelRep->findPersonnelBySite(
                zones: $request->get("zone"),
                statutPlanning: ['inactif']
            );

        } else {
                $personels = $personnelRep->findPersonnelBySite(
                    site: $site,
                    typePersonnel: [$typePersonnel],
                    statutPlanning: ['inactif']
                );
        }

        $personnelsGroupes = [];
        foreach ($personels as $personel) {

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

        // dd($personnelsGroupes);

        return $this->render('logescom/rh/personel/index.html.twig', [
            'personnelsGroupes' => $personnelsGroupes,
            'site' => $site,
            'zones' => $zoneRep->findAll()
        ]);

    }
}
