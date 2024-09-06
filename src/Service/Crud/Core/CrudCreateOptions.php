<?php

namespace App\Service\Crud\Core;

use Symfony\Component\Form\FormInterface;

class CrudCreateOptions extends CrudOptions
{
    private FormInterface $form;

    private ?CrudActionInterface $crudAction = null;

    public function getForm(): FormInterface
    {
        return $this->form;
    }

    public function setForm(FormInterface $form): CrudCreateOptions
    {
        $this->form = $form;

        return $this;
    }

    public function getCrudAction(): ?CrudActionInterface
    {
        return $this->crudAction;
    }

    public function setCrudAction(?CrudActionInterface $crudAction): CrudCreateOptions
    {
        $this->crudAction = $crudAction;

        return $this;
    }
}