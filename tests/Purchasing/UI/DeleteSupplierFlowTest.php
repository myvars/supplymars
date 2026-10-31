<?php

declare(strict_types=1);

namespace App\Tests\Purchasing\UI;

use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

final class DeleteSupplierFlowTest extends WebTestCase
{
    use HasBrowser;

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

        SupplierFactory::assert()->notExists(['publicId' => $publicId]);
    }

    public function testStaffIsRefused(): void
    {
        $supplier = SupplierFactory::createOne();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/supplier/' . $supplier->getPublicId()->value() . '/delete/confirm')
            ->click('Delete Supplier')
            ->assertSee('Deleting is disabled for this user.');

        SupplierFactory::assert()->exists(['id' => $supplier->getId()]);
    }
}
