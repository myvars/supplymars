<?php

declare(strict_types=1);

namespace App\Tests\Customer\UI;

use App\Tests\Shared\Factory\TicketFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\UI\ActionFormToken;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

use function Zenstruck\Foundry\Persistence\save;

final class DeleteCustomerFlowTest extends WebTestCase
{
    use HasBrowser;
    use ActionFormToken;

    public function testDeleteViaConfirmFlow(): void
    {
        $customer = UserFactory::createOne(['fullName' => 'To Be Deleted']);
        $publicId = $customer->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/customer/' . $publicId . '/delete/confirm')
            ->click('Delete Customer')
            ->assertOn('/customer/')
            ->assertSee('Customer deleted');
    }

    public function testConfirmPageRefusesASuperAdmin(): void
    {
        $superAdmin = UserFactory::new()->asSuperAdmin()->create(['email' => 'super@example.com']);
        self::assertContains('ROLE_ADMIN', $superAdmin->getRoles());

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/customer/' . $superAdmin->getPublicId()->value() . '/delete/confirm')
            ->assertSee('You cannot delete an admin user.')
            ->assertNotSeeElement('form[action="/customer/' . $superAdmin->getPublicId()->value() . '/delete"]');
    }

    public function testDeletingASuperAdminStoredWithoutRoleAdminIsRefused(): void
    {
        $customer = UserFactory::createOne(['fullName' => 'Promoted Later']);
        $publicId = $customer->getPublicId()->value();

        // The confirm page rendered before the account was made a super admin by hand
        $browser = $this->browser()->actingAs(UserFactory::new()->asStaff()->create());
        $token = $this->actionToken($browser, '/customer/' . $publicId . '/delete/confirm', '/customer/' . $publicId . '/delete');

        $promoted = UserFactory::repository()->findOrFail($customer->getId());
        $promoted->setRoles(['ROLE_SUPER_ADMIN']);
        save($promoted);

        $browser
            ->post('/customer/' . $publicId . '/delete', ['body' => ['_token' => $token]])
            ->assertSee('Customer cannot be deleted.');

        UserFactory::assert()->exists(['id' => $customer->getId()]);
    }

    public function testDeletingACustomerWithATicketShowsAnError(): void
    {
        $customer = UserFactory::createOne();
        TicketFactory::createOne(['customer' => $customer]);
        $publicId = $customer->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/customer/' . $publicId . '/delete/confirm')
            ->click('Delete Customer')
            ->assertSuccessful()
            ->assertSee('Customer cannot be deleted.');

        UserFactory::assert()->exists(['id' => $customer->getId()]);
    }
}
