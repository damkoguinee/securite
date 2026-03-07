<?php

namespace App\Controller\Logescom\Contact;


use App\Entity\SmsEnvoyes;
use App\Repository\AchatFournisseurRepository;
use App\Repository\BonCommandeFournisseurRepository;
use App\Service\LogicielService;
use App\Service\OrangeSmsService;
use App\Repository\UserRepository;
use App\Repository\ClientRepository;
use App\Repository\EntrepriseRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\ConfigurationSmsRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\MouvementCollaborateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/contact')]
class SmsController extends AbstractController
{
    #[Route('/sms/orange/configuration', name: 'app_contact_sms_orange_configuration')]
    public function index(EntrepriseRepository $entrepriseRep): Response
    {
        return $this->render('contact/sms/orange_configuration.html.twig', [
            'entreprise' => $entrepriseRep->findOneBy([]),
        ]);
    }

    #[Route('/sms/orange/envoi', name: 'app_contact_sms_orange_envoi', methods: ['GET'])]
    public function sendSms(Request $request, EntrepriseRepository $entrepriseRep, ClientRepository $clientRep, UserRepository $userRep, MouvementCollaborateurRepository $mouvementColabRep, OrangeSmsService $orangeSmsService, LogicielService $service, AchatFournisseurRepository $achatFournisseurRep, BonCommandeFournisseurRepository $bonRep, ConfigurationSmsRepository $configSmsRep, EntityManagerInterface $em): Response
    {
        try {
            # gestion envoi sms alert
            if ($service->estConnecteInternet()) {// vérifie si il ya une connexion internet
                # on verifie si l'envoi de notification est actif pour le versement
                $etat_notification = $configSmsRep->findOneBy(['nom' => 'client inactif', 'etat' => 'actif']);

                if ($etat_notification) {                
                    if ($service->verifierForfaitDisponible()) {// verifie si il ya un forfait disponible
                        $entreprise = $entrepriseRep->findOneBy([]);
                        if ($request->get('origine') && $request->get('origine') == 'clientInactif') {
                            $limit = $request->get('limit');
                            $type1 = $request->get('type1') ? $request->get('type1') : 'client';
                            $type2 = $request->get('type2') ? $request->get('type2') : 'client-fournisseur';

                            if ($request->get('clientSimple')) {
                                $user = $userRep->find($request->get('clientSimple'));
                                $clients = $clientRep->findBy(['user' => $user]);
                            }else{

                                $clients = $clientRep->listeDesClientsGeneralParType($type1, $type2);
                            }

                            $dateLimite = new \DateTime();
                            $dateLimite->modify('-' . $limit . ' days');
                            $comptesInactifs = [];
                    
                            foreach ($clients as $client) {
                                $soldes = $mouvementColabRep->comptesInactif($client->getUser());
                                $telephone = $client->getUser()->getTelephone();

                                $telephone = $service->normaliserTelephone($telephone);

                    
                                if ($telephone and strlen($telephone) >= 9) {
                                    if ($soldes) {
                                        $derniereOperation = $mouvementColabRep->findOneBy(
                                            ['collaborateur' => $client->getUser()],
                                            ['dateOperation' => 'DESC']
                                        );
                    
                                        if ($derniereOperation && $derniereOperation->getDateOperation() <= $dateLimite) {
                                            $comptesInactifs[] = [
                                                'collaborateur' => $client->getUser(),
                                                'soldes' => $soldes,
                                                'derniereOp' => $derniereOperation,
                                                'telephone' => $telephone
                                            ];
                                        }
                                    }
                                }
                            }
                    
                            // Envoi des SMS pour les clients inactifs
                            foreach ($comptesInactifs as $compte) {
                                $recipientPhoneNumber = $compte['telephone'];
                                $countrySenderNumber = 'tel:+2240000';  // Numéro de l'expéditeur pour la Guinée

                                $soldes_collaborateur = $mouvementColabRep->findSoldeCollaborateur($compte['collaborateur']);
                                $soldeDetails = '';
                                if (!empty($soldes_collaborateur)) {
                                    foreach ($soldes_collaborateur as $solde) {
                                        $montant_solde = number_format(-$solde['montant'], 0, ',', ' ');
                                        $devise_solde = strtoupper($solde['devise']);
                                        $soldeDetails .= $montant_solde . ' ' . $devise_solde . "\n";
                                    }
                                } else {
                                    $soldeDetails = "Solde indisponible.\n";
                                }

                                // Récupération des informations sur le solde et la dernière opération
                                $solde = $compte['soldes'][0]['montant']; // Le montant du solde
                                $derniereOperation = new \DateTime($compte['soldes'][0]['derniereOperation']); // La date de la dernière opération
                                // Construction du message
                                $message = "Cher ".ucwords($compte['collaborateur']->getPrenom()).",\n";
                                $message .= "Sauf erreur de notre part, à la date du ".date('d/m/Y à H:i')." vous nous devez " . number_format(-$solde, 0, ',', ' ') . " GNF.\n";
                                $message .= "Votre dernière opération date du " . $derniereOperation->format('d/m/Y') . "\n"; 
                                $message .= "Voici le solde de vos comptes au " . date('d/m/Y à H:i') . " :\n";
                                $message .= $soldeDetails;
                                $message .= "Merci de régulariser votre situation.\n";
                                $message .= "Cordialement,\n";
                                $message .= $entreprise->getNomEntreprise().",\n";
                                
                                $senderName = $entreprise->getNomEntreprise(); // Nom de l'expéditeur
                    
                                // Appel au service pour envoyer le SMS
                            
                                $response = $orangeSmsService->sendSms(
                                    $recipientPhoneNumber,
                                    $countrySenderNumber,
                                    $message,
                                    $senderName
                                );

                                // Vérification si le sms est site envoyé
                                if (isset($response['outboundSMSMessageRequest']['resourceURL'])) {

                                    $sms = new SmsEnvoyes();
                                    $sms->setDestinataire($recipientPhoneNumber)
                                        ->setMessage($message)
                                        ->setCommentaire('notification compte inactif ')
                                        ->setDateEnvoie(new \DateTime())
                                        ->setForfait($service->verifierForfaitDisponible());
                                    $em->persist($sms);
                                    $em->flush();

                                }
                            }

                            if (sizeof($clients) < 2) {
                                $this->addFlash('success', 'Message envoyé avec succès :)');

                            }
                            $referer = $request->headers->get('referer');
                            // Si l'URL de référence existe, redirigez l'utilisateur vers cette URL
                            if ($referer) {
                                return $this->redirect($referer);
                            }
                    
                            return new JsonResponse(['success' => true, 'response' => $response]);
                        }
                    }
                }

                if ($service->verifierForfaitDisponible() and $request->get('origine') == 'achat_fournisseur') {// verifie si il ya un forfait disponible
                    $entreprise = $entrepriseRep->findOneBy([]);
                    if ($request->get('origine') && $request->get('origine') == 'achat_fournisseur') {
                        $id_achat = $request->get('id_achat');
                        $achat = $achatFournisseurRep->find($id_achat);
                        $telephone = $achat->getFournisseur()->getTelephone();
                        $telephone = $service->normaliserTelephone($telephone);

                        if ($telephone and strlen($telephone) >= 9) {

                            $recipientPhoneNumber = $telephone;
                            $countrySenderNumber = 'tel:+2240000'; 
                            
                            $soldes_collaborateur = $mouvementColabRep->findSoldeCollaborateur($achat->getFournisseur());
                            
                            $soldeDetails = '';
                            if (!empty($soldes_collaborateur)) {
                                foreach ($soldes_collaborateur as $solde) {
                                    $devise_solde = strtoupper($solde['devise']);
                                    if ($solde['montant'] > 0) {
                                        $montant_solde = number_format($solde['montant'], 0, ',', ' ');
                                        $soldeDetails .="Nous vous devons ". $montant_solde . ' ' . $devise_solde . "\n";
                                    }else{
                                        $montant_solde = number_format(-$solde['montant'], 0, ',', ' ');
                                        $soldeDetails .="Vous nous devez ". $montant_solde . ' ' . $devise_solde . "\n";
                                    }
                                }
                            } else {
                                $soldeDetails = "Solde indisponible.\n";
                            }
                            
                            // Récupérer le nom du fournisseur
                            $nomFournisseur = $achat->getFournisseur()->getClients()[0]->getSociete() 
                            ? $achat->getFournisseur()->getClients()[0]->getSociete() 
                            : $achat->getFournisseur()->getPrenom();

                            // Message principal
                            $message = "Cher " . ucwords($nomFournisseur) . ",\n";
                            $message .= "Réception fact. N°" . $achat->getNumeroFacture() . 
                                    " | Mnt: " . number_format($achat->getMontant(), 0, ',', ' ') . " " . $achat->getDevise()->getNomDevise() . ".\n";
                            $message .= "Produits livrés:\n";

                            // Récupérer les produits du bon de commande en vérifiant si le bon de commande existe
                            $bonCommande = $achat->getBonCommande();
                            $produitsBonCommande = $bonCommande ? $bonCommande->getListeProductBonFournisseurs() : [];

                            $produitsAffiches = 0;
                            $maxProduits = 3; // Limiter à 3 produits

                            foreach ($achat->getListeProductAchatFournisseurs() as $liste) {
                                if ($produitsAffiches >= $maxProduits) {
                                    $message .= "... et d'autres.\n"; 
                                    break;
                                }

                                // Trouver la quantité commandée
                                $quantiteBonCommande = 0;
                                
                                // Vérifier si la liste des produits du bon de commande est vide
                                if (!empty($produitsBonCommande)) {
                                    foreach ($produitsBonCommande as $produitBon) {
                                        if ($produitBon->getProduct()->getId() === $liste->getProduct()->getId()) {
                                            $quantiteBonCommande = $produitBon->getQuantite();
                                            break;
                                        }
                                    }
                                }

                                $message .= "- " . $liste->getProduct()->getDesignation() . " | Qté reçue: " . $liste->getQuantite() . " / Qté commandée: " . $quantiteBonCommande . "\n";
                                
                                $produitsAffiches++;
                            

                                // Ajouter les informations en version courte
                                $message .= "- " . $liste->getProduct()->getDesignation()
                                    . " | Cmd: " . $quantiteBonCommande
                                    . " | Livré: " . $liste->getQuantite()
                                    . " | Prix: " . number_format($liste->getPrixAchat(), 0, ',', ' ')
                                    . "\n";
                            }

                            // Ajouter le solde et les remerciements de manière concise
                            $message .= "A la date du " . date('d/m/Y à H:i') . " :\n";
                            $message .= $soldeDetails . "\n";
                            $message .= $this->getUser()->getLieuVente()->getEntreprise()->getNomEntreprise() . ".\n";

                            $senderName = $this->getUser()->getLieuVente()->getEntreprise()->getNomEntreprise();
                           
                            // Appel au service pour envoyer le SMS
                            
                            $response = $orangeSmsService->sendSms(
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
                                    ->setCommentaire('achat fournisseur '.$achat->getNumeroFacture())
                                    ->setDateEnvoie(new \DateTime())
                                    ->setForfait($service->verifierForfaitDisponible());
                                $em->persist($sms);
                                $em->flush();

                            }
                            
                        }

                        $referer = $request->headers->get('referer');
                        // Si l'URL de référence existe, redirigez l'utilisateur vers cette URL
                        if ($referer) {
                            return $this->redirect($referer);
                        }
                
                        return new JsonResponse(['success' => true, 'response' => $response]);
                    }
                }

                if ($service->verifierForfaitDisponible() and $request->get('origine') == 'bon_fournisseur') {// verifie si il ya un forfait disponible
                    $entreprise = $entrepriseRep->findOneBy([]);
                    if ($request->get('origine') && $request->get('origine') == 'bon_fournisseur') {
                        $id_bon = $request->get('id_bon');
                        $bon = $bonRep->find($id_bon);
                        $telephone = $bon->getFournisseur()->getTelephone();
                        $telephone = $service->normaliserTelephone($telephone);

                        if ($telephone and strlen($telephone) >= 9) {

                            $recipientPhoneNumber = $telephone;
                            $countrySenderNumber = 'tel:+2240000'; 
                            
                            $soldes_collaborateur = $mouvementColabRep->findSoldeCollaborateur($bon->getFournisseur());
                            
                            $soldeDetails = '';
                            if (!empty($soldes_collaborateur)) {
                                foreach ($soldes_collaborateur as $solde) {
                                    $devise_solde = strtoupper($solde['devise']);
                                    if ($solde['montant'] > 0) {
                                        $montant_solde = number_format($solde['montant'], 0, ',', ' ');
                                        $soldeDetails .="Nous vous devons ". $montant_solde . ' ' . $devise_solde . "\n";
                                    }else{
                                        $montant_solde = number_format(-$solde['montant'], 0, ',', ' ');
                                        $soldeDetails .="Vous nous devez ". $montant_solde . ' ' . $devise_solde . "\n";
                                    }
                                }
                            } else {
                                $soldeDetails = "Solde indisponible.\n";
                            }
                            
                            // Récupérer le nom du fournisseur
                            $nomFournisseur = $bon->getFournisseur()->getClients()[0]->getSociete() 
                            ? $bon->getFournisseur()->getClients()[0]->getSociete() 
                            : $bon->getFournisseur()->getPrenom();

                            // Message principal
                            $message = "Cher " . ucwords($nomFournisseur) . ",\n";
                            $message .= "Nouvelle Cmd N°" . $bon->getNumeroBon() . ".\n";
                            $message .= "Produits:\n";

                            // Récupérer les produits du bon de commande
                            $produitsBonCommande = $bon->getListeProductBonFournisseurs();
                            $produitsAffiches = 0;
                            $maxProduits = 3; // Limiter à 3 produits

                            foreach ($produitsBonCommande as $liste) {
                                if ($produitsAffiches >= $maxProduits) {
                                    $message .= "... et d'autres.\n"; 
                                    break;
                                }

                                // Ajouter les informations en version courte
                                $message .= "- " . $liste->getProduct()->getDesignation()
                                    . " | Qtité: " . $liste->getQuantite()
                                    . "\n";

                                $produitsAffiches++;
                            }

                            // Ajouter le solde et les remerciements
                            $message .= "A la date du " . date('d/m/Y à H:i') . " :\n";
                            $message .= $soldeDetails . "\n";
                            $message .= "Merci de votre collaboration. \n";
                            $message .= $this->getUser()->getLieuVente()->getEntreprise()->getNomEntreprise() . ".\n";
                            $senderName = $this->getUser()->getLieuVente()->getEntreprise()->getNomEntreprise();
                           
                            // Appel au service pour envoyer le SMS
                            
                            $response = $orangeSmsService->sendSms(
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
                                    ->setCommentaire('achat fournisseur '.$bon->getNumeroBon())
                                    ->setDateEnvoie(new \DateTime())
                                    ->setForfait($service->verifierForfaitDisponible());
                                $em->persist($sms);
                                $em->flush();

                            }
                            
                        }

                        $referer = $request->headers->get('referer');
                        // Si l'URL de référence existe, redirigez l'utilisateur vers cette URL
                        if ($referer) {
                            return $this->redirect($referer);
                        }
                
                        return new JsonResponse(['success' => true, 'response' => $response]);
                    }
                }
            }
        
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()]);
        }
        
    }
}

