<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Scheduler;

use Symfony\Component\Console\Messenger\RunCommandMessage;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Every scheduled job, for both stacks. The production messenger container runs them by consuming
 * scheduler_default, one at a time; development and make up-prod-local consume only async, so nothing
 * here runs locally. Times are UTC (date.timezone in docker/php/conf.d/10-app.ini).
 */
#[AsSchedule]
final readonly class MainSchedule implements ScheduleProviderInterface
{
    public function __construct(
        #[Autowire(service: 'cache.scheduler')]
        private CacheInterface $cache,
        private LockFactory $lockFactory,
        #[Autowire(env: 'bool:PLAYGROUND_MODE')]
        private bool $playgroundMode,
    ) {
    }

    public function getSchedule(): Schedule
    {
        $schedule = new Schedule();

        if (!$this->playgroundMode) {
            // First in the list, so it runs first of the jobs due at 02:00 and its copy is written
            // before the playground reset reads it at 02:15
            $schedule->add($this->job('0 2 * * *', 'app:backup-database --local-copy=/backups/latest.sql.gz'));
        }

        $schedule->add(
            // One order at a random moment in each half of every 5 minutes: 12 an hour on average
            $this->job('*/5 * * * *', 'app:create-customer-orders 1 --random')->withJitter(290),
            $this->job('2-59/5 * * * *', 'app:create-customer-orders 1 --random')->withJitter(290),
            $this->job('*/15 * * * *', 'app:build-purchase-orders 20'),
            $this->job('1,16,31,46 * * * *', 'app:accept-purchase-orders 20'),
            $this->job('0 * * * *', 'app:ship-purchase-order-items 100'),
            $this->job('0 * * * *', 'app:deliver-purchase-order-items 100'),
            $this->job('*/15 * * * *', 'app:update-supplier-stock 20'),
            $this->job('3 0 * * *', 'app:calculate-product-sales 1 1'),
            $this->job('5 0 * * *', 'app:calculate-product-sales-summary 1'),
            $this->job('10,40 * * * *', 'app:calculate-product-sales 1'),
            $this->job('7 0 * * *', 'app:calculate-order-sales 1 1'),
            $this->job('9 0 * * *', 'app:calculate-order-sales-summary 1'),
            $this->job('12,42 * * * *', 'app:calculate-order-sales 1'),
            $this->job('11 0 * * *', 'app:calculate-customer-sales 1 1'),
            $this->job('13 0 * * *', 'app:calculate-customer-sales-summary 1'),
            $this->job('15,45 * * * *', 'app:calculate-customer-sales 1'),
        );

        if (!$this->playgroundMode) {
            // Live only: a playground refund at 01:30 would be replaced by the 02:15 reset from live's backup
            $schedule->add($this->job('30 1 * * *', 'app:refund-purchase-orders 50'));
        }

        return $schedule
            // The last run time is kept in the app cache, so a run missed while the worker restarts
            // happens when it comes back. Only the latest missed run of each job is made up.
            ->stateful($this->cache)
            ->processOnlyLastMissedRun(true)
            // Two workers consuming scheduler_default in this container never run the same job twice
            ->lock($this->lockFactory->createLock('main-schedule'));
    }

    private function job(string $cron, string $command): RecurringMessage
    {
        return RecurringMessage::cron($cron, new RunCommandMessage($command));
    }
}
