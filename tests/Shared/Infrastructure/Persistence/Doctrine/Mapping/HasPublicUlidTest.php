<?php

namespace App\Tests\Shared\Infrastructure\Persistence\Doctrine\Mapping;

use PHPUnit\Framework\TestCase;

final class HasPublicUlidTest extends TestCase
{
    public function testInitializePublicIdSetsOnceAndIsIdempotent(): void
    {
        $entity = new DummyEntityForUlid();

        $entity->initializePublicId();

        $first = $entity->publicId();

        $entity->initializePublicId();
        $second = $entity->publicId();

        self::assertSame($first, $second);
        self::assertMatchesRegularExpression('/^[0-7][0-9A-HJKMNP-TV-Z]{25}$/', $first);
    }

    public function testEnsurePublicIdSetsWhenMissing(): void
    {
        $entity = new DummyEntityForUlid();

        $entity->ensurePublicId();

        $id = $entity->publicId();

        self::assertMatchesRegularExpression('/^[0-7][0-9A-HJKMNP-TV-Z]{25}$/', $id);
    }

    public function testEnsurePublicIdDoesNotOverrideExisting(): void
    {
        $entity = new DummyEntityForUlid();
        $entity->initializePublicId();

        $first = $entity->publicId();

        $entity->ensurePublicId();
        $second = $entity->publicId();

        self::assertSame($first, $second);
    }

    public function testPublicIdStringThrowsWhenNotInitialized(): void
    {
        $entity = new DummyEntityForUlid();

        $this->expectException(\LogicException::class);
        $entity->publicId();
    }
}
