<?php

namespace App\Controller\Logescom\Rh;

use App\Entity\site;
use App\Entity\AbsencePersonnel;
use App\Form\AbsencePersonnelType;
use App\Repository\UserRepository;
use App\Repository\PersonelRepository;
use App\Repository\EntrepriseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\AbsencePersonnelRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/logescom/rh/absence/personnel')]
class AbsencePersonnelController extends AbstractController
{
    #[Route('/accueil/{site}', name: 'app_logescom_rh_absence_personnel_index', methods: ['GET'])]
    public function index(AbsencePersonnelRepository $absencePersonnelsRepository, Site $site): Response
    {
        
        return $this->render('logescom/rh/absence_personnel/index.html.twig', [
            'site' => $site,
            'absences_personnels' => $absencePersonnelsRepository->findBy(['site' => $site], ['id' => 'DESC']),
        ]);
    }

    #[Route('/new/{site}', name: 'app_logescom_rh_absence_personnel_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        Site $site,
        PersonelRepository $personelRep,
        EntityManagerInterface $entityManager
    ): Response {
        $jour = $request->get('jour') ?? date('Y-m-d');
        $absences = $request->request->all('absences');

        // 🧩 Récupération du tableau des absences soumises
        if ($request->isMethod('POST') && !empty($absences)) {
            $dateAbsence = new \DateTime($jour);

            foreach ($absences as $absenceData) {
                if (empty($absenceData['duree']) || empty($absenceData['type'])) {
                    continue; // Ignore les lignes incomplètes
                }

                $absence = new AbsencePersonnel();
                $duree = (float) $absenceData['duree'];
                $type = $absenceData['type'];
                // 🧾 Remplissage des infos
                $absence->setDateAbsence($dateAbsence)
                        ->setType($type)
                        ->setHeureAbsence($type == "absence" ? -$duree : $duree)
                        ->setPersonnel($personelRep->find($absenceData['personnel']))
                        ->setCommentaire($absenceData['commentaire'] ?? null)
                        ->setDateSaisie(new \DateTime())
                        ->setSaisiePar($this->getUser())
                        ->setSite($site);

                $entityManager->persist($absence);
            }

            $entityManager->flush();

            $this->addFlash('success', '✅ Absences et heures supplémentaires enregistrées avec succès !');
            return $this->redirectToRoute('app_logescom_rh_absence_personnel_index', [
                'site' => $site->getId(),
            ]);
        }

        // 🧭 Affichage du formulaire
        return $this->render('logescom/rh/absence_personnel/new.html.twig', [
            'site' => $site,
            'jour' => $jour,
            'personnels' => $personelRep->findPersonnelBySite($site),
        ]);
    }


    #[Route('/show/{id}/{site}', name: 'app_logescom_rh_absence_personnel_show', methods: ['GET'])]
    public function show(AbsencePersonnel $absencePersonnel, Site $site): Response
    {
        return $this->render('logescom/rh/absence_personnel/show.html.twig', [
            
            'site' => $site,
            'absence_personnel' => $absencePersonnel,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_logescom_absence_personnel_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, AbsencePersonnel $absencePersonnel, EntityManagerInterface $entityManager, Site $site): Response
    {
        $form = $this->createForm(AbsencePersonnelType::class, $absencePersonnel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_logescom_rh_absence_personnel_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('logescom/personel/absence_personnel/edit.html.twig', [
            'absence_personnel' => $absencePersonnel,
            'form' => $form,

        ]);
    }

    #[Route('/delete/{id}/{site}', name: 'app_logescom_rh_absence_personnel_delete', methods: ['POST', 'GET'])]
    public function delete(Request $request, AbsencePersonnel $absencePersonnel, EntityManagerInterface $entityManager, Site $site): Response
    {
        
        $entityManager->remove($absencePersonnel);
        $entityManager->flush();
        

        return $this->redirectToRoute('app_logescom_rh_absence_personnel_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
    }
}
