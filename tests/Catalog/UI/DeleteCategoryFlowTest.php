<?php

declare(strict_types=1);

namespace App\Tests\Catalog\UI;

use App\Tests\Shared\Factory\CategoryFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

final class DeleteCategoryFlowTest extends WebTestCase
{
    use HasBrowser;

    public function testDeleteViaConfirmFlow(): void
    {
        $category = CategoryFactory::createOne(['name' => 'To Be Deleted']);
        $publicId = $category->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/category/' . $publicId . '/delete/confirm')
            ->click('Delete Category')
            ->assertOn('/category/')
            ->assertSee('Category deleted');

        CategoryFactory::assert()->notExists(['publicId' => $publicId]);
    }
}
