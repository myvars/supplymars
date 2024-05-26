<?php

namespace App\Service\Crud;

use App\Service\Crud\Core\CrudCreateOptions;
use App\Service\Crud\Core\CrudCreateStrategy;
use App\Service\Crud\Core\CrudCreateStrategyInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;

class CrudCreator extends AbstractController
{
    public const TEMPLATE = 'new';

    public function __construct(
        public readonly CrudHelper $crudHelper,
        private readonly CrudCreateOptions $crudOptions,
        #[Autowire(service: CrudCreateStrategy::class)]
        private readonly CrudCreateStrategyInterface $crudStrategy
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
            ->setSuccessLink('app_'.$this->crudHelper->snakeCase($section).'_index')
            ->setBackLink(null)
            ->setCrudStrategy($this->crudStrategy)
            ->setCrudStrategyContext(null);
    }

    public function build(CrudCreateOptions $crudOptions): Response
    {
        $form = $crudOptions->getForm();
        $crudStrategy = $crudOptions->getCrudStrategy() ?: $this->crudStrategy;
        $form->handleRequest($this->crudHelper->getRequest());
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $crudStrategy->create($crudOptions->getEntity(), $crudOptions->getCrudStrategyContext());
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

            return $this->crudHelper->redirectToRoute($crudOptions->getSuccessLink());
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