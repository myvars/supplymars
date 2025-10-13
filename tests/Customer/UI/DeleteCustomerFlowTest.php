<?php

namespace App\Tests\Customer\UI;

use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class DeleteCustomerFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testDeleteViaConfirmFlow(): void
    {
        $customer = UserFactory::createOne(['fullName' => 'To Be Deleted']);
        $publicId = $customer->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/customer/'.$publicId.'/delete/confirm')
            ->click('Delete Customer')
            ->assertOn('/customer/')
            ->assertSee('Customer deleted');
    }
}
