<?php

namespace App\Tests\Purchasing\UI;

use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class DeleteSupplierFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testDeleteViaConfirmFlow(): void
    {
        $supplier = SupplierFactory::createOne(['name' => 'To Be Deleted']);
        $publicId = $supplier->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asSuperAdmin()->create())
            ->get('/supplier/' . $publicId . '/delete/confirm')
            ->click('Delete Supplier')
            ->assertOn('/supplier/')
            ->assertSee('Supplier deleted');
    }
}
