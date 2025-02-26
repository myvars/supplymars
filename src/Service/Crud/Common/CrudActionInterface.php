<?php

namespace App\Service\Crud\Common;

interface CrudActionInterface
{
    public function handle(CrudOptions $crudOptions): void;
}
