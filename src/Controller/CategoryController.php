<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use App\Service\CrudHelper;
use App\Strategy\CategoryCrudStrategy;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/category')]
class CategoryController extends AbstractController
{
    public const SECTION = 'Category';

    public function __construct(
        private readonly CrudHelper $crudHelper,
        CategoryCrudStrategy $crudStrategy
    ) {
        $this->crudHelper->setSection(self::SECTION);
        $this->crudHelper->setStrategy($crudStrategy);
    }

    #[Route('/', name: 'app_category_index', methods: ['GET'])]
    public function index(CategoryRepository $categoryRepository): Response
    {
        $sortOptions = ['id', 'name', 'defaultMarkup', 'isActive'];

        return $this->crudHelper->renderIndex(
            $categoryRepository,
            $sortOptions
        );
    }

    #[Route('/new', name: 'app_category_new', methods: ['GET', 'POST'])]
    public function new(): Response
    {
        return $this->crudHelper->renderCreate(
            new Category(),
            CategoryType::class
        );
    }

    #[Route('/{id}', name: 'app_category_show', methods: ['GET'])]
    public function show(?Category $category): Response
    {
        return $this->crudHelper->renderShow($category);
    }

    #[Route('/{id}/edit', name: 'app_category_edit', methods: ['GET', 'POST'])]
    public function edit(?Category $category): Response
    {
        return $this->crudHelper->renderUpdate(
            $category,
            CategoryType::class
        );
    }

    #[Route('/{id}/delete/confirm', name: 'app_category_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(?Category $category): Response
    {
        return $this->crudHelper->renderDeleteConfirm($category);
    }

    #[Route('/{id}/delete', name: 'app_category_delete', methods: ['POST'])]
    public function delete(?Category $category): Response
    {
        return $this->crudHelper->renderDelete($category);
    }
}
