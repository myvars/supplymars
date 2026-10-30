<?php

declare(strict_types=1);

namespace App\Tests\Catalog\UI;

use App\Tests\Shared\Factory\ManufacturerFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\UI\InlineRename;
use App\Tests\Shared\UI\PostedRecordId;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

final class UpdateManufacturerFlowTest extends WebTestCase
{
    use HasBrowser;
    use PostedRecordId;
    use InlineRename;

    public function testEditFormUpdatesAndRedirects(): void
    {
        $manufacturer = ManufacturerFactory::createOne(['name' => 'Before', 'isActive' => false]);
        $publicId = $manufacturer->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/manufacturer/' . $publicId . '/edit')
            ->fillField('manufacturer[name]', 'After')
            ->checkField('manufacturer[isActive]')
            ->click('Update Manufacturer')
            ->assertOn('/manufacturer/' . $publicId)
            ->assertSee('Manufacturer updated');
    }

    public function testEditFormValidationError(): void
    {
        $manufacturer = ManufacturerFactory::createOne();
        $publicId = $manufacturer->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/manufacturer/' . $publicId . '/edit')
            ->fillField('manufacturer[name]', '')
            ->click('Update Manufacturer')
            ->assertOn('/manufacturer/' . $publicId . '/edit')
            ->assertSee('Please enter a manufacturer name');
    }

    public function testInlineEditRejectsOverLengthName(): void
    {
        $entity = ManufacturerFactory::createOne(['name' => 'Old Value']);

        $this->assertInlineEditRefused(
            $this->browser()->actingAs(UserFactory::new()->asStaff()->create()),
            '/manufacturer/' . $entity->getPublicId()->value() . '/inline/name',
            str_repeat('a', 256),
            'Max 255 characters',
        );

        self::assertSame('Old Value', $entity->getName());
    }

    public function testInlineEditRejectsBlankName(): void
    {
        $entity = ManufacturerFactory::createOne(['name' => 'Old Value']);

        $this->assertInlineEditRefused(
            $this->browser()->actingAs(UserFactory::new()->asStaff()->create()),
            '/manufacturer/' . $entity->getPublicId()->value() . '/inline/name',
            '',
            'Please enter a manufacturer name',
        );

        self::assertSame('Old Value', $entity->getName());
    }

    public function testInlineRenameRefreshesThePage(): void
    {
        $entity = ManufacturerFactory::createOne(['name' => 'Old Value']);
        $publicId = $entity->getPublicId()->value();

        $this->assertInlineRenameRefreshes(
            $this->browser()->actingAs(UserFactory::new()->asStaff()->create()),
            '/manufacturer/' . $publicId . '/inline/name',
            '/manufacturer/' . $publicId,
        );
    }

    #[DataProvider('postedRecordIds')]
    public function testEditRefusesAManufacturerIdPostedInTheForm(string $postedId): void
    {
        $other = ManufacturerFactory::createOne(['name' => 'Other']);
        $manufacturer = ManufacturerFactory::createOne(['name' => 'Before Edit']);

        $this->assertRefusesPostedRecordId(
            $this->browser()->actingAs(UserFactory::new()->asStaff()->create()),
            '/manufacturer/' . $manufacturer->getPublicId()->value() . '/edit',
            'Update Manufacturer',
            'id',
            $postedId,
            $other->getPublicId()->value(),
            ['name' => 'Renamed'],
        );

        ManufacturerFactory::assert()->exists(['id' => $other->getId(), 'name' => 'Other']);
        ManufacturerFactory::assert()->exists(['id' => $manufacturer->getId(), 'name' => 'Before Edit']);
    }
}
