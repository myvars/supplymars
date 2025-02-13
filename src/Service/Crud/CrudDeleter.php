<?php

namespace App\Service\Crud;

use App\Service\Crud\Common\BaseCrudHandler;
use App\Service\Crud\Common\CrudActionInterface;
use App\Service\Crud\Common\CrudDeleteAction;
use App\Service\Crud\Common\CrudHelper;
use App\Service\Crud\Common\CrudOptions;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;

class CrudDeleter extends BaseCrudHandler
{
    public const string TEMPLATE = 'delete';

    public function __construct(
        public readonly CrudHelper $crudHelper,
        private readonly CrudOptions $crudOptions,
        #[Autowire(service: CrudDeleteAction::class)]
        private readonly CrudActionInterface $defaultCrudAction
    ) {
        parent::__construct($crudHelper);
    }

    public function deleteConfirm(string $section, ?object $entity): Response
    {
        if ($entity === null) {
            return $this->crudHelper->crudError($section);
        }

        return $this->render($this->crudHelper::CRUD_BASE_TEMPLATE, [
            'section' => $section,
            'template' => self::TEMPLATE,
            'result' => $entity,
        ]);
    }

    public function delete(string $section, ?object $entity): Response
    {
        return $this->process($section, $entity);
    }

    public function setDefaults(): CrudOptions
    {
        return $this->crudOptions::create()
            ->setBackLink(null)
            ->setCrudAction($this->defaultCrudAction)
            ->setCrudActionContext(null);
    }

    public function setup(string $section, object $entity, string $formType=''): CrudOptions
    {
        return $this->setDefaults()
            ->setSection($section)
            ->setEntity($entity)
            ->setSuccessFlash($section.' deleted!')
            ->setErrorFlash('Can not delete '.$section.', it has dependents!')
            ->setSuccessLink(
                $this->generateUrl('app_'.$this->crudHelper->snakeCase($section).'_index')
            );
    }

    public function build(CrudOptions $crudOptions): Response
    {
        if ($this->isCsrfTokenValid(
            'delete'.$crudOptions->getEntity()->getId(),
            $this->crudHelper->getRequest()->get('_token')
        )) {
            return $this->handle($crudOptions);
        }

        return $this->crudHelper->redirectTolink($crudOptions->getSuccessLink(), $crudOptions->isUrlRefresh());
    }
}