<?php

namespace App\Service\Crud\Common;

use Symfony\Component\Form\FormInterface;

class CrudOptions
{
    private ?string $template = null;

    private ?string $section = null;

    private ?object $entity = null;

    private ?FormInterface $form = null;

    private ?CrudActionInterface $crudAction = null;

    private ?array $crudActionContext = null;

    private ?string $successFlash = null;

    private ?string $errorFlash = null;

    private ?string $successLink = null;

    private ?string $safetyLink = null;

    private ?string $backLink = null;

    private bool $isUrlRefresh = false;

    private bool $allowDelete = false;


    public function getTemplate(): ?string
    {
        return $this->template;
    }

    public function setTemplate(?string $template): static
    {
        $this->template = $template;

        return $this;
    }

    public function getSection(): ?string
    {
        return $this->section;
    }

    public function setSection(?string $section): static
    {
        $this->section = $section;

        return $this;
    }

    public function getEntity(): ?object
    {
        return $this->entity;
    }

    public function setEntity(?object $entity): static
    {
        $this->entity = $entity;

        return $this;
    }

    public function getForm(): ?FormInterface
    {
        return $this->form;
    }

    public function setForm(?FormInterface $form): static
    {
        $this->form = $form;

        return $this;
    }

    public function getCrudAction(): ?CrudActionInterface
    {
        return $this->crudAction;
    }

    public function setCrudAction(?CrudActionInterface $crudAction): static
    {
        $this->crudAction = $crudAction;

        return $this;
    }

    public function getCrudActionContext(): ?array
    {
        return $this->crudActionContext;
    }

    public function setCrudActionContext(?array $crudActionContext): static
    {
        $this->crudActionContext = $crudActionContext;

        return $this;
    }

    public function getSuccessFlash(): ?string
    {
        return $this->successFlash;
    }

    public function setSuccessFlash(?string $successFlash): static
    {
        $this->successFlash = $successFlash;

        return $this;
    }

    public function getErrorFlash(): ?string
    {
        return $this->errorFlash;
    }

    public function setErrorFlash(?string $errorFlash): static
    {
        $this->errorFlash = $errorFlash;

        return $this;
    }

    public function getSuccessLink(): string
    {
        return $this->successLink;
    }

    public function setSuccessLink(string $successLink): static
    {
        $this->successLink = $successLink;

        return $this;
    }

    public function getSafetyLink(): ?string
    {
        return $this->safetyLink;
    }

    public function setSafetyLink(?string $safetyLink): static
    {
        $this->safetyLink = $safetyLink;

        return $this;
    }

    public function getBackLink(): ?string
    {
        return $this->backLink;
    }

    public function setBackLink(?string $backLink): static
    {
        $this->backLink = $backLink;

        return $this;
    }

    public function isUrlRefresh(): bool
    {
        return $this->isUrlRefresh;
    }

    public function setIsUrlRefresh(bool $isUrlRefresh): static
    {
        $this->isUrlRefresh = $isUrlRefresh;

        return $this;
    }

    public function isAllowDelete(): bool
    {
        return $this->allowDelete;
    }

    public function setAllowDelete(bool $allowDelete): static
    {
        $this->allowDelete = $allowDelete;

        return $this;
    }

    public function useSafetyLink(): void
    {
        if ($this->safetyLink === null) {
            return;
        }

        $this->setSuccessLink($this->safetyLink);
    }

    public static function create(): static
    {
        // return a new instance of the class
        return new static();
    }
}