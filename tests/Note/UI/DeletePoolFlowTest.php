<?php

namespace App\Tests\Note\UI;

use App\Tests\Shared\Factory\PoolFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class DeletePoolFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testDeleteFromConfirmRemovesAndRedirects(): void
    {
        $pool = PoolFactory::createOne();
        $publicId = $pool->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/note/pool/' . $publicId . '/delete/confirm')
            ->click('Delete')
            ->assertOn('/note/pool/')
            ->assertSee('Pool deleted');
    }

    public function testDeleteEmptyPoolRemovesCleanly(): void
    {
        $pool = PoolFactory::createOne();
        $publicId = $pool->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/note/pool/' . $publicId . '/delete/confirm')
            ->click('Delete')
            ->assertOn('/note/pool/')
            ->assertSee('Pool deleted');

        PoolFactory::assert()->notExists(['publicId' => $publicId]);
    }
}
