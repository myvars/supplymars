<?php

namespace App\Service\Crud;

use App\Service\Crud\Core\CrudActionInterface;
use App\Service\Crud\Core\CrudUpdateOptions;
use App\Service\Crud\Core\CrudUpdateAction;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;

class CrudUpdater extends AbstractController
{
    public const TEMPLATE = 'edit';

    public function __construct(
        public readonly CrudHelper $crudHelper,
        private readonly CrudUpdateOptions $crudOptions,
        #[Autowire(service: CrudUpdateAction::class)]
        private readonly CrudActionInterface $crudAction,
    ) {
    }

    public function update(string $section, ?object $entity, string $formType): Response
    {
        if (!$entity) {
            return $this->crudHelper->crudError($section);
        }

        $crudOptions = $this->createOptions($section, $entity, $formType);

        return $this->build($crudOptions);
    }

    public function createOptions(string $section, ?object $entity, string $formType): CrudUpdateOptions
    {
        $form = $this->createForm($formType, $entity, [
            'action' => $this->generateUrl(
                'app_'.$this->crudHelper->snakeCase($section).'_edit', ['id' => $entity->getId()]
            ),
        ]);

        return $this->resetOptions()
            ->setSection($section)
            ->setEntity($entity)
            ->setForm($form)
            ->setSuccessLink($this->generateUrl('app_'.$this->crudHelper->snakeCase($section).'_index'))
            ->setBackLink($this->generateUrl('app_'.$this->crudHelper->snakeCase($section).'_index'))
            ->setAllowDelete(true);
    }

    public function build(CrudUpdateOptions $crudOptions): Response
    {
        $form = $crudOptions->getForm();
        $crudAction = $crudOptions->getCrudAction() ?: $this->crudAction;

        $form->handleRequest($this->crudHelper->getRequest());
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $crudAction->handle($crudOptions->getEntity(), $crudOptions->getCrudActionContext());
                $this->addFlash(
                    'success',
                    $crudOptions->getSection().' updated!'
                );
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    'Can not update '.$crudOptions->getSection().'!'
                );
            }

            return $this->crudHelper->redirectTolink($crudOptions->getSuccessLink());
        }

        return $this->render($this->crudHelper::CRUD_BASE_TEMPLATE, [
            'section' => $crudOptions->getSection(),
            'template' => self::TEMPLATE,
            'result' => $crudOptions->getEntity(),
            'form' => $form,
            'backLink' => $crudOptions->getBackLink(),
            'allowDelete' => $crudOptions->isAllowDelete(),
            'formColumns' => 1
        ]);
    }

    public function resetOptions(): CrudUpdateOptions
    {
        return $this->crudOptions::create();
    }
}