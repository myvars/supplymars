<?php

declare(strict_types=1);

namespace App\Tests\Customer\UI;

use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\UI\InlineRename;
use App\Tests\Shared\UI\PostedRecordId;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

final class UpdateCustomerFlowTest extends WebTestCase
{
    use HasBrowser;
    use InlineRename;
    use PostedRecordId;

    #[DataProvider('postedRecordIds')]
    public function testEditRefusesACustomerIdPostedInTheForm(string $postedId): void
    {
        $other = UserFactory::createOne(['fullName' => 'Other']);
        $customer = UserFactory::createOne(['fullName' => 'Before Edit']);

        $this->assertRefusesPostedRecordId(
            $this->browser()->actingAs(UserFactory::new()->asStaff()->create()),
            '/customer/' . $customer->getPublicId()->value() . '/edit',
            'Update Customer',
            'id',
            $postedId,
            $other->getPublicId()->value(),
            ['fullName' => 'Renamed', 'email' => 'renamed@example.com'],
        );

        UserFactory::assert()->exists(['id' => $other->getId(), 'fullName' => 'Other']);
        UserFactory::assert()->exists(['id' => $customer->getId(), 'fullName' => 'Before Edit']);
    }

    public function testSuccessfulEditViaForm(): void
    {
        $actor = UserFactory::new()->asStaff()->create();
        $customer = UserFactory::createOne(['fullName' => 'Before Edit', 'email' => 'before@example.com']);

        $publicId = $customer->getPublicId()->value();

        $this->browser()
            ->actingAs($actor)
            ->get('/customer/' . $publicId . '/edit')
            ->fillField('customer[fullName]', 'After Edit')
            ->fillField('customer[email]', 'after@example.com')
            ->uncheckField('customer[isVerified]')
            ->uncheckField('customer[isStaff]')
            ->click('Update Customer')
            ->assertOn('/customer/' . $publicId)
            ->assertSee('After Edit');
    }

    public function testValidationErrorOnEmptyName(): void
    {
        $actor = UserFactory::new()->asStaff()->create();
        $customer = UserFactory::createOne(['fullName' => 'To Edit', 'email' => 'toedit@example.com']);

        $publicId = $customer->getPublicId()->value();

        $this->browser()
            ->actingAs($actor)
            ->get('/customer/' . $publicId . '/edit')
            ->fillField('customer[fullName]', '')
            ->click('Update Customer')
            ->assertOn('/customer/' . $publicId . '/edit')
            ->assertSee('Please enter a full name');
    }

    public function testRejectsOverLengthEmailAsFieldError(): void
    {
        $customer = UserFactory::createOne(['fullName' => 'Before Edit', 'email' => 'before@example.com']);
        // A valid address of 212 characters, over the 180 the column holds
        $email = 'user@' . implode('.', array_fill(0, 4, str_repeat('a', 50))) . '.com';

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/customer/' . $customer->getPublicId()->value() . '/edit')
            ->fillField('customer[email]', $email)
            ->click('Update Customer')
            ->assertStatus(422)
            ->assertSeeIn('[data-slot="field-error"]', 'Max 180 characters')
            ->assertNotSee('Object(');

        self::assertSame('before@example.com', $customer->getEmail());
    }

