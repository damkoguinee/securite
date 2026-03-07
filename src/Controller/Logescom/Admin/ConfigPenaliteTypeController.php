<?php

namespace App\Controller\Logescom\Admin;

use App\Entity\ConfigPenaliteType;
use App\Form\ConfigPenaliteTypeType;
use App\Repository\EntrepriseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ConfigPenaliteTypeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/logescom/admin/config/penalite/type')]
final class ConfigPenaliteTypeController extends AbstractController
{
    #[Route(name: 'app_logescom_admin_config_penalite_type_index', methods: ['GET'])]
    public function index(ConfigPenaliteTypeRepository $configPenaliteTypeRepository, EntrepriseRepository $entrepriseRep): Response
    {
        return $this->render('logescom/admin/config_penalite_type/index.html.twig', [
            'config_penalite_types' => $configPenaliteTypeRepository->findAll(),
            'entreprise' => $entrepriseRep->findOneBy([]),
        ]);
    }

    #[Route('/new', name: 'app_logescom_admin_config_penalite_type_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, EntrepriseRepository $entrepriseRep): Response
    {
        $configPenaliteType = new ConfigPenaliteType();
        $form = $this->createForm(ConfigPenaliteTypeType::class, $configPenaliteType);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($configPenaliteType);
            $entityManager->flush();

            return $this->redirectToRoute('app_logescom_admin_config_penalite_type_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('logescom/admin/config_penalite_type/new.html.twig', [
            'config_penalite_type' => $configPenaliteType,
            'form' => $form,
            'entreprise' => $entrepriseRep->findOneBy([]),
        ]);
    }

    #[Route('/{id}', name: 'app_logescom_admin_config_penalite_type_show', methods: ['GET'])]
    public function show(ConfigPenaliteType $configPenaliteType, EntrepriseRepository $entrepriseRep): Response
    {
        return $this->render('logescom/admin/config_penalite_type/show.html.twig', [
            'config_penalite_type' => $configPenaliteType,
            'entreprise' => $entrepriseRep->findOneBy([]),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_logescom_admin_config_penalite_type_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ConfigPenaliteType $configPenaliteType, EntityManagerInterface $entityManager, EntrepriseRepository $entrepriseRep): Response
    {
        $form = $this->createForm(ConfigPenaliteTypeType::class, $configPenaliteType);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_logescom_admin_config_penalite_type_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('logescom/admin/config_penalite_type/edit.html.twig', [
            'config_penalite_type' => $configPenaliteType,
            'form' => $form,
            'entreprise' => $entrepriseRep->findOneBy([]),
        ]);
    }

    #[Route('/{id}', name: 'app_logescom_admin_config_penalite_type_delete', methods: ['POST'])]
    public function delete(Request $request, ConfigPenaliteType $configPenaliteType, EntityManagerInterface $entityManager, EntrepriseRepository $entrepriseRep): Response
    {
        if ($this->isCsrfTokenValid('delete'.$configPenaliteType->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($configPenaliteType);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_logescom_admin_config_penalite_type_index', [], Response::HTTP_SEE_OTHER);
    }
}
