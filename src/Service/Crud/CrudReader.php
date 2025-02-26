<?php

namespace App\Service\Crud;

use App\Service\Crud\Common\BaseCrudHandler;
use App\Service\Crud\Common\CrudHelper;
use App\Service\Crud\Common\CrudOptions;
use Symfony\Component\HttpFoundation\Response;

class CrudReader extends BaseCrudHandler
{
    public const string TEMPLATE = 'show';

    public function __construct(
        public readonly CrudHelper $crudHelper,
        private readonly CrudOptions $crudOptions,
    ) {
        parent::__construct($crudHelper);
    }

    public function read(string $section, ?object $entity): Response
    {
        return $this->process($section, $entity);
    }

    public function setDefaults(): CrudOptions
    {
        return $this->crudOptions::create()
            ->setTemplate(self::TEMPLATE);
    }

    public function setup(string $section, object $entity, string $formType = ''): CrudOptions
    {
        return $this->setDefaults()
            ->setSection($section)
            ->setEntity($entity)
            ->setBackLink(
                $this->generateUrl('app_'.$this->crudHelper->snakeCase($section).'_index')
            );
    }

    public function build(CrudOptions $crudOptions): Response
    {
        return $this->render($this->crudHelper::CRUD_BASE_TEMPLATE, [
            'section' => $crudOptions->getSection(),
            'template' => $crudOptions->getTemplate(),
            'result' => $crudOptions->getEntity(),
            'backLink' => $crudOptions->getBackLink(),
        ]);
    }
}
