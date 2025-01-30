<?php

namespace App\Service\Crud\Common;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

abstract class BaseCrudHandler extends AbstractController
{
    public function __construct(private readonly CrudHelper $crudHelper)
    {
    }

    abstract protected function setDefaults(): CrudOptions;

    abstract protected function setup(string $section, object $entity, string $formType = ''): CrudOptions;

    abstract protected function build(CrudOptions $crudOptions): Response;

    protected function process(string $section, ?object $entity, string $formType = ''): Response
    {
        if ($entity === null) {
            return $this->getError($section);
        }

        return $this->build(
            $this->setup($section, $entity, $formType)
        );
    }

    protected function handle(CrudOptions $crudOptions): Response
    {
        try {
            $crudOptions->getCrudAction()->handle($crudOptions);

            if ($crudOptions->getSuccessFlash()) {
                $this->addFlash('success', $crudOptions->getSuccessFlash());
            }
        } catch (\Exception) {
            if ($crudOptions->getErrorFlash()) {
                $this->addFlash('error', $crudOptions->getErrorFlash());
            }
        }

        return $this->crudHelper->redirectToLink(
                $crudOptions->getSuccessLink(),
                $crudOptions->isUrlRefresh()
            );
    }

    public function getError(string $section): Response
    {
        return $this->crudHelper->crudError($section);
    }
}