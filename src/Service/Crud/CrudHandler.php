<?php

namespace App\Service\Crud;

use App\Service\Crud\Common\BaseCrudHandler;
use App\Service\Crud\Common\CrudHelper;
use App\Service\Crud\Common\CrudOptions;
use Symfony\Component\HttpFoundation\Response;

class CrudHandler extends BaseCrudHandler
{
    public function __construct(
        public readonly CrudHelper $crudHelper,
        private readonly CrudOptions $crudOptions,
    ) {
        parent::__construct($crudHelper);
    }

    public function setDefaults(): CrudOptions
    {
        return $this->crudOptions::create();
    }

    public function setup(string $section, object $entity, string $formType=''): CrudOptions
    {
        return $this->setDefaults()
            ->setSection($section)
            ->setEntity($entity);
    }

    public function build(CrudOptions $crudOptions): Response
    {
        return $this->handle($crudOptions);
    }
}