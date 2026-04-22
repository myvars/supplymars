<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

abstract readonly class AbstractIntId implements \Stringable, \JsonSerializable
{
    final private function __construct(private int $value)
    {
    }

    public static function fromInt(int $value): static
    {
        if ($value <= 0) {
            throw new \InvalidArgumentException(static::class . ' must be a positive integer');
        }

        return new static($value);
    }

    public function value(): int
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return static::class === $other::class && $this->value === $other->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }

    public function jsonSerialize(): int
    {
        return $this->value;
    }
}
