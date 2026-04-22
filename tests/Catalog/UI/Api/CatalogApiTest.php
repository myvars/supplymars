<?php

declare(strict_types=1);

namespace App\Tests\Catalog\UI\Api;

use App\Tests\Shared\Factory\CategoryFactory;
use App\Tests\Shared\Factory\ManufacturerFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\SubcategoryFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class CatalogApiTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testProductIndexReturnsCollectionWithMeta(): void
    {
        ProductFactory::createMany(3);

        $this->browser()
            ->get('/api/v1/catalog/products')
            ->assertSuccessful()
            ->assertJson()
            ->assertJsonMatches('length(data)', 3)
            ->assertJsonMatches('"meta"."page"', 1)
            ->assertJsonMatches('"meta"."total"', 3);
    }

    public function testProductIndexIncludesPaginationLinks(): void
    {
        ProductFactory::createMany(3);

        $this->browser()
            ->get('/api/v1/catalog/products?limit=2')
            ->assertSuccessful()
            ->assertJson()
            ->assertJsonMatches('length(data)', 2)
            ->assertJsonMatches('"meta"."total"', 3)
            ->assertJsonMatches('contains("links"."self", \'limit=2\')', true)
            ->assertJsonMatches('"links"."next" != null', true)
            ->assertJsonMatches('"links"."prev"', null);
    }

    public function testProductIndexPage2HasPrevLink(): void
    {
        ProductFactory::createMany(3);

        $this->browser()
            ->get('/api/v1/catalog/products?page=2&limit=2')
            ->assertSuccessful()
            ->assertJson()
            ->assertJsonMatches('length(data)', 1)
            ->assertJsonMatches('"links"."prev" != null', true)
            ->assertJsonMatches('"links"."next"', null);
    }

    public function testProductShowReturnsItem(): void
    {
        $product = ProductFactory::createOne();

        $this->browser()
            ->get('/api/v1/catalog/products/' . $product->getPublicId()->value())
            ->assertSuccessful()
            ->assertJson()
            ->assertJsonMatches('"data"."id"', $product->getPublicId()->value())
            ->assertJsonMatches('"data"."name"', $product->getName());
    }

    public function testCategoryIndexReturnsCollection(): void
    {
        CategoryFactory::createMany(2);

        $this->browser()
            ->get('/api/v1/catalog/categories')
            ->assertSuccessful()
            ->assertJson()
            ->assertJsonMatches('length(data)', 2)
            ->assertJsonMatches('"meta"."page"', 1);
    }

    public function testCategoryIndexIncludesPaginationLinks(): void
    {
        CategoryFactory::createMany(2);

        $this->browser()
            ->get('/api/v1/catalog/categories')
            ->assertSuccessful()
            ->assertJson()
            ->assertJsonMatches('"links"."self" != null', true);
    }

    public function testCategoryShowIncludesSubcategories(): void
    {
        $category = CategoryFactory::createOne();
        SubcategoryFactory::createMany(2, ['category' => $category]);

        $this->browser()
            ->get('/api/v1/catalog/categories/' . $category->getPublicId()->value())
            ->assertSuccessful()
            ->assertJson()
            ->assertJsonMatches('"data"."id"', $category->getPublicId()->value())
            ->assertJsonMatches('length("data"."subcategories")', 2);
    }

    public function testSubcategoryIndexReturnsCollection(): void
    {
        SubcategoryFactory::createMany(2);

        $this->browser()
            ->get('/api/v1/catalog/subcategories')
            ->assertSuccessful()
            ->assertJson()
            ->assertJsonMatches('length(data)', 2);
    }

    public function testSubcategoryIndexFiltersByCategory(): void
    {
        $category = CategoryFactory::createOne();
        SubcategoryFactory::createMany(2, ['category' => $category]);
        SubcategoryFactory::createOne(); // different category

        $this->browser()
            ->get('/api/v1/catalog/subcategories?category=' . $category->getPublicId()->value())
            ->assertSuccessful()
            ->assertJson()
            ->assertJsonMatches('length(data)', 2);
    }

    public function testManufacturerIndexReturnsCollection(): void
    {
        ManufacturerFactory::createMany(2);

        $this->browser()
            ->get('/api/v1/catalog/manufacturers')
            ->assertSuccessful()
            ->assertJson()
            ->assertJsonMatches('length(data)', 2);
    }

    public function testManufacturerShowReturnsItem(): void
    {
        $manufacturer = ManufacturerFactory::createOne();

        $this->browser()
            ->get('/api/v1/catalog/manufacturers/' . $manufacturer->getPublicId()->value())
            ->assertSuccessful()
            ->assertJson()
            ->assertJsonMatches('"data"."id"', $manufacturer->getPublicId()->value());
    }

    public function testCatalogEndpointsArePubliclyAccessible(): void
    {
        ProductFactory::createOne();

        // No authentication needed for catalog endpoints
        $this->browser()
            ->get('/api/v1/catalog/products')
            ->assertSuccessful();
    }
}