    public function testRejectsEmailInUseAsFieldError(): void
    {
        UserFactory::createOne(['email' => 'taken@example.com']);
        $customer = UserFactory::createOne(['fullName' => 'Before Edit', 'email' => 'before@example.com']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/customer/' . $customer->getPublicId()->value() . '/edit')
            ->fillField('customer[email]', 'taken@example.com')
            ->click('Update Customer')
            ->assertStatus(422)
            ->assertSeeIn('[data-slot="field-error"]', 'There is already an account with this email')
            ->assertNotSee('Object(');

        self::assertSame('before@example.com', UserFactory::repository()->find($customer->getId())?->getEmail());
    }

    public function testKeepingOwnEmailIsNotADuplicate(): void
    {
        $customer = UserFactory::createOne(['fullName' => 'Before Edit', 'email' => 'mine@example.com']);
        $publicId = $customer->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/customer/' . $publicId . '/edit')
            ->fillField('customer[fullName]', 'After Edit')
            ->click('Update Customer')
            ->assertOn('/customer/' . $publicId)
            ->assertSee('After Edit');
    }

    public function testInlineEditRejectsOverLengthFullName(): void
    {
        $entity = UserFactory::createOne(['fullName' => 'Old Value']);

        $this->assertInlineEditRefused(
            $this->browser()->actingAs(UserFactory::new()->asStaff()->create()),
            '/customer/' . $entity->getPublicId()->value() . '/inline/fullname',
            str_repeat('a', 51),
            'Max 50 characters',
        );

        self::assertSame('Old Value', $entity->getFullName());
    }

    public function testInlineEditRejectsBlankFullName(): void
    {
        $entity = UserFactory::createOne(['fullName' => 'Old Value']);

        $this->assertInlineEditRefused(
            $this->browser()->actingAs(UserFactory::new()->asStaff()->create()),
            '/customer/' . $entity->getPublicId()->value() . '/inline/fullname',
            '',
            'Please enter a full name',
        );

        self::assertSame('Old Value', $entity->getFullName());
    }

    public function testInlineEditOfStaffNameIsForbiddenForStaffUser(): void
    {
        $staff = UserFactory::new()->asStaff()->create(['fullName' => 'Old Value']);
        $url = '/customer/' . $staff->getPublicId()->value() . '/inline/fullname';

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get($url . '?edit=1')
            ->assertStatus(403)
            ->post($url, ['body' => ['inline_field' => ['value' => 'New Value']]])
            ->assertStatus(403);

        self::assertSame('Old Value', UserFactory::repository()->find($staff->getId())?->getFullName());
    }

    public function testEditOfStaffAccountIsRefusedForStaffUser(): void
    {
        $staff = UserFactory::new()->asStaff()->create(['fullName' => 'Old Value']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/customer/' . $staff->getPublicId()->value() . '/edit')
            ->fillField('customer[fullName]', 'New Value')
            ->click('Update Customer')
            ->assertSee('Staff accounts can be edited only by a super admin.');

        self::assertSame('Old Value', $staff->getFullName());
    }

    public function testStaffAccountShowsNoEditControlsToStaffUser(): void
    {
        $staff = UserFactory::new()->asStaff()->create(['fullName' => 'Staff Member']);
        $customer = UserFactory::createOne(['fullName' => 'Plain Customer']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/customer/' . $staff->getPublicId()->value())
            ->assertSee('Staff Member')
            ->assertElementCount('a[href$="/inline/fullname"], a[href$="/edit"]', 0)
            ->get('/customer/' . $customer->getPublicId()->value())
            ->assertElementCount('a[href$="/edit"]', 1);
    }

    public function testInlineEditOfStaffNameIsAllowedForSuperAdmin(): void
    {
        $staff = UserFactory::new()->asStaff()->create(['fullName' => 'Old Value']);

        $this->browser()
            ->actingAs(UserFactory::new()->asSuperAdmin()->create())
            ->get('/customer/' . $staff->getPublicId()->value() . '/inline/fullname?edit=1')
            ->fillField('inline_field[value]', 'New Value')
            ->click('Save (Enter)')
            ->assertSuccessful();

        self::assertSame('New Value', UserFactory::repository()->find($staff->getId())?->getFullName());
    }

    public function testInlineRenameRefreshesThePage(): void
    {
        $entity = UserFactory::createOne(['fullName' => 'Old Value']);
        $publicId = $entity->getPublicId()->value();

        $this->assertInlineRenameRefreshes(
            $this->browser()->actingAs(UserFactory::new()->asStaff()->create()),
            '/customer/' . $publicId . '/inline/fullname',
            '/customer/' . $publicId,
        );
    }
}
