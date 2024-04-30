<?php

namespace App\Service\Crud\Core;

class CrudReadOptions
{
    private string $section;
    private object $entity;
    private ?string $backLink = null;

    public function getSection(): string
    {
        return $this->section;
    }

    public function setSection(string $section): CrudReadOptions
    {
        $this->section = $section;

        return $this;
    }

    public function getEntity(): object
    {
        return $this->entity;
    }

    public function setEntity(object $entity): CrudReadOptions
    {
        $this->entity = $entity;

        return $this;
    }

    public static function create(): static
    {
        // return a new instance of the class
        return new static();
    }

    public function getBackLink(): ?string
    {
        return $this->backLink;
    }

    public function setBackLink(?string $backLink): CrudReadOptions
    {
        $this->backLink = $backLink;

        return $this;
    }
}