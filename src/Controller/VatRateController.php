<?php

namespace App\Controller;

use App\Entity\VatRate;
use App\Form\VatRateType;
use App\Repository\VatRateRepository;
use App\Service\CrudHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/vat-rate')]
class VatRateController extends AbstractController
{
    public const string SECTION = 'VAT Rate';
    public const int FORM_COLUMNS = 2;

    public function __construct(private readonly CrudHelper $crudHelper)
    {
        $this->crudHelper->setSection(self::SECTION);
        $this->crudHelper->setFormColumns(self::FORM_COLUMNS);
    }

    #[Route('/', name: 'app_vat_rate_index', methods: ['GET'])]
    public function index(
        VatRateRepository $vatRateRepository,
        #[MapQueryParameter] int $page = 1,
        #[MapQueryParameter] int $limit = 10,
        #[MapQueryParameter] string $sort = 'id',
        #[MapQueryParameter] string $sortDirection = 'ASC',
        #[MapQueryParameter] ?string $query = null,
    ): Response {
        $validSorts = ['id', 'name'];
        $sort = in_array($sort, $validSorts) ? $sort : 'id';

        return $this->crudHelper->renderIndex(
            $vatRateRepository->findBySearchQueryBuilder($query, $sort, $sortDirection),
            $page,
            $limit,
            $sort,
            $sortDirection,
            $query,
        );
    }

    #[Route('/new', name: 'app_vat_rate_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        return $this->crudHelper->renderCreate(
            $request,
            new VatRate(),
            VatRateType::class,
        );
    }

    #[Route('/{id}', name: 'app_vat_rate_show', methods: ['GET'])]
    public function show(?VatRate $vatRate): Response
    {
        return $this->crudHelper->renderShow($vatRate);
    }

    #[Route('/{id}/edit', name: 'app_vat_rate_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ?VatRate $vatRate): Response
    {
        return $this->crudHelper->renderUpdate(
            $request,
            $vatRate,
            VatRateType::class,
        );
    }

    #[Route('/{id}/delete', name: 'app_vat_rate_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(?VatRate $vatRate): Response
    {
        return $this->crudHelper->renderDeleteConfirm($vatRate);
    }

    #[Route('/{id}', name: 'app_vat_rate_delete', methods: ['POST'])]
    public function delete(Request $request, ?VatRate $vatRate): Response
    {
        return $this->crudHelper->renderDelete(
            $request,
            $vatRate,
        );
    }
}
