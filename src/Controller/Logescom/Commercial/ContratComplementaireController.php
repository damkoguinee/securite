<?php

namespace App\Controller\Logescom\Commercial;

use App\Entity\Site;
use App\Repository\BienRepository;
use App\Entity\ContratComplementaire;
use App\Entity\ContratSurveillance;
use App\Form\ContratComplementaireType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\Common\Collections\ArrayCollection;
use App\Repository\ContratComplementaireRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/logescom/commercial/contrat/complementaire')]
final class ContratComplementaireController extends AbstractController
{
    #[Route('/index/{site}/{contrat}', name: 'app_logescom_commercial_contrat_complementaire_index', methods: ['GET'])]
    public function index(ContratComplementaireRepository $contratComplRep, Site $site, ContratSurveillance $contrat, Request $request): Response
    {
        return $this->render('logescom/commercial/contrat_complementaire/index.html.twig', [
            'contrat' => $contrat,
            'site' => $site,
        ]);
    }

   #[Route('/new/{site}/{contrat}', name: 'app_logescom_commercial_contrat_complementaire_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        Site $site,
        ContratSurveillance $contrat
    ): Response
    {
        $contratComplementaire = new ContratComplementaire();
        $contratComplementaire->setContrat($contrat);
        $contratComplementaire->setDateSaisie(new \DateTime());
        $contratComplementaire->setSaisiePar($this->getUser());

        $form = $this->createForm(ContratComplementaireType::class, $contratComplementaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            foreach ($contratComplementaire->getComplementTypeSurveillances() as $cts) {
                $cts->setContratComplementaire($contratComplementaire);
                $entityManager->persist($cts);
            }

            $entityManager->persist($contratComplementaire);
            $entityManager->flush();

            $this->addFlash('success', 'Contrat complémentaire créé avec succès.');

            return $this->redirectToRoute('app_logescom_commercial_contrat_surveillance_show', [
                'id' => $contrat->getId(),
                'site' => $site->getId()
            ]);
        }


        return $this->render('logescom/commercial/contrat_surveillance/contrat_complementaire/new.html.twig', [
            'site' => $site,
            'contrat' => $contrat,
            'form' => $form->createView(),
        ]);
    }



    #[Route('/{id}/{site}/{contrat}', name: 'app_logescom_commercial_contrat_complementaire_show', methods: ['GET'])]
    public function show(ContratComplementaire $contratComplementaire, Site $site, ContratSurveillance $contrat,): Response
    {
        return $this->render('logescom/commercial/contrat_complementaire/show.html.twig', [
            'contrat_complementaire' => $contratComplementaire,
            'site' => $site,
            'contrat' => $contrat,
        ]);
    }

    #[Route('/edit/co/{id}/{site}', name: 'app_logescom_commercial_contrat_complementaire_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        EntityManagerInterface $entityManager,
        ContratComplementaire $contratComplementaire,
        Site $site,
    ): Response
    {
        // Préserver les anciennes valeurs de la collection pour détecter les suppressions
        $originalTypes = new ArrayCollection();

        foreach ($contratComplementaire->getComplementTypeSurveillances() as $cts) {
            $originalTypes->add($cts);
        }

        /* --------------------------------------
        FORMULAIRE
        --------------------------------------- */
        $form = $this->createForm(ContratComplementaireType::class, $contratComplementaire);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            // 🗑️ Vérifier les suppressions
            foreach ($originalTypes as $oldCTS) {
                if (!$contratComplementaire->getComplementTypeSurveillances()->contains($oldCTS)) {
                    // Retirer l’élément supprimé
                    $entityManager->remove($oldCTS);
                }
            }

            // 🔄 Réassigner le contrat à chaque élément (sécurisé)
            foreach ($contratComplementaire->getComplementTypeSurveillances() as $cts) {
                $cts->setContratComplementaire($contratComplementaire);
                $entityManager->persist($cts);
            }

            $entityManager->persist($contratComplementaire);
            $entityManager->flush();

            $this->addFlash('success', 'Contrat complémentaire modifié avec succès.');

            return $this->redirectToRoute('app_logescom_commercial_contrat_surveillance_show', [
                'id'   => $contratComplementaire->getContrat()->getId(),
                'site' => $site->getId()
            ]);
        }

        return $this->render('logescom/commercial/contrat_surveillance/contrat_complementaire/edit.html.twig', [
            'contrat_complementaire' => $contratComplementaire,
            'form'  => $form->createView(),
            'site'  => $site,
        ]);
    }



    #[Route('/{id}/{site}', name: 'app_logescom_commercial_contrat_complementaire_delete', methods: ['POST'])]
    public function delete(Request $request, ContratComplementaire $contratComplementaire, EntityManagerInterface $entityManager, Site $site): Response
    {
        if ($this->isCsrfTokenValid('delete'.$contratComplementaire->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($contratComplementaire);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_logescom_commercial_contrat_surveillance_show', ['id' => $contratComplementaire->getContrat()->getId(), "site" => $site->getId()], Response::HTTP_SEE_OTHER);
    }
}
