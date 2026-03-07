<?php

namespace App\Controller\Logescom\Admin;

use App\Repository\EntrepriseRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\ConfigCommune;
use App\Form\ConfigCommuneType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Common\Collections\ArrayCollection;
use App\Repository\ConfigCommuneRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/logescom/admin/config/commune')]
class ConfigCommuneController extends AbstractController
{
    #[Route('/', name: 'app_logescom_admin_config_commune_index', methods: ['GET'])]
    public function index(ConfigCommuneRepository $configCommuneRepository, EntrepriseRepository $entrepriseRep): Response
    {
        $Communes = $configCommuneRepository->findBy([], ['nom' => 'ASC']);
        // Initialisation du tableau pour regrouper les régions par région Administrative
        $communesGroupe = [];
        foreach ($Communes as $Commune) {
            $sousPrefecture = $Commune->getSousPrefecture();
            $nomSousPrefecture = $sousPrefecture->getNom();
            // dd($communesGroupe[$nomSousPrefecture]);
            if (!isset($communesGroupe[$nomSousPrefecture])) {
                $communesGroupe[$nomSousPrefecture] = new ArrayCollection();
            }

            $communesGroupe[$nomSousPrefecture]->add($Commune);
        }
        // Rendu de la vue
        return $this->render('logescom/admin/config_commune/index.html.twig', [
            'prefecturesGroupe' => $communesGroupe,
            'entreprise' => $entrepriseRep->findOneBy([])
        ]);
    }

    #[Route('/new', name: 'app_logescom_admin_config_commune_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, EntrepriseRepository $entrepriseRep): Response
    {
        $configCommune = new ConfigCommune();

        $form = $this->createForm(ConfigCommuneType::class, $configCommune);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($configCommune);
            $entityManager->flush();

            return $this->redirectToRoute('app_logescom_admin_config_commune_index', [], Response::HTTP_SEE_OTHER);
        }
        return $this->render('logescom/admin/config_commune/new.html.twig', [
            'prefecture' => $configCommune,
            'form' => $form,
            'entreprise' => $entrepriseRep->findOneBy([])
        ]);
    }

    #[Route('/{id}', name: 'app_logescom_admin_config_commune_show', methods: ['GET'])]
    public function show(ConfigCommune $configCommune, EntrepriseRepository $entrepriseRep): Response
    {
        return $this->render('logescom/admin/config_commune/show.html.twig', [
            'prefecture' => $configCommune,
            'entreprise' => $entrepriseRep->findOneBy([])
        ]);
    }

    #[Route('/{id}/edit', name: 'app_logescom_admin_config_commune_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ConfigCommune $configCommune, EntityManagerInterface $entityManager, EntrepriseRepository $entrepriseRep): Response
    {
        $form = $this->createForm(ConfigCommuneType::class, $configCommune);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_logescom_admin_config_commune_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('logescom/admin/config_commune/edit.html.twig', [
            'prefecture' => $configCommune,
            'form' => $form,
            'entreprise' => $entrepriseRep->findOneBy([])
        ]);
    }

    #[Route('/{id}', name: 'app_logescom_admin_config_commune_delete', methods: ['POST'])]
    public function delete(Request $request, ConfigCommune $configCommune, EntityManagerInterface $entityManager, EntrepriseRepository $entrepriseRep): Response
    {
        if ($this->isCsrfTokenValid('delete'.$configCommune->getId(), $request->request->get('_token'))) {
            $entityManager->remove($configCommune);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_logescom_admin_config_commune_index', [], Response::HTTP_SEE_OTHER);
    }
}
