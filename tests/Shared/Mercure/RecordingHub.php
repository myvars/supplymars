<?php

declare(strict_types=1);

namespace App\Tests\Shared\Mercure;

use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;
use Symfony\Component\Mercure\ProtocolVersion;
use Symfony\Component\Mercure\Update;

/**
 * Replaces the hub in tests (config/services.yaml, when@test): records each update instead of sending
 * it, or throws when failWith() was called. The real hub still signs the subscribers' cookies.
 */
final class RecordingHub implements HubInterface
{
    /** @var list<Update> */
    private array $updates = [];

    private ?\Throwable $failure = null;

    public function __construct(private readonly HubInterface $inner)
    {
    }

    public function failWith(\Throwable $failure): void
    {
        $this->failure = $failure;
    }

    /** @return list<Update> */
    public function getUpdates(): array
    {
        return $this->updates;
    }

    public function publish(Update $update): string
    {
        if ($this->failure instanceof \Throwable) {
            throw $this->failure;
        }

        $this->updates[] = $update;

        return 'urn:uuid:' . count($this->updates);
    }

    public function getPublicUrl(): string
    {
        return $this->inner->getPublicUrl();
    }

    public function getFactory(): ?TokenFactoryInterface
    {
        return $this->inner->getFactory();
    }

    public function getProtocolVersion(): ProtocolVersion
    {
        return $this->inner->getProtocolVersion();
    }

    public function getCookieName(): string
    {
        return $this->inner->getCookieName();
    }
}
