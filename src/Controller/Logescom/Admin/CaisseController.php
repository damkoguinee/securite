<?php

namespace App\Controller\Logescom\Admin;

use App\Entity\Caisse;
use App\Form\CaisseType;
use App\Repository\CaisseRepository;
use App\Repository\EntrepriseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/logescom/admin/caisse')]
final class CaisseController extends AbstractController
{
    #[Route(name: 'app_logescom_admin_caisse_index', methods: ['GET'])]
    public function index(CaisseRepository $caisseRepository, EntrepriseRepository $entrepriseRep): Response
    { 
        return $this->render('logescom/admin/caisse/index.html.twig', [
            'caisses' => $caisseRepository->findAll(),
            'entreprise' => $entrepriseRep->findOneBy([]),
        ]);
    }

    #[Route('/new', name: 'app_logescom_admin_caisse_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, EntrepriseRepository $entrepriseRep): Response
    {
        $caisse = new Caisse();
        $form = $this->createForm(CaisseType::class, $caisse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($caisse);
            $entityManager->flush();

            $this->addFlash('success', 'La caisse a été créer avec succès :)');

            return $this->redirectToRoute('app_logescom_admin_caisse_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('logescom/admin/caisse/new.html.twig', [
            'caisse' => $caisse,
            'form' => $form,
            'entreprise' => $entrepriseRep->findOneBy([]),
        ]);
    }

    #[Route('/{id}', name: 'app_logescom_admin_caisse_show', methods: ['GET'])]
    public function show(Caisse $caisse, EntrepriseRepository $entrepriseRep): Response
    {

        return $this->render('logescom/admin/caisse/show.html.twig', [
            'caisse' => $caisse,
            'entreprise' => $entrepriseRep->findOneBy([]),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_logescom_admin_caisse_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Caisse $caisse, EntityManagerInterface $entityManager, EntrepriseRepository $entrepriseRep): Response
    {
        $form = $this->createForm(CaisseType::class, $caisse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'La caisse a été modifier avec succès :)');

            return $this->redirectToRoute('app_logescom_admin_caisse_index', [], Response::HTTP_SEE_OTHER);
        }
        return $this->render('logescom/admin/caisse/edit.html.twig', [
            'caisse' => $caisse,
            'form' => $form,
            'entreprise' => $entrepriseRep->findOneBy([]),

        ]);
    }

    #[Route('/{id}', name: 'app_logescom_admin_caisse_delete', methods: ['POST'])]
    public function delete(Request $request, Caisse $caisse, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$caisse->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($caisse);
            $entityManager->flush();

            $this->addFlash('success', 'La caisse a été supprimer avec succès :)');
        } else {
            $this->addFlash('warning', 'La caisse n\'a pas été supprimer :)');
    
        }

        return $this->redirectToRoute('app_logescom_admin_caisse_index', [], Response::HTTP_SEE_OTHER);
    }
}
