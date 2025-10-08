<?php

namespace App\ValueObject;

/**
 * Represents a stock level change.
 */
final readonly class StockChange
{
    private function __construct(
        private int $before,
        private int $after,
    ) {
    }

    public static function from(int $before, int $after): self
    {
        return new self($before, $after);
    }

    public function before(): int
    {
        return $this->before;
    }

    public function after(): int
    {
        return $this->after;
    }

    public function hasChanged(): bool
    {
        return $this->before !== $this->after;
    }

    public function describe(): string
    {
        return $this->before.' → '.$this->after;
    }
}
