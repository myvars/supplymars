<?php

namespace App\Tests\Note\UI;

use App\Tests\Shared\Factory\PoolFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class UpdatePoolFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testSuccessfulUpdateViaForm(): void
    {
        $pool = PoolFactory::createOne(['name' => 'Original Name']);
        $publicId = $pool->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/note/pool/' . $publicId . '/edit')
            ->fillField('pool[name]', 'Updated Name')
            ->click('Update Pool')
            ->assertSuccessful()
            ->assertSee('Updated Name');
    }

    public function testUpdateDescription(): void
    {
        $pool = PoolFactory::createOne(['description' => 'Old description']);
        $publicId = $pool->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/note/pool/' . $publicId . '/edit')
            ->fillField('pool[description]', 'New description')
            ->click('Update Pool')
            ->assertSuccessful()
            ->assertSee('New description');
    }

    public function testToggleActive(): void
    {
        $pool = PoolFactory::createOne(['isActive' => true]);
        $publicId = $pool->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/note/pool/' . $publicId . '/edit')
            ->uncheckField('pool[isActive]')
            ->click('Update Pool')
            ->assertSuccessful();

        PoolFactory::assert()->exists(['publicId' => $publicId, 'isActive' => false]);
    }

    public function testToggleCustomerVisibility(): void
    {
        $pool = PoolFactory::createOne(['isCustomerVisible' => true]);
        $publicId = $pool->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/note/pool/' . $publicId . '/edit')
            ->uncheckField('pool[isCustomerVisible]')
            ->click('Update Pool')
            ->assertSuccessful();

        PoolFactory::assert()->exists(['publicId' => $publicId, 'isCustomerVisible' => false]);
    }

    public function testValidationErrorOnEmptyName(): void
    {
        $pool = PoolFactory::createOne();
        $publicId = $pool->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/note/pool/' . $publicId . '/edit')
            ->fillField('pool[name]', '')
            ->click('Update Pool')
            ->assertSee('Please enter a pool name');
    }
}
