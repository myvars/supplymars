<?php

namespace App\Service\Crud\Core;

use Symfony\Component\Form\FormInterface;

class CrudUpdateOptions extends CrudOptions
{
    private FormInterface $form;
    private ?CrudActionInterface $crudAction = null;
    private bool $allowDelete = false;

    public function getForm(): FormInterface
    {
        return $this->form;
    }

    public function setForm(FormInterface $form): CrudUpdateOptions
    {
        $this->form = $form;

        return $this;
    }

    public function getCrudAction(): ?CrudActionInterface
    {
        return $this->crudAction;
    }

    public function setCrudAction(?CrudActionInterface $crudAction): CrudUpdateOptions
    {
        $this->crudAction = $crudAction;

        return $this;
    }

    public function isAllowDelete(): bool
    {
        return $this->allowDelete;
    }

    public function setAllowDelete(bool $allowDelete): CrudUpdateOptions
    {
        $this->allowDelete = $allowDelete;

        return $this;
    }
}