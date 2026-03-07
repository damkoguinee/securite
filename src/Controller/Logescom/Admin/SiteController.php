<?php

namespace App\Controller\Logescom\Admin;

use App\Entity\Site;
use App\Form\SiteType;
use App\Repository\EntrepriseRepository;
use App\Repository\SiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/logescom/admin/site')]
final class SiteController extends AbstractController
{
    #[Route(name: 'app_logescom_admin_site_index', methods: ['GET'])]
    public function index(SiteRepository $siteRepository, EntrepriseRepository $entrepriseRep): Response
    {
        return $this->render('logescom/admin/site/index.html.twig', [
            'sites' => $siteRepository->findAll(),
            'entreprise' => $entrepriseRep->findOneBy([]),
        ]); 
    }

    #[Route('/new', name: 'app_logescom_admin_site_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, EntrepriseRepository $entrepriseRep): Response
    {
        $Site = new Site();
        $form = $this->createForm(SiteType::class, $Site);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            
            $entityManager->persist($Site);
            $entityManager->flush();

            $this->addFlash('success', 'La Site a été créer avec succès :)');

            return $this->redirectToRoute('app_logescom_admin_site_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('logescom/admin/site/new.html.twig', [
            'site' => $Site,
            'form' => $form,
            'entreprise' => $entrepriseRep->findOneBy([]),

        ]);
    }

    #[Route('/{id}', name: 'app_logescom_admin_site_show', methods: ['GET'])]
    public function show(Site $Site): Response
    {
        return $this->render('logescom/admin/site/show.html.twig', [
            'site' => $Site,
            'entreprise' => $Site->getEntreprise(),

        ]);
    }

    #[Route('/{id}/edit', name: 'app_logescom_admin_site_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Site $Site, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SiteType::class, $Site);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'La Site a été modifier avec succès :)');

            return $this->redirectToRoute('app_logescom_admin_site_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('logescom/admin/site/edit.html.twig', [
            'site' => $Site,
            'form' => $form,
            'entreprise' => $Site->getEntreprise(),
        ]);
    }

    #[Route('/{id}', name: 'app_logescom_admin_site_delete', methods: ['POST'])]
    public function delete(Request $request, Site $Site, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$Site->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($Site);
            $entityManager->flush();

            $this->addFlash('success', 'La Site a été supprimer avec succès :)');
        } else {
            $this->addFlash('warning', 'La Site n\'a pas été supprimer :)');
        }

        return $this->redirectToRoute('app_logescom_admin_site_index', [], Response::HTTP_SEE_OTHER);
    }
}
