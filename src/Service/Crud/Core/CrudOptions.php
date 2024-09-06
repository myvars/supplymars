<?php

namespace App\Service\Crud\Core;

abstract class CrudOptions
{
    private string $section;

    private object $entity;

    private string $successLink;

    private ?string $backLink = null;

    private ?array $crudActionContext = null;

    public function getSection(): string
    {
        return $this->section;
    }

    public function setSection(string $section): CrudOptions
    {
        $this->section = $section;

        return $this;
    }

    public function getEntity(): object
    {
        return $this->entity;
    }

    public function setEntity(object $entity): CrudOptions
    {
        $this->entity = $entity;

        return $this;
    }

    public function getSuccessLink(): string
    {
        return $this->successLink;
    }

    public function setSuccessLink(string $successLink): CrudOptions
    {
        $this->successLink = $successLink;

        return $this;
    }

    public function getBackLink(): ?string
    {
        return $this->backLink;
    }

    public function setBackLink(?string $backLink): CrudOptions
    {
        $this->backLink = $backLink;

        return $this;
    }

    public function getCrudActionContext(): ?array
    {
        return $this->crudActionContext;
    }

    public function setCrudActionContext(?array $crudActionContext): CrudOptions
    {
        $this->crudActionContext = $crudActionContext;

        return $this;
    }

    public static function create(): static
    {
        // return a new instance of the class
        return new static();
    }
}