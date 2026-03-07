<?php

namespace App\Controller\Logescom\Admin;

use App\Repository\EntrepriseRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\ConfigRegionAdministrative;
use App\Form\ConfigRegionAdministrativeType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Common\Collections\ArrayCollection;
use App\Repository\ConfigRegionAdministrativeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/logescom/admin/config/region/administrative')]
class ConfigRegionAdministrativeController extends AbstractController
{
    #[Route('/', name: 'app_logescom_admin_config_region_administrative_index', methods: ['GET'])]
    public function index(ConfigRegionAdministrativeRepository $configRegionAdministrativeRepository, EntrepriseRepository $entrepriseRep): Response
    {
        $regions = $configRegionAdministrativeRepository->findBy([], ['nom' => 'ASC']);
        // Initialisation du tableau pour regrouper les régions par région naturelle
        $regionsParNaturelle = [];

        foreach ($regions as $region) {
            // Obtenir la région naturelle
            $regionNaturelle = $region->getRegionNaturelle();

            // Obtenir le nom ou l'identifiant de la région naturelle
            $nomRegionNaturelle = $regionNaturelle->getNom();

            // Grouper les régions par nom de région naturelle
            if (!isset($regionsParNaturelle[$nomRegionNaturelle])) {
                $regionsParNaturelle[$nomRegionNaturelle] = new ArrayCollection();
            }

            $regionsParNaturelle[$nomRegionNaturelle]->add($region);
        }
        // Rendu de la vue
        return $this->render('logescom/admin/config_region_administrative/index.html.twig', [
            'regions_par_naturelle' => $regionsParNaturelle,
            'entreprise' => $entrepriseRep->findOneBy([])
        ]);
    }

    #[Route('/new', name: 'app_logescom_admin_config_region_administrative_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, EntrepriseRepository $entrepriseRep): Response
    {
        $configRegionAdministrative = new ConfigRegionAdministrative();
        $form = $this->createForm(ConfigRegionAdministrativeType::class, $configRegionAdministrative);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($configRegionAdministrative);
            $entityManager->flush();

            return $this->redirectToRoute('app_logescom_admin_config_region_administrative_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('logescom/admin/config_region_administrative/new.html.twig', [
            'config_region_administrative' => $configRegionAdministrative,
            'form' => $form,
            'entreprise' => $entrepriseRep->findOneBy([])
        ]);
    }

    #[Route('/{id}', name: 'app_logescom_admin_config_region_administrative_show', methods: ['GET'])]
    public function show(ConfigRegionAdministrative $configRegionAdministrative, EntrepriseRepository $entrepriseRep): Response
    {
        return $this->render('logescom/admin/config_region_administrative/show.html.twig', [
            'config_region_administrative' => $configRegionAdministrative,
            'entreprise' => $entrepriseRep->findOneBy([])
        ]);
    }

    #[Route('/{id}/edit', name: 'app_logescom_admin_config_region_administrative_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ConfigRegionAdministrative $configRegionAdministrative, EntityManagerInterface $entityManager, EntrepriseRepository $entrepriseRep): Response
    {
        $form = $this->createForm(ConfigRegionAdministrativeType::class, $configRegionAdministrative);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_logescom_admin_config_region_administrative_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('logescom/admin/config_region_administrative/edit.html.twig', [
            'config_region_administrative' => $configRegionAdministrative,
            'form' => $form,
            'entreprise' => $entrepriseRep->findOneBy([])
        ]);
    }

    #[Route('/{id}', name: 'app_logescom_admin_config_region_administrative_delete', methods: ['POST'])]
    public function delete(Request $request, ConfigRegionAdministrative $configRegionAdministrative, EntityManagerInterface $entityManager, EntrepriseRepository $entrepriseRep): Response
    {
        if ($this->isCsrfTokenValid('delete'.$configRegionAdministrative->getId(), $request->request->get('_token'))) {
            $entityManager->remove($configRegionAdministrative);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_logescom_admin_config_region_administrative_index', [], Response::HTTP_SEE_OTHER);
    }
}
