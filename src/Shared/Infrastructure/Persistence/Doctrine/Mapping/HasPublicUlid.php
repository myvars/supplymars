<?php

namespace App\Shared\Infrastructure\Persistence\Doctrine\Mapping;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Ulid;

/**
 * Drop-in for any Doctrine entity that needs a ULID publicId.
 */
// #[ORM\HasLifecycleCallbacks]
trait HasPublicUlid
{
    // During backfill: set nullable:true; after backfill flip to false and run a new migration.
    #[ORM\Column(type: 'ulid', unique: true, nullable: true)]
    private ?string $publicId = null;

    /** Call from your entity constructor. */
    public function initializePublicId(): void
    {
        if (null === $this->publicId) {
            $this->publicId = (string) new Ulid();
        }
    }

    /** Ensures a value exists for new rows even if constructor initialization was skipped. */
    // #[ORM\PrePersist]
    public function ensurePublicId(): void
    {
        if (null === $this->publicId) {
            $this->publicId = (string) new Ulid();
        }
    }

    /** Internal helper for entity-specific VO getter. */
    protected function publicIdString(): string
    {
        if (null === $this->publicId) {
            throw new \LogicException('publicId is not set (are you mid-backfill or missing initialization?)');
        }

        return $this->publicId;
    }
}
