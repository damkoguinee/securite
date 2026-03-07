<?php

namespace App\Controller\Paiement;

use Stripe\Stripe;
use App\Entity\ForfaitSms;
use Doctrine\ORM\EntityManager;
use App\Service\LogicielService;
use App\Service\OrangeSmsService;
use App\Repository\ForfaitSmsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session as StripeSession;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\ConfigForfaitSmsRepository;
use App\Repository\LicenceRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class StripeController extends AbstractController
{
    #[Route('/paiement/stripe', name: 'app_paiement_stripe')]
    public function paiementStripe(
        ConfigForfaitSmsRepository $forfaitSmsRep,
        Request $request,
        LicenceRepository $licenceRep,

    ): Response {
        $origine = $request->get('origine');
        $plan = $request->get('plan');
        if ($request->get('origine') === 'sms') {
            $forfaitSms = $forfaitSmsRep->find($request->get('plan'));

            if (!$forfaitSms) {
                throw $this->createNotFoundException("Forfait non trouvé.");
            }

            $prixClient = ($forfaitSms->getPrixFournisseur() * (1 + $forfaitSms->getMarge() / 100)) ; 

            

            $stripe_secret_key = $_ENV['STRIPE_SECRET_KEY'];
            
            Stripe::setApiKey($stripe_secret_key);

            // Création de la session Stripe
            $session = StripeSession::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'gnf',
                        'product_data' => [
                            'name' => $forfaitSms->getNom(),
                            'description' => $forfaitSms->getRemarque(),
                        ],
                        'unit_amount' => $prixClient,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $this->generateUrl('app_paiement_success', ['origine' => $origine, 'plan' => $plan ], UrlGeneratorInterface::ABSOLUTE_URL),
                'cancel_url' => $this->generateUrl('app_paiement_cancel', ['origine' => $origine, 'plan' => $plan], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);

            return $this->redirect($session->url, 303);
        }elseif ($request->get('origine') === 'licence') {
            $licence = $licenceRep->findOneBy([]);

            if (!$licence) {
                throw $this->createNotFoundException("Forfait non trouvé.");
            }

            $prixClient = ($licence->getTarif()) ; 
            $prixClient = (int)$prixClient;
            $stripe_secret_key = $_ENV['STRIPE_SECRET_KEY'];
            
            Stripe::setApiKey($stripe_secret_key);

            // Création de la session Stripe
            $session = StripeSession::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'gnf',
                        'product_data' => [
                            'name' => $licence->getNumeroLicence(),
                            'description' => $licence->getEntreprise()->getNomEntreprise(),
                        ],
                        'unit_amount' => $prixClient,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $this->generateUrl('app_paiement_success', ['origine' => $origine, 'plan' => $plan ], UrlGeneratorInterface::ABSOLUTE_URL),
                'cancel_url' => $this->generateUrl('app_paiement_cancel', ['origine' => $origine, 'plan' => $plan], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);

            return $this->redirect($session->url, 303);
        }

        throw $this->createNotFoundException("Origine inconnue.");
    }

    #[Route('/paiement/success', name: 'app_paiement_success')]
    public function paiementSuccess(Request $request, ConfigForfaitSmsRepository $configForfaitRep, ForfaitSmsRepository $forfaitSmsRep, LicenceRepository $licenceRep, OrangeSmsService $orangeSmsService, LogicielService $service, EntityManagerInterface $em): Response
    {
        $origine = $request->get('origine');
        $plan = $request->get('plan');
        $licence = $licenceRep->findOneBy([]);            

        if ($origine == 'sms') {
            $plan = $configForfaitRep->find($request->get('plan'));
            $prixClient = ($plan->getPrixFournisseur() * (1 + $plan->getMarge() / 100)) ;

            $forfait = new ForfaitSms();
            $forfait->setForfait($plan)
                ->setPrix($prixClient)
                ->setEtat('actif')
                ->setDateSouscription(new \DateTime());

            $em->persist($forfait);
            $em->flush(); 
            
            # notification sms de l'achat

            $telephones = ['628196628', '622112308', '627222161'];
            foreach ($telephones as $value) {
                $telephone = $service->normaliserTelephone($value);
                $recipientPhoneNumber = $telephone;
                $countrySenderNumber = 'tel:+2240000';  // Numéro de l'expéditeur pour la Guinée

                $message = "Bonjour DAMKO ,\n";
                $message .= "la société " . $licence->getEntreprise()->getNomEntreprise() . " vient d'achèter le forfait suivant:\n";
                $message .= "-forfait ".$plan->getNom(). ",\n"; 
                $message .= "-Nbre sms: ".$plan->getSms(). ",\n"; 
                $message .= "-Montant achat: ".$plan->getPrixFournisseur(). ",\n"; 
                $message .= "-Marge: ".$plan->getMarge(). ",\n"; 
                $message .= "Date d'achat le " . date('d/m/Y à H:i') . " :\n";
                $message .= "Cordialement,\n";
                
                $senderName = 'DAMKO'; // Nom de l'expéditeur

                // Appel au service pour envoyer le SMS
            
                $response = $orangeSmsService->sendSms(
                    $recipientPhoneNumber,
                    $countrySenderNumber,
                    $message,
                    $senderName
                );
            }

            $telephone = $service->normaliserTelephone($licence->getEntreprise()->getTelephone());
            $recipientPhoneNumber = $telephone;
            $countrySenderNumber = 'tel:+2240000';  // Numéro de l'expéditeur pour la Guinée

            $message = "Cher Client,\n";
            $message .= "Votre achat de forfait sms a été éffectuée avec succès !\n";
            $message .= "Détails de l'opération :\n";
            $message .= "-forfait ".$plan->getNom(). ",\n"; 
            $message .= "-Nbre sms: ".$plan->getSms(). ",\n"; 
            $message .= "-Montant achat: ".($plan->getPrixFournisseur() * (1 + $plan->getMarge() / 100)). ",\n"; 
            $message .= "- Date d'achat : " . date('d/m/Y à H:i') . "\n";
            $message .= "\nMerci pour votre confiance.\n";
            $message .= "Cordialement,\n";
            $message .= "L'équipe de DAMKO";

            $senderName = 'DAMKO'; // Nom de l'expéditeur

            // Appel au service pour envoyer le SMS
        
            $response = $orangeSmsService->sendSms(
                $recipientPhoneNumber,
                $countrySenderNumber,
                $message,
                $senderName
            );

            $telephone = $service->normaliserTelephone(628196628);
            $recipientPhoneNumber = $telephone;
            $countrySenderNumber = 'tel:+2240000';  // Numéro de l'expéditeur pour la Guinée

            $message = "Achat de sms\n";
            $message .= "Entreprise : " . $licence->getEntreprise()->getNomEntreprise() . "\n";
            $message .= "Détails de l'opération :\n";
            $message .= "-forfait ".$plan->getNom(). ",\n"; 
            $message .= "-Nbre sms: ".$plan->getSms(). ",\n"; 
            $message .= "-Montant achat: ".($plan->getPrixFournisseur() * (1 + $plan->getMarge() / 100)). ",\n"; 
            $message .= "- Date d'achat : " . date('d/m/Y à H:i') . "\n";
            
            $senderName = 'DAMKO'; // Nom de l'expéditeur

            // Appel au service pour envoyer le SMS
        
            $response = $orangeSmsService->sendSms(
                $recipientPhoneNumber,
                $countrySenderNumber,
                $message,
                $senderName
            );
        
            $this->addFlash("success", "forfait sms souscrit avec succès :)");
            return $this->redirectToRoute('app_contact_configuration_sms_index', [], Response::HTTP_SEE_OTHER);

        }elseif ($origine == 'licence') {

            $licence = $licenceRep->findOneBy([]);
            $dateFin = $licence->getDatefin();
            if ($dateFin) {
                $nouvelleDateFin = (clone $dateFin)->modify('+1 year');                    
                $licence->setDatefin($nouvelleDateFin);
                $em->persist($licence);
                $em->flush();
                
                $this->addFlash('success', 'La date de fin de la licence a été mise à jour.');
            } else {
                $this->addFlash('error', 'La date de fin est introuvable.');
            }
            
            # notification sms de l'achat

            $telephones = ['628196628', '622112308', '627222161'];
            foreach ($telephones as $value) {
                $telephone = $service->normaliserTelephone($value);
                $recipientPhoneNumber = $telephone;
                $countrySenderNumber = 'tel:+2240000';  // Numéro de l'expéditeur pour la Guinée

                $message = "Bonjour DAMKO ,\n";
                $message .= "la société " . $licence->getEntreprise()->getNomEntreprise() . " vient de renouveler sa licence:\n";
                $message .= "-Entreprise ".$licence->getEntreprise()->getNomEntreprise().' Licence : '.$licence->getNumeroLicence(). ",\n"; 
                $message .= "-Montant achat: ".$licence->getTarif(). ",\n"; 
                $message .= "Date d'achat le " . date('d/m/Y à H:i') . " :\n";
                $message .= "Cordialement,\n";
                
                $senderName = 'DAMKO'; // Nom de l'expéditeur

                // Appel au service pour envoyer le SMS
            
                $response = $orangeSmsService->sendSms(
                    $recipientPhoneNumber,
                    $countrySenderNumber,
                    $message,
                    $senderName
                );
            }


            $telephone = $service->normaliserTelephone($licence->getEntreprise()->getTelephone());
            $recipientPhoneNumber = $telephone;
            $countrySenderNumber = 'tel:+2240000';  // Numéro de l'expéditeur pour la Guinée

            $message = "Cher Client,\n";
            $message .= "Votre licence a été renouvelée avec succès !\n";
            $message .= "Détails de l'opération :\n";
            $message .= "- Date de fin : " . $licence->getDatefin()->format('d/m/Y') . "\n";
            $message .= "- Montant de l'achat : " . $licence->getTarif() . " GNF\n";
            $message .= "- Date d'achat : " . date('d/m/Y à H:i') . "\n";
            $message .= "\nMerci pour votre confiance.\n";
            $message .= "Cordialement,\n";
            $message .= "L'équipe de DAMKO";
            
            $senderName = 'DAMKO'; // Nom de l'expéditeur

            // Appel au service pour envoyer le SMS
        
            $response = $orangeSmsService->sendSms(
                $recipientPhoneNumber,
                $countrySenderNumber,
                $message,
                $senderName
            );

            $telephone = $service->normaliserTelephone(628196628);
            $recipientPhoneNumber = $telephone;
            $countrySenderNumber = 'tel:+2240000';  // Numéro de l'expéditeur pour la Guinée

            $message = "Renouvellement de licence\n";
            $message .= "Entreprise : " . $licence->getEntreprise()->getNomEntreprise() . "\n";
            $message .= "Détails de l'opération :\n";
            $message .= "- Date de fin : " . $licence->getDatefin()->format('d/m/Y') . "\n";
            $message .= "- Montant : " . $licence->getTarif() . " GNF\n";
            $message .= "- Date d'achat : " . date('d/m/Y à H:i') . "\n";
            
            $senderName = 'DAMKO'; // Nom de l'expéditeur

            // Appel au service pour envoyer le SMS
        
            $response = $orangeSmsService->sendSms(
                $recipientPhoneNumber,
                $countrySenderNumber,
                $message,
                $senderName
            );
            
            
            $this->addFlash("success", "Licence renouvélée avec succès :)");
            return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
        }else{




            $this->addFlash("success", "forfait sms souscrit avec succès :)");
            return $this->redirectToRoute('app_contact_configuration_sms_index', [], Response::HTTP_SEE_OTHER);
        }
        
    }

    #[Route('/paiement/cancel', name: 'app_paiement_cancel')]
    public function paiementCancel(OrangeSmsService $orangeSmsService, LogicielService $service, LicenceRepository $licenceRep, Request $request): Response
    {
        $this->addFlash("warning", "Echec de la transaction :)");
        if ($request->get('origine') === 'sms') {
            return $this->redirectToRoute('app_contact_configuration_sms_index', [], Response::HTTP_SEE_OTHER);
        }else{
            return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);

        }
    }
}
