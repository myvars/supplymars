<?php

declare(strict_types=1);

namespace App\Tests\Purchasing\UI;

use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\UI\InlineRename;
use App\Tests\Shared\UI\PostedRecordId;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

final class UpdateSupplierFlowTest extends WebTestCase
{
    use HasBrowser;
    use InlineRename;
    use PostedRecordId;

    #[DataProvider('postedRecordIds')]
    public function testEditRefusesASupplierIdPostedInTheForm(string $postedId): void
    {
        $other = SupplierFactory::createOne(['name' => 'Other']);
        $supplier = SupplierFactory::createOne(['name' => 'Before Edit']);

        $this->assertRefusesPostedRecordId(
            $this->browser()->actingAs(UserFactory::new()->asStaff()->create()),
            '/supplier/' . $supplier->getPublicId()->value() . '/edit',
            'Update Supplier',
            'id',
            $postedId,
            $other->getPublicId()->value(),
            ['name' => 'Renamed'],
        );

        SupplierFactory::assert()->exists(['id' => $other->getId(), 'name' => 'Other']);
        SupplierFactory::assert()->exists(['id' => $supplier->getId(), 'name' => 'Before Edit']);
    }

    public function testSuccessfulEditViaForm(): void
    {
        $supplier = SupplierFactory::createOne(['name' => 'Before Edit', 'isActive' => true]);
        $publicId = $supplier->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/supplier/' . $publicId . '/edit')
            ->fillField('supplier[name]', 'After Edit')
            ->uncheckField('supplier[isActive]')
            ->click('Update Supplier')
            ->assertOn('/supplier/' . $publicId)
            ->assertSee('After Edit');
    }

    public function testValidationErrorOnEmptyName(): void
    {
        $supplier = SupplierFactory::createOne(['name' => 'Will Fail', 'isActive' => true]);
        $publicId = $supplier->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/supplier/' . $publicId . '/edit')
            ->fillField('supplier[name]', '')
            ->click('Update Supplier')
            ->assertOn('/supplier/' . $publicId . '/edit')
            ->assertSee('Please enter a supplier name');
    }

    public function testInlineEditRejectsOverLengthName(): void
    {
        $supplier = SupplierFactory::createOne(['name' => 'Old Name']);

        $this->assertInlineEditRefused(
            $this->browser()->actingAs(UserFactory::new()->asStaff()->create()),
            '/supplier/' . $supplier->getPublicId()->value() . '/inline/name',
            str_repeat('a', 256),
            'Max 255 characters',
        );

        self::assertSame('Old Name', $supplier->getName());
    }

    public function testInlineEditRejectsBlankName(): void
    {
        $entity = SupplierFactory::createOne(['name' => 'Old Value']);

        $this->assertInlineEditRefused(
            $this->browser()->actingAs(UserFactory::new()->asStaff()->create()),
            '/supplier/' . $entity->getPublicId()->value() . '/inline/name',
            '',
            'Please enter a supplier name',
        );

        self::assertSame('Old Value', $entity->getName());
    }

    public function testInlineRenameRefreshesThePage(): void
    {
        $entity = SupplierFactory::createOne(['name' => 'Old Value']);
        $publicId = $entity->getPublicId()->value();

        $this->assertInlineRenameRefreshes(
            $this->browser()->actingAs(UserFactory::new()->asStaff()->create()),
            '/supplier/' . $publicId . '/inline/name',
            '/supplier/' . $publicId,
        );
    }
}
