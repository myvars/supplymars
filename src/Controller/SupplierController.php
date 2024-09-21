<?php

namespace App\Controller;

use App\Entity\Supplier;
use App\Form\SupplierType;
use App\Repository\SupplierRepository;
use App\Service\Crud\CrudCreator;
use App\Service\Crud\CrudDeleter;
use App\Service\Crud\CrudIndexer;
use App\Service\Crud\CrudUpdater;
use App\Service\Crud\CrudReader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/supplier')]
#[IsGranted('ROLE_USER')]
class SupplierController extends AbstractController
{
    public const SECTION = 'Supplier';

    #[Route('/', name: 'app_supplier_index', methods: ['GET'])]
    public function index(SupplierRepository $repository, CrudIndexer $crudIndexer): Response
    {
        $sortOptions = ['id', 'name', 'isActive'];

        return $crudIndexer->index(self::SECTION, $repository, $sortOptions);
    }

    #[Route('/new', name: 'app_supplier_new', methods: ['GET', 'POST'])]
    public function new(CrudCreator $crudCreator): Response
    {
        return $crudCreator->create(self::SECTION, new Supplier(), SupplierType::class);
    }

    #[Route('/{id}', name: 'app_supplier_show', methods: ['GET'])]
    public function show(?Supplier $supplier, CrudReader $crudReader): Response
    {
        return $crudReader->read(self::SECTION, $supplier);
    }

    #[Route('/{id}/edit', name: 'app_supplier_edit', methods: ['GET', 'POST'])]
    public function edit(Supplier $supplier, CrudUpdater $crudUpdater): Response
    {
        return $crudUpdater->update(self::SECTION, $supplier, SupplierType::class);
    }

    #[Route('/{id}/delete/confirm', name: 'app_supplier_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(?Supplier $supplier, CrudDeleter $crudDeleter): Response
    {
        return $crudDeleter->deleteConfirm(self::SECTION, $supplier);
    }

    #[Route('/{id}/delete', name: 'app_supplier_delete', methods: ['POST'])]
    public function delete(?Supplier $supplier, CrudDeleter $crudDeleter): Response
    {
        return $crudDeleter->delete(self::SECTION, $supplier);
    }
}
