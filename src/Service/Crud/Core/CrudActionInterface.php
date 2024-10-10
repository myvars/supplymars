<?php

namespace App\Service\Crud\Core;

interface CrudActionInterface
{
    public function handle(object $crudOptions): void;
}