<?php

namespace App\Service\Crud\Core;

use Symfony\Component\HttpFoundation\RedirectResponse;

abstract class CrudOptions
{
    private string $section;
    private object $entity;
    private RedirectResponse $successResponse;
    private ?string $backLink = null;
    private ?array $crudStrategyContext = null;

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

    public function getSuccessResponse(): RedirectResponse
    {
        return $this->successResponse;
    }

    public function setSuccessResponse(RedirectResponse $successResponse): CrudOptions
    {
        $this->successResponse = $successResponse;

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

    public function getCrudStrategyContext(): ?array
    {
        return $this->crudStrategyContext;
    }

    public function setCrudStrategyContext(?array $crudStrategyContext): CrudOptions
    {
        $this->crudStrategyContext = $crudStrategyContext;

        return $this;
    }

    public static function create(): static
    {
        // return a new instance of the class
        return new static();
    }
}