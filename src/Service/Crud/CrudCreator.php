<?php

namespace App\Service\Crud;

use App\Service\Crud\Core\CrudCreateOptions;
use App\Service\Crud\Core\CrudCreateAction;
use App\Service\Crud\Core\CrudActionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;

class CrudCreator extends AbstractController
{
    public const TEMPLATE = 'new';

    public function __construct(
        public readonly CrudHelper $crudHelper,
        private readonly CrudCreateOptions $crudOptions,
        #[Autowire(service: CrudCreateAction::class)]
        private readonly CrudActionInterface $crudAction
    ) {
    }

    public function create(string $section, ?object $entity, string $formType): Response
    {
        if (!$entity) {
            return $this->crudHelper->crudError($section);
        }
        $crudOptions = $this->createOptions($section, $entity, $formType);

        return $this->build($crudOptions);
    }

    public function createOptions(string $section, ?object $entity, string $formType): CrudCreateOptions
    {
        $form = $this->createForm($formType, $entity, [
            'action' => $this->generateUrl('app_'.$this->crudHelper->snakeCase($section).'_new'),
        ]);

        return $this->resetOptions()
            ->setSection($section)
            ->setEntity($entity)
            ->setForm($form)
            ->setSuccessLink($this->generateUrl('app_'.$this->crudHelper->snakeCase($section).'_index'))
            ->setBackLink(null)
            ->setCrudAction($this->crudAction)
            ->setCrudActionContext(null);
    }

    public function build(CrudCreateOptions $crudOptions): Response
    {
        $form = $crudOptions->getForm();
        $crudAction = $crudOptions->getCrudAction() ?: $this->crudAction;
        $form->handleRequest($this->crudHelper->getRequest());
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $crudAction->handle($crudOptions->getEntity(), $crudOptions->getCrudActionContext());
                $this->addFlash(
                    'success',
                    'New '.$crudOptions->getSection().' added!'
                );
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    'Can not add '.$crudOptions->getSection().'!'
                );
            }

            return $this->crudHelper->redirectToLink($crudOptions->getSuccessLink());
        }

        return $this->render($this->crudHelper::CRUD_BASE_TEMPLATE, [
            'section' => $crudOptions->getSection(),
            'template' => self::TEMPLATE,
            'result' => $crudOptions->getEntity(),
            'form' => $form,
            'formColumns' => 1,
            'backLink' => $crudOptions->getBackLink(),
        ]);
    }

    public function resetOptions(): CrudCreateOptions
    {
        return $this->crudOptions::create();
    }
}