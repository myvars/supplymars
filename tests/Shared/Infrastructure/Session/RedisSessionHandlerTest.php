<?php

declare(strict_types=1);

namespace App\Tests\Shared\Infrastructure\Session;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\RedisSessionHandler;

/**
 * Sessions are saved in Redis through the handler on REDIS_URL, the one production uses.
 */
final class RedisSessionHandlerTest extends KernelTestCase
{
    public function testSessionsAreSavedInRedisOnRedisUrl(): void
    {
        self::bootKernel();

        // Tests themselves keep sessions in mock files (framework.yaml), so take the handler directly
        $handler = self::getContainer()->get('session.handler');
        self::assertInstanceOf(RedisSessionHandler::class, $handler);

        /** @var \Redis $redis */
        $redis = self::getContainer()->get('snc_redis.default');
        $sessionId = bin2hex(random_bytes(16));

        try {
            $handler->open('', 'PHPSESSID');
            self::assertTrue($handler->write($sessionId, 'logged-in'));

            self::assertSame(1, $redis->exists('sf_s' . $sessionId));
            self::assertSame('logged-in', $handler->read($sessionId));
        } finally {
            $handler->destroy($sessionId);
        }
    }
}
