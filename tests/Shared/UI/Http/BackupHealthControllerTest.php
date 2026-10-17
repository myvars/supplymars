<?php

declare(strict_types=1);

namespace App\Tests\Shared\UI\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Zenstruck\Browser\Test\HasBrowser;

class BackupHealthControllerTest extends WebTestCase
{
    use HasBrowser;

    private string $backupsDir;

    protected function setUp(): void
    {
        self::bootKernel();

        /** @var string $backupsDir */
        $backupsDir = self::getContainer()->getParameter('test_backups_dir');
        $this->backupsDir = $backupsDir;
        new Filesystem()->remove($this->backupsDir);

        self::ensureKernelShutdown();
    }

    public function testPassesWhenLastNightsBackupLanded(): void
    {
        $this->writeBackup('supplymars-old.sql.gz', ageHours: 50);
        $this->writeBackup('supplymars-new.sql.gz', ageHours: 25);

        // No actingAs(): the uptime monitor calls it anonymously.
        $this->browser()
            ->interceptRedirects()
            ->get('/health/backup')
            ->assertStatus(200)
            ->assertJsonMatches('status', 'ok')
            ->assertJsonMatches('checks.backup', 'ok')
            ->assertHeaderContains('Cache-Control', 'no-store');
    }

    public function testFailsWhenTheNewestBackupIsOlderThan26Hours(): void
    {
        $this->writeBackup('supplymars-stale.sql.gz', ageHours: 27);
        $this->writeBackup('notes.txt', ageHours: 1);

        $this->browser()
            ->get('/health/backup')
            ->assertStatus(503)
            ->assertJsonMatches('status', 'fail')
            ->assertJsonMatches('checks.backup', 'fail')
            ->assertNotContains('hours old');
    }

    public function testFailsWhenThereAreNoBackups(): void
    {
        $this->browser()
            ->get('/health/backup')
            ->assertStatus(503)
            ->assertJsonMatches('checks.backup', 'fail');
    }

    public function testAStaleBackupDoesNotFailTheMainHealthCheck(): void
    {
        $this->writeBackup('supplymars-stale.sql.gz', ageHours: 72);

        $this->browser()
            ->get('/health')
            ->assertStatus(200)
            ->assertJsonMatches('status', 'ok');
    }

    /**
     * Written straight into the test backups adapter's directory, so the age can be set.
     */
    private function writeBackup(string $path, int $ageHours): void
    {
        new Filesystem()->dumpFile($this->backupsDir . '/' . $path, 'dump');
        touch($this->backupsDir . '/' . $path, time() - $ageHours * 3600);
    }
}
