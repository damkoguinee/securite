<?php

namespace App\Controller\Logescom\Rh;

use App\Entity\Rh;
use App\Entity\Site;
use App\Entity\SmsEnvoyes;
use App\Entity\PrimePersonnel;
use App\Form\PrimePersonnelType;
use App\Service\LogicielService;
use App\Service\OrangeSmsService;
use App\Repository\UserRepository;
use App\Entity\HistoriqueChangement;
use App\Repository\PersonelRepository;
use App\Repository\EntrepriseRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\PrimePersonnelRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\ConfigurationSmsRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/logescom/rh/prime/personnel')]
class PrimePersonnelController extends AbstractController
{
    #[Route('/accueil/{site}', name: 'app_logescom_rh_prime_personnel_index', methods: ['GET'])]
    public function index(PrimePersonnelRepository $primePersonnelRepository , Site $site): Response
    {
        $primes = $primePersonnelRepository->findBy(['site' => $site], ['periode' => 'ASC']);
        // Regrouper par mois (format mm-aaaa)
        $primesParMois = [];
        foreach ($primes as $prime) {
            $periode = $prime->getPeriode();
            $mois = $periode->format('m-Y'); // ex: "08-2025"

            if (!isset($primesParMois[$mois])) {
                $primesParMois[$mois] = [];
            }
            $primesParMois[$mois][] = $prime;
        }


        return $this->render('logescom/rh/prime_personnel/index.html.twig', [
            'primesParMois' => $primesParMois,
            'site' => $site,
        ]);

        return $this->render('logescom/rh/prime_personnel/index.html.twig', [
            'prime_personnels' => $primePersonnelRepository->findBy(['site' => $site], ['id' => 'DESC']),
            
            'site' => $site,

        ]);
    }

