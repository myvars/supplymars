<?php

declare(strict_types=1);

namespace App\Tests\Shared\Infrastructure\Notification;

use App\Shared\Infrastructure\Notification\MercureLiveUpdatePublisher;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mercure\Exception\RuntimeException;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\MockHub;
use Symfony\Component\Mercure\Update;

class MercureLiveUpdatePublisherTest extends TestCase
{
    /** @var list<Update> */
    private array $published = [];

    private TestHandler $log;

    protected function setUp(): void
    {
        $this->log = new TestHandler();
    }

    public function testPublishesAPrivateUpdateOnTheTopic(): void
    {
        $this->publisher(fn (Update $update): string => $this->record($update))
            ->publish('order/01ORDER', '<turbo-stream action="remove" target="x"></turbo-stream>');

        self::assertCount(1, $this->published);
        self::assertSame(['order/01ORDER'], $this->published[0]->getTopics());
        self::assertTrue($this->published[0]->isPrivate());
        self::assertSame('<turbo-stream action="remove" target="x"></turbo-stream>', $this->published[0]->getData());
    }

    public function testRefreshMorphsAndKeepsTheScrollPosition(): void
    {
        $this->publisher(fn (Update $update): string => $this->record($update))->refresh('order/01ORDER');

        self::assertSame(['order/01ORDER'], $this->published[0]->getTopics());
        self::assertTrue($this->published[0]->isPrivate());
        self::assertSame('<turbo-stream action="refresh" method="morph" scroll="preserve"></turbo-stream>', $this->published[0]->getData());
    }

    public function testHubFailureIsLoggedNotThrown(): void
    {
        $this->publisher(static fn (): never => throw new RuntimeException('Failed to send an update.'))
            ->refresh('order/01ORDER');

        self::assertTrue($this->log->hasErrorThatContains('Live update not published'));
        $record = $this->log->getRecords()[0];
        self::assertSame('order/01ORDER', $record->context['topic']);
        self::assertInstanceOf(RuntimeException::class, $record->context['exception']);
    }

    /** @param callable(Update): string $publisher */
    private function publisher(callable $publisher): MercureLiveUpdatePublisher
    {
        return new MercureLiveUpdatePublisher(
            new MockHub('https://hub.test/.well-known/mercure', new StaticTokenProvider('token'), $publisher),
            new Logger('test', [$this->log]),
        );
    }

    private function record(Update $update): string
    {
        $this->published[] = $update;

        return 'id';
    }
}
