<?php

namespace App\Shared\UI\Console\Utilities;

use Aws\S3\S3Client;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:restore-database',
    description: 'Restore the MySQL database from a .sql.gz backup (local or S3).',
)]
#[When(env: 'dev')]
final readonly class RestoreDatabaseCommand
{
    private string $backupsDir;

    public function __construct(
        private LoggerInterface $logger,
        #[Autowire('%env(DATABASE_URL)%')]
        private string $databaseUrl,
        #[Autowire('%env(AWS_S3_BUCKET)%')]
        private string $s3Bucket,
        #[Autowire('%env(AWS_S3_REGION)%')]
        private string $s3Region,
        #[Autowire('%kernel.project_dir%')]
        string $projectDir,
    ) {
        $this->backupsDir = $projectDir . '/var/backups';
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Option(description: 'Download the latest backup from S3 before restoring.')]
        bool $fromS3 = false,
        #[Option(description: 'Restore a specific filename from var/backups/.')]
        ?string $file = null,
        #[Option(description: 'Skip the confirmation prompt.')]
        bool $force = false,
    ): int {
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

        if ($fromS3) {
            $file = $this->downloadFromS3($io);
            if ($file === null) {
                return Command::FAILURE;
            }
        }

        $filename = $file ?? $this->findLatestBackup();
        if ($filename === null) {
            $io->error('No .sql.gz backups found in var/backups/.');

            return Command::FAILURE;
        }

        $backupPath = $this->backupsDir . '/' . $filename;
        if (!is_file($backupPath)) {
            $io->error(sprintf('Backup file not found: %s', $filename));

            return Command::FAILURE;
        }

        if (!$force) {
            $io->warning('This will overwrite the current database.');
            $io->listing([
                'Database: ' . $dbName,
                'Host: ' . $host . ':' . $port,
                'File: ' . $filename,
            ]);

            if (!$io->confirm('Proceed with restore?', false)) {
                $io->note('Restore cancelled.');

                return Command::SUCCESS;
            }
        }

        $io->section('Restoring database: ' . $dbName);
        $startTime = microtime(true);

        $process = Process::fromShellCommandline(sprintf(
            'set -o pipefail && gunzip < %s | mysql --host=%s --port=%s --user=%s %s',
            escapeshellarg($backupPath),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($user),
            escapeshellarg($dbName),
        ));
        $process->setEnv(['MYSQL_PWD' => $password]);
        $process->setTimeout(600);
        $process->run();

        if (!$process->isSuccessful()) {
            $error = $process->getErrorOutput();
            $io->error('Restore failed: ' . $error);
            $this->logger->error('Database restore failed', ['error' => $error, 'file' => $filename]);

            return Command::FAILURE;
        }

        $duration = round(microtime(true) - $startTime, 2);
        $io->success(sprintf('Restore complete in %ss from %s', $duration, $filename));
        $this->logger->info('Database restore completed', ['filename' => $filename, 'duration_s' => $duration]);

        return Command::SUCCESS;
    }

    private function downloadFromS3(SymfonyStyle $io): ?string
    {
        $io->section('Downloading latest backup from S3');

        $accessKey = $io->ask('AWS_S3_ACCESS_ID (prod credentials)');
        $secretKey = $io->askHidden('AWS_S3_SECRET_ACCESS_KEY (prod credentials)');

        if (!$accessKey || !$secretKey) {
            $io->error('AWS credentials are required.');

            return null;
        }

        try {
            $s3Client = new S3Client([
                'version' => 'latest',
                'region' => $this->s3Region,
                'credentials' => [
                    'key' => $accessKey,
                    'secret' => $secretKey,
                ],
            ]);

            $adapter = new AwsS3V3Adapter($s3Client, $this->s3Bucket, 'backups');
            $s3Fs = new Filesystem($adapter);

            $files = [];
            foreach ($s3Fs->listContents('') as $item) {
                if ($item->isFile() && str_ends_with($item->path(), '.sql.gz')) {
                    $files[] = $item->path();
                }
            }

            if ($files === []) {
                $io->error('No .sql.gz backups found on S3.');

                return null;
            }

            rsort($files);
            $latest = $files[0];
            $localPath = $this->backupsDir . '/' . $latest;

            $io->writeln(sprintf('  Downloading <info>%s</info>...', $latest));

            if (!is_dir($this->backupsDir)) {
                mkdir($this->backupsDir, 0o755, true);
            }

            file_put_contents($localPath, $s3Fs->read($latest));

            $io->writeln('  Download complete.');

            return $latest;
        } catch (\Throwable $throwable) {
            $io->error('S3 download failed: ' . $throwable->getMessage());
            $this->logger->error('S3 backup download failed', ['error' => $throwable->getMessage()]);

            return null;
        }
    }

    private function findLatestBackup(): ?string
    {
        $files = glob($this->backupsDir . '/*.sql.gz');
        if ($files === [] || $files === false) {
            return null;
        }

        rsort($files);

        return basename($files[0]);
    }
}
