<?php

namespace App\Service\Crud\Core;

use Symfony\Component\Form\FormInterface;

class CrudUpdateOptions extends CrudOptions
{
    private FormInterface $form;
    private ?CrudUpdateStrategyInterface $crudStrategy = null;
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

    public function getCrudStrategy(): ?CrudUpdateStrategyInterface
    {
        return $this->crudStrategy;
    }

    public function setCrudStrategy(?CrudUpdateStrategyInterface $crudStrategy): CrudUpdateOptions
    {
        $this->crudStrategy = $crudStrategy;

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