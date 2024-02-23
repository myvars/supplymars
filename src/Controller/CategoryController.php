<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use App\Service\CrudHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/category')]
class CategoryController extends AbstractController
{
    CONST string SECTION = 'Category';
    const int FORM_COLUMNS = 1;

    public function __construct(private readonly CrudHelper $crudHelper)
    {
        $this->crudHelper->setSection(self::SECTION);
        $this->crudHelper->setFormColumns(self::FORM_COLUMNS);
    }

    #[Route('/', name: 'app_category_index', methods: ['GET'])]
    public function index(
        CategoryRepository $categoryRepository,
        #[MapQueryParameter] int $page = 1,
        #[MapQueryParameter] int $limit = 10,
        #[MapQueryParameter] string $sort = 'id',
        #[MapQueryParameter] string $sortDirection = 'ASC',
        #[MapQueryParameter] string $query = null,
    ): Response
    {
        $validSorts = ['id', 'name', 'defaultMarkup', 'isActive'];
        $sort = in_array($sort, $validSorts) ? $sort : 'id';

        return $this->crudHelper->renderIndex(
            $categoryRepository->findBySearchQueryBuilder($query, $sort, $sortDirection),
            $page,
            $limit,
            $sort,
            $sortDirection,
            $query,
        );
    }

    #[Route('/new', name: 'app_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        return $this->crudHelper->renderCreate(
            $request,
            new Category(),
            CategoryType::class,
        );
    }

    #[Route('/{id}', name: 'app_category_show', methods: ['GET'])]
    public function show(?Category $category): Response
    {
        return $this->crudHelper->renderShow($category);
    }

    #[Route('/{id}/edit', name: 'app_category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ?Category $category): Response
    {
        return $this->crudHelper->renderUpdate(
            $request,
            $category,
            CategoryType::class,
        );
    }

    #[Route('/{id}/delete', name: 'app_category_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(?Category $category): Response
    {
        return $this->crudHelper->renderDeleteConfirm($category);
    }

    #[Route('/{id}', name: 'app_category_delete', methods: ['POST'])]
    public function delete(Request $request, ?Category $category): Response
    {
        return $this->crudHelper->renderDelete(
            $request,
            $category,
        );
    }

    #[Route('/{id}/subcategories', name: 'app_category_subcategories', methods: ['GET'])]
    public function getSubcategories(?Category $category): Response
    {;
        return $this->render('category/subcategories.html.twig', [
            'subcategories' => $category?->getSubcategories(),
        ]);
    }
}
