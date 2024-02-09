<?php

namespace App\Controller;

use App\Entity\Subcategory;
use App\Form\SubcategoryType;
use App\Repository\SubcategoryRepository;
use App\Service\CrudHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/subcategory')]
class SubcategoryController extends AbstractController
{
    CONST string SECTION = 'Subcategory';
    const int FORM_COLUMNS = 1;

    public function __construct(private readonly CrudHelper $crudHelper)
    {
        $this->crudHelper->setSection(self::SECTION);
        $this->crudHelper->setFormColumns(self::FORM_COLUMNS);
    }

    #[Route('/', name: 'app_subcategory_index', methods: ['GET'])]
    public function index(
        SubcategoryRepository $subcategoryRepository,
        #[MapQueryParameter] int $page = 1,
        #[MapQueryParameter] int $limit = 10,
        #[MapQueryParameter] string $sort = 'id',
        #[MapQueryParameter] string $sortDirection = 'ASC',
        #[MapQueryParameter] string $query = null,
    ): Response
    {
        $validSorts = ['id', 'name', 'category.name', 'markup', 'isActive'];
        $sort = in_array($sort, $validSorts) ? $sort : 'id';

        return $this->crudHelper->renderIndex(
            $subcategoryRepository->findBySearchQueryBuilder($query, $sort, $sortDirection),
            $page,
            $limit,
            $sort,
            $sortDirection,
            $query,
        );
    }

    #[Route('/new', name: 'app_subcategory_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        return $this->crudHelper->renderCreate(
            $request,
            new Subcategory(),
            SubcategoryType::class,
        );
    }

    #[Route('/{id}', name: 'app_subcategory_show', methods: ['GET'])]
    public function show(?Subcategory $subcategory): Response
    {
        return $this->crudHelper->renderShow($subcategory);
    }

    #[Route('/{id}/edit', name: 'app_subcategory_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ?Subcategory $subcategory): Response
    {
        return $this->crudHelper->renderUpdate(
            $request,
            $subcategory,
            SubcategoryType::class,
        );
    }

    #[Route('/{id}/delete', name: 'app_subcategory_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(?Subcategory $subcategory): Response
    {
        return $this->crudHelper->renderDeleteConfirm($subcategory);
    }

    #[Route('/{id}', name: 'app_subcategory_delete', methods: ['POST'])]
    public function delete(Request $request, ?Subcategory $subcategory): Response
    {
        return $this->crudHelper->renderDelete(
            $request,
            $subcategory,
        );
    }
}