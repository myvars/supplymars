<?php

declare(strict_types=1);

namespace App\Tests\Shared\UI\Http;

use App\Shared\Infrastructure\Health\HealthCheckInterface;
use App\Shared\Infrastructure\Health\QueueHealthCheck;
use App\Shared\Infrastructure\Health\RedisHealthCheck;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Zenstruck\Browser\Test\HasBrowser;

final class HealthControllerTest extends WebTestCase
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

        self::getContainer()->set(RedisHealthCheck::class, new class implements HealthCheckInterface {
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

    public function testQueueCheckFailsWhenTheTransportCannotCountMessages(): void
    {
        $browser = $this->browser();

        // The test transport (in-memory://) cannot count, so stand in for one that can but is unreachable
        $transport = new class implements TransportInterface, MessageCountAwareInterface {
            public function get(): iterable
            {
                return [];
            }

            public function ack(Envelope $envelope): void
            {
            }

            public function reject(Envelope $envelope): void
            {
            }

            public function send(Envelope $envelope): Envelope
            {
                return $envelope;
            }

            public function getMessageCount(): int
            {
                throw new \RuntimeException('Connection refused to database:3306');
            }
        };
        self::getContainer()->set(QueueHealthCheck::class, new QueueHealthCheck($transport));

        $browser
            ->get('/health')
            ->assertStatus(503)
            ->assertJsonMatches('checks.queue', 'fail')
            ->assertJsonMatches('checks.database', 'ok');
    }
}
