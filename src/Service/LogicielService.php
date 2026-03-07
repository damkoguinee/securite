<?php

namespace App\Service;

use App\Repository\ForfaitSmsRepository;
use App\Repository\SmsEnvoyesRepository;

class LogicielService
{
    private $forfaitSmsRep;
    private $smsRep;

    public function __construct(ForfaitSmsRepository $forfaitSmsRep, SmsEnvoyesRepository $smsRep)
    {
        $this->forfaitSmsRep = $forfaitSmsRep;
        $this->smsRep = $smsRep;
    }

   
    public function normaliserTelephone($telephone)
    {
        $telephone = str_replace(' ', '', $telephone);

        if (!preg_match('/^(00|\+)/', $telephone)) {
            $telephone = '+224' . $telephone;
        } else {
            $telephone = preg_replace('/^00/', '+', $telephone);
        }

        return $telephone;
    }

    /**
     * Vérifie si un forfait SMS est disponible parmi les forfaits actifs.
     *
     * @return mixed Le forfait disponible ou null si aucun forfait n'est disponible
     */
    public function verifierForfaitDisponible()
    {
        $forfaits = $this->forfaitSmsRep->findBy(['etat' => 'actif']);
        
        $forfaitDisponible = null;
        $dateActuelle = new \DateTime(); // Obtenir la date actuelle

        foreach ($forfaits as $forfait) {
            // Récupération des informations sur la validité
            $dateSouscription = $forfait->getDateSouscription();
            $validite = $forfait->getForfait()->getValidite();

            // Vérifier si le forfait a une validité illimitée
            if ($validite === null || $validite === 0) {
                // Forfait illimité, passer à la vérification des SMS restants
            } else {
                // Calcul de la date d'expiration pour les forfaits limités
                $dateExpiration = (clone $dateSouscription)->modify("+$validite days");

                // Vérifier si le forfait est expiré
                if ($dateExpiration < $dateActuelle) {
                    continue; // Passer au forfait suivant si expiré
                }
            }

            // Calculer le nombre de SMS restants
            $nb_sms_forfait = $forfait->getForfait()->getSms();
            $nb_sms_envoyes = count($forfait->getSmsEnvoyes());
            $nb_sms_restants = $nb_sms_forfait - $nb_sms_envoyes;

            // Vérifier si des SMS sont encore disponibles
            if ($nb_sms_restants > 0) {
                $forfaitDisponible = $forfait;
                break;
            }
        }

        return $forfaitDisponible;
    }



    /**
     * Vérifie si un forfait SMS est disponible parmi les forfaits actifs.
     *
     * @return mixed Le forfait disponible ou null si aucun forfait n'est disponible
     */
    public function verifierForfaitMultipleDisponible($smsPrev)
    {
        $forfaits = $this->forfaitSmsRep->findBy(['etat' => 'actif']);
        $totalSmsRestants = 0; // Variable pour cumuler les SMS restants
        $dateActuelle = new \DateTime(); // Date actuelle pour la vérification

        foreach ($forfaits as $forfait) {
            $validite = $forfait->getForfait()->getValidite();
            $dateSouscription = $forfait->getDateSouscription();

            // Vérifier la validité du forfait
            if ($validite === null || $validite === 0) {
                // Forfait illimité, continuer à vérifier les SMS restants
            } else {
                // Calcul de la date d'expiration pour les forfaits limités
                $dateExpiration = (clone $dateSouscription)->modify("+$validite days");

                // Vérifier si le forfait est expiré
                if ($dateExpiration < $dateActuelle) {
                    continue; // Passer au forfait suivant si expiré
                }
            }
            $nb_sms_forfait = $forfait->getForfait()->getSms();
            $nb_sms_envoyes = count($forfait->getSmsEnvoyes());

            $nb_sms_restants = $nb_sms_forfait - $nb_sms_envoyes;
            $totalSmsRestants += $nb_sms_restants; // Cumuler les SMS restants
        }

        // Comparer le total des SMS restants avec les SMS prévus
        if ($smsPrev <= $totalSmsRestants) {
            return true; // Il y a assez de SMS restants
        }

        return false; // Pas assez de SMS restants
    }


    /**
     * Vérifie si le collaborateur a déjà été notifié en fonction de la fréquence.
     *
     * @param Collaborateur $collaborateur Le collaborateur à vérifier
     * @param string $frequence La fréquence de notification (chaque_jour, chaque_semaine, etc.)
     * @return bool True si déjà notifié, false sinon
     */
    public function estDejaNotifie($collaborateur, $frequence)
    {
        // Récupérer le dernier message envoyé au collaborateur
        $dernierMessage = $this->smsRep->findOneBy(
            ['destinataire' => $collaborateur],
            ['dateEnvoie' => 'DESC'] // Trier par date d'envoi (du plus récent au plus ancien)
        );

        // Vérifier si aucun message n'a été trouvé
        if (!$dernierMessage) {
            return false; // Aucun message envoyé, donc pas encore notifié
        }

        $dateDernierMessage = $dernierMessage->getDateEnvoie();
        $dateActuelle = new \DateTime(); // Date actuelle

        // Comparer en fonction de la fréquence
        switch ($frequence) {
            case 'chaque_jour':
                // Si la dernière notification est plus ancienne qu'un jour, renvoyer false
                $interval = $dateDernierMessage->diff($dateActuelle);
                return $interval->days < 1; // Moins de 1 jour, donc notifié
            case 'chaque_semaine':
                // Si la dernière notification est plus ancienne qu'une semaine, renvoyer false
                $interval = $dateDernierMessage->diff($dateActuelle);
                return $interval->days < 7; // Moins de 7 jours, donc notifié
            case 'chaque_2_semaines':
                // Si la dernière notification est plus ancienne que 2 semaines, renvoyer false
                $interval = $dateDernierMessage->diff($dateActuelle);
                return $interval->days < 14; // Moins de 14 jours, donc notifié
            case 'chaque_3_semaines':
                // Si la dernière notification est plus ancienne que 3 semaines, renvoyer false
                $interval = $dateDernierMessage->diff($dateActuelle);
                return $interval->days < 21; // Moins de 21 jours, donc notifié
            case 'chaque_4_semaines':
                // Si la dernière notification est plus ancienne que 4 semaines, renvoyer false
                $interval = $dateDernierMessage->diff($dateActuelle);
                return $interval->days < 28; // Moins de 28 jours, donc notifié
            default:
                return false; // Si la fréquence est inconnue, retourner false
        }
    }



    /**
     * Vérifie si l'ordinateur est connecté à Internet.
     *
     * @return bool Retourne true si une connexion Internet est disponible, sinon false.
     */
    public function estConnecteInternet(): bool
    {
        $connected = @fsockopen("www.google.com", 80); // Essaye de se connecter à Google
        if ($connected) {
            fclose($connected);
            return true; // Internet est disponible
        }
        return false; // Pas de connexion Internet
    }

}
