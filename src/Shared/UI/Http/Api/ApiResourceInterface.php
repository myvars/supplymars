<?php

namespace App\Shared\UI\Http\Api;

interface ApiResourceInterface
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
