<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Health;

use League\Flysystem\FilesystemOperator;
use Symfony\Component\DependencyInjection\Attribute\Target;

/**
 * Whether last night's backup landed. Deliberately not a HealthCheckInterface: /health is the nginx
 * healthcheck and the deploy check, and a missed backup must not mark the stack down.
 */
final readonly class BackupFreshnessCheck
{
    // The 02:00 run plus a margin for a slow dump
    private const int MAX_AGE_HOURS = 26;

    public function __construct(
        #[Target('backups_fsFilesystem')]
        private FilesystemOperator $backupsFsFilesystem,
    ) {
    }

    public function name(): string
    {
        return 'backup';
    }

    public function check(): void
    {
        $newest = null;
        foreach ($this->backupsFsFilesystem->listContents('') as $item) {
            if ($item->isFile() && str_ends_with($item->path(), '.sql.gz')) {
                $newest = max($newest ?? 0, (int) $item->lastModified());
            }
        }

        if ($newest === null) {
            throw new \RuntimeException('No backups found.');
        }

        $ageHours = (time() - $newest) / 3600;
        if ($ageHours > self::MAX_AGE_HOURS) {
            throw new \RuntimeException(sprintf('The newest backup is %.1f hours old.', $ageHours));
        }
    }
}
