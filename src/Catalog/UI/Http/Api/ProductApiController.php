<?php

namespace App\Catalog\UI\Http\Api;

use App\Catalog\Application\Search\ProductSearchCriteria;
use App\Catalog\Domain\Model\Category\CategoryPublicId;
use App\Catalog\Domain\Model\Manufacturer\ManufacturerPublicId;
use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Model\Subcategory\SubcategoryPublicId;
use App\Catalog\Domain\Repository\ProductRepository;
use App\Catalog\UI\Http\Api\Resource\ProductListResource;
use App\Catalog\UI\Http\Api\Resource\ProductResource;
use App\Review\Domain\Repository\ReviewSummaryRepository;
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
#[OA\Tag(name: 'Catalog - Products')]
class ProductApiController extends AbstractApiController
{
    #[Route('/products', name: 'api_catalog_product_index', methods: ['GET'])]
    #[OA\Get(summary: 'List products')]
    public function index(
        Request $request,
        ProductRepository $products,
        Paginator $paginator,
        #[MapQueryString] ProductSearchCriteria $criteria = new ProductSearchCriteria(),
    ): JsonResponse {
        $criteria->categoryId = $this->resolveFilterId($request, 'category', CategoryPublicId::class);
        $criteria->subcategoryId = $this->resolveFilterId($request, 'subcategory', SubcategoryPublicId::class);
        $criteria->manufacturerId = $this->resolveFilterId($request, 'manufacturer', ManufacturerPublicId::class);

        $pager = $paginator->searchPagination($products, $criteria);

        return ApiResponse::collection(
            pager: $pager,
            resource: ProductListResource::class,
            request: $request
        );
    }

    #[Route('/products/{id}', name: 'api_catalog_product_show', methods: ['GET'])]
    #[OA\Get(summary: 'Get a product')]
    public function show(
        #[ValueResolver('public_id')] Product $product,
        ReviewSummaryRepository $reviewSummaries,
    ): JsonResponse {
        $reviewSummary = $reviewSummaries->findByProduct($product);
        $resource = ProductResource::fromEntity($product, $reviewSummary);

        return ApiResponse::item($resource->toArray());
    }
}
