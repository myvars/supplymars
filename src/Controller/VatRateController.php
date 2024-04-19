<?php

namespace App\Controller;

use App\Entity\VatRate;
use App\Form\VatRateType;
use App\Repository\VatRateRepository;
use App\Service\CrudHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/vat-rate')]
class VatRateController extends AbstractController
{
    public const SECTION = 'VAT Rate';
    public const COLUMNS = 2;

    public function __construct(private readonly CrudHelper $crudHelper)
    {
        $this->crudHelper->setSection(self::SECTION);
    }

    #[Route('/', name: 'app_vat_rate_index', methods: ['GET'])]
    public function index(VatRateRepository $vatRateRepository): Response
    {
        $sortOptions = ['id', 'name'];

        return $this->crudHelper->renderIndex(
            $vatRateRepository,
            $sortOptions
        );
    }

    #[Route('/new', name: 'app_vat_rate_new', methods: ['GET', 'POST'])]
    public function new(): Response
    {
        return $this->crudHelper->renderCreate(
            new VatRate(),
            VatRateType::class,
            self::COLUMNS
        );
    }

    #[Route('/{id}', name: 'app_vat_rate_show', methods: ['GET'])]
    public function show(?VatRate $vatRate): Response
    {
        return $this->crudHelper->renderShow($vatRate);
    }

    #[Route('/{id}/edit', name: 'app_vat_rate_edit', methods: ['GET', 'POST'])]
    public function edit(?VatRate $vatRate): Response
    {
        return $this->crudHelper->renderUpdate(
            $vatRate,
            VatRateType::class,
            self::COLUMNS
        );
    }

    #[Route('/{id}/delete/confirm', name: 'app_vat_rate_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(?VatRate $vatRate): Response
    {
        return $this->crudHelper->renderDeleteConfirm($vatRate);
    }

    #[Route('/{id}/delete', name: 'app_vat_rate_delete', methods: ['POST'])]
    public function delete(?VatRate $vatRate): Response
    {
        return $this->crudHelper->renderDelete($vatRate);
    }
}