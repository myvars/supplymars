<?php

namespace App\Tests\Catalog\UI;

use App\Tests\Shared\Factory\SubcategoryFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class DeleteSubcategoryFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testDeleteViaConfirmFlow(): void
    {
        $subcategory = SubcategoryFactory::createOne(['name' => 'To Be Deleted']);
        $publicId = $subcategory->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/subcategory/'.$publicId.'/delete/confirm')
            ->click('Delete Subcategory')
            ->assertOn('/subcategory/')
            ->assertSee('Subcategory deleted');
    }
}
