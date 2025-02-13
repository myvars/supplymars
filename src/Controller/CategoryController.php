<?php

namespace App\Controller;

use App\DTO\SearchDto\CategorySearchDto;
use App\Entity\Category;
use App\Form\CategoryType;
use App\Form\SearchForm\CategorySearchFilterType;
use App\Repository\CategoryRepository;
use App\Service\Crud\CrudCreator;
use App\Service\Crud\CrudDeleter;
use App\Service\Crud\CrudReader;
use App\Service\Crud\CrudSearcher;
use App\Service\Crud\CrudUpdater;
use App\Service\Search\SearchFilter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/category')]
#[IsGranted('ROLE_ADMIN')]
class CategoryController extends AbstractController
{
    public const string SECTION = 'Category';

    #[Route('/', name: 'app_category_index', methods: ['GET'])]
    public function index(
        Request $request,
        CrudSearcher $handler,
        CategoryRepository $repository,
        #[MapQueryString] CategorySearchDto $dto = new CategorySearchDto()
    ): Response {
        return $handler->search(self::SECTION, $dto, $repository, $request->query->all());
    }

    #[Route('/search/filter', name: 'app_category_search_filter', methods: ['GET', 'POST'])]
    public function searchFilter(
        Request $request,
        CrudUpdater $handler,
        SearchFilter $action,
        #[MapQueryString] CategorySearchDto $dto = new CategorySearchDto()
    ): Response {
        $dto->setQueryString($request->getQueryString());
        $form = $this->createForm(CategorySearchFilterType::class, $dto, [
            'action' => $this->generateUrl('app_category_search_filter', $request->query->all()),
        ]);

        return $handler->build(
            $handler->setDefaults()
                ->setTemplate($dto::TEMPLATE)
                ->setForm($form)
                ->setEntity($dto)
                ->setCrudAction($action)
                ->setSuccessLink(
                    $this->generateUrl('app_category_index')
                )
        );
    }

    #[Route('/new', name: 'app_category_new', methods: ['GET', 'POST'])]
    public function new(CrudCreator $handler): Response
    {
        return $handler->create(self::SECTION, new Category(), CategoryType::class);
    }

    #[Route('/{id}', name: 'app_category_show', methods: ['GET'])]
    public function show(
        Category $category,
        CrudReader $handler
    ): Response {
        return $handler->read(self::SECTION, $category);
    }

    #[Route('/{id}/edit', name: 'app_category_edit', methods: ['GET', 'POST'])]
    public function edit(
        Category $category,
        CrudUpdater $handler
    ): Response {
        return $handler->update(self::SECTION, $category, CategoryType::class);
    }

    #[Route('/{id}/delete/confirm', name: 'app_category_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(
        Category $category,
        CrudDeleter $handler
    ): Response {
        return $handler->deleteConfirm(self::SECTION, $category);
    }

    #[Route('/{id}/delete', name: 'app_category_delete', methods: ['POST'])]
    public function delete(
        Category $category,
        CrudDeleter $handler
    ): Response {
        return $handler->delete(self::SECTION, $category);
    }
}
