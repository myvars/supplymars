<?php

declare(strict_types=1);

namespace App\Tests\Shared\Infrastructure\Scheduler;

use App\Shared\Infrastructure\Scheduler\SkipJobsDuringPlaygroundReset;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;
use Symfony\Component\Console\Messenger\RunCommandMessage;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;
use Symfony\Component\Scheduler\Generator\MessageContext;
use Symfony\Component\Scheduler\Messenger\ScheduledStamp;
use Symfony\Component\Scheduler\Trigger\CronExpressionTrigger;

final class SkipJobsDuringPlaygroundResetTest extends KernelTestCase
{
    use ClockSensitiveTrait;

    /**
     * @return iterable<string, array{string, bool, bool}>
     */
    public static function runs(): iterable
    {
        yield 'playground, 02:09, before the window' => ['02:09:59', true, true];
        yield 'playground, 02:10, the window opens' => ['02:10:00', true, false];
        yield 'playground, 02:15, during the restore' => ['02:15:00', true, false];
        yield 'playground, 02:29, the window closes' => ['02:29:59', true, false];
        yield 'playground, 02:30, after the window' => ['02:30:00', true, true];
        yield 'playground, 03:00' => ['03:00:00', true, true];
        yield 'live, 02:15' => ['02:15:00', false, true];
        yield 'live, 03:00' => ['03:00:00', false, true];
    }

    #[DataProvider('runs')]
    public function testTheWorkerSkipsAPlaygroundJobOnlyDuringTheReset(string $time, bool $playgroundMode, bool $handled): void
    {
        self::bootKernel();
        $container = self::getContainer();
        self::mockTime('2026-11-08 ' . $time . ' UTC');
        $container->set(
            SkipJobsDuringPlaygroundReset::class,
            new SkipJobsDuringPlaygroundReset($container->get(ClockInterface::class), $playgroundMode),
        );

        $event = $this->receiveScheduledJob('app:build-purchase-orders 20');

        self::assertSame($handled, $event->shouldHandle());
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function environments(): iterable
    {
        yield 'playground' => ['1', false];
        yield 'live' => ['0', true];
    }

    #[DataProvider('environments')]
    public function testTheRegisteredListenerReadsPlaygroundModeFromTheEnvironment(string $playgroundMode, bool $handled): void
    {
        // The container's own listener, not a replacement: wired by #[AsEventListener], mode from the env
        $previous = [$_SERVER['PLAYGROUND_MODE'] ?? null, $_ENV['PLAYGROUND_MODE'] ?? null];
        $_SERVER['PLAYGROUND_MODE'] = $_ENV['PLAYGROUND_MODE'] = $playgroundMode;

        try {
            self::bootKernel();
            self::mockTime('2026-11-08 02:15:00 UTC');

            self::assertSame($handled, $this->receiveScheduledJob('app:build-purchase-orders 20')->shouldHandle());
        } finally {
            [$_SERVER['PLAYGROUND_MODE'], $_ENV['PLAYGROUND_MODE']] = $previous;
        }
    }

    /**
     * What the worker dispatches when it takes a due job from scheduler_default, before handling it.
     */
    private function receiveScheduledJob(string $command): WorkerMessageReceivedEvent
    {
        /** @var ClockInterface $clock */
        $clock = self::getContainer()->get(ClockInterface::class);
        $context = new MessageContext('default', 'job', CronExpressionTrigger::fromSpec('*/15 * * * *'), $clock->now());
        $event = new WorkerMessageReceivedEvent(
            new Envelope(new RunCommandMessage($command), [new ScheduledStamp($context)]),
            'scheduler_default',
        );

        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = self::getContainer()->get(EventDispatcherInterface::class);
        $dispatcher->dispatch($event);

        return $event;
    }
}
