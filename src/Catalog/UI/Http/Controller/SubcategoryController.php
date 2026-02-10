<?php

namespace App\Catalog\UI\Http\Controller;

use App\Catalog\Application\Command\Subcategory\DeleteSubcategory;
use App\Catalog\Application\Handler\Subcategory\CreateSubcategoryHandler;
use App\Catalog\Application\Handler\Subcategory\DeleteSubcategoryHandler;
use App\Catalog\Application\Handler\Subcategory\SubcategoryFilterHandler;
use App\Catalog\Application\Handler\Subcategory\UpdateSubcategoryHandler;
use App\Catalog\Application\Search\SubcategorySearchCriteria;
use App\Catalog\Domain\Model\Subcategory\Subcategory;
use App\Catalog\Domain\Repository\SubcategoryRepository;
use App\Catalog\UI\Http\Form\Mapper\CreateSubcategoryMapper;
use App\Catalog\UI\Http\Form\Mapper\SubcategoryFilterMapper;
use App\Catalog\UI\Http\Form\Mapper\UpdateSubcategoryMapper;
use App\Catalog\UI\Http\Form\Model\SubcategoryForm;
use App\Catalog\UI\Http\Form\Type\SubcategoryFilterType;
use App\Catalog\UI\Http\Form\Type\SubcategoryType;
use App\Shared\UI\Http\FormFlow\DeleteFlow;
use App\Shared\UI\Http\FormFlow\FormFlow;
use App\Shared\UI\Http\FormFlow\InlineEdit\InlineEditContext;
use App\Shared\UI\Http\FormFlow\InlineEdit\InlineEditFlow;
use App\Shared\UI\Http\FormFlow\SearchFlow;
use App\Shared\UI\Http\FormFlow\View\FlowContext;
use App\Shared\UI\Http\FormFlow\View\FlowModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class SubcategoryController extends AbstractController
{
    private function model(): FlowModel
    {
        return FlowModel::create('catalog', 'subcategory');
    }

    #[Route(path: '/subcategory/', name: 'app_catalog_subcategory_index', methods: ['GET'])]
    public function index(
        Request $request,
        SearchFlow $flow,
        SubcategoryRepository $repository,
        #[MapQueryString] SubcategorySearchCriteria $criteria = new SubcategorySearchCriteria(),
    ): Response {
        return $flow->search(
            request: $request,
            repository: $repository,
            criteria: $criteria,
            context: FlowContext::forSearch($this->model()),
        );
    }

    #[Route(path: '/subcategory/search/filter', name: 'app_catalog_subcategory_search_filter', methods: ['GET', 'POST'])]
    public function searchFilter(
        Request $request,
        SubcategoryFilterMapper $mapper,
        SubcategoryFilterHandler $handler,
        FormFlow $flow,
        #[MapQueryString] SubcategorySearchCriteria $criteria = new SubcategorySearchCriteria(),
    ): Response {
        return $flow->form(
            request: $request,
            formType: SubcategoryFilterType::class,
            data: $criteria,
            mapper: $mapper,
            handler: $handler,
            context: FlowContext::forFilter($this->model()),
        );
    }

    #[Route(path: '/subcategory/new', name: 'app_catalog_subcategory_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        CreateSubcategoryMapper $mapper,
        CreateSubcategoryHandler $handler,
        FormFlow $flow,
    ): Response {
        return $flow->form(
            request: $request,
            formType: SubcategoryType::class,
            data: new SubcategoryForm(),
            mapper: $mapper,
            handler: $handler,
            context: FlowContext::forCreate($this->model()),
        );
    }

    #[Route(path: '/subcategory/{id}/edit', name: 'app_catalog_subcategory_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        #[ValueResolver('public_id')] Subcategory $subcategory,
        UpdateSubcategoryMapper $mapper,
        UpdateSubcategoryHandler $handler,
        FormFlow $flow,
    ): Response {
        return $flow->form(
            request: $request,
            formType: SubcategoryType::class,
            data: SubcategoryForm::fromEntity($subcategory),
            mapper: $mapper,
            handler: $handler,
            context: FlowContext::forUpdate($this->model())
                ->allowDelete(true)
                ->successRoute('app_catalog_subcategory_show', ['id' => $subcategory->getPublicId()->value()])
        );
    }

    #[Route(path: '/subcategory/{id}/delete/confirm', name: 'app_catalog_subcategory_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(
        #[ValueResolver('public_id')] Subcategory $subcategory,
        DeleteFlow $flow,
    ): Response {
        return $flow->deleteConfirm(
            entity: $subcategory,
            context: FlowContext::forDelete($this->model()),
        );
    }

    #[Route(path: '/subcategory/{id}/delete', name: 'app_catalog_subcategory_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        #[ValueResolver('public_id')] Subcategory $subcategory,
        DeleteSubcategoryHandler $handler,
        DeleteFlow $flow,
    ): Response {
        return $flow->delete(
            request: $request,
            command: new DeleteSubcategory($subcategory->getPublicId()),
            handler: $handler,
            context: FlowContext::forDelete($this->model()),
        );
    }

    #[Route(path: '/subcategory/{id}', name: 'app_catalog_subcategory_show', methods: ['GET'])]
    public function show(#[ValueResolver('public_id')] Subcategory $subcategory): Response
    {
        return $this->render('/catalog/subcategory/show.html.twig', ['result' => $subcategory]);
    }

    #[Route(path: '/subcategory/{id}/inline/name', name: 'app_catalog_subcategory_inline_name', methods: ['GET', 'POST'])]
    public function inlineName(
        Request $request,
        #[ValueResolver('public_id')] Subcategory $subcategory,
        InlineEditFlow $flow,
    ): Response {
        return $flow->handleField(
            request: $request,
            value: $subcategory->getName(),
            onSave: fn ($value) => $subcategory->rename((string) $value),
            context: InlineEditContext::create(
                frameId: 'inline-edit-subcategory-' . $subcategory->getPublicId() . '-name',
                displayTemplate: 'catalog/subcategory/_inline_name.html.twig',
                entity: $subcategory,
            ),
        );
    }
}
