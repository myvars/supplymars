<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine\Mapping;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Ulid;

/**
 * Drop-in for any Doctrine entity that needs a ULID publicId.
 *
 * Adding it to a table that already has rows: make the migration's new column nullable, run
 * app:backfill-ulids, then generate a second migration for NOT NULL.
 */
trait HasPublicUlid
{
    #[ORM\Column(type: 'ulid', unique: true)]
    private ?Ulid $publicId = null;

    /** Call from your entity constructor. */
    public function initializePublicId(): void
    {
        $this->publicId ??= new Ulid();
    }

    /** Internal helper for entity-specific VO getter. */
    protected function publicIdString(): string
    {
        if (null === $this->publicId) {
            throw new \LogicException('publicId is not set (does the constructor call initializePublicId()?)');
        }

        return (string) $this->publicId;
    }
}
