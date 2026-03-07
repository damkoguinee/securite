<?php

namespace App\Controller\Logescom\Admin;

use App\Repository\EntrepriseRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\ConfigDivisionLocale;
use App\Form\ConfigDivisionLocaleType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Common\Collections\ArrayCollection;
use App\Repository\ConfigDivisionLocaleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/logescom/admin/config/prefecture')]
class ConfigPrefectureController extends AbstractController
{
    #[Route('/', name: 'app_logescom_admin_config_prefecture_index', methods: ['GET'])]
    public function index(ConfigDivisionLocaleRepository $configDivisionLocaleRepository, EntrepriseRepository $entrepriseRep): Response
    {
        $prefectures = $configDivisionLocaleRepository->findBy(['type' => 'préfecture'], ['nom' => 'ASC']);
        // Initialisation du tableau pour regrouper les régions par région Administrative
        $prefectureGroupe = [];
        foreach ($prefectures as $prefecture) {
            $regionAdministrative = $prefecture->getRegion();
            $nomRegionAdministrative = $regionAdministrative->getNom();
            // dd($prefectureGroupe[$nomRegionAdministrative]);
            if (!isset($prefectureGroupe[$nomRegionAdministrative])) {
                $prefectureGroupe[$nomRegionAdministrative] = new ArrayCollection();
            }

            $prefectureGroupe[$nomRegionAdministrative]->add($prefecture);
        }
        // Rendu de la vue
        return $this->render('logescom/admin/config_prefecture/index.html.twig', [
            'prefecturesGroupe' => $prefectureGroupe,
            'entreprise' => $entrepriseRep->findOneBy([])
        ]);
    }

    #[Route('/new', name: 'app_logescom_admin_config_prefecture_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, EntrepriseRepository $entrepriseRep): Response
    {
        $configDivisionLocale = new ConfigDivisionLocale();

        $form = $this->createForm(ConfigDivisionLocaleType::class, $configDivisionLocale);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($configDivisionLocale);
            $entityManager->flush();

            return $this->redirectToRoute('app_logescom_admin_config_prefecture_index', [], Response::HTTP_SEE_OTHER);
        }
        return $this->render('logescom/admin/config_prefecture/new.html.twig', [
            'prefecture' => $configDivisionLocale,
            'form' => $form,
            'entreprise' => $entrepriseRep->findOneBy([])
        ]);
    }

    #[Route('/{id}', name: 'app_logescom_admin_config_prefecture_show', methods: ['GET'])]
    public function show(ConfigDivisionLocale $configDivisionLocale, EntrepriseRepository $entrepriseRep): Response
    {
        return $this->render('logescom/admin/config_prefecture/show.html.twig', [
            'prefecture' => $configDivisionLocale,
            'entreprise' => $entrepriseRep->findOneBy([])
        ]);
    }

    #[Route('/{id}/edit', name: 'app_logescom_admin_config_prefecture_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ConfigDivisionLocale $configDivisionLocale, EntityManagerInterface $entityManager, EntrepriseRepository $entrepriseRep): Response
    {
        $form = $this->createForm(ConfigDivisionLocaleType::class, $configDivisionLocale);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_logescom_admin_config_prefecture_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('logescom/admin/config_prefecture/edit.html.twig', [
            'prefecture' => $configDivisionLocale,
            'form' => $form,
            'entreprise' => $entrepriseRep->findOneBy([])
        ]);
    }

    #[Route('/{id}', name: 'app_logescom_admin_config_prefecture_delete', methods: ['POST'])]
    public function delete(Request $request, ConfigDivisionLocale $configDivisionLocale, EntityManagerInterface $entityManager, EntrepriseRepository $entrepriseRep): Response
    {
        if ($this->isCsrfTokenValid('delete'.$configDivisionLocale->getId(), $request->request->get('_token'))) {
            $entityManager->remove($configDivisionLocale);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_logescom_admin_config_prefecture_index', [], Response::HTTP_SEE_OTHER);
    }
}