    #[Route('/new/{site}', name: 'app_logescom_rh_prime_personnel_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        PersonelRepository $personelRep,
        EntityManagerInterface $entityManager,
        Site $site
    ): Response {
        $jour = $request->request->get("jour") ?? $request->query->get("jour") ?? date("Y-m-d");

        // 🔹 Récupération du tableau des primes (et non via $request->get())
        $primes = $request->request->all('primes');

        if ($request->isMethod('POST') && !empty($primes)) {
            $datePeriode = new \DateTime($jour);

            foreach ($primes as $primeData) {
                if (empty($primeData['montant'])) continue;

                $prime = new PrimePersonnel();
                $montant = floatval(preg_replace('/[^0-9,.]/', '', $primeData['montant']));

                $prime->setMontant($montant)
                    ->setPeriode($datePeriode)
                    ->setPersonnel($personelRep->find($primeData['personnel']))
                    ->setCommentaire($primeData['commentaire'] ?? null)
                    ->setDateSaisie(new \DateTime())
                    ->setSaisiePar($this->getUser())
                    ->setSite($site);

                $entityManager->persist($prime);
            }

            $entityManager->flush();

            $this->addFlash('success', '✅ Primes enregistrées avec succès.');
            return $this->redirectToRoute('app_logescom_rh_prime_personnel_index', [
                'site' => $site->getId(),
            ]);
        }

        return $this->render('logescom/rh/prime_personnel/new.html.twig', [
            'personnels' => $personelRep->findPersonnelBySite($site),
            'site' => $site,
        ]);
    }



    #[Route('/show/{id}/{site}', name: 'app_logescom_rh_prime_personnel_show', methods: ['GET'])]
    public function show(PrimePersonnel $primePersonnel , Site $site): Response
    {
        return $this->render('logescom/rh/prime_personnel/show.html.twig', [
            'prime_personnel' => $primePersonnel,
            
            'site' => $site,

        ]);
    }

    #[Route('/edit/{id}/{site}', name: 'app_logescom_rh_prime_personnel_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, PrimePersonnel $primePersonnel, EntityManagerInterface $entityManager , Site $site): Response
    {
        $form = $this->createForm(PrimePersonnelType::class, $primePersonnel, ['site' => $site]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $montant = floatval(preg_replace('/[^0-9,.]/', '', $primePersonnel->getMontant()));

            $primePersonnel->setSaisiePar($this->getUser())
                            ->setPeriode($primePersonnel->getPeriode())
                            ->setMontant($montant)
                            ->setDateSaisie(new \DateTime("now"));
            $entityManager->persist($primePersonnel);
            

            $entityManager->flush();

            return $this->redirectToRoute('app_logescom_rh_prime_personnel_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('logescom/rh/prime_personnel/edit.html.twig', [
            'prime_personnel' => $primePersonnel,
            'form' => $form,
            'site' => $site,

        ]);
    }

    #[Route('/confirm/delete/{id}', name: 'app_logescom_rh_prime_personnel_confirm_delete', methods: ['GET', 'POST'])]
    public function confirmDelete(PrimePersonnel $primePersonnel, Request $request): Response
    {
        $param = $request->request->get('param'); // Récupération du paramètre

        $route_suppression = $this->generateUrl('app_logescom_rh_prime_personnel_delete', [
            'id' => $primePersonnel->getId(),
            'site' => $primePersonnel->getSite()->getId(),
        ]);
        
        return $this->render('logescom/rh/prime_personnel/confirm_delete.html.twig', [
            'route_suppression' => $route_suppression,
            'param' => $param,
            'site' => $primePersonnel->getSite(),
            'entreprise' => $primePersonnel->getSite()->getEntreprise(),
            'operation' => $primePersonnel
        ]);
    }

    #[Route('/delete/{id}/{site}', name: 'app_logescom_rh_prime_personnel_delete', methods: ['POST'])]
    public function delete(Request $request, PrimePersonnel $primePersonnel, EntityManagerInterface $entityManager, Site $site, LogicielService $service, OrangeSmsService $orangeService, ConfigurationSmsRepository $configSmsRep): Response
    {
        if ($this->isCsrfTokenValid('delete'.$primePersonnel->getId(), $request->request->get('_token'))) {

            $entityManager->remove($primePersonnel);

            $deleteReason = $request->request->get('delete_reason');
            $montant = $primePersonnel->getMontant();

            $dateOperation = $primePersonnel->getPeriode() 
                ? $primePersonnel->getPeriode()->format('d/m/Y H:i') 
                : 'Date non définie';

            $information = "Montant : {$montant} | Date : {$dateOperation}";

            $personnel = $this->getUser();
            $historiqueSup = new HistoriqueChangement();
            $historiqueSup->setSaisiePar($personnel)
                    ->setDateSaisie(new \DateTime())
                    ->setMotif($deleteReason)
                    ->setInformation($information)
                    ->setType('prime personnel')
                    ->setSite($primePersonnel->getSite());
            $entityManager->persist($historiqueSup);

            $entityManager->flush();

            # gestion envoi sms alert
            if ($service->estConnecteInternet()) {// vérifie si il ya une connexion internet
                # on verifie si l'envoi de notification est actif pour le versement
                $etat_notification = $configSmsRep->findOneBy(['nom' => 'suppression_modification', 'etat' => 'actif']);

                if ($etat_notification) {                
                    if ($service->verifierForfaitDisponible()) {// verifie si il ya un forfait disponible
                        // Forfait disponible : envoyer le SMS
                        $telephone = $site->getEntreprise()->getTelephone();
                        $telephone = $service->normaliserTelephone($telephone);
                        if ($telephone and strlen($telephone) >= 9) {

                            $recipientPhoneNumber = $telephone;
                            $countrySenderNumber = 'tel:+2240000'; 

                            $message  = "⚠️ Alerte Suppression Prime Personnel ⚠️\n";
                            $message .= "La prime de " . $primePersonnel->getPersonnel()->getNomComplet() . " d'un montant de " . number_format($primePersonnel->getMontant(), 0, ',', ' ') . " a été supprimée.\n";
                            $message .= "Date de suppression : " . date('d/m/Y à H:i') . ".\n";
                            $message .= "Supprimé par : " . ucwords($this->getUser()->getPrenom()) . " " . strtoupper($this->getUser()->getNom()) . ".";
                            
                            $senderName = $site->getEntreprise()->getNom(); // Nom de l'expéditeur
                            // Appel au service pour envoyer le SMS
                        
                            $response = $orangeService->sendSms(
                                $recipientPhoneNumber,
                                $countrySenderNumber,
                                $message,
                                $senderName
                            );
                            // Vérification si le sms est site envoyé
                            if (isset($response['outboundSMSMessageRequest']['resourceURL'])) {

                                $sms = new SmsEnvoyes();
                                $sms->setDestinataire($telephone)
                                    ->setMessage($message)
                                    ->setCommentaire($primePersonnel->getCommentaire())
                                    ->setDateEnvoie(new \DateTime())
                                    ->setForfait($service->verifierForfaitDisponible());
                                $entityManager->persist($sms);
                                $entityManager->flush();

                            }
                            
                        }
                    }
                }
            }

            $this->addFlash("success", "Depense supprimé avec succès :)");
        }

        return $this->redirectToRoute('app_logescom_rh_prime_personnel_index', ['site' => $site->getId()], Response::HTTP_SEE_OTHER);
    }
}
