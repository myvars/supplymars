<?php

namespace App\Service\Crud\Core;

use Symfony\Component\Form\FormInterface;

class CrudCreateOptions extends CrudOptions
{
    private FormInterface $form;
    private ?CrudCreateStrategyInterface $crudStrategy = null;

    public function getForm(): FormInterface
    {
        return $this->form;
    }

    public function setForm(FormInterface $form): CrudCreateOptions
    {
        $this->form = $form;

        return $this;
    }

    public function getCrudStrategy(): ?CrudCreateStrategyInterface
    {
        return $this->crudStrategy;
    }

    public function setCrudStrategy(?CrudCreateStrategyInterface $crudStrategy): CrudCreateOptions
    {
        $this->crudStrategy = $crudStrategy;

        return $this;
    }
}