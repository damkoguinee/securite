<?php

namespace App\Controller\Logescom\Admin;

use App\Repository\EntrepriseRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\ConfigDivisionLocale;
use App\Entity\ConfigQuartier;
use App\Form\ConfigDivisionLocaleType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Common\Collections\ArrayCollection;
use App\Repository\ConfigDivisionLocaleRepository;
use App\Repository\ConfigQuartierRepository;
use App\Repository\ConfigSousPrefectureRepository;
use App\Repository\ConfigZoneAdresseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/logescom/admin/config/division/locale')]
class ConfigDivisionLocaleController extends AbstractController
{
    #[Route('/', name: 'app_logescom_admin_config_division_locale_index', methods: ['GET'])]
    public function index(ConfigDivisionLocaleRepository $configDivisionLocaleRepository, ConfigSousPrefectureRepository $sousprefRep, EntrepriseRepository $entrepriseRep, EntityManagerInterface $em): Response
    {
    
        $divisions = $configDivisionLocaleRepository->findBy([], ['nom' => 'ASC']);
        // Initialisation du tableau pour regrouper les régions par région Administrative
        $divisionsGroupe = [];
        foreach ($divisions as $division) {
            $regionAdministrative = $division->getRegion();
            $nomRegionAdministrative = $regionAdministrative->getNom();
            // dd($divisionsGroupe[$nomRegionAdministrative]);
            if (!isset($divisionsGroupe[$nomRegionAdministrative])) {
                $divisionsGroupe[$nomRegionAdministrative] = new ArrayCollection();
            }

            $divisionsGroupe[$nomRegionAdministrative]->add($division);
        }
        ksort($divisionsGroupe);
        // Trier les divisions à l'intérieur de chaque région, d'abord par type, puis par nom
        foreach ($divisionsGroupe as $region => $divisions) {
            $divisionsGroupe[$region] = $divisions->toArray();  // Convertir en tableau pour trier
    
            // Trier d'abord par type (préfecture, sous préfecture, commune urbaine) puis par nom
            usort($divisionsGroupe[$region], function ($a, $b) {
                // Tri par type
                $typeComparison = strcmp($a->getType(), $b->getType());
                if ($typeComparison === 0) {
                    // Si les types sont identiques, trier par nom
                    return strcmp($a->getNom(), $b->getNom());
                }
                return $typeComparison;
            });
        }
        // dd($divisionsGroupe);
        // Rendu de la vue
        return $this->render('logescom/admin/config_division_locale/index.html.twig', [
            'divisionsGroupe' => $divisionsGroupe,
            'entreprise' => $entrepriseRep->findOneBy([])
        ]);
    }

    #[Route('/prefecture', name: 'app_logescom_admin_config_division_locale_prefecture', methods: ['GET'])]
    public function prefecture(ConfigDivisionLocaleRepository $configDivisionLocaleRepository, ConfigSousPrefectureRepository $sousprefRep, EntrepriseRepository $entrepriseRep, EntityManagerInterface $em): Response
    {
    
        $types = ['préfecture'];
        $divisions = $configDivisionLocaleRepository->listeDivisionParType($types);
        // Initialisation du tableau pour regrouper les régions par région Administrative
        $divisionsGroupe = [];
        foreach ($divisions as $division) {
            $regionAdministrative = $division->getRegion();
            $nomRegionAdministrative = $regionAdministrative->getNom();
            // dd($divisionsGroupe[$nomRegionAdministrative]);
            if (!isset($divisionsGroupe[$nomRegionAdministrative])) {
                $divisionsGroupe[$nomRegionAdministrative] = new ArrayCollection();
            }

            $divisionsGroupe[$nomRegionAdministrative]->add($division);
        }
        ksort($divisionsGroupe);
        // Trier les divisions à l'intérieur de chaque région, d'abord par type, puis par nom
        foreach ($divisionsGroupe as $region => $divisions) {
            $divisionsGroupe[$region] = $divisions->toArray();  // Convertir en tableau pour trier
    
            // Trier d'abord par type (préfecture, sous préfecture, commune urbaine) puis par nom
            usort($divisionsGroupe[$region], function ($a, $b) {
                // Tri par type
                $typeComparison = strcmp($a->getType(), $b->getType());
                if ($typeComparison === 0) {
                    // Si les types sont identiques, trier par nom
                    return strcmp($a->getNom(), $b->getNom());
                }
                return $typeComparison;
            });
        }
        // dd($divisionsGroupe);
        // Rendu de la vue
        return $this->render('logescom/admin/config_prefecture/index.html.twig', [
            'prefecturesGroupe' => $divisionsGroupe,
            'entreprise' => $entrepriseRep->findOneBy([])
        ]);
    }

