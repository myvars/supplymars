<?php

namespace App\Controller;

use App\DTO\SearchDto\SupplierSearchDto;
use App\Entity\Supplier;
use App\Form\SupplierType;
use App\Repository\SupplierRepository;
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

#[IsGranted('ROLE_ADMIN')]
class SupplierController extends AbstractController
{
    public const string SECTION = 'Supplier';

    #[Route(path: '/supplier/', name: 'app_supplier_index', methods: ['GET'])]
    public function index(
        Request $request,
        CrudSearcher $handler,
        SupplierRepository $repository,
        #[MapQueryString] SupplierSearchDto $dto = new SupplierSearchDto(),
    ): Response {
        return $handler->search(self::SECTION, $dto, $repository, $request->query->all());
    }

    #[Route(path: '/supplier/new', name: 'app_supplier_new', methods: ['GET', 'POST'])]
    public function new(CrudCreator $handler): Response
    {
        return $handler->create(self::SECTION, new Supplier(), SupplierType::class);
    }

    #[Route(path: '/supplier/{id}', name: 'app_supplier_show', methods: ['GET'])]
    public function show(
        ?Supplier $supplier,
        CrudReader $handler,
    ): Response {
        return $handler->read(self::SECTION, $supplier);
    }

    #[Route(path: '/supplier/{id}/edit', name: 'app_supplier_edit', methods: ['GET', 'POST'])]
    public function edit(
        Supplier $supplier,
        CrudUpdater $handler,
    ): Response {
        return $handler->update(self::SECTION, $supplier, SupplierType::class);
    }

    #[Route(path: '/supplier/{id}/delete/confirm', name: 'app_supplier_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(
        ?Supplier $supplier,
        CrudDeleter $handler,
    ): Response {
        return $handler->deleteConfirm(self::SECTION, $supplier);
    }

    #[Route(path: '/supplier/{id}/delete', name: 'app_supplier_delete', methods: ['POST'])]
    public function delete(
        Supplier $supplier,
        CrudDeleter $handler,
    ): Response {
        return $handler->delete(self::SECTION, $supplier);
    }
}
