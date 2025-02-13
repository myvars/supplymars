<?php

namespace App\Controller;

use App\DTO\SearchDto\VatRateSearchDto;
use App\Entity\VatRate;
use App\Form\VatRateType;
use App\Repository\VatRateRepository;
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

#[Route('/vat-rate')]
#[IsGranted('ROLE_ADMIN')]
class VatRateController extends AbstractController
{
    public const string SECTION = 'VAT Rate';

    #[Route('/', name: 'app_vat_rate_index', methods: ['GET'])]
    public function index(
        Request $request,
        CrudSearcher $handler,
        VatRateRepository $repository,
        #[MapQueryString] VatRateSearchDto $dto = new VatRateSearchDto()
    ): Response {
        return $handler->search(self::SECTION, $dto, $repository, $request->query->all());
    }

    #[Route('/new', name: 'app_vat_rate_new', methods: ['GET', 'POST'])]
    public function new(CrudCreator $handler): Response
    {
        return $handler->create(self::SECTION, new VatRate(), VatRateType::class);
    }

    #[Route('/{id}', name: 'app_vat_rate_show', methods: ['GET'])]
    public function show(
        VatRate $vatRate,
        CrudReader $handler
    ): Response {
        return $handler->read(self::SECTION, $vatRate);
    }

    #[Route('/{id}/edit', name: 'app_vat_rate_edit', methods: ['GET', 'POST'])]
    public function edit(
        VatRate $vatRate,
        CrudUpdater $handler
    ): Response {
        return $handler->update(self::SECTION, $vatRate, VatRateType::class);
    }

    #[Route('/{id}/delete/confirm', name: 'app_vat_rate_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(
        VatRate $vatRate,
        CrudDeleter $handler
    ): Response {
        return $handler->deleteConfirm(self::SECTION, $vatRate);
    }

    #[Route('/{id}/delete', name: 'app_vat_rate_delete', methods: ['POST'])]
    public function delete(
        VatRate $vatRate,
        CrudDeleter $handler
    ): Response {
        return $handler->delete(self::SECTION, $vatRate);
    }
}