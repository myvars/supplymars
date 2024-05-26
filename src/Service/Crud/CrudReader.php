<?php

namespace App\Service\Crud;

use App\Service\Crud\Core\CrudReadOptions;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class CrudReader extends AbstractController
{
    public const TEMPLATE = 'show';

    public function __construct(
        public readonly CrudHelper $crudHelper,
        private readonly CrudReadOptions $crudOptions,
    ) {
    }

    public function read(string $section, ?object $entity): Response
    {
        if (!$entity) {
            return $this->crudHelper->showEmpty($section);
        }
        $crudOptions = $this->createOptions($section, $entity);

        return $this->build($crudOptions);
    }

    public function createOptions(string $section, ?object $entity): CrudReadOptions
    {
        $backLink = $this->generateUrl('app_'.$this->crudHelper->snakeCase($section).'_index');

        return $this->resetOptions()
            ->setSection($section)
            ->setEntity($entity)
            ->setBackLink($backLink);
    }

    public function build(CrudReadOptions $crudOptions): Response
    {
        return $this->render($this->crudHelper::CRUD_BASE_TEMPLATE, [
            'section' => $crudOptions->getSection(),
            'template' => self::TEMPLATE,
            'result' => $crudOptions->getEntity(),
        ]);
    }

    public function resetOptions(): CrudReadOptions
    {
        return $this->crudOptions::create();
    }
}
