<?php

namespace App\Tests\Shared\Infrastructure\Persistence\Doctrine\Mapping;

use App\Shared\Infrastructure\Persistence\Doctrine\Mapping\HasPublicUlid;

/**
 * Minimal dummy entity exposing a public getter for testing the trait.
 */
final class DummyEntityForUlid
{
    use HasPublicUlid;

    public function publicId(): string
    {
        return $this->publicIdString();
    }
}
