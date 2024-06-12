<?php

namespace App\Service\Crud\Core;

class CrudDeleteOptions extends CrudOptions
{
    private ?CrudActionInterface $crudAction = null;

    public function getCrudAction(): ?CrudActionInterface
    {
        return $this->crudAction;
    }

    public function setCrudAction(?CrudActionInterface $crudAction): CrudDeleteOptions
    {
        $this->crudAction = $crudAction;

        return $this;
    }
}