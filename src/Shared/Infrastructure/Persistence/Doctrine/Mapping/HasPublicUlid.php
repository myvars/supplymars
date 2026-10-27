<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine\Mapping;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Ulid;

/**
 * Drop-in for any Doctrine entity that needs a ULID publicId.
 *
 * Adding it to a table that already has rows takes two deploys. First, edit the generated
 * migration so the new column is nullable (NOT NULL would fill every row with the same zero bytes
 * and break the unique index), deploy, and run app:backfill-ulids. Then generate a second
 * migration, which makes it NOT NULL. Between the two, the schema and mapping disagree on purpose.
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
