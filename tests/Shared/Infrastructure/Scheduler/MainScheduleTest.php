<?php

declare(strict_types=1);

namespace App\Tests\Shared\Infrastructure\Scheduler;

use App\Shared\Infrastructure\Scheduler\MainSchedule;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Console\Messenger\RunCommandMessage;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Scheduler\Generator\MessageContext;
use Symfony\Component\Scheduler\Generator\MessageGenerator;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Contracts\Cache\CacheInterface;

final class MainScheduleTest extends KernelTestCase
{
    private const array SHARED_JOBS = [
        ['*/5 * * * * with 0-290 second jitter', 'app:create-customer-orders 1 --random'],
        ['2-59/5 * * * * with 0-290 second jitter', 'app:create-customer-orders 1 --random'],
        ['*/15 * * * *', 'app:build-purchase-orders 20'],
        ['1,16,31,46 * * * *', 'app:accept-purchase-orders 20'],
        ['0 * * * *', 'app:ship-purchase-order-items 100'],
        ['0 * * * *', 'app:deliver-purchase-order-items 100'],
        ['*/15 * * * *', 'app:update-supplier-stock 20'],
        ['3 0 * * *', 'app:calculate-product-sales 1 1'],
        ['5 0 * * *', 'app:calculate-product-sales-summary 1'],
        ['10,40 * * * *', 'app:calculate-product-sales 1'],
        ['7 0 * * *', 'app:calculate-order-sales 1 1'],
        ['9 0 * * *', 'app:calculate-order-sales-summary 1'],
        ['12,42 * * * *', 'app:calculate-order-sales 1'],
        ['11 0 * * *', 'app:calculate-customer-sales 1 1'],
        ['13 0 * * *', 'app:calculate-customer-sales-summary 1'],
        ['15,45 * * * *', 'app:calculate-customer-sales 1'],
    ];

    public function testLiveRunsEverySimulationAndReportingJobWithTheRefundTheBackupAndThePrune(): void
    {
        self::assertSame(
            [
                ['0 2 * * *', 'app:backup-database --local-copy=/backups/latest.sql.gz'],
                ...self::SHARED_JOBS,
                ['30 1 * * *', 'app:refund-purchase-orders 50'],
                ['30 3 * * *', 'app:prune-status-change-log'],
            ],
            $this->jobs($this->schedule(playgroundMode: false)),
        );
    }

    public function testPlaygroundRunsNoneOfTheRefundTheBackupAndThePrune(): void
    {
        // A refund would be replaced by the nightly reset from live's backup, live takes the backup, and the
        // reset brings live's pruned history
        self::assertSame(self::SHARED_JOBS, $this->jobs($this->schedule(playgroundMode: true)));
    }

    public function testTheBackupRunsFirstOfTheJobsDueAtTwo(): void
    {
        // The worker runs one job at a time, so the backup's copy is written before the playground
        // reset reads it at 02:15. Driven through the scheduler's own generator, at 01:59 then 02:00
        self::bootKernel();
        $container = self::getContainer();
        /** @var CacheInterface $cache */
        $cache = $container->get('cache.scheduler');
        $provider = new MainSchedule($cache, $container->get(LockFactory::class), playgroundMode: false);

        $clock = new MockClock(new \DateTimeImmutable('2026-10-21 01:59:00', new \DateTimeZone('UTC')));
        $generator = new MessageGenerator($provider, 'default', $clock);
        self::assertSame([], [...$generator->getMessages()]);

        $clock->sleep(60);
        $commands = [];
        foreach ($generator->getMessages() as $message) {
            self::assertInstanceOf(RunCommandMessage::class, $message);
            $commands[] = $message->input;
        }

        self::assertSame('app:backup-database --local-copy=/backups/latest.sql.gz', $commands[0] ?? null);
        foreach (['app:build-purchase-orders 20', 'app:ship-purchase-order-items 100', 'app:deliver-purchase-order-items 100', 'app:update-supplier-stock 20'] as $dueToo) {
            self::assertContains($dueToo, $commands);
        }
    }

    public function testMakesUpAMissedRunAndNeverRunsAJobTwice(): void
    {
        $schedule = $this->schedule(playgroundMode: false);

        self::assertNotNull($schedule->getState(), 'stateful: a run missed during a restart happens afterwards');
        self::assertTrue($schedule->shouldProcessOnlyLastMissedRun());
        self::assertNotNull($schedule->getLock(), 'locked: two workers never run the same job');
    }

    public function testIsTheScheduleTheWorkerConsumes(): void
    {
        $tester = new CommandTester(new Application(self::bootKernel())->find('debug:scheduler'));

        $tester->execute(['schedule' => ['default']]);

        $tester->assertCommandIsSuccessful();

        $display = $tester->getDisplay();
        self::assertStringContainsString('app:create-customer-orders 1 --random', $display);
        // .env has PLAYGROUND_MODE=0: the live schedule
        self::assertSame(\count(self::SHARED_JOBS) + 3, substr_count($display, 'RunCommandMessage'));
    }

    private function schedule(bool $playgroundMode): Schedule
    {
        self::bootKernel();
        $container = self::getContainer();

        /** @var CacheInterface $cache */
        $cache = $container->get('cache.scheduler');

        return new MainSchedule($cache, $container->get(LockFactory::class), $playgroundMode)->getSchedule();
    }

    /**
     * @return list<array{string, string}>
     */
    private function jobs(Schedule $schedule): array
    {
        $jobs = [];
        foreach ($schedule->getRecurringMessages() as $recurringMessage) {
            $jobs[] = [(string) $recurringMessage->getTrigger(), $this->command($recurringMessage)];
        }

        return $jobs;
    }

    private function command(RecurringMessage $recurringMessage): string
    {
        $context = new MessageContext('default', $recurringMessage->getId(), $recurringMessage->getTrigger(), new \DateTimeImmutable());
        $messages = [...$recurringMessage->getMessages($context)];
        self::assertCount(1, $messages);
        self::assertInstanceOf(RunCommandMessage::class, $messages[0]);

        return $messages[0]->input;
    }
}
