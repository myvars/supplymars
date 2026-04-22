<?php

declare(strict_types=1);

namespace App\Shared\UI\Http\Api;

interface ApiResourceInterface
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
