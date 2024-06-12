<?php

namespace App\Service\Crud\Core;

interface CrudActionInterface
{
    public function handle(object $entity, ?array $context): void;
}