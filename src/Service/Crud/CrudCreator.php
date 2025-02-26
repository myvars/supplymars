<?php

namespace App\Service\Crud;

use App\Service\Crud\Common\BaseCrudHandler;
use App\Service\Crud\Common\CrudActionInterface;
use App\Service\Crud\Common\CrudCreateAction;
use App\Service\Crud\Common\CrudHelper;
use App\Service\Crud\Common\CrudOptions;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;

class CrudCreator extends BaseCrudHandler
{
    public const string TEMPLATE = 'new';

    public function __construct(
        public readonly CrudHelper $crudHelper,
        private readonly CrudOptions $crudOptions,
        #[Autowire(service: CrudCreateAction::class)]
        private readonly CrudActionInterface $defaultCrudAction,
    ) {
        parent::__construct($crudHelper);
    }

    public function create(string $section, ?object $entity, string $formType): Response
    {
        return $this->process($section, $entity, $formType);
    }

    public function setDefaults(): CrudOptions
    {
        return $this->crudOptions::create()
            ->setTemplate(self::TEMPLATE)
            ->setBackLink(null)
            ->setCrudAction($this->defaultCrudAction)
            ->setCrudActionContext(null);
    }

    public function setup(string $section, object $entity, string $formType = ''): CrudOptions
    {
        $form = $this->createForm($formType, $entity, [
            'action' => $this->generateUrl('app_'.$this->crudHelper->snakeCase($section).'_new'),
        ]);

        return $this->setDefaults()
            ->setSection($section)
            ->setEntity($entity)
            ->setForm($form)
            ->setSuccessFlash('New '.$section.' added!')
            ->setErrorFlash('Can not add '.$section.'!')
            ->setSuccessLink(
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

        return $this->render($this->crudHelper::CRUD_BASE_TEMPLATE, [
            'section' => $crudOptions->getSection(),
            'template' => $crudOptions->getTemplate(),
            'result' => $crudOptions->getEntity(),
            'backLink' => $crudOptions->getBackLink(),
            'form' => $form,
        ]);
    }
}
