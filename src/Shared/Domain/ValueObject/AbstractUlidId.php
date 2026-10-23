<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

use Symfony\Component\Uid\Ulid;

abstract readonly class AbstractUlidId implements \Stringable, \JsonSerializable
{
    final private function __construct(private string $value)
    {
    }

    public static function new(): static
    {
        return new static((string) new Ulid());
    }

    public static function fromString(string $value): static
    {
        return static::tryFromString($value)
            ?? throw new \InvalidArgumentException(static::class . ' must be a valid ULID string');
    }

    /** Null when the value is not a ULID, for input that may be malformed (a URL path). */
    public static function tryFromString(string $value): ?static
    {
        return Ulid::isValid($value, Ulid::FORMAT_BASE_32) ? new static($value) : null;
    }

    public static function fromUlid(Ulid $ulid): static
    {
        return new static((string) $ulid);
    }

    public function asUlid(): Ulid
    {
        return Ulid::fromString($this->value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return static::class === $other::class && $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
