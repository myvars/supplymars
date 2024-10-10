<?php

namespace App\Service\Crud\Core;

use Symfony\Component\Form\FormInterface;

class CrudHandlerOptions extends CrudOptions
{
    private string $template;

    private FormInterface $form;

    private ?CrudActionInterface $crudAction = null;

    private ?string $successFlash = null;

    private ?string $errorFlash = null;

    private bool $isUrlRefresh = false;


    public function getTemplate(): string
    {
        return $this->template;
    }

    public function setTemplate(string $template): CrudHandlerOptions
    {
        $this->template = $template;

        return $this;
    }

    public function getForm(): FormInterface
    {
        return $this->form;
    }

    public function setForm(FormInterface $form): CrudHandlerOptions
    {
        $this->form = $form;

        return $this;
    }

    public function getCrudAction(): ?CrudActionInterface
    {
        return $this->crudAction;
    }

    public function setCrudAction(?CrudActionInterface $crudAction): CrudHandlerOptions
    {
        $this->crudAction = $crudAction;

        return $this;
    }

    public function getSuccessFlash(): ?string
    {
        return $this->successFlash;
    }

    public function setSuccessFlash(?string $successFlash): CrudHandlerOptions
    {
        $this->successFlash = $successFlash;

        return $this;
    }

    public function getErrorFlash(): ?string
    {
        return $this->errorFlash;
    }

    public function setErrorFlash(?string $errorFlash): CrudHandlerOptions
    {
        $this->errorFlash = $errorFlash;

        return $this;
    }

    public function isUrlRefresh(): bool
    {
        return $this->isUrlRefresh;
    }

    public function setIsUrlRefresh(bool $isUrlRefresh): CrudHandlerOptions
    {
        $this->isUrlRefresh = $isUrlRefresh;

        return $this;
    }


}

