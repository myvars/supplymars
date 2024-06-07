<?php

namespace App\Service\Crud;

use App\Service\Crud\Core\CrudDeleteOptions;
use App\Service\Crud\Core\CrudDeleteStrategy;
use App\Service\Crud\Core\CrudDeleteStrategyInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;

class CrudDeleter extends AbstractController
{
    public const TEMPLATE = 'delete';

    public function __construct(
        public readonly CrudHelper $crudHelper,
        private readonly CrudDeleteOptions $crudOptions,
        #[Autowire(service: CrudDeleteStrategy::class)]
        private readonly CrudDeleteStrategyInterface $crudStrategy
    ) {
    }

    public function deleteConfirm(string $section, ?object $entity): Response
    {
        if (!$entity) {
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
        if (!$entity) {
            return $this->crudHelper->crudError($section);
        }
        $crudOptions = $this->createOptions($section, $entity);

        return $this->build($crudOptions);
    }

    public function createOptions(string $section, ?object $entity): CrudDeleteOptions
    {
        return $this->resetOptions()
            ->setSection($section)
            ->setEntity($entity)
            ->setSuccessLink($this->generateUrl('app_'.$this->crudHelper->snakeCase($section).'_index'))
            ->setBackLink(null)
            ->setCrudStrategy($this->crudStrategy)
            ->setCrudStrategyContext(null);
    }

    public function build(CrudDeleteOptions $crudOptions): Response
    {
        $crudStrategy = $crudOptions->getCrudStrategy() ?: $this->crudStrategy;
        if ($this->isCsrfTokenValid(
            'delete'.$crudOptions->getEntity()->getId(),
            $this->crudHelper->getRequest()->get('_token'))
        ) {
            try {
                $crudStrategy->delete($crudOptions->getEntity(), $crudOptions->getCrudStrategyContext());
                $this->addFlash(
                    'success',
                    $crudOptions->getSection().' deleted!'
                );
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    'Can not delete '.$crudOptions->getSection().', it has dependents!'
                );
            }

            return $this->crudHelper->redirectToLink($crudOptions->getSuccessLink());
        }

        return $this->crudHelper->redirectTolink($crudOptions->getSuccessLink());
    }

    public function resetOptions(): CrudDeleteOptions
    {
        return $this->crudOptions::create();
    }
}