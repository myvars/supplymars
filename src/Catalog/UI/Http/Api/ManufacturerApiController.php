<?php

namespace App\Catalog\UI\Http\Api;

use App\Catalog\Application\Search\ManufacturerSearchCriteria;
use App\Catalog\Domain\Model\Manufacturer\Manufacturer;
use App\Catalog\Domain\Repository\ManufacturerRepository;
use App\Catalog\UI\Http\Api\Resource\ManufacturerResource;
use App\Shared\Infrastructure\Persistence\Search\Paginator;
use App\Shared\UI\Http\Api\AbstractApiController;
use App\Shared\UI\Http\Api\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/catalog')]
#[OA\Tag(name: 'Catalog - Manufacturers')]
class ManufacturerApiController extends AbstractApiController
{
    #[Route('/manufacturers', name: 'api_catalog_manufacturer_index', methods: ['GET'])]
    #[OA\Get(summary: 'List manufacturers')]
    public function index(
        Request $request,
        ManufacturerRepository $manufacturers,
        Paginator $paginator,
        #[MapQueryString] ManufacturerSearchCriteria $criteria = new ManufacturerSearchCriteria(),
    ): JsonResponse {
        $pager = $paginator->searchPagination($manufacturers, $criteria);

        return ApiResponse::collection(
            pager: $pager,
            resource: ManufacturerResource::class,
            request: $request
        );
    }

    #[Route('/manufacturers/{id}', name: 'api_catalog_manufacturer_show', methods: ['GET'])]
    #[OA\Get(summary: 'Get a manufacturer')]
    public function show(
        #[ValueResolver('public_id')] Manufacturer $manufacturer,
    ): JsonResponse {
        $resource = ManufacturerResource::fromEntity($manufacturer);

        return ApiResponse::item($resource->toArray());
    }
}
