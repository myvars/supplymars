<?php

namespace App\Service\Crud;

use App\Service\Crud\Core\CrudActionInterface;
use App\Service\Crud\Core\CrudHandlerOptions;
use App\Service\Crud\Core\CrudUpdateAction;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;

class CrudHandler extends AbstractController
{
    public function __construct(
        public readonly CrudHelper $crudHelper,
        private readonly CrudHandlerOptions $crudOptions,
        #[Autowire(service: CrudUpdateAction::class)]
        private readonly CrudActionInterface $crudAction,
    ) {
    }

    public function build(CrudHandlerOptions $crudOptions): Response
    {
        $form = $crudOptions->getForm();
        $crudAction = $crudOptions->getCrudAction() ?: $this->crudAction;

        $form->handleRequest($this->crudHelper->getRequest());
        if ($form->isSubmitted() && $form->isValid() && !$this->crudHelper->isAutoUpdate($form)) {
            try {
                $crudAction->handle($crudOptions);

                if ($this->crudOptions->getSuccessFlash()) {
                    $this->addFlash('success', $this->crudOptions->getSuccessFlash());
                }
            } catch (\Exception) {
                if ($this->crudOptions->getErrorFlash()) {
                    $this->addFlash('error', $this->crudOptions->getErrorFlash());
                }
            }

            return $this->crudHelper->redirectTolink($crudOptions->getSuccessLink(), $crudOptions->isUrlRefresh());
        }

        if ($this->crudHelper->isAutoUpdate($form)) {
            $form->clearErrors(true);
        }

        return $this->render($this->crudHelper::CRUD_BASE_TEMPLATE, [
            'template' => $crudOptions->getTemplate(),
            'result' => $crudOptions->getEntity(),
            'form' => $form,
            'backLink' => $crudOptions->getBackLink(),
        ]);
    }

    public function getOptions(): CrudHandlerOptions
    {
        return $this->crudOptions::create();
    }
}