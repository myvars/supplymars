<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Scheduler;

use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Scheduler\Event\PreRunEvent;

/**
 * The playground reset (02:15 UTC, its own container's cron) restores live's backup table by table, for
 * about two minutes, and a job run meanwhile fails on missing tables and rows. The window starts early
 * enough to let a job begun before the reset finish. A cancelled run is skipped, not made up later.
 */
#[AsEventListener]
final readonly class SkipJobsDuringPlaygroundReset
{
    private const string WINDOW_START = '02:10';

    private const string WINDOW_END = '02:30';

    public function __construct(
        private ClockInterface $clock,
        #[Autowire(env: 'bool:PLAYGROUND_MODE')]
        private bool $playgroundMode,
    ) {
    }

    public function __invoke(PreRunEvent $event): void
    {
        if (!$this->playgroundMode) {
            return;
        }

        $time = $this->clock->now()->setTimezone(new \DateTimeZone('UTC'))->format('H:i');
        if ($time >= self::WINDOW_START && $time < self::WINDOW_END) {
            $event->shouldCancel(true);
        }
    }
}
