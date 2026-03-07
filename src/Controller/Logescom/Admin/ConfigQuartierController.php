<?php

namespace App\Controller\Logescom\Admin;

use App\Repository\EntrepriseRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\ConfigQuartier;
use App\Form\ConfigQuartierType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Common\Collections\ArrayCollection;
use App\Repository\ConfigQuartierRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/logescom/admin/config/quartier')]
class ConfigQuartierController extends AbstractController
{
    #[Route('/', name: 'app_logescom_admin_config_quartier_index', methods: ['GET'])]
    public function index(ConfigQuartierRepository $configQuartierRepository, EntrepriseRepository $entrepriseRep): Response
    {
        $quartiers = $configQuartierRepository->findBy([], ['nom' => 'ASC']);
        // Initialisation du tableau pour regrouper les régions par région Administrative
        $quartiersGroupe = [];
        foreach ($quartiers as $quartier) {
            $communes = $quartier->getDivisionLocale();
            $nomCommune = $communes->getNom();
            // dd($quartiersGroupe[$nomCommune]);
            if (!isset($quartiersGroupe[$nomCommune])) {
                $quartiersGroupe[$nomCommune] = new ArrayCollection();
            }

            $quartiersGroupe[$nomCommune]->add($quartier);
        }
        // Rendu de la vue
        return $this->render('logescom/admin/config_quartier/index.html.twig', [
            'quartiersGroupe' => $quartiersGroupe,
            'entreprise' => $entrepriseRep->findOneBy([])
        ]);
    }

    #[Route('/new', name: 'app_logescom_admin_config_quartier_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, EntrepriseRepository $entrepriseRep): Response
    {
        $configQuartier = new ConfigQuartier();

        $form = $this->createForm(ConfigQuartierType::class, $configQuartier);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($configQuartier);
            $entityManager->flush();

            return $this->redirectToRoute('app_logescom_admin_config_quartier_index', [], Response::HTTP_SEE_OTHER);
        }
        return $this->render('logescom/admin/config_quartier/new.html.twig', [
            'prefecture' => $configQuartier,
            'form' => $form,
            'entreprise' => $entrepriseRep->findOneBy([])
        ]);
    }

    

    #[Route('/{id}', name: 'app_logescom_admin_config_quartier_show', methods: ['GET'])]
    public function show(ConfigQuartier $configQuartier, EntrepriseRepository $entrepriseRep): Response
    {
        return $this->render('logescom/admin/config_quartier/show.html.twig', [
            'prefecture' => $configQuartier,
            'entreprise' => $entrepriseRep->findOneBy([])
        ]);
    }

    #[Route('/{id}/edit', name: 'app_logescom_admin_config_quartier_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ConfigQuartier $configQuartier, EntityManagerInterface $entityManager, EntrepriseRepository $entrepriseRep): Response
    {
        $form = $this->createForm(ConfigQuartierType::class, $configQuartier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Le quartier/Village a été modifié avec succès :)');
            return $this->redirectToRoute('app_logescom_admin_config_division_locale_show', ['id' => $configQuartier->getDivisionLocale()->getId() ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('logescom/admin/config_quartier/edit.html.twig', [
            'prefecture' => $configQuartier,
            'form' => $form,
            'entreprise' => $entrepriseRep->findOneBy([])
        ]);
    }

    #[Route('/{id}', name: 'app_logescom_admin_config_quartier_delete', methods: ['POST'])]
    public function delete(Request $request, ConfigQuartier $configQuartier, EntityManagerInterface $entityManager, EntrepriseRepository $entrepriseRep): Response
    {
        if ($this->isCsrfTokenValid('delete'.$configQuartier->getId(), $request->request->get('_token'))) {
            $entityManager->remove($configQuartier);
            $entityManager->flush();
        }

        $this->addFlash('success', 'Le quartier/Village a été supprimé avec succès :)');
        return $this->redirectToRoute('app_logescom_admin_config_division_locale_show', ['id' => $configQuartier->getDivisionLocale()->getId() ], Response::HTTP_SEE_OTHER);
    }
}
