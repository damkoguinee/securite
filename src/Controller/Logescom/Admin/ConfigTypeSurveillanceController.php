<?php

namespace App\Controller\Logescom\Admin;

use App\Entity\ConfigTypeSurveillance;
use App\Form\ConfigTypeSurveillanceType;
use App\Repository\ConfigTypeSurveillanceRepository;
use App\Repository\EntrepriseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/logescom/admin/config/type/surveillance')]
final class ConfigTypeSurveillanceController extends AbstractController
{
    #[Route(name: 'app_logescom_admin_config_type_surveillance_index', methods: ['GET'])]
    public function index(ConfigTypeSurveillanceRepository $configTypeSurveillanceRepository, EntrepriseRepository $entrepriseRep): Response
    {
        return $this->render('logescom/admin/config_type_surveillance/index.html.twig', [
            'config_type_surveillances' => $configTypeSurveillanceRepository->findAll(),
            'entreprise' => $entrepriseRep->findOneBy([]),
        ]);
    }

    #[Route('/new', name: 'app_logescom_admin_config_type_surveillance_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, EntrepriseRepository $entrepriseRep): Response
    {
        $configTypeSurveillance = new ConfigTypeSurveillance();
        $form = $this->createForm(ConfigTypeSurveillanceType::class, $configTypeSurveillance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($configTypeSurveillance);
            $entityManager->flush();

            return $this->redirectToRoute('app_logescom_admin_config_type_surveillance_index', [], Response::HTTP_SEE_OTHER);
        }
        return $this->render('logescom/admin/config_type_surveillance/new.html.twig', [
            'config_type_surveillance' => $configTypeSurveillance,
            'form' => $form,
            'entreprise' => $entrepriseRep->findOneBy([]),
        ]);
    }

    #[Route('/{id}', name: 'app_logescom_admin_config_type_surveillance_show', methods: ['GET'])]
    public function show(ConfigTypeSurveillance $configTypeSurveillance, EntrepriseRepository $entrepriseRep): Response
    {
        return $this->render('logescom/admin/config_type_surveillance/show.html.twig', [
            'config_type_surveillance' => $configTypeSurveillance,
            'entreprise' => $entrepriseRep->findOneBy([]),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_logescom_admin_config_type_surveillance_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ConfigTypeSurveillance $configTypeSurveillance, EntityManagerInterface $entityManager, EntrepriseRepository $entrepriseRep): Response
    {
        $form = $this->createForm(ConfigTypeSurveillanceType::class, $configTypeSurveillance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_logescom_admin_config_type_surveillance_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('logescom/admin/config_type_surveillance/edit.html.twig', [
            'config_type_surveillance' => $configTypeSurveillance,
            'form' => $form,
            'entreprise' => $entrepriseRep->findOneBy([]),
        ]);
    }

    #[Route('/{id}', name: 'app_logescom_admin_config_type_surveillance_delete', methods: ['POST'])]
    public function delete(Request $request, ConfigTypeSurveillance $configTypeSurveillance, EntityManagerInterface $entityManager, EntrepriseRepository $entrepriseRep): Response
    {
        if ($this->isCsrfTokenValid('delete'.$configTypeSurveillance->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($configTypeSurveillance);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_logescom_admin_config_type_surveillance_index', [], Response::HTTP_SEE_OTHER);
    }
}
