<?php

namespace App\Controller\Logescom\Operation;

use App\Entity\site;
use App\Entity\Presence;
use App\Service\SmsService;
use App\Service\EmailService;
use App\Entity\ControlPersonnel;
use App\Repository\UserRepository;
use App\Repository\PresenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\InscriptionRepository;
use App\Repository\ControlAccesRepository;
use App\Repository\ControlEleveRepository;
use App\Repository\PersonnelActifRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\AffectationAgentRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\FonctionnementScolaireRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/logescom/operation/control/acces')]
final class ControlAccesController extends AbstractController
{
    

    #[Route('/new/{site}', name: 'app_logescom_operation_control_acces_new', methods: ['GET', 'POST'])]
    public function new(
        Site $site,
        Request $request,
        UserRepository $userRep,
        SessionInterface $session,
        PresenceRepository $presenceRep,
        AffectationAgentRepository $affectationRep,
        EntityManagerInterface $entityManager
    ): Response {
        $now = new \DateTime();

        /**
         * 🔹 1️⃣ — Cas AJAX (scan du QR code)
         */
        if ($request->isXmlHttpRequest() && $request->get('identifiant')) {
            $identifiant = $request->get('identifiant');
            $user = $userRep->findOneBy(['reference' => $identifiant]);

            if ($user) {
                return $this->json([
                    'reference' => $user->getReference(),
                    'prenom' => $user->getPrenom(),
                    'nom' => $user->getNom(),
                    'photo' => $user->getPhoto(),
                ]);
            }

            return new Response("error", 400);
        }

        /**
         * 🔹 2️⃣ — Cas POST normal (saisie ou scan soumis avec coordonnées)
         */
        $identifiant = $request->get('identifiant');
        if ($identifiant) {
            $user = $userRep->findOneBy(['reference' => $identifiant]);

            if (!$user) {
                $this->addFlash("warning", "Code inconnu 😕");
                return $this->redirectToRoute('app_logescom_operation_control_acces_new', [
                    'site' => $site->getId(),
                ]);
            }

            // 🧾 Vérifier une affectation en cours
            $aff = $affectationRep->findAffectationNow(personnel: $user, date: $now);
            if (!$aff) {
                $this->addFlash('warning', "Aucune affectation active trouvée pour cet agent à cette date ou heure.");
                return $this->redirectToRoute('app_logescom_operation_control_acces_new', [
                    'site' => $site->getId(),
                ]);
            }

            // 📍 Coordonnées GPS
            $latitude = $request->get('latitude');
            $longitude = $request->get('longitude');

            if (!$latitude || !$longitude) {
                $this->addFlash('danger', 'Localisation GPS requise pour valider la présence.');
                return $this->redirectToRoute('app_logescom_operation_control_acces_new', [
                    'site' => $site->getId(),
                ]);
            }

            // 🧭 Vérification distance agent ↔ site
            $siteLatitude = $aff->getContrat()?->getBien()?->getLatitude();
            $siteLongitude = $aff->getContrat()?->getBien()?->getLongitude();

            $isNearSite = false;
            $distanceMetres = null;

            if ($siteLatitude && $siteLongitude) {
                $distance = static function ($lat1, $lon1, $lat2, $lon2): float {
                    $earthRadius = 6371000; // m
                    $lat1Rad = deg2rad($lat1);
                    $lat2Rad = deg2rad($lat2);
                    $deltaLat = deg2rad($lat2 - $lat1);
                    $deltaLon = deg2rad($lon2 - $lon1);
                    $a = sin($deltaLat / 2) ** 2 +
                        cos($lat1Rad) * cos($lat2Rad) * sin($deltaLon / 2) ** 2;
                    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
                    return $earthRadius * $c;
                };

                $distanceMetres = $distance($latitude, $longitude, $siteLatitude, $siteLongitude);
                $rayonTolere = 75; // ✅ plus strict
                $isNearSite = $distanceMetres <= $rayonTolere;
            }

            if (!$isNearSite) {
                $this->addFlash('danger', sprintf(
                    "Vous êtes trop éloigné du site (%.0f m) — pointage refusé.",
                    $distanceMetres ?? 0
                ));
                return $this->redirectToRoute('app_logescom_operation_control_acces_new', [
                    'site' => $site->getId(),
                ]);
            }

            // ✅ Récupérer le dernier pointage de l’affectation
            $lastPresence = $presenceRep->findOneBy(
                ['affectationAgent' => $aff],
                ['datePointage' => 'DESC']
            );

            $action = 'entree'; // valeur par défaut
            $now = new \DateTime();

            if ($lastPresence) {
                $dernierType = $lastPresence->getTypePointage();
                $dernierDate = $lastPresence->getDatePointage();

                // ⏱️ Si le dernier pointage date de moins de 5 minutes → refus double scan
                $diffSeconds = $now->getTimestamp() - $dernierDate->getTimestamp();
                if ($diffSeconds < 300) { // 300 sec = 5 min
                    $this->addFlash('warning', "Pointage déjà effectué il y a moins de 5 minutes.");
                    return $this->redirectToRoute('app_logescom_operation_control_acces_new', [
                        'site' => $site->getId(),
                    ]);
                }

                // 🧭 Si dernière action = entrée
                if ($dernierType === 'entree') {
                    // Si l’entrée date de plus de 6h → nouvelle entrée (nouvelle journée ou service)
                    if ($diffSeconds > 6 * 3600) {
                        $action = 'entree';
                    } else {
                        $action = 'sortie';
                    }
                } else {
                    // Si dernière action = sortie → prochaine = entrée
                    $action = 'entree';
                }
            }


            // ✅ Création du pointage
            $presence = new Presence();
            $presence->setAffectationAgent($aff)
                ->setDatePointage($now)
                ->setTypePointage($action) // ⚙️ tu peux ajuster ici selon contexte
                ->setMode('auto')
                ->setSaisiePar($user)
                ->setDateSaisie(new \DateTime())
                ->setLatitude($latitude)
                ->setLongitude($longitude)
                ->setCommentaire(sprintf(
                    'Pointage automatique (distance %.0f m du site).',
                    $distanceMetres
                ));

            // ⚙️ Calcul du retard ou présence à l’heure
            if ($aff->getHeureDebut()) {
                $datePrevue = (clone $aff->getDateOperation())->setTime(
                    (int) $aff->getHeureDebut()->format('H'),
                    (int) $aff->getHeureDebut()->format('i')
                );

                $diff = $now->getTimestamp() - $datePrevue->getTimestamp();
                $hoursDiff = round($diff / 3600, 2);
                $presence->setEcart($hoursDiff)
                    ->setStatut($hoursDiff <= 0 ? "present" : "retard");
            } else {
                $presence->setStatut("present");
            }

            // 🔄 Sauvegarde
            $entityManager->persist($presence);
            $entityManager->flush();

            $this->addFlash("success", sprintf(
                "✅ Présence enregistrée à %.1f m du site (%s).",
                $distanceMetres,
                $presence->getStatut()
            ));

            return $this->redirectToRoute('app_logescom_operation_control_acces_new', [
                'site' => $site->getId(),
            ]);
        }

        /**
         * 🔹 3️⃣ — Cas GET normal : affichage de la page
         */
        $page = $request->query->getInt('page', 1);
        $limit = 100;

        $acces = $presenceRep->acces(
            startDate: $now,
            personnel: $this->getUser(),
            page: $page,
            limit: $limit
        );

        // 🔹 Groupement par type d’utilisateur
        $accesesGrouped = ['agent' => []];
        foreach ($acces['data'] as $item) {
            $type = $item->getAffectationAgent()->getPersonnel()->getFonction();
            $accesesGrouped[$type][] = $item;
        }
        return $this->render('logescom/operation/control_acces/new.html.twig', [
            'site' => $site,
            'acceses' => $accesesGrouped,
            'page' => $acces['pageEncours'],
            'limit' => $acces['limit'],
            'nbrePages' => $acces['nbrePages'],
            'startDate' => $now,
        ]);
    }



    
}
