<?php

namespace App\Service\Crud\Core;

interface CrudDeleteStrategyInterface
{
    public function delete(object $entity, ?array $context): void;
}