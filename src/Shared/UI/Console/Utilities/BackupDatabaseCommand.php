<?php

namespace App\Shared\UI\Console\Utilities;

use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:backup-database',
    description: 'Backup the MySQL database to the backups filesystem (local dev, S3 prod).',
)]
final readonly class BackupDatabaseCommand
{
    public function __construct(
        private FilesystemOperator $backupsFsFilesystem,
        private LoggerInterface $logger,
        #[Autowire('%env(DATABASE_URL)%')]
        private string $databaseUrl,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Option(description: 'Number of days to retain backups (older backups are deleted).')]
        int $retentionDays = 30,
        #[Option(description: 'Show what would happen without executing.')]
        bool $dryRun = false,
    ): int {
        $startTime = microtime(true);
        $filename = sprintf('supplymars-%s.sql.gz', date('Y-m-d-His'));

        $db = parse_url($this->databaseUrl);
        if (!isset($db['host'], $db['user'], $db['pass'], $db['path'])) {
            $io->error('DATABASE_URL is missing required components (host, user, pass, path).');

            return Command::FAILURE;
        }

        $host = $db['host'];
        $port = (string) ($db['port'] ?? '3306');
        $user = $db['user'];
        $password = $db['pass'];
        $dbName = ltrim($db['path'], '/');

        if ($dryRun) {
            $io->note('Dry run mode — no changes will be made.');
            $io->listing([
                'Database: ' . $dbName,
                'Host: ' . $host . ':' . $port,
                'Filename: ' . $filename,
                'Retention: ' . $retentionDays . ' days',
            ]);

            return Command::SUCCESS;
        }

        $io->section('Backing up database: ' . $dbName);

        // 1. Run mysqldump piped to gzip — password via env to avoid leaking in process list
        $tempFile = tempnam(sys_get_temp_dir(), 'db_backup_');

        $process = Process::fromShellCommandline(sprintf(
            'set -o pipefail && mysqldump --host=%s --port=%s --user=%s --single-transaction --quick --routines %s | gzip > %s',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($user),
            escapeshellarg($dbName),
            escapeshellarg($tempFile),
        ));
        $process->setEnv(['MYSQL_PWD' => $password]);
        $process->setTimeout(600);
        $process->run();

        if (!$process->isSuccessful()) {
            $error = $process->getErrorOutput();
            $io->error('mysqldump failed: ' . $error);
            $this->logger->error('Database backup failed', ['error' => $error]);
            $this->cleanupTempFile($tempFile);

            return Command::FAILURE;
        }

        $fileSize = filesize($tempFile);
        if ($fileSize === false || $fileSize === 0) {
            $io->error('Backup file is empty — mysqldump may have failed silently.');
            $this->logger->error('Database backup produced empty file');
            $this->cleanupTempFile($tempFile);

            return Command::FAILURE;
        }

        // 2. Upload to backups filesystem
        try {
            $this->backupsFsFilesystem->write($filename, (string) file_get_contents($tempFile));
        } catch (\Throwable $throwable) {
            $io->error('Failed to upload backup: ' . $throwable->getMessage());
            $this->logger->error('Database backup upload failed', ['error' => $throwable->getMessage()]);

            return Command::FAILURE;
        } finally {
            $this->cleanupTempFile($tempFile);
        }

        $sizeMb = round($fileSize / 1024 / 1024, 2);
        $io->writeln(sprintf('  Uploaded <info>%s</info> (%s MB)', $filename, $sizeMb));

        // 3. Retention cleanup
        $deleted = $this->cleanupOldBackups($retentionDays);
        if ($deleted > 0) {
            $io->writeln(sprintf('  Cleaned up <comment>%d</comment> backup(s) older than %d days.', $deleted, $retentionDays));
        }

        $duration = round(microtime(true) - $startTime, 2);
        $io->success(sprintf('Backup complete in %ss — %s (%s MB)', $duration, $filename, $sizeMb));

        $this->logger->info('Database backup completed', [
            'filename' => $filename,
            'size_mb' => $sizeMb,
            'duration_s' => $duration,
            'retention_deleted' => $deleted,
        ]);

        return Command::SUCCESS;
    }

    private function cleanupOldBackups(int $retentionDays): int
    {
        $cutoff = time() - ($retentionDays * 86400);
        $deleted = 0;

        foreach ($this->backupsFsFilesystem->listContents('') as $item) {
            if ($item->isFile() && str_ends_with($item->path(), '.sql.gz') && $item->lastModified() < $cutoff) {
                $this->backupsFsFilesystem->delete($item->path());
                ++$deleted;
            }
        }

        return $deleted;
    }

    private function cleanupTempFile(string $path): void
    {
        if (file_exists($path)) {
            unlink($path);
        }
    }
}
