<?php

declare(strict_types=1);

namespace App\Tests\Shared\Mercure;

use PHPUnit\Framework\Assert;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

/**
 * For tests of a toast sent from a queued message: handles what the request queued, as the worker would,
 * then reads what reached the hub (RecordingHub). Pass the test container after the request and before the
 * next one, which reboots the kernel and empties both: intercept a redirect rather than follow it.
 */
final class QueuedNotifications
{
    /**
     * Handles every message on the async transport, as messenger:consume would, and returns them.
     *
     * @return list<object>
     */
    public static function handle(ContainerInterface $container): array
    {
        $transport = $container->get('messenger.transport.async');
        Assert::assertInstanceOf(InMemoryTransport::class, $transport);
        $bus = $container->get(MessageBusInterface::class);
        Assert::assertInstanceOf(MessageBusInterface::class, $bus);

        // get() returns one message at a time, as a worker's receiver does
        $messages = [];
        while ([] !== $envelopes = [...$transport->get()]) {
            foreach ($envelopes as $envelope) {
                $bus->dispatch($envelope->with(new ReceivedStamp('async')));
                $transport->ack($envelope);
                $messages[] = $envelope->getMessage();
            }
        }

        return $messages;
    }

    /** @return list<Update> */
    public static function published(ContainerInterface $container): array
    {
        return self::hub($container)->getUpdates();
    }

    public static function hub(ContainerInterface $container): RecordingHub
    {
        $hub = $container->get(RecordingHub::class);
        Assert::assertInstanceOf(RecordingHub::class, $hub);

        return $hub;
    }
}
