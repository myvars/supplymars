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

#[Route('/supplier')]
#[IsGranted('ROLE_ADMIN')]
class SupplierController extends AbstractController
{
    public const SECTION = 'Supplier';

    #[Route('/', name: 'app_supplier_index', methods: ['GET'])]
    public function index(
        Request $request,
        CrudSearcher $crudSearcher,
        SupplierRepository $repository,
        #[MapQueryString] SupplierSearchDto $dto = new SupplierSearchDto()
    ): Response {
        return $crudSearcher->search(self::SECTION, $dto, $repository, $request->query->all());
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
