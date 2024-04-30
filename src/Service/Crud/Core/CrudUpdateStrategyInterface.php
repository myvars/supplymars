<?php

namespace App\Service\Crud\Core;

interface CrudUpdateStrategyInterface
{
    public function update(object $entity, ?array $context): void;
}