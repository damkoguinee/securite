<?php

namespace App\Controller\Paiement;

use App\Entity\Entreprise;
use App\Entity\ForfaitSms;
use App\Service\LogicielService;
use App\Service\OrangeSmsService;
use App\Repository\LicenceRepository;
use App\Repository\EntrepriseRepository;
use App\Repository\ForfaitSmsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\ConfigForfaitSmsRepository;
use App\Repository\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class OrangeMoneyController extends AbstractController
{

    #[Route('/paiement/orange/money', name: 'app_paiement_orange_money')]
    public function paiementOrangeMoney(
        EntrepriseRepository $entrepriseRep,
        LicenceRepository $licenceRep,
        ConfigForfaitSmsRepository $forfaitSmsRep,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        $licence = $licenceRep->findOneBy([]); 
        $origine = $request->get('origine');
        $plan = $request->get('plan');
        // Récupérer les données sensibles à partir des variables d'environnement
        $msisdn = $_ENV['ORANGE_MONEY_MSISDN'];
        $agentCode = $_ENV['ORANGE_MONEY_AGENT_CODE'];
        $pin = $_ENV['ORANGE_MONEY_PIN'];
        $merchantKey = $_ENV['ORANGE_MONEY_MERCHANT_KEY'];
        // Obtenir le token d'accès
        $accessToken = $this->getAccessToken();
        // dd($accessToken);
        if ($accessToken) {
            if ($request->get('origine') === 'sms') {
                $forfaitSms = $forfaitSmsRep->find($request->get('plan'));
    
                if (!$forfaitSms) {
                    throw $this->createNotFoundException("Forfait non trouvé.");
                }
    
                $prixClient = ($forfaitSms->getPrixFournisseur() * (1 + $forfaitSms->getMarge() / 100)) ;
                $prixClient = (int)$prixClient; // Convertit en entier (supprime la partie décimale)
            }elseif ($request->get('origine') === 'licence') {
                $licence = $licenceRep->findOneBy([]);
                $prixClient = ($licence->getTarif()) ;
                $prixClient = (int)$prixClient; // Convertit en entier (supprime la partie décimale)
            }else{
                $prixClient = 0;
            }
            // Appel à l'API d'Orange Money
            $apiResponse = $this->callOrangeMoneyApi($msisdn, $agentCode, $pin, $accessToken, $merchantKey, $prixClient, $origine, $plan);
            // dd($apiResponse);
            if ($apiResponse['payment_url']) {
                return $this->redirect($apiResponse['payment_url']); // Remplacez par votre route de succès
            } else {
                $this->addFlash('error', $apiResponse['message']);
            }
        } else {
            $this->addFlash('error', 'Erreur lors de l\'obtention du token d\'accès.');
        }

        $referer = $request->headers->get('referer');
        return $this->redirect($referer);
    }
    private function getAccessToken(): ?string
    {
        $clientId = $_ENV['ORANGE_MONEY_CLIENT_ID'];
        $clientSecret = $_ENV['ORANGE_MONEY_CLIENT_SECRET'];
        $authorizationHeader = base64_encode("$clientId:$clientSecret");

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.orange.com/oauth/v3/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Basic $authorizationHeader",
            "Content-Type: application/x-www-form-urlencoded",
            "Accept: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        // curl_setopt($ch, CURLOPT_CAINFO, __DIR__ . '/../Certifications/cacert.pem');
        curl_setopt($ch, CURLOPT_CAINFO, realpath(__DIR__ . '/../../Certifications/cacert.pem'));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Débogage : afficher la réponse et le code HTTP
        // dump("Response from Token API: ", $response, "HTTP Code: ", $httpCode, "cURL Error: ", $curlError);

        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return $data['access_token'] ?? null;
        }

        return null;
    }

    private function callOrangeMoneyApi(string $msisdn, string $agentCode, string $pin, string $accessToken, string $merchantKey, $prixClient, $origine, $plan)
    {
        $url = 'https://api.orange.com/orange-money-webpay/gn/v1/webpayment';

        // Générer un order_id unique
        $orderId = uniqid('Order_');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $accessToken",
            "Content-Type: application/json",
            "Accept: application/json"
        ]);

        curl_setopt($ch, CURLOPT_CAINFO, realpath(__DIR__ . '/../../Certifications/cacert.pem'));

        // Préparer les données de la requête
        $data = [
            'msisdn' => $msisdn,
            'agent_code' => $agentCode,
            'pin' => $pin,
            'merchant_key' => $merchantKey,
            'currency' => 'GNF',
            'order_id' => $orderId,  // Utiliser l'order_id généré
            'amount' => $prixClient,

            'return_url' => $this->generateUrl('app_paiement_success_orange_money', ['origine' => $origine, 'plan' => $plan], UrlGeneratorInterface::ABSOLUTE_URL),

            'cancel_url' => $this->generateUrl('app_paiement_cancel_orange_money', ['origine' => $origine, 'plan' => $plan], UrlGeneratorInterface::ABSOLUTE_URL),

            'notif_url' => $this->generateUrl('app_paiement_notification_orange_money', ['origine' => $origine, 'plan' => $plan], UrlGeneratorInterface::ABSOLUTE_URL),

            // 'notif_url' => $this->generateUrl('app_paiement_notification_orange_money', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'lang' => 'fr',
            "reference" => "ref-xyz.456",
        ];

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Vérifier si la requête a réussi
        if ($response === false) {
            return [
                'success' => false,
                'message' => "Erreur lors de l'appel à l'API: $curlError",
            ];
        }

        // Décoder la réponse
        $responseData = json_decode($response, true);
        
        // Vérifier si la réponse indique un succès
        if ($httpCode === 201 && isset($responseData['pay_token'])) {
            // Vous pouvez maintenant utiliser le pay_token pour vérifier le statut plus tard
            return [
                'success' => true,
                'pay_token' => $responseData['pay_token'],
                'payment_url' => $responseData['payment_url'], // URL de paiement à envoyer à l'utilisateur
            ];
        }

        return [
            'success' => false,
            'message' => "Erreur lors de l'appel à l'API: $response",
        ];
    }
   

    #[Route('/paiement/notification/orange', name: 'app_paiement_notification_orange_money', methods: ['POST'])]
    public function handleNotification(
        Request $request, 
        ConfigForfaitSmsRepository $configForfaitRep, 
        EntityManagerInterface $em, 
        LoggerInterface $logger,
        LicenceRepository $licenceRep,
        OrangeSmsService $orangeSmsService,
        LogicielService $service,
    ): Response {
        // Récupérer le contenu brut de la requête
        $content = $request->getContent();
        $logger->info('Contenu brut de la requête reçu : ' . $content);

        // Décoder les données JSON
        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $logger->error("Erreur de décodage JSON : " . json_last_error_msg());
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier la présence des champs requis
        // if (!isset($data['status'], $data['notif_token'], $data['txnid'])) {
        //     $logger->error("Champs manquants dans la notification", $data);
        //     return $this->json(['error' => 'Missing required fields'], Response::HTTP_BAD_REQUEST);
        // }

        // Extraire les données nécessaires
        $status = $data['status'];
        $notifToken = $data['notif_token'];
        $transactionId = $data['txnid'];
        $plan = $request->get('plan');
        $origine = $request->get('origine');
        $licence = $licenceRep->findOneBy([]);


        // // Vérifier le token de notification
        // $expectedToken = 'votre_token_attendu'; // Remplacez par la valeur réelle
        // if ($notifToken !== $expectedToken) {
        //     $logger->error("Token de notification invalide : {$notifToken}");
        //     return $this->json(['error' => 'Invalid notif_token'], Response::HTTP_UNAUTHORIZED);
        // }

        // Traitement en fonction du statut
        if ($status === "SUCCESS") {
            $logger->info("Paiement réussi, transaction ID : {$transactionId}");
            if ($origine == 'sms') {
                // Récupérer un plan et calculer le prix client
                $plan = $configForfaitRep->find($plan);
                if (!$plan) {
                    $logger->error("Plan introuvable pour le traitement de la notification");
                    return $this->json(['error' => 'Plan not found'], Response::HTTP_NOT_FOUND);
                }
                $prixClient = ($plan->getPrixFournisseur() * (1 + $plan->getMarge() / 100));
                $forfait = new ForfaitSms();
                $forfait->setForfait($plan)
                    ->setPrix($prixClient)
                    ->setEtat('actif')
                    ->setDateSouscription(new \DateTime())
                    ->setIdentifiant($transactionId);
    
                $em->persist($forfait);
                $em->flush();

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
                $message .= "Entreprise : " . $licence->getEntreprise()->getNom() . "\n";
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
            }elseif ($origine == 'licence') {
                $dateFin = $licence->getDatefin();
                if ($dateFin) {
                    $nouvelleDateFin = (clone $dateFin)->modify('+1 year');                    
                    $licence->setDatefin($nouvelleDateFin);
                    $em->persist($licence);
                    $em->flush();

                    $licence = $licenceRep->findOneBy([]);            
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
                    $message .= "Entreprise : " . $licence->getEntreprise()->getNom() . "\n";
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
                    
                    $this->addFlash('success', 'La date de fin de la licence a été mise à jour.');
                } else {
                    $this->addFlash('error', 'La date de fin est introuvable.');
                }
            }else {
            }
            $logger->warning("Paiement échoué ou statut inconnu : {$status}");
        }

        return $this->json(['message' => 'Notification processed successfully'], Response::HTTP_OK);
    }



    


    #[Route('/paiement/success/orange', name: 'app_paiement_success_orange_money')]
    public function paiementSuccess(Request $request, ConfigForfaitSmsRepository $configForfaitRep, ForfaitSmsRepository $forfaitSmsRep, OrangeSmsService $orangeSmsService, LogicielService $service, EntityManagerInterface $em): Response
    {
        if ($request->get('origine') === 'sms') {
            return $this->redirectToRoute('app_contact_configuration_sms_index', [], Response::HTTP_SEE_OTHER);
        }else{
            return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);

        }
        
    }

    #[Route('/paiement/cancel/orange', name: 'app_paiement_cancel_orange_money')]
    public function paiementCancel(OrangeSmsService $orangeSmsService, LogicielService $service, Request $request): Response
    {
        // $telephones = ['628196628', '622112308', '627222161'];
        // foreach ($telephones as $value) {
        //     $telephone = $service->normaliserTelephone($value);
        //     $recipientPhoneNumber = $telephone;
        //     $countrySenderNumber = 'tel:+2240000';  // Numéro de l'expéditeur pour la Guinée

        //     $message = "Bonjour DAMKO ,\n";
        //     $message .= "la société " . $this->getUser()->getLieuVente()->getEntreprise()->getNom() . " rencontre des difficultés pour achèter des sms :\n";
        //     $message .= "Date " . date('d/m/Y à H:i') . " :\n";
        //     $message .= "Cordialement,\n";
            
        //     $senderName = $this->getUser()->getLieuVente()->getEntreprise()->getNom(); // Nom de l'expéditeur

        //     // Appel au service pour envoyer le SMS
        
        //     $response = $orangeSmsService->sendSms(
        //         $recipientPhoneNumber,
        //         $countrySenderNumber,
        //         $message,
        //         $senderName
        //     );
        // }
        $this->addFlash("warning", "Echec de la transaction :)");
        if ($request->get('origine') === 'sms') {
            return $this->redirectToRoute('app_contact_configuration_sms_index', [], Response::HTTP_SEE_OTHER);
        }else{
            return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);

        }
    }
}
