<?php

namespace App\Controller\Logescom\Admin;

use App\Entity\ConfigTypeBien;
use App\Form\ConfigTypeBienType;
use App\Repository\EntrepriseRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ConfigTypeBienRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/logescom/admin/config/type/bien')]
final class ConfigTypeBienController extends AbstractController
{
    #[Route(name: 'app_logescom_admin_config_type_bien_index', methods: ['GET'])]
    public function index(ConfigTypeBienRepository $configTypeBienRepository, EntrepriseRepository $entrepriseRep): Response
    {
        return $this->render('logescom/admin/config_type_bien/index.html.twig', [
            'config_type_biens' => $configTypeBienRepository->findAll(),
            'entreprise' => $entrepriseRep->findOneBy([]),
        ]);
    }

    #[Route('/new', name: 'app_logescom_admin_config_type_bien_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, EntrepriseRepository $entrepriseRep): Response
    {
        $configTypeBien = new ConfigTypeBien();
        $form = $this->createForm(ConfigTypeBienType::class, $configTypeBien);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($configTypeBien);
            $entityManager->flush();

            return $this->redirectToRoute('app_logescom_admin_config_type_bien_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('logescom/admin/config_type_bien/new.html.twig', [
            'config_type_bien' => $configTypeBien,
            'form' => $form,
            'entreprise' => $entrepriseRep->findOneBy([]),
        ]);
    }

    #[Route('/{id}', name: 'app_logescom_admin_config_type_bien_show', methods: ['GET'])]
    public function show(ConfigTypeBien $configTypeBien, EntrepriseRepository $entrepriseRep): Response
    {
        return $this->render('logescom/admin/config_type_bien/show.html.twig', [
            'config_type_bien' => $configTypeBien,
            'entreprise' => $entrepriseRep->findOneBy([]),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_logescom_admin_config_type_bien_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ConfigTypeBien $configTypeBien, EntityManagerInterface $entityManager, EntrepriseRepository $entrepriseRep): Response
    {
        $form = $this->createForm(ConfigTypeBienType::class, $configTypeBien);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_logescom_admin_config_type_bien_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('logescom/admin/config_type_bien/edit.html.twig', [
            'config_type_bien' => $configTypeBien,
            'form' => $form,
            'entreprise' => $entrepriseRep->findOneBy([]),
        ]);
    }

    #[Route('/{id}', name: 'app_logescom_admin_config_type_bien_delete', methods: ['POST'])]
    public function delete(Request $request, ConfigTypeBien $configTypeBien, EntityManagerInterface $entityManager, EntrepriseRepository $entrepriseRep): Response
    {
        if ($this->isCsrfTokenValid('delete'.$configTypeBien->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($configTypeBien);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_logescom_admin_config_type_bien_index', [], Response::HTTP_SEE_OTHER);
    }
}
