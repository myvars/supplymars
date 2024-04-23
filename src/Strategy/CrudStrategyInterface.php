<?php

namespace App\Strategy;

interface CrudStrategyInterface
{
    public function create(object $entity): void;
    public function update(object $entity): void;
    public function delete(object $entity): void;
}