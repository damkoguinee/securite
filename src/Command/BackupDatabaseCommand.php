<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:backup-database',
    description: 'Sauvegarde la base de données dans un fichier SQL et l\'importe sur le serveur distant.',
)]
class BackupDatabaseCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setDescription('Sauvegarde la base de données dans un fichier SQL et l\'importe sur le serveur distant.')
            ->addArgument('output', InputArgument::OPTIONAL, 'Le chemin du fichier de sauvegarde', 'C:\Users\damad\Desktop\damko/restaurant_dump.sql')
            ->addOption('overwrite', null, InputOption::VALUE_NONE, 'Écraser le fichier de sauvegarde s\'il existe');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Récupérer le chemin de sauvegarde depuis l'argument
        $cheminDump = $input->getArgument('output');

        // Vérifier si le fichier existe déjà et si l'option overwrite est activée
        if (file_exists($cheminDump) && !$input->getOption('overwrite')) {
            $io->error("Le fichier de sauvegarde existe déjà. Utilisez l'option --overwrite pour le remplacer.");
            return Command::FAILURE;
        }

        // Format de la date pour le fichier de sauvegarde
        $date = date('Y-m-d_H-i-s');
        $cheminDump = str_replace('.sql', "_{$date}.sql", $cheminDump);

        // Chemin complet vers mysqldump
        $cheminMysqldump = 'C:/wamp64/bin/mysql/mysql5.7.26/bin/mysqldump.exe'; // Remplacez ce chemin par le chemin de votre installation de mysqldump

        // Récupérer les informations de connexion depuis le fichier .env
        $databaseUrl = $_ENV['DATABASE_URL'];
        $urlComponents = parse_url($databaseUrl);

        // Extraire les informations nécessaires
        $user = $urlComponents['user'];
        $password = $urlComponents['pass'] ?? ''; // Peut être vide
        $host = $urlComponents['host'];
        $port = $urlComponents['port'] ?? 3306; // Port par défaut si non spécifié
        $dbname = ltrim($urlComponents['path'], '/');

        // Construire la commande mysqldump avec mot de passe
        $commande = [
            $cheminMysqldump,
            '-u', $user,
            // '-p' . $password, // Inclure le mot de passe s'il est défini
            '-h', $host,
            '-P', $port,
            $dbname
        ];

        // Créer un objet Process pour exécuter la commande
        $process = new Process($commande);
        $process->setTimeout(3600); // Timeout d'une heure

        // Rediriger la sortie vers le fichier de dump
        $process->setTty(false);
        $process->run(function ($type, $buffer) use ($cheminDump) {
            if (Process::ERR === $type) {
                // Gérer les erreurs ici si nécessaire
                file_put_contents($cheminDump, $buffer, FILE_APPEND);
            } else {
                // Pour la redirection correcte, la sortie est écrite directement dans le fichier
                file_put_contents($cheminDump, $buffer, FILE_APPEND);
            }
        });

        if (!$process->isSuccessful()) {
            $io->error('Échec du dump de la base de données : ' . $process->getErrorOutput());
            return Command::FAILURE;
        }

        // 2. Transférer la sauvegarde sur le serveur distant
        $sshUser = 'u553146816'; // Remplacez par votre utilisateur SSH
        $sshHost = '213.130.146.135'; // Remplacez par votre hôte SSH
        $sshPort = '65002'; // Spécifiez le port SSH
        $remoteDumpPath = 'domains/damkocompany.com/public_html/codebar/var/tmp/restaurant_dump.sql'; // Remplacez par le chemin distant de votre fichier

        $scpCommand = [
            'scp',
            '-P', $sshPort,
            $cheminDump,
            $sshUser . '@' . $sshHost . ':' . $remoteDumpPath
        ];

        $scpProcess = new Process($scpCommand);
        $scpProcess->run();

        if (!$scpProcess->isSuccessful()) {
            $io->error('Échec du transfert du fichier de sauvegarde : ' . $scpProcess->getErrorOutput());
            return Command::FAILURE;
        }

        // 3. Importer la base de données sur le serveur distant
        $remoteDbUser = 'u553146816_sauvegardebdd'; // Remplacez par votre utilisateur de la base de données en ligne
        $remoteDbPassword = '040188Am@'; // Remplacez par votre mot de passe de la base de données en ligne
        $remoteDbHost = 'localhost'; // Remplacez par votre hôte de la base de données en ligne
        $remoteDbName = 'u553146816_sauvegardebdd'; // Remplacez par le nom de votre base de données en ligne

        $sshCommand = sprintf(
            'mysql -u %s -p%s -h %s %s < %s',
            $remoteDbUser,
            $remoteDbPassword,
            $remoteDbHost,
            $remoteDbName,
            $remoteDumpPath
        );

        $sshProcess = new Process(['ssh', '-p', $sshPort, $sshUser . '@' . $sshHost, $sshCommand]);
        $sshProcess->run();

        if (!$sshProcess->isSuccessful()) {
            $io->error('Échec de l\'importation de la base de données sur le serveur distant : ' . $sshProcess->getErrorOutput());
            return Command::FAILURE;
        }

        // 4. Renommer le fichier distant pour inclure la date
        $newRemoteDumpPath = 'domains/damkocompany.com/public_html/codebar/var/tmp/restaurant_dump_' . $date . '.sql';
        $renameCommand = sprintf('mv %s %s', $remoteDumpPath, $newRemoteDumpPath);
        $renameProcess = new Process(['ssh', '-p', $sshPort, $sshUser . '@' . $sshHost, $renameCommand]);
        $renameProcess->run();

        if (!$renameProcess->isSuccessful()) {
            $io->error('Échec du renommage du fichier distant : ' . $renameProcess->getErrorOutput());
            return Command::FAILURE;
        }

        // 5. Supprimer la sauvegarde locale (optionnel)
        // unlink($cheminDump);

        $io->success('Sauvegarde réussie et importation sur le serveur distant effectuée. Le fichier distant a été renommé.');
        return Command::SUCCESS;
    }
}
