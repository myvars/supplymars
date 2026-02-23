<?php

namespace App\Catalog\UI\Http\Api;

use App\Catalog\Application\Search\SubcategorySearchCriteria;
use App\Catalog\Domain\Model\Category\CategoryPublicId;
use App\Catalog\Domain\Model\Subcategory\Subcategory;
use App\Catalog\Domain\Repository\SubcategoryRepository;
use App\Catalog\UI\Http\Api\Resource\SubcategoryResource;
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
#[OA\Tag(name: 'Catalog - Subcategories')]
class SubcategoryApiController extends AbstractApiController
{
    #[Route('/subcategories', name: 'api_catalog_subcategory_index', methods: ['GET'])]
    #[OA\Get(summary: 'List subcategories')]
    public function index(
        Request $request,
        SubcategoryRepository $subcategories,
        Paginator $paginator,
        #[MapQueryString] SubcategorySearchCriteria $criteria = new SubcategorySearchCriteria(),
    ): JsonResponse {
        $criteria->categoryId = $this->resolveFilterId($request, 'category', CategoryPublicId::class);

        $pager = $paginator->searchPagination($subcategories, $criteria);

        return ApiResponse::collection(
            pager: $pager,
            resource: SubcategoryResource::class,
            request: $request
        );
    }

    #[Route('/subcategories/{id}', name: 'api_catalog_subcategory_show', methods: ['GET'])]
    #[OA\Get(summary: 'Get a subcategory')]
    public function show(
        #[ValueResolver('public_id')] Subcategory $subcategory,
    ): JsonResponse {
        $resource = SubcategoryResource::fromEntity($subcategory);

        return ApiResponse::item($resource->toArray());
    }
}
