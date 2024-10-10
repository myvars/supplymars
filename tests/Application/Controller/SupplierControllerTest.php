<?php

namespace App\Tests\Application\Controller;

use App\Factory\SupplierFactory;
use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

class SupplierControllerTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testIndexSupplier(): void
    {
        SupplierFactory::createMany(3);

        $this->browser()
            ->actingAs(UserFactory::createOne()->_real())
            ->get('/supplier/')
            ->assertSuccessful()
            ->assertSee('Supplier List')
            ->assertSee('3 results');
    }

    public function testShowSupplier(): void
    {
        $supplier = SupplierFactory::createone(['name' => 'Supplier to be shown']);

        $this->browser()
            ->actingAs(UserFactory::createOne()->_real())
            ->get("/supplier/" . $supplier->getId())
            ->assertSuccessful()
            ->assertSee('Supplier to be shown');
    }

    public function testNewSupplier(): void
    {
        $this->browser()
            ->actingAs(UserFactory::createOne()->_real())
            ->get('/supplier/new')
            ->assertSuccessful()
            ->fillField('supplier[name]','Test Supplier')
            ->fillField('supplier[isActive]','1')
            ->click('Create Supplier')
            ->assertOn('/supplier/')
            ->assertSee('Test Supplier');
    }

    public function testNewSupplierValidation(): void
    {
        $this->browser()
            ->actingAs(UserFactory::createOne()->_real())
            ->get('/supplier/new')
            ->assertSuccessful()
            // Intentionally omitting form data or filling it with invalid data
            ->click('Create Supplier')
            ->assertOn('/supplier/new')
            ->assertSee('Please enter a supplier name');
    }

    public function testEditSupplier(): void
    {
        $supplier = SupplierFactory::createone(['name' => 'Supplier to be edited']);

        $this->browser()
            ->actingAs(UserFactory::createOne()->_real())
            ->get("/supplier/" . $supplier->getId() . "/edit")
            ->assertSuccessful()
            ->fillField('supplier[name]','Edited Supplier')
            ->click('Update Supplier')
            ->assertOn('/supplier/')
            ->assertSee('Edited Supplier');
    }

    public function testEditSupplierEditValidation(): void
    {
        $supplier = SupplierFactory::createone(['name' => 'Supplier to be edited']);

        $this->browser()
            ->actingAs(UserFactory::createOne()->_real())
            ->get("/supplier/" . $supplier->getId() . "/edit")
            ->assertSuccessful()
            // Intentionally filling form with invalid data
            ->fillField('supplier[name]','')
            ->click('Update Supplier')
            ->assertOn("/supplier/" . $supplier->getId() . "/edit")
            ->assertSee('Please enter a supplier name');
    }

    public function testDeleteSupplierConfirmation(): void
    {
        $supplier = SupplierFactory::createone(['name' => 'Supplier to be deleted']);

        $this->browser()
            ->actingAs(UserFactory::createOne()->_real())
            ->get("/supplier/" . $supplier->getId() . "/delete/confirm")
            ->assertSuccessful()
            ->assertSee('Are you sure you want to delete this Supplier');
    }

    public function testDeleteSupplier(): void
    {
        $supplier = SupplierFactory::createone(['name' => 'Supplier to be deleted']);

        $this->browser()
            ->actingAs(UserFactory::createOne()->_real())
            ->get("/supplier/" . $supplier->getId() . "/delete/confirm")
            ->assertSuccessful()
            ->click('Delete')
            ->assertOn('/supplier/')
            ->assertNotSee('Supplier to be deleted');
    }

    public function testMissingSupplier(): void
    {
        $this->browser()
            ->actingAs(UserFactory::createOne()->_real())
            ->get("/supplier/999")
            ->assertSee("Sorry, we can't find that Supplier");
    }
}