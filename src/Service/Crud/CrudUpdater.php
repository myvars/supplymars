<?php

namespace App\Service\Crud;

use App\Service\Crud\Common\BaseCrudHandler;
use App\Service\Crud\Common\CrudActionInterface;
use App\Service\Crud\Common\CrudHelper;
use App\Service\Crud\Common\CrudOptions;
use App\Service\Crud\Common\CrudUpdateAction;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;

class CrudUpdater extends BaseCrudHandler
{
    public const TEMPLATE = 'edit';

    public function __construct(
        public readonly CrudHelper $crudHelper,
        private readonly CrudOptions $crudOptions,
        #[Autowire(service: CrudUpdateAction::class)]
        private readonly CrudActionInterface $defaultCrudAction,
    ) {
        parent::__construct($crudHelper);
    }

    public function update(string $section, ?object $entity, string $formType): Response
    {
        return $this->process($section, $entity, $formType);
    }

    public function setDefaults(): CrudOptions
    {
        return $this->crudOptions::create()
            ->setTemplate(self::TEMPLATE)
            ->setCrudAction($this->defaultCrudAction);
    }

    public function setup(string $section, object $entity, string $formType=''): CrudOptions
    {
        $form = $this->createForm($formType, $entity, [
            'action' => $this->generateUrl(
                'app_'.$this->crudHelper->snakeCase($section) . '_edit',
                ['id' => $entity->getId()]
            )]
        );

        return $this->setDefaults()
            ->setSection($section)
            ->setEntity($entity)
            ->setForm($form)
            ->setAllowDelete(true)
            ->setSuccessFlash($section.' updated!')
            ->setErrorFlash('Can not update '.$section.'!')
            ->setSuccessLink(
                $this->generateUrl('app_'.$this->crudHelper->snakeCase($section).'_index')
            )
            ->setBackLink(
                $this->generateUrl('app_'.$this->crudHelper->snakeCase($section).'_index')
            );
    }

    public function build(CrudOptions $crudOptions): Response
    {
        $form = $crudOptions->getForm();

        $form->handleRequest($this->crudHelper->getRequest());
        if ($form->isSubmitted() && $form->isValid() && !$this->crudHelper->isAutoUpdate($form)) {
            return $this->handle($crudOptions);
        }

        $this->crudHelper->autoUpdateClearErrors($form);

        return $this->render(
            $this->crudHelper::CRUD_BASE_TEMPLATE, [
                'section' => $crudOptions->getSection(),
                'template' => $crudOptions->getTemplate(),
                'result' => $crudOptions->getEntity(),
                'backLink' => $crudOptions->getBackLink(),
                'form' => $form,
                'allowDelete' => $crudOptions->isAllowDelete()
            ]
        );
    }
}