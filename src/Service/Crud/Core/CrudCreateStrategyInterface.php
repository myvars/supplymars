<?php

namespace App\Service\Crud\Core;

interface CrudCreateStrategyInterface
{
    public function create(object $entity, ?array $context): void;
}