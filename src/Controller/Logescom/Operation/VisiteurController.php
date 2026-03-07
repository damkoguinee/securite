<?php

namespace App\Controller\Logescom\Operation;


use App\Entity\Site;
use App\Entity\Visiteur;
use App\Form\VisiteurType;
use App\Repository\VisiteurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/gandaal/administration/securite/visiteur')]
final class VisiteurController extends AbstractController
{
    #[Route(name: 'app_gandaal_administration_securite_visiteur_index', methods: ['GET'])]
    public function index(VisiteurRepository $visiteurRepository): Response
    {
        return $this->render('gandaal/administration/securite/visiteur/index.html.twig', [
            'visiteurs' => $visiteurRepository->findAll(),
        ]);
    }

    #[Route('/new/{site}', name: 'app_gandaal_administration_securite_visiteur_new', methods: ['GET', 'POST'])]
    public function new(Site $site, VisiteurRepository $visiteurRep, Request $request, EntityManagerInterface $entityManager): Response
    {
        $visiteur = new Visiteur();
        $form = $this->createForm(VisiteurType::class, $visiteur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $visiteur->setSite($site)
                ->setSaisiePar($this->getUser())
                ->setDateSaisie(new \DateTime());
            $entityManager->persist($visiteur);
            $entityManager->flush();

            $this->addFlash("success", "Visiteur enregistré avec succés :)");
            return $this->redirectToRoute('app_gandaal_administration_securite_visiteur_new', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
        }

        
        $visiteurs = $visiteurRep->findVisiteur($site, new \DateTime(), new \DateTime());
        // dd($visiteurs);
        return $this->render('gandaal/administration/securite/visiteur/new.html.twig', [
            'visiteur' => $visiteur,
            'form' => $form,
            'site' => $site,
            'visiteurs' => $visiteurs
        ]);
    }

    #[Route('/{id}', name: 'app_gandaal_administration_securite_visiteur_show', methods: ['GET'])]
    public function show(Visiteur $visiteur): Response
    {
        return $this->render('gandaal/administration/securite/visiteur/show.html.twig', [
            'visiteur' => $visiteur,
        ]);
    }

    #[Route('/edit/{id}/{site}', name: 'app_gandaal_administration_securite_visiteur_edit', methods: ['GET', 'POST'])]
    public function edit(Site $site, Request $request, Visiteur $visiteur, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(VisiteurType::class, $visiteur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash("success", "Visiteur modifié avce succès :)");
            return $this->redirectToRoute('app_gandaal_administration_securite_visiteur_new', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('gandaal/administration/securite/visiteur/edit.html.twig', [
            'visiteur' => $visiteur,
            'form' => $form,
            'site' => $site,
        ]);
    }

    #[Route('/{id}/{site}', name: 'app_gandaal_administration_securite_visiteur_delete', methods: ['POST'])]
    public function delete(Site $site, Request $request, Visiteur $visiteur, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$visiteur->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($visiteur);
            $entityManager->flush();
        }

        $this->addFlash("success", "Visiteurs supprimés avec succées");
        return $this->redirectToRoute('app_gandaal_administration_securite_visiteur_new', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
    }
}
