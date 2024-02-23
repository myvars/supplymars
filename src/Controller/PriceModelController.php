<?php

namespace App\Controller;

use App\Entity\PriceModel;
use App\Form\PriceModelType;
use App\Repository\PriceModelRepository;
use App\Service\CrudHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/price/model')]
class PriceModelController extends AbstractController
{
    CONST string SECTION = 'Price Model';
    const int FORM_COLUMNS = 1;

    public function __construct(private readonly CrudHelper $crudHelper)
    {
        $this->crudHelper->setSection(self::SECTION);
        $this->crudHelper->setFormColumns(self::FORM_COLUMNS);
    }

    #[Route('/', name: 'app_price_model_index', methods: ['GET'])]
    public function index(
        PriceModelRepository $priceModelRepository,
        #[MapQueryParameter] int $page = 1,
        #[MapQueryParameter] int $limit = 10,
        #[MapQueryParameter] string $sort = 'id',
        #[MapQueryParameter] string $sortDirection = 'ASC',
        #[MapQueryParameter] string $query = null,
    ): Response
    {
        $validSorts = ['id', 'name', 'description', 'modelTag', 'isActive'];
        $sort = in_array($sort, $validSorts) ? $sort : 'id';

        return $this->crudHelper->renderIndex(
            $priceModelRepository->findBySearchQueryBuilder($query, $sort, $sortDirection),
            $page,
            $limit,
            $sort,
            $sortDirection,
            $query,
        );
    }

    #[Route('/new', name: 'app_price_model_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        return $this->crudHelper->renderCreate(
            $request,
            new PriceModel(),
            PriceModelType::class,
        );
    }

    #[Route('/{id}', name: 'app_price_model_show', methods: ['GET'])]
    public function show(?PriceModel $priceModel): Response
    {
        return $this->crudHelper->renderShow($priceModel);
    }

    #[Route('/{id}/edit', name: 'app_price_model_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ?PriceModel $priceModel): Response
    {
        return $this->crudHelper->renderUpdate(
            $request,
            $priceModel,
            PriceModelType::class,
        );
    }

    #[Route('/{id}/delete', name: 'app_price_model_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(?PriceModel $priceModel): Response
    {
        return $this->crudHelper->renderDeleteConfirm($priceModel);
    }

    #[Route('/{id}', name: 'app_price_model_delete', methods: ['POST'])]
    public function delete(Request $request, ?PriceModel $priceModel): Response
    {
        return $this->crudHelper->renderDelete(
            $request,
            $priceModel,
        );
    }

    #[Route('/{id}/test', name: 'app_price_model_test', methods: ['GET'])]
    public function test(): Response
    {

        $cost = '8.00'; // Example cost in smallest currency unit (e.g., cents)
        $vatRate = '20.00'; // Example VAT rate (e.g., 20%)
        $scale = 'high'; // Desired pricing scale
        $initialMarkup = '10.00'; // Example initial markup percentage
        /*
                    $sellPrice = $this->markupCalculator->calculateSellPrice($cost, $initialMarkup);
                    $sellPriceIncVat = $this->markupCalculator->calculateSellPriceIncVat($sellPrice, $vatRate);
                    $prettyPrice = $this->prettyPrice->fromSellPriceIncVat($sellPriceIncVat, $scale);
                    $prettyPriceBeforeVat = $this->markupCalculator->calculateSellPriceBeforeVat($prettyPrice, $vatRate);
                    $requiredMarkup = $this->markupCalculator->markupFromSellPrice($cost, $prettyPriceBeforeVat);*/

        dd($cost, $vatRate, $scale, $initialMarkup, $sellPrice, $sellPriceIncVat, $prettyPrice, $requiredMarkup);

        return new Response("Required Markup for Pretty Price:" . $requiredMarkup . "%");
    }
}