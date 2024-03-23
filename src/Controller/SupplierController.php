<?php

namespace App\Controller;

use App\Entity\Supplier;
use App\Form\SupplierType;
use App\Repository\SupplierRepository;
use App\Service\CrudHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/supplier')]
class SupplierController extends AbstractController
{
    public const string SECTION = 'Supplier';
    public const int FORM_COLUMNS = 1;

    public function __construct(private readonly CrudHelper $crudHelper)
    {
        $this->crudHelper->setSection(self::SECTION);
        $this->crudHelper->setFormColumns(self::FORM_COLUMNS);
    }

    #[Route('/', name: 'app_supplier_index', methods: ['GET'])]
    public function index(SupplierRepository $supplierRepository,
        #[MapQueryParameter] int $page = 1,
        #[MapQueryParameter] int $limit = 10,
        #[MapQueryParameter] string $sort = 'id',
        #[MapQueryParameter] string $sortDirection = 'ASC',
        #[MapQueryParameter] ?string $query = null,
    ): Response {
        $validSorts = ['id', 'name'];
        $sort = in_array($sort, $validSorts) ? $sort : 'id';

        return $this->crudHelper->renderIndex(
            $supplierRepository->findBySearchQueryBuilder($query, $sort, $sortDirection),
            $page,
            $limit,
            $sort,
            $sortDirection,
            $query,
        );
    }

    #[Route('/new', name: 'app_supplier_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        return $this->crudHelper->renderCreate(
            $request,
            new Supplier(),
            SupplierType::class,
        );
    }

    #[Route('/{id}', name: 'app_supplier_show', methods: ['GET'])]
    public function show(?Supplier $supplier): Response
    {
        return $this->crudHelper->renderShow($supplier);
    }

    #[Route('/{id}/edit', name: 'app_supplier_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Supplier $supplier): Response
    {
        return $this->crudHelper->renderUpdate(
            $request,
            $supplier,
            SupplierType::class,
        );
    }

    #[Route('/{id}/delete/confirm', name: 'app_supplier_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(?Supplier $supplier): Response
    {
        return $this->crudHelper->renderDeleteConfirm($supplier);
    }

    #[Route('/{id}/delete', name: 'app_supplier_delete', methods: ['POST'])]
    public function delete(Request $request, ?Supplier $supplier): Response
    {
        return $this->crudHelper->renderDelete(
            $request,
            $supplier,
        );
    }
}
