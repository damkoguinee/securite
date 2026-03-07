<?php

namespace App\Command;

use App\Entity\SmsEnvoyes;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Repository\ConfigurationSmsRepository;
use App\Repository\EntrepriseRepository;
use App\Service\LogicielService;
use App\Service\OrangeSmsService;


#[AsCommand(
    name: 'app:send:sms-reservation-reminders',
    description: 'Envoie des notifications.',
)]
class AppSendClientsSmsCommand extends Command
{
    private EntityManagerInterface $em;
    private LogicielService $logicielService;
    private OrangeSmsService $smsService;
    private ConfigurationSmsRepository $configSmsRep;
    private EntrepriseRepository $entrepriseRep;
    // private ReservationRepository $reservationRep;

    public function __construct(
        EntityManagerInterface $em,
        LogicielService $logicielService, 
        OrangeSmsService $smsService,
        ConfigurationSmsRepository $configSmsRep, 
        EntrepriseRepository $entrepriseRep,
        // ReservationRepository $reservationRep,
    ) {
        parent::__construct();
        $this->em = $em;
        $this->logicielService = $logicielService;
        $this->smsService = $smsService;
        $this->configSmsRep = $configSmsRep;
        $this->entrepriseRep = $entrepriseRep;
        // $this->reservationRep = $reservationRep;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Démarrage de l\'envoi des SMS aux clients...');

        if (!$this->logicielService->estConnecteInternet()) {
            $output->writeln('<error>Pas de connexion Internet. Arrêt du processus.</error>');
            return Command::FAILURE;
        }

        $config = $this->configSmsRep->findOneBy(['etat' => 'actif']);
        if (!$config) {
            $output->writeln('<error>Configuration SMS.</error>');
            return Command::FAILURE;
        }

        if (!$this->logicielService->verifierForfaitMultipleDisponible(1)) {
            $output->writeln('<error>Forfait SMS épuisé.</error>');
            return Command::FAILURE;
        }

        $etat_notification = $this->configSmsRep->findOneBy(['nom' => 'notification', 'etat' => 'actif']);
        if (!$etat_notification || $etat_notification->getFrequence() === null) {
            $output->writeln('<comment>Fréquence de notification non définie.</comment>');
            return Command::SUCCESS;
        }
        $frequence = $etat_notification ? $etat_notification->getFrequence() : '';
        $entreprise = $this->entrepriseRep->findOneBy([]);

        if (!$etat_notification) {
            $output->writeln('<comment>Notifications désactivées.</comment>');
            return Command::SUCCESS;
        }

      
        $reservationsAvenir = $this->reservationRep->getReservationsAVenirParJour($frequence);
        $reservations = [];

        foreach ($reservationsAvenir as $reservation) {
            $telephone = $reservation->getClient()->getTelephone();
            $telephone = $this->logicielService->normaliserTelephone($telephone);
            $reservations[] = [
                'collaborateur' => $reservation->getClient(),
                'reservation' => $reservation,
                'telephone' => $telephone
            ];
                
            
        }
        $smsPrev = count($reservations);

        if (!$this->logicielService->verifierForfaitMultipleDisponible($smsPrev)) {
            $output->writeln('<error>Forfait SMS insuffisant pour relancer tous les clients.</error>');
            return Command::FAILURE;
        }

        $batchSize = 1500;
        $delay = 2;
        $comptesChunks = array_chunk($reservations, $batchSize);

        foreach ($comptesChunks as $chunk) {
            foreach ($chunk as $compte) {
                $recipientPhoneNumber = $compte['telephone'];
                if ($recipientPhoneNumber && strlen($recipientPhoneNumber) >= 9 && !$this->logicielService->estDejaNotifie($recipientPhoneNumber, $frequence)) {                        
                    $message = "Cher " . ucwords($compte['collaborateur']->getPrenom()) . ",\n";
                    $message .= "Rappel : vous avez une réservation prévue le " . $compte['reservation']->getDateDebut()->format('d/m/Y') . " à " . $compte['reservation']->getDateDebut()->format('H:i') . ".\n";
                    $message .= "Adresse : " . ucfirst(strtolower($compte['reservation']->getAireJeu()->getSite()->getAdresse()->getNom()." / ".$compte['reservation']->getAireJeu()->getSite()->getComplementAdresse())) . ".\n";
                    $message .= "Merci de vous présenter à l'heure.\n";
                    $message .= "Cordialement,\n" . ucwords(strtolower($entreprise->getNom())) . ",\n";

                    $response = $this->smsService->sendSms(
                        $recipientPhoneNumber,
                        'tel:+2240000',
                        $message,
                        $entreprise->getNom()
                    );
                    if (isset($response['outboundSMSMessageRequest']['resourceURL'])) {
                        $sms = new SmsEnvoyes();
                        $sms->setDestinataire($recipientPhoneNumber)
                            ->setMessage($message)
                            ->setCommentaire('notification reservation à venir')
                            ->setDateEnvoie(new \DateTime())
                            ->setForfait($this->logicielService->verifierForfaitDisponible());
                        $this->em->persist($sms);
                    }
                }
            }
            $this->em->flush(); // Assure-toi que les données sont site enregistrées
            sleep($delay);
        }

        $output->writeln('<info>Envoi des SMS terminé.</info>');
        return Command::SUCCESS;
    }
}
