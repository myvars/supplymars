<?php

namespace App\ValueObject;

/**
 * Represents a cost change
 */
final readonly class CostChange
{
    private function __construct(
        private string $before,
        private string $after,
    ) {
    }

    public static function from(string $before, string $after): self
    {
        return new self($before, $after);
    }

    public function before(): string
    {
        return $this->before;
    }

    public function after(): string
    {
        return $this->after;
    }

    public function hasChanged(): bool
    {
        return $this->before !== $this->after;
    }

    public function describe(): string
    {
        return $this->before . ' → ' . $this->after;
    }
}
