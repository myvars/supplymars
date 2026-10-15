<?php

namespace App\Tests\Shared\UI\Http;

use App\Shared\Infrastructure\Health\HealthCheckInterface;
use App\Shared\Infrastructure\Health\RedisHealthCheck;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

class HealthControllerTest extends WebTestCase
{
    use HasBrowser;

    public function testHealthyResponseIsPublicAndListsEveryCheck(): void
    {
        // No actingAs(): the endpoint must answer an anonymous caller.
        $this->browser()
            ->interceptRedirects()
            ->get('/health')
            ->assertStatus(200)
            ->assertJson()
            ->assertJsonMatches('status', 'ok')
            ->assertJsonMatches('checks.database', 'ok')
            ->assertJsonMatches('checks.redis', 'ok')
            ->assertJsonMatches('checks.queue', 'ok')
            ->assertHeaderContains('Cache-Control', 'no-store');
    }

    public function testFailingCheckReturns503AndNamesIt(): void
    {
        $browser = $this->browser();

        static::getContainer()->set(RedisHealthCheck::class, new class implements HealthCheckInterface {
            public function name(): string
            {
                return 'redis';
            }

            public function check(): void
            {
                throw new \RuntimeException('Connection refused to redis:6379');
            }
        });

        $browser
            ->get('/health')
            ->assertStatus(503)
            ->assertJsonMatches('status', 'fail')
            ->assertJsonMatches('checks.redis', 'fail')
            ->assertJsonMatches('checks.database', 'ok')
            ->assertJsonMatches('checks.queue', 'ok')
            ->assertNotContains('Connection refused');
    }
}
