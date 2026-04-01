<?php

namespace App\Order\Application\Command;

final readonly class CreateDemoOrder
{
    public string $id;

    public function __construct()
    {
        $this->id = 'demo';
    }
}
