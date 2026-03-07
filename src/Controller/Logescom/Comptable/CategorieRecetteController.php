<?php

namespace App\Controller\Logescom\Comptable;

use App\Entity\CategorieRecette;
use App\Entity\Site;
use App\Form\CategorieRecetteType;
use App\Repository\CategorieRecetteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/logescom/comptable/categorie/recette')]
final class CategorieRecetteController extends AbstractController
{
    #[Route(name: 'app_logescom_comptable_categorie_recette_index', methods: ['GET'])]
    public function index(CategorieRecetteRepository $categorieRecetteRepository): Response
    {
        return $this->render('logescom/comptable/categorie_recette/index.html.twig', [
            'categorie_recettes' => $categorieRecetteRepository->findAll(),
        ]);
    }

    #[Route('/new/{site}', name: 'app_logescom_comptable_categorie_recette_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Site $site, EntityManagerInterface $entityManager): Response
    {
        $categorieRecette = new CategorieRecette();
        $form = $this->createForm(CategorieRecetteType::class, $categorieRecette);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($categorieRecette);
            $entityManager->flush();
            $this->addFlash("success", "Catégorie ajoutée avec succès :)");
            return $this->redirectToRoute('app_logescom_comptable_recette_new', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('logescom/comptable/categorie_recette/new.html.twig', [
            'categorie_recette' => $categorieRecette,
            'form' => $form,
            'site' => $site,
        ]);
    }
    #[Route('/{id}', name: 'app_logescom_comptable_categorie_recette_show', methods: ['GET'])]
    public function show(CategorieRecette $categorieRecette): Response
    {
        return $this->render('logescom/comptable/categorie_recette/show.html.twig', [
            'categorie_recette' => $categorieRecette,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_logescom_comptable_categorie_recette_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CategorieRecette $categorieRecette, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CategorieRecetteType::class, $categorieRecette);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_logescom_comptable_categorie_recette_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('logescom/comptable/categorie_recette/edit.html.twig', [
            'categorie_recette' => $categorieRecette,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_logescom_comptable_categorie_recette_delete', methods: ['POST'])]
    public function delete(Request $request, CategorieRecette $categorieRecette, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$categorieRecette->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($categorieRecette);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_logescom_comptable_categorie_recette_index', [], Response::HTTP_SEE_OTHER);
    }
}
