<?php

namespace App\Controller;

use App\DTO\SearchDto\CategorySearchDto;
use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use App\Service\Crud\CrudCreator;
use App\Service\Crud\CrudDeleter;
use App\Service\Crud\CrudReader;
use App\Service\Crud\CrudSearcher;
use App\Service\Crud\CrudUpdater;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/category')]
#[IsGranted('ROLE_USER')]
class CategoryController extends AbstractController
{
    public const SECTION = 'Category';

    #[Route('/', name: 'app_category_index', methods: ['GET'])]
    public function index(
        Request $request,
        CrudSearcher $crudSearcher,
        CategoryRepository $repository,
        #[MapQueryString] CategorySearchDto $dto = new CategorySearchDto()
    ): Response {
        return $crudSearcher->search(self::SECTION, $dto, $repository, $request->query->all());
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
