<?php

namespace App\Controller;

use App\Entity\Supplier;
use App\Form\SupplierType;
use App\Repository\SupplierRepository;
use App\Service\CrudHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/supplier')]
class SupplierController extends AbstractController
{
    public const SECTION = 'Supplier';

    public function __construct(private readonly CrudHelper $crudHelper)
    {
        $this->crudHelper->setSection(self::SECTION);
    }

    #[Route('/', name: 'app_supplier_index', methods: ['GET'])]
    public function index(SupplierRepository $supplierRepository): Response
    {
        $sortOptions = ['id', 'name', 'isActive'];

        return $this->crudHelper->renderIndex(
            $supplierRepository,
            $sortOptions
        );
    }

    #[Route('/new', name: 'app_supplier_new', methods: ['GET', 'POST'])]
    public function new(): Response
    {
        return $this->crudHelper->renderCreate(new Supplier(), SupplierType::class);
    }

    #[Route('/{id}', name: 'app_supplier_show', methods: ['GET'])]
    public function show(?Supplier $supplier): Response
    {
        return $this->crudHelper->renderShow($supplier);
    }

    #[Route('/{id}/edit', name: 'app_supplier_edit', methods: ['GET', 'POST'])]
    public function edit(Supplier $supplier): Response
    {
        return $this->crudHelper->renderUpdate(
            $supplier,
            SupplierType::class
        );
    }

    #[Route('/{id}/delete/confirm', name: 'app_supplier_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(?Supplier $supplier): Response
    {
        return $this->crudHelper->renderDeleteConfirm($supplier);
    }

    #[Route('/{id}/delete', name: 'app_supplier_delete', methods: ['POST'])]
    public function delete(?Supplier $supplier): Response
    {
        return $this->crudHelper->renderDelete($supplier);
    }
}
