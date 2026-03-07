<?php

namespace App\Controller\Logescom\Comptable\Salaire_personnel;

use App\Entity\ConfigSalaire;
use App\Entity\Site;
use App\Form\ConfigSalaireType;
use App\Repository\ConfigSalaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/logescom/comptable/salaire/personnel/config/salaire')]
final class ConfigSalaireController extends AbstractController
{
    #[Route('/{site}',  name: 'app_logescom_comptable_salaire_personnel_config_salaire_index', methods: ['GET'])]
    public function index(ConfigSalaireRepository $configSalaireRepository, Site $site): Response
    {
        return $this->render('logescom/comptable/salaire_personnel/config_salaire/index.html.twig', [
            'config_salaires' => $configSalaireRepository->findBy(['site' => $site]),
            'site' => $site,
        ]);
    }

    #[Route('/new/{site}', name: 'app_logescom_comptable_salaire_personnel_config_salaire_new', methods: ['GET', 'POST'])]
    public function new(Site $site, Request $request, EntityManagerInterface $entityManager): Response
    {
        $configSalaire = new ConfigSalaire();
        $form = $this->createForm(ConfigSalaireType::class, $configSalaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $configSalaire->setSite($site);
            $entityManager->persist($configSalaire);
            $entityManager->flush();

            return $this->redirectToRoute('app_logescom_comptable_salaire_personnel_config_salaire_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('logescom/comptable/salaire_personnel/config_salaire/new.html.twig', [
            'config_salaire' => $configSalaire,
            'form' => $form,
            'site' => $site,
        ]);
    }

    #[Route('/show/{id}/{site}', name: 'app_logescom_comptable_salaire_personnel_config_salaire_show', methods: ['GET'])]
    public function show(ConfigSalaire $configSalaire, Site $site): Response
    {
        return $this->render('logescom/comptable/salaire_personnel/config_salaire/show.html.twig', [
            'config_salaire' => $configSalaire,
            'site' => $site,
        ]);
    }

    #[Route('/edit/{id}/{site}', name: 'app_logescom_comptable_salaire_personnel_config_salaire_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ConfigSalaire $configSalaire, EntityManagerInterface $entityManager, Site $site): Response
    {
        $form = $this->createForm(ConfigSalaireType::class, $configSalaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_logescom_comptable_salaire_personnel_config_salaire_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('logescom/comptable/salaire_personnel/config_salaire/edit.html.twig', [
            'config_salaire' => $configSalaire,
            'form' => $form,
            'site' => $site,
        ]);
    }

    #[Route('/delete/{id}/{site}', name: 'app_logescom_comptable_salaire_personnel_config_salaire_delete', methods: ['POST'])]
    public function delete(Request $request, ConfigSalaire $configSalaire, EntityManagerInterface $entityManager, Site $site): Response
    {
        if ($this->isCsrfTokenValid('delete'.$configSalaire->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($configSalaire);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_logescom_comptable_salaire_personnel_config_salaire_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
    }
}
