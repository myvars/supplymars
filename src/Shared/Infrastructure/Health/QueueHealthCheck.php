<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Health;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

final readonly class QueueHealthCheck implements HealthCheckInterface
{
    public function __construct(
        #[Autowire(service: 'messenger.transport.async')]
        private TransportInterface $transport,
    ) {
    }

    public function name(): string
    {
        return 'queue';
    }

    public function check(): void
    {
        // Counting the waiting messages needs a working connection. A transport that cannot
        // count (sync://, in-memory://) has no connection to test, so it passes.
        if ($this->transport instanceof MessageCountAwareInterface) {
            $this->transport->getMessageCount();
        }
    }
}
