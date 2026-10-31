<?php

declare(strict_types=1);

namespace App\Tests\Catalog\UI;

use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\UI\InlineRename;
use App\Tests\Shared\UI\PostedRecordId;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

final class UpdateProductFlowTest extends WebTestCase
{
    use HasBrowser;
    use InlineRename;
    use PostedRecordId;

    public function testSuccessfulUpdateViaForm(): void
    {
        $user = UserFactory::new()->asStaff()->create();
        $product = ProductFactory::new()->withActiveSource()->create(['isActive' => false]);
        $publicId = $product->getPublicId()->value();

        $this->browser()
            ->actingAs($user)
            ->get('/product/' . $publicId . '/edit')
            ->fillField('product[name]', 'Flow Updated Product')
            ->fillField('product[mfrPartNumber]', 'FLOW-9876')
            ->fillField('product[isActive]', '1')
            ->click('Update Product')
            ->assertOn('/product/' . $publicId)
            ->assertSee('Flow Updated Product');

        self::assertSame('Flow Updated Product', $product->getName());
        self::assertSame('FLOW-9876', $product->getMfrPartNumber());
        self::assertTrue($product->isActive());
    }

    public function testUpdateOfNeverSourcedProduct(): void
    {
        $user = UserFactory::new()->asStaff()->create();
        $product = ProductFactory::createOne();
        $publicId = $product->getPublicId()->value();

        $this->browser()
            ->actingAs($user)
            ->get('/product/' . $publicId . '/edit')
            ->fillField('product[name]', 'Unsourced Product')
            ->click('Update Product')
            ->assertOn('/product/' . $publicId)
            ->assertSee('Unsourced Product');
    }

    public function testValidationErrorsOnEmptySubmission(): void
    {
        $user = UserFactory::new()->asStaff()->create();
        $product = ProductFactory::new()->withActiveSource()->create();
        $publicId = $product->getPublicId()->value();

        $this->browser()
            ->actingAs($user)
            ->get('/product/' . $publicId . '/edit')
            ->fillField('product[name]', '')
            ->fillField('product[mfrPartNumber]', '')
            ->click('Update Product')
            ->assertOn('/product/' . $publicId . '/edit')
            ->assertSee('Please enter a product name')
            ->assertSee('Please enter a manufacturer part number');
    }

    public function testInlineEditRejectsOverLengthName(): void
    {
        $entity = ProductFactory::createOne(['name' => 'Old Value']);

        $this->assertInlineEditRefused(
            $this->browser()->actingAs(UserFactory::new()->asStaff()->create()),
            '/product/' . $entity->getPublicId()->value() . '/inline/name',
            str_repeat('a', 256),
            'Max 255 characters',
        );

        self::assertSame('Old Value', $entity->getName());
    }

    public function testInlineEditRejectsBlankName(): void
    {
        $entity = ProductFactory::createOne(['name' => 'Old Value']);

        $this->assertInlineEditRefused(
            $this->browser()->actingAs(UserFactory::new()->asStaff()->create()),
            '/product/' . $entity->getPublicId()->value() . '/inline/name',
            '',
            'Please enter a product name',
        );

        self::assertSame('Old Value', $entity->getName());
    }

    public function testInlineRenameRefreshesThePage(): void
    {
        $entity = ProductFactory::createOne(['name' => 'Old Value']);
        $publicId = $entity->getPublicId()->value();

        $this->assertInlineRenameRefreshes(
            $this->browser()->actingAs(UserFactory::new()->asStaff()->create()),
            '/product/' . $publicId . '/inline/name',
            '/product/' . $publicId,
        );
    }

    #[DataProvider('postedRecordIds')]
    public function testEditRefusesAProductIdPostedInTheForm(string $postedId): void
    {
        $other = ProductFactory::createOne(['name' => 'Other']);
        $product = ProductFactory::createOne(['name' => 'Before Edit']);

        $this->assertRefusesPostedRecordId(
            $this->browser()->actingAs(UserFactory::new()->asStaff()->create()),
            '/product/' . $product->getPublicId()->value() . '/edit',
            'Update Product',
            'id',
            $postedId,
            $other->getPublicId()->value(),
            ['name' => 'Renamed'],
        );

        ProductFactory::assert()->exists(['id' => $other->getId(), 'name' => 'Other']);
        ProductFactory::assert()->exists(['id' => $product->getId(), 'name' => 'Before Edit']);
    }
}
