<?php

declare(strict_types=1);

namespace App\Tests\Shared\Infrastructure\Notification;

use App\Shared\Infrastructure\Notification\MercureLiveUpdatePublisher;
use App\Shared\Infrastructure\Notification\MercureUserNotifier;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mercure\Exception\RuntimeException;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\MockHub;
use Symfony\Component\Mercure\Update;
use Twig\Environment;

class MercureUserNotifierTest extends KernelTestCase
{
    /** @var list<Update> */
    private array $published = [];

    private TestHandler $log;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->log = new TestHandler();
    }

    public function testPublishesAPrivateToastOnTheUsersTopic(): void
    {
        $this->notifier(fn (Update $update): string => $this->record($update))
            ->notify('01USER', 'info', 'Sam Carter released your lock on order #000123', '/order/01ORDER');

        self::assertCount(1, $this->published);
        $update = $this->published[0];
        self::assertSame(['user/01USER'], $update->getTopics());
        self::assertTrue($update->isPrivate());
        self::assertStringContainsString('<turbo-stream action="append" target="flash-container">', $update->getData());
        self::assertStringContainsString('Sam Carter released your lock on order #000123', $update->getData());
        self::assertStringContainsString('data-type="info"', $update->getData());
        // What the toaster, and live_toaster's morph handling, pick a toast up by
        self::assertMatchesRegularExpression('#<li[^>]*data-slot="toast"[^>]*data-sonner-target="toast"#s', $update->getData());
        self::assertMatchesRegularExpression('#<a\s+href="/order/01ORDER"[^>]*>View</a>#', $update->getData());
    }

    public function testToastWithoutAUrlHasNoLink(): void
    {
        $this->notifier(fn (Update $update): string => $this->record($update))
            ->notify('01USER', 'success', 'Export ready');

        self::assertStringNotContainsString('data-slot="toast-action"', $this->published[0]->getData());
    }

    public function testHubFailureIsLoggedNotThrown(): void
    {
        $this->notifier(static fn (): never => throw new RuntimeException('Failed to send an update.'))
            ->notify('01USER', 'info', 'Sam Carter released your lock');

        self::assertTrue($this->log->hasErrorThatContains('Live update not published'));
        $record = $this->log->getRecords()[0];
        self::assertSame('user/01USER', $record->context['topic']);
        self::assertInstanceOf(RuntimeException::class, $record->context['exception']);
    }

    /** @param callable(Update): string $publisher */
    private function notifier(callable $publisher): MercureUserNotifier
    {
        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        return new MercureUserNotifier(
            new MercureLiveUpdatePublisher(
                new MockHub('https://hub.test/.well-known/mercure', new StaticTokenProvider('token'), $publisher),
                new Logger('test', [$this->log]),
            ),
            $twig,
        );
    }

    private function record(Update $update): string
    {
        $this->published[] = $update;

        return 'id';
    }
}
