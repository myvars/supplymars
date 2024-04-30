<?php

namespace App\Controller;

use App\Entity\VatRate;
use App\Form\VatRateType;
use App\Repository\VatRateRepository;
use App\Service\Crud\CrudCreator;
use App\Service\Crud\CrudDeleter;
use App\Service\Crud\CrudIndexer;
use App\Service\Crud\CrudUpdater;
use App\Service\Crud\CrudReader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/vat-rate')]
class VatRateController extends AbstractController
{
    public const SECTION = 'VAT Rate';

    #[Route('/', name: 'app_vat_rate_index', methods: ['GET'])]
    public function index(VatRateRepository $vatRateRepository, CrudIndexer $crudIndexer): Response
    {
        $sortOptions = ['id', 'name'];

        return $crudIndexer->index(self::SECTION, $vatRateRepository, $sortOptions);
    }

    #[Route('/new', name: 'app_vat_rate_new', methods: ['GET', 'POST'])]
    public function new(CrudCreator $crudCreator): Response
    {
        return $crudCreator->create(self::SECTION, new VatRate(), VatRateType::class);
    }

    #[Route('/{id}', name: 'app_vat_rate_show', methods: ['GET'])]
    public function show(?VatRate $vatRate, CrudReader $crudReader): Response
    {
        return $crudReader->read(self::SECTION, $vatRate);
    }

    #[Route('/{id}/edit', name: 'app_vat_rate_edit', methods: ['GET', 'POST'])]
    public function edit(?VatRate $vatRate, CrudUpdater $crudUpdater): Response
    {
        return $crudUpdater->update(self::SECTION, $vatRate, VatRateType::class);
    }

    #[Route('/{id}/delete/confirm', name: 'app_vat_rate_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(?VatRate $vatRate, CrudDeleter $crudDeleter): Response
    {
        return $crudDeleter->deleteConfirm(self::SECTION, $vatRate);
    }

    #[Route('/{id}/delete', name: 'app_vat_rate_delete', methods: ['POST'])]
    public function delete(?VatRate $vatRate, CrudDeleter $crudDeleter): Response
    {
        return $crudDeleter->delete(self::SECTION, $vatRate);
    }
}