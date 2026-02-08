<?php

namespace App\Catalog\UI\Http\Controller;

use App\Catalog\Application\Command\Category\DeleteCategory;
use App\Catalog\Application\Handler\Category\CategoryFilterHandler;
use App\Catalog\Application\Handler\Category\CreateCategoryHandler;
use App\Catalog\Application\Handler\Category\DeleteCategoryHandler;
use App\Catalog\Application\Handler\Category\UpdateCategoryHandler;
use App\Catalog\Application\Search\CategorySearchCriteria;
use App\Catalog\Domain\Model\Category\Category;
use App\Catalog\Domain\Repository\CategoryRepository;
use App\Catalog\UI\Http\Form\Mapper\CategoryFilterMapper;
use App\Catalog\UI\Http\Form\Mapper\CreateCategoryMapper;
use App\Catalog\UI\Http\Form\Mapper\UpdateCategoryMapper;
use App\Catalog\UI\Http\Form\Model\CategoryForm;
use App\Catalog\UI\Http\Form\Type\CategoryFilterType;
use App\Catalog\UI\Http\Form\Type\CategoryType;
use App\Shared\UI\Http\FormFlow\DeleteFlow;
use App\Shared\UI\Http\FormFlow\FormFlow;
use App\Shared\UI\Http\FormFlow\InlineEdit\InlineEditContext;
use App\Shared\UI\Http\FormFlow\InlineEdit\InlineEditFlow;
use App\Shared\UI\Http\FormFlow\SearchFlow;
use App\Shared\UI\Http\FormFlow\View\FlowContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class CategoryController extends AbstractController
{
    public const string MODEL = 'catalog/category';

    #[Route(path: '/category/', name: 'app_catalog_category_index', methods: ['GET'])]
    public function index(
        Request $request,
        SearchFlow $flow,
        CategoryRepository $repository,
        #[MapQueryString] CategorySearchCriteria $criteria = new CategorySearchCriteria(),
    ): Response {
        return $flow->search(
            request: $request,
            repository: $repository,
            criteria: $criteria,
            context: FlowContext::forSearch(self::MODEL),
        );
    }

    #[Route(path: '/category/search/filter', name: 'app_catalog_category_search_filter', methods: ['GET', 'POST'])]
    public function searchFilter(
        Request $request,
        CategoryFilterMapper $mapper,
        CategoryFilterHandler $handler,
        FormFlow $flow,
        #[MapQueryString] CategorySearchCriteria $criteria = new CategorySearchCriteria(),
    ): Response {
        return $flow->form(
            request: $request,
            formType: CategoryFilterType::class,
            data: $criteria,
            mapper: $mapper,
            handler: $handler,
            context: FlowContext::forFilter(self::MODEL),
        );
    }

    #[Route(path: '/category/new', name: 'app_catalog_category_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        CreateCategoryMapper $mapper,
        CreateCategoryHandler $handler,
        FormFlow $flow,
    ): Response {
        return $flow->form(
            request: $request,
            formType: CategoryType::class,
            data: new CategoryForm(),
            mapper: $mapper,
            handler: $handler,
            context: FlowContext::forCreate(self::MODEL),
        );
    }

    #[Route(path: '/category/{id}/edit', name: 'app_catalog_category_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        #[ValueResolver('public_id')] Category $category,
        UpdateCategoryMapper $mapper,
        UpdateCategoryHandler $handler,
        FormFlow $flow,
    ): Response {
        return $flow->form(
            request: $request,
            formType: CategoryType::class,
            data: CategoryForm::fromEntity($category),
            mapper: $mapper,
            handler: $handler,
            context: FlowContext::forUpdate(self::MODEL)
                ->allowDelete(true)
                ->successRoute('app_catalog_category_show', ['id' => $category->getPublicId()->value()]),
        );
    }

    #[Route(path: '/category/{id}/delete/confirm', name: 'app_catalog_category_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(
        #[ValueResolver('public_id')] Category $category,
        DeleteFlow $flow,
    ): Response {
        return $flow->deleteConfirm(
            entity: $category,
            context: FlowContext::forDelete(self::MODEL),
        );
    }

    #[Route(path: '/category/{id}/delete', name: 'app_catalog_category_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        #[ValueResolver('public_id')] Category $category,
        DeleteCategoryHandler $handler,
        DeleteFlow $flow,
    ): Response {
        return $flow->delete(
            request: $request,
            command: new DeleteCategory($category->getPublicId()),
            handler: $handler,
            context: FlowContext::forDelete(self::MODEL),
        );
    }

    #[Route(path: '/category/{id}', name: 'app_catalog_category_show', methods: ['GET'])]
    public function show(#[ValueResolver('public_id')] Category $category): Response
    {
        return $this->render('/catalog/category/show.html.twig', ['result' => $category]);
    }

    #[Route(path: '/category/{id}/inline/name', name: 'app_catalog_category_inline_name', methods: ['GET', 'POST'])]
    public function inlineName(
        Request $request,
        #[ValueResolver('public_id')] Category $category,
        InlineEditFlow $flow,
    ): Response {
        return $flow->handleField(
            request: $request,
            value: $category->getName(),
            onSave: fn ($value) => $category->rename((string) $value),
            context: InlineEditContext::create(
                frameId: 'inline-edit-category-' . $category->getPublicId() . '-name',
                displayTemplate: 'catalog/category/_inline_name.html.twig',
                entity: $category,
            ),
        );
    }
}
