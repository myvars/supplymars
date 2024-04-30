<?php

namespace App\Service\Crud\Core;

class CrudDeleteOptions extends CrudOptions
{
    private ?CrudDeleteStrategyInterface $crudStrategy = null;

    public function getCrudStrategy(): ?CrudDeleteStrategyInterface
    {
        return $this->crudStrategy;
    }

    public function setCrudStrategy(?CrudDeleteStrategyInterface $crudStrategy): CrudDeleteOptions
    {
        $this->crudStrategy = $crudStrategy;

        return $this;
    }
}