<?php

namespace App\Controller\Logescom\Admin;

use App\Entity\Appartement;
use App\Form\AppartementType;
use App\Repository\AppartementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/logescom/admin/appartement')]
final class AppartementController extends AbstractController
{
    #[Route(name: 'app_logescom_admin_appartement_index', methods: ['GET'])]
    public function index(AppartementRepository $appartementRepository): Response
    {
        return $this->render('logescom/admin/appartement/index.html.twig', [
            'appartements' => $appartementRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_logescom_admin_appartement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $appartement = new Appartement();
        $form = $this->createForm(AppartementType::class, $appartement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($appartement);
            $entityManager->flush();

            return $this->redirectToRoute('app_logescom_admin_appartement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('logescom/admin/appartement/new.html.twig', [
            'appartement' => $appartement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_logescom_admin_appartement_show', methods: ['GET'])]
    public function show(Appartement $appartement): Response
    {
        return $this->render('logescom/admin/appartement/show.html.twig', [
            'appartement' => $appartement,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_logescom_admin_appartement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Appartement $appartement, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AppartementType::class, $appartement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_logescom_admin_appartement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('logescom/admin/appartement/edit.html.twig', [
            'appartement' => $appartement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_logescom_admin_appartement_delete', methods: ['POST'])]
    public function delete(Request $request, Appartement $appartement, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$appartement->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($appartement);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_logescom_admin_appartement_index', [], Response::HTTP_SEE_OTHER);
    }
}
