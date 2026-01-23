<?php

namespace App\Shared\Domain\ValueObject;

/**
 * Represents a change from one status to another.
 */
final readonly class StatusChange
{
    private function __construct(
        private \BackedEnum $before,
        private \BackedEnum $after,
    ) {
    }

    public static function from(\BackedEnum $before, \BackedEnum $after): self
    {
        return new self($before, $after);
    }

    public function before(): \BackedEnum
    {
        return $this->before;
    }

    public function after(): \BackedEnum
    {
        return $this->after;
    }

    public function hasChanged(): bool
    {
        return $this->before !== $this->after;
    }

    public function describe(): string
    {
        return $this->before->value . ' → ' . $this->after->value;
    }
}
