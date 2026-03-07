<?php

namespace App\Controller\Logescom\Admin;

use App\Repository\EntrepriseRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\ConfigSousPrefecture;
use App\Form\ConfigSousPrefectureType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Common\Collections\ArrayCollection;
use App\Repository\ConfigSousPrefectureRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/logescom/admin/config/sous/prefecture')]
class ConfigSousPrefectureController extends AbstractController
{
    #[Route('/', name: 'app_logescom_admin_config_sous_prefecture_index', methods: ['GET'])]
    public function index(ConfigSousPrefectureRepository $configSousPrefectureRepository, EntrepriseRepository $entrepriseRep): Response
    {
        $sousPrefectures = $configSousPrefectureRepository->findBy([], ['nom' => 'ASC']);
        // Initialisation du tableau pour regrouper les régions par région Administrative
        $sousPrefctureGroupe = [];
        foreach ($sousPrefectures as $sousPrefecture) {
            $Prefecture = $sousPrefecture->getPrefecture();
            $nomPrefecture = $Prefecture->getNom();
            // dd($sousPrefctureGroupe[$nomPrefecture]);
            if (!isset($sousPrefctureGroupe[$nomPrefecture])) {
                $sousPrefctureGroupe[$nomPrefecture] = new ArrayCollection();
            }

            $sousPrefctureGroupe[$nomPrefecture]->add($sousPrefecture);
        }
        // Rendu de la vue
        return $this->render('logescom/admin/config_sous_prefecture/index.html.twig', [
            'prefecturesGroupe' => $sousPrefctureGroupe,
            'entreprise' => $entrepriseRep->findOneBy([])
        ]);
    }

    #[Route('/new', name: 'app_logescom_admin_config_sous_prefecture_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, EntrepriseRepository $entrepriseRep): Response
    {
        $configSousPrefecture = new ConfigSousPrefecture();

        $form = $this->createForm(ConfigSousPrefectureType::class, $configSousPrefecture);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($configSousPrefecture);
            $entityManager->flush();

            return $this->redirectToRoute('app_logescom_admin_config_sous_prefecture_index', [], Response::HTTP_SEE_OTHER);
        }
        return $this->render('logescom/admin/config_sous_prefecture/new.html.twig', [
            'prefecture' => $configSousPrefecture,
            'form' => $form,
            'entreprise' => $entrepriseRep->findOneBy([])
        ]);
    }

    #[Route('/{id}', name: 'app_logescom_admin_config_sous_prefecture_show', methods: ['GET'])]
    public function show(ConfigSousPrefecture $configSousPrefecture, EntrepriseRepository $entrepriseRep): Response
    {
        return $this->render('logescom/admin/config_sous_prefecture/show.html.twig', [
            'prefecture' => $configSousPrefecture,
            'entreprise' => $entrepriseRep->findOneBy([])
        ]);
    }

    #[Route('/{id}/edit', name: 'app_logescom_admin_config_sous_prefecture_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ConfigSousPrefecture $configSousPrefecture, EntityManagerInterface $entityManager, EntrepriseRepository $entrepriseRep): Response
    {
        $form = $this->createForm(ConfigSousPrefectureType::class, $configSousPrefecture);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_logescom_admin_config_sous_prefecture_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('logescom/admin/config_sous_prefecture/edit.html.twig', [
            'prefecture' => $configSousPrefecture,
            'form' => $form,
            'entreprise' => $entrepriseRep->findOneBy([])
        ]);
    }

    #[Route('/{id}', name: 'app_logescom_admin_config_sous_prefecture_delete', methods: ['POST'])]
    public function delete(Request $request, ConfigSousPrefecture $configSousPrefecture, EntityManagerInterface $entityManager, EntrepriseRepository $entrepriseRep): Response
    {
        if ($this->isCsrfTokenValid('delete'.$configSousPrefecture->getId(), $request->request->get('_token'))) {
            $entityManager->remove($configSousPrefecture);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_logescom_admin_config_sous_prefecture_index', [], Response::HTTP_SEE_OTHER);
    }
}
