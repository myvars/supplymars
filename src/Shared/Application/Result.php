<?php

namespace App\Shared\Application;

final readonly class Result
{
    public function __construct(
        public bool $ok,
        public ?string $message = null,
        public mixed $payload = null,
        public ?RedirectTarget $redirect = null,
    ) {
    }

    public static function ok(?string $message = null, mixed $payload = null, ?RedirectTarget $redirect = null): self
    {
        return new self(true, $message, $payload, $redirect);
    }

    public static function fail(?string $message = null, mixed $payload = null): self
    {
        return new self(false, $message, $payload);
    }
}