    #[Route('/sous/prefecture', name: 'app_logescom_admin_config_division_locale_sous_prefecture', methods: ['GET'])]
    public function sousPrefecture(ConfigDivisionLocaleRepository $configDivisionLocaleRepository, ConfigSousPrefectureRepository $sousprefRep, EntrepriseRepository $entrepriseRep, EntityManagerInterface $em): Response
    {
    
        $types = ['sous préfecture', 'commune urbaine'];
        $divisions = $configDivisionLocaleRepository->listeDivisionParType($types);
        // Initialisation du tableau pour regrouper les régions par région Administrative
        $divisionsGroupe = [];
        foreach ($divisions as $division) {
            $regionAdministrative = $division->getRegion();
            $nomRegionAdministrative = $regionAdministrative->getNom();
            // dd($divisionsGroupe[$nomRegionAdministrative]);
            if (!isset($divisionsGroupe[$nomRegionAdministrative])) {
                $divisionsGroupe[$nomRegionAdministrative] = new ArrayCollection();
            }

            $divisionsGroupe[$nomRegionAdministrative]->add($division);
        }
        ksort($divisionsGroupe);
        // Trier les divisions à l'intérieur de chaque région, d'abord par type, puis par nom
        foreach ($divisionsGroupe as $region => $divisions) {
            $divisionsGroupe[$region] = $divisions->toArray();  // Convertir en tableau pour trier
    
            // Trier d'abord par type (préfecture, sous préfecture, commune urbaine) puis par nom
            usort($divisionsGroupe[$region], function ($a, $b) {
                // Tri par type
                $typeComparison = strcmp($a->getType(), $b->getType());
                if ($typeComparison === 0) {
                    // Si les types sont identiques, trier par nom
                    return strcmp($a->getNom(), $b->getNom());
                }
                return $typeComparison;
            });
        }
        // dd($divisionsGroupe);
        // Rendu de la vue
        return $this->render('logescom/admin/config_sous_prefecture/index.html.twig', [
            'prefecturesGroupe' => $divisionsGroupe,
            'entreprise' => $entrepriseRep->findOneBy([])
        ]);
    }

    #[Route('/quartier/new/{division}', name: 'app_logescom_admin_config_division_locale_quartier_new', methods: ['GET'])]
    public function quartier(ConfigDivisionLocale $division, ConfigDivisionLocaleRepository $configDivisionLocaleRepository, ConfigSousPrefectureRepository $sousprefRep, EntrepriseRepository $entrepriseRep, EntityManagerInterface $em): Response
    {
    
        $types = ['sous préfecture'];
        $divisions = $configDivisionLocaleRepository->listeDivisionParType($types);
        // Initialisation du tableau pour regrouper les régions par région Administrative
        $divisionsGroupe = [];
        foreach ($divisions as $division) {
            $regionAdministrative = $division->getRegion();
            $nomRegionAdministrative = $regionAdministrative->getNom();
            // dd($divisionsGroupe[$nomRegionAdministrative]);
            if (!isset($divisionsGroupe[$nomRegionAdministrative])) {
                $divisionsGroupe[$nomRegionAdministrative] = new ArrayCollection();
            }

            $divisionsGroupe[$nomRegionAdministrative]->add($division);
        }
        ksort($divisionsGroupe);
        // Trier les divisions à l'intérieur de chaque région, d'abord par type, puis par nom
        foreach ($divisionsGroupe as $region => $divisions) {
            $divisionsGroupe[$region] = $divisions->toArray();  // Convertir en tableau pour trier
    
            // Trier d'abord par type (préfecture, sous préfecture, commune urbaine) puis par nom
            usort($divisionsGroupe[$region], function ($a, $b) {
                // Tri par type
                $typeComparison = strcmp($a->getType(), $b->getType());
                if ($typeComparison === 0) {
                    // Si les types sont identiques, trier par nom
                    return strcmp($a->getNom(), $b->getNom());
                }
                return $typeComparison;
            });
        }
        // dd($divisionsGroupe);
        // Rendu de la vue
        return $this->render('logescom/admin/config_sous_prefecture/index.html.twig', [
            'prefecturesGroupe' => $divisionsGroupe,
            'entreprise' => $entrepriseRep->findOneBy([])
        ]);
    }

