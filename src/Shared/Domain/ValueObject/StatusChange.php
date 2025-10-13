<?php

namespace App\Shared\Domain\ValueObject;

/**
 * Represents a change from one status to another.
 */
final readonly class StatusChange
{
    private function __construct(
        private \UnitEnum $before,
        private \UnitEnum $after,
    ) {
    }

    public static function from(\UnitEnum $before, \UnitEnum $after): self
    {
        return new self($before, $after);
    }

    public function before(): \UnitEnum
    {
        return $this->before;
    }

    public function after(): \UnitEnum
    {
        return $this->after;
    }

    public function hasChanged(): bool
    {
        return $this->before !== $this->after;
    }

    public function describe(): string
    {
        return $this->before->value.' → '.$this->after->value;
    }
}
