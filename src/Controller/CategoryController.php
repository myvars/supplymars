<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use App\Service\Crud\CrudCreator;
use App\Service\Crud\CrudDeleter;
use App\Service\Crud\CrudIndexer;
use App\Service\Crud\CrudUpdater;
use App\Service\Crud\CrudReader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/category')]
class CategoryController extends AbstractController
{
    public const SECTION = 'Category';

    #[Route('/', name: 'app_category_index', methods: ['GET'])]
    public function index(CategoryRepository $repository, CrudIndexer $crudIndexer): Response
    {
        $sortOptions = ['id', 'name', 'defaultMarkup', 'isActive'];

        return $crudIndexer->index(self::SECTION, $repository, $sortOptions);
    }

    #[Route('/new', name: 'app_category_new', methods: ['GET', 'POST'])]
    public function new(CrudCreator $crudCreator): Response
    {
        return $crudCreator->create(self::SECTION, new Category(), CategoryType::class);
    }

    #[Route('/{id}', name: 'app_category_show', methods: ['GET'])]
    public function show(?Category $category, CrudReader $crudReader): Response
    {
        return $crudReader->read(self::SECTION, $category);
    }

    #[Route('/{id}/edit', name: 'app_category_edit', methods: ['GET', 'POST'])]
    public function edit(?Category $category, CrudUpdater $crudUpdater): Response
    {
        return $crudUpdater->update(self::SECTION, $category, CategoryType::class);
    }

    #[Route('/{id}/delete/confirm', name: 'app_category_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(?Category $category, CrudDeleter $crudDeleter): Response
    {
        return $crudDeleter->deleteConfirm(self::SECTION, $category);
    }

    #[Route('/{id}/delete', name: 'app_category_delete', methods: ['POST'])]
    public function delete(?Category $category, CrudDeleter $crudDeleter): Response
    {
        return $crudDeleter->delete(self::SECTION, $category);
    }
}