    #[Route('/new', name: 'app_logescom_admin_config_division_locale_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, EntrepriseRepository $entrepriseRep): Response
    {
        $configDivisionLocale = new ConfigDivisionLocale();

        $form = $this->createForm(ConfigDivisionLocaleType::class, $configDivisionLocale);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($configDivisionLocale);
            $entityManager->flush();

            return $this->redirectToRoute('app_logescom_admin_config_division_locale_index', [], Response::HTTP_SEE_OTHER);
        }
        return $this->render('logescom/admin/config_division_locale/new.html.twig', [
            'division_locale' => $configDivisionLocale,
            'form' => $form,
            'entreprise' => $entrepriseRep->findOneBy([])
        ]);
    }

    #[Route('/{id}', name: 'app_logescom_admin_config_division_locale_show', methods: ['GET'])]
    public function show(ConfigDivisionLocale $configDivisionLocale, ConfigDivisionLocaleRepository $configDivisionLocaleRepository, ConfigQuartierRepository $quartierRep, EntrepriseRepository $entrepriseRep, ConfigZoneAdresseRepository $zoneRep, Request $request, EntityManagerInterface $em): Response
    {
        if ($request->get('division') and $request->get('nom')) {
            $nom = $request->get('nom');
            $code = $request->get('code');
            $longitude = $request->get('longitude');
            $latitude = $request->get('latitude');
            $division = $request->get('division');
            $division = $configDivisionLocaleRepository->find($division);

            // Vérifier si un quartier/village avec le même nom existe déjà dans cette division
            $existingQuartier = $quartierRep->findOneBy([
                'nom' => $nom,
                'divisionLocale' => $division,
            ]);

            if ($existingQuartier) {
                $this->addFlash('warning', 'Un quartier ou village avec ce nom existe déjà dans cette division.');
                return $this->redirectToRoute('app_logescom_admin_config_division_locale_show', ['id' => $division->getId()]);
            }

            $quartier = new ConfigQuartier();
            $quartier->setCode($code)
                    ->setNom($nom)
                    ->setLongitude($longitude)
                    ->setLatitude($latitude)
                    ->setDivisionLocale($division);

            $em->persist($quartier);
            $em->flush();
            $this->addFlash('success', 'Le quartier/Village a été ajouté avec succès :)');
            return $this->redirectToRoute('app_logescom_admin_config_division_locale_show', ['id' => $configDivisionLocale->getId()], Response::HTTP_SEE_OTHER);
        }

        $quartiers = $quartierRep->findBy(['divisionLocale' => $configDivisionLocale], ['nom' => 'ASC']);
        return $this->render('logescom/admin/config_division_locale/show.html.twig', [
            'division_locale' => $configDivisionLocale,
            'entreprise' => $entrepriseRep->findOneBy([]),
            'quartiers' => $quartiers,
            'zones' => $zoneRep->findAll(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_logescom_admin_config_division_locale_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ConfigDivisionLocale $configDivisionLocale, EntityManagerInterface $entityManager, EntrepriseRepository $entrepriseRep): Response
    {
        $form = $this->createForm(ConfigDivisionLocaleType::class, $configDivisionLocale);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_logescom_admin_config_division_locale_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('logescom/admin/config_division_locale/edit.html.twig', [
            'division_locale' => $configDivisionLocale,
            'form' => $form,
            'entreprise' => $entrepriseRep->findOneBy([])
        ]);
    }

    #[Route('/{id}', name: 'app_logescom_admin_config_division_locale_delete', methods: ['POST'])]
    public function delete(Request $request, ConfigDivisionLocale $configDivisionLocale, EntityManagerInterface $entityManager, EntrepriseRepository $entrepriseRep): Response
    {
        if ($this->isCsrfTokenValid('delete'.$configDivisionLocale->getId(), $request->request->get('_token'))) {
            $entityManager->remove($configDivisionLocale);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_logescom_admin_config_division_locale_index', [], Response::HTTP_SEE_OTHER);
    }
}
