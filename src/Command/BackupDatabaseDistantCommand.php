<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'app:backup-database-distant',
    description: 'Sauvegarde la base de données dans un fichier SQL compressé (.sql.gz) sur le serveur distant.',
)]
class BackupDatabaseDistantCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('app:backup-database-distant')
            ->setDescription('Sauvegarde la base de données dans un fichier SQL compressé sur Distant.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filesystem = new Filesystem();

        // === 1️⃣ Récupération du chemin du projet depuis .env ===
        $projectPath = $_ENV['PROJECT_PATH'] ?? null;
        if (!$projectPath) {
            $io->error('❌ La variable PROJECT_PATH est absente du fichier .env.');
            return Command::FAILURE;
        }

        // === 2️⃣ Dossier de sauvegarde ===
        $backupDir = rtrim($projectPath, '/') . '/backups';
        $filesystem->mkdir($backupDir);

        // === 3️⃣ Informations de connexion à la base ===
        $databaseUrl = $_ENV['DATABASE_URL'] ?? null;
        if (!$databaseUrl) {
            $io->error('❌ La variable DATABASE_URL est absente du fichier .env.');
            return Command::FAILURE;
        }

        $urlComponents = parse_url($databaseUrl);
        $user = $urlComponents['user'];
        $password = $urlComponents['pass'] ?? '';
        $host = $urlComponents['host'];
        $port = $urlComponents['port'] ?? 3306;
        $dbname = ltrim($urlComponents['path'], '/');

        // === 4️⃣ Préparation des fichiers ===
        $date = date('Y-m-d_H-i-s');
        $sqlFile = sprintf('%s/%s_%s.sql', $backupDir, $dbname, $date);
        $gzFile  = $sqlFile . '.gz';

        $io->section("💾 Sauvegarde de la base '$dbname' vers : $gzFile");

        // === 5️⃣ Exécution du dump SQL ===
        $command = [
            '/usr/bin/mariadb-dump',
            '-u', $user,
            '-p' . $password,
            '-h', $host,
            '-P', $port,
            $dbname,
        ];

        $process = new Process($command);
        $process->setWorkingDirectory($backupDir);
        $process->setTimeout(600);
        $process->run(function ($type, $buffer) use ($sqlFile) {
            file_put_contents($sqlFile, $buffer, FILE_APPEND);
        });

        if (!$process->isSuccessful()) {
            $io->error('❌ Échec du dump : ' . $process->getErrorOutput());
            return Command::FAILURE;
        }

        // === 6️⃣ Compression du fichier SQL ===
        $io->text('📦 Compression du fichier SQL...');
        $data = file_get_contents($sqlFile);
        if ($data === false) {
            $io->error('❌ Impossible de lire le fichier SQL généré.');
            return Command::FAILURE;
        }

        file_put_contents($gzFile, gzencode($data, 9));
        unlink($sqlFile); // Supprime le .sql non compressé

        // === 7️⃣ Affichage du résultat ===
        $size = round(filesize($gzFile) / 1024 / 1024, 2);
        $io->success("✅ Sauvegarde effectuée avec succès ({$size} Mo)");
        $io->writeln("📂 Fichier créé : $gzFile");

        // === 8️⃣ Nettoyage automatique des sauvegardes de +7 jours ===
        $cleanup = new Process([
            'find', $backupDir,
            '-type', 'f',
            '-name', '*.sql.gz',
            '-mtime', '+7',
            '-delete'
        ]);
        $cleanup->run();

        $io->info('🧹 Sauvegardes de plus de 7 jours supprimées.');
        return Command::SUCCESS;
    }
}
