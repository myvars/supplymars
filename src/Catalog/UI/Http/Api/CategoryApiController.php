<?php

namespace App\Catalog\UI\Http\Api;

use App\Catalog\Application\Search\CategorySearchCriteria;
use App\Catalog\Domain\Model\Category\Category;
use App\Catalog\Domain\Repository\CategoryRepository;
use App\Catalog\UI\Http\Api\Resource\CategoryResource;
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
#[OA\Tag(name: 'Catalog - Categories')]
class CategoryApiController extends AbstractApiController
{
    #[Route('/categories', name: 'api_catalog_category_index', methods: ['GET'])]
    #[OA\Get(summary: 'List categories')]
    public function index(
        Request $request,
        CategoryRepository $categories,
        Paginator $paginator,
        #[MapQueryString] CategorySearchCriteria $criteria = new CategorySearchCriteria(),
    ): JsonResponse {
        $pager = $paginator->searchPagination($categories, $criteria);

        return ApiResponse::collection(
            pager: $pager,
            resource: CategoryResource::class,
            request: $request
        );
    }

    #[Route('/categories/{id}', name: 'api_catalog_category_show', methods: ['GET'])]
    #[OA\Get(summary: 'Get a category')]
    public function show(
        #[ValueResolver('public_id')] Category $category,
    ): JsonResponse {
        $resource = CategoryResource::fromEntity($category, includeSubcategories: true);

        return ApiResponse::item($resource->toArray());
    }
}
