<?php

declare(strict_types=1);

namespace App\Tests\Shared\UI\Console\Utilities;

use App\Shared\UI\Console\Utilities\BackupDatabaseCommand;
use Doctrine\DBAL\Connection;
use League\Flysystem\FilesystemOperator;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Lock\LockFactory;

final class BackupDatabaseCommandTest extends KernelTestCase
{
    private FilesystemOperator $backups;

    private string $backupsDir;

    private BufferedOutput $output;

    private LockFactory $lockFactory;

    protected function setUp(): void
    {
        self::bootKernel();

        /** @var FilesystemOperator $backups */
        $backups = self::getContainer()->get('oneup_flysystem.backups_fs_filesystem');
        $this->backups = $backups;

        /** @var string $cacheDir */
        $cacheDir = self::getContainer()->getParameter('kernel.cache_dir');
        $this->backupsDir = $cacheDir . '/backups';
        new Filesystem()->remove([$this->backupsDir, $this->backupsDir . '-local']);

        $this->output = new BufferedOutput();

        /** @var LockFactory $lockFactory */
        $lockFactory = self::getContainer()->get(LockFactory::class);
        $this->lockFactory = $lockFactory;
    }

    public function testSkipsTheRunWhileAnotherBackupHoldsTheLock(): void
    {
        $runningBackup = $this->lockFactory->createLock('app:backup-database');
        self::assertTrue($runningBackup->acquire());

        try {
            $status = $this->backUp();
        } finally {
            $runningBackup->release();
        }

        self::assertSame(Command::FAILURE, $status);
        self::assertStringContainsString('A backup is already running', $this->output->fetch());
        self::assertSame([], $this->backupFiles());
    }

    public function testReleasesTheLockWhenTheRunEnds(): void
    {
        $command = $this->command();
        $io = new SymfonyStyle(new ArrayInput([]), $this->output);

        self::assertSame(Command::SUCCESS, $command($io, dryRun: true));
        self::assertSame(Command::SUCCESS, $command($io, dryRun: true), $this->output->fetch());
    }

    public function testReplacesTheLocalCopyWithTheNewBackup(): void
    {
        $localCopy = $this->backupsDir . '-local/nested/latest.sql.gz';
        new Filesystem()->dumpFile($localCopy, 'last night');

        $status = $this->backUp(localCopy: $localCopy);

        self::assertSame(Command::SUCCESS, $status, $this->output->fetch());
        $files = $this->backupFiles();
        self::assertCount(1, $files);
        self::assertMatchesRegularExpression('/^supplymars-\d{4}-\d{2}-\d{2}-\d{6}\.sql\.gz$/', $files[0]);
        self::assertSame($this->backups->read($files[0]), file_get_contents($localCopy));
        self::assertStringContainsString('CREATE TABLE `user`', (string) gzdecode((string) file_get_contents($localCopy)));
        self::assertFileDoesNotExist($localCopy . '.partial');
    }

    private function backUp(?string $localCopy = null): int
    {
        $command = $this->command();

        return $command(new SymfonyStyle(new ArrayInput([]), $this->output), localCopy: $localCopy);
    }

    private function command(): BackupDatabaseCommand
    {
        return new BackupDatabaseCommand($this->backups, new NullLogger(), $this->testDatabaseUrl(), $this->lockFactory);
    }

    /**
     * The test database name has a suffix that DATABASE_URL does not, so the URL is built from the connection.
     */
    private function testDatabaseUrl(): string
    {
        /** @var Connection $connection */
        $connection = self::getContainer()->get(Connection::class);
        $params = $connection->getParams();

        return sprintf(
            'mysql://%s:%s@%s:%s/%s',
            rawurlencode((string) ($params['user'] ?? '')),
            rawurlencode((string) ($params['password'] ?? '')),
            $params['host'] ?? '',
            $params['port'] ?? 3306,
            $params['dbname'] ?? '',
        );
    }

    /**
     * @return list<string>
     */
    private function backupFiles(): array
    {
        $files = $this->backups->listContents('')
            ->filter(static fn ($item): bool => $item->isFile())
            ->map(static fn ($item): string => $item->path())
            ->toArray();
        sort($files);

        return $files;
    }
}
