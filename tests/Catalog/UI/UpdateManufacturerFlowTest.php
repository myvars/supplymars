<?php

declare(strict_types=1);

namespace App\Tests\Catalog\UI;

use App\Tests\Shared\Factory\ManufacturerFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

final class UpdateManufacturerFlowTest extends WebTestCase
{
    use HasBrowser;

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

        self::assertSame('After', $manufacturer->getName());
        self::assertTrue($manufacturer->isActive());
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

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/manufacturer/' . $entity->getPublicId()->value() . '/inline/name?edit=1')
            ->fillField('inline_field[value]', str_repeat('a', 256))
            ->click('Save (Enter)')
            ->assertStatus(422)
            ->assertSeeIn('form', 'Max 255 characters') // the field's error, rendered inside the form
            ->assertSeeElement('input[name="inline_field[value]"]');

        self::assertSame('Old Value', $entity->getName());
    }

    public function testInlineEditRejectsBlankName(): void
    {
        $entity = ManufacturerFactory::createOne(['name' => 'Old Value']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/manufacturer/' . $entity->getPublicId()->value() . '/inline/name?edit=1')
            ->fillField('inline_field[value]', '')
            ->click('Save (Enter)')
            ->assertStatus(422)
            ->assertSeeIn('form', 'Please enter a manufacturer name');

        self::assertSame('Old Value', $entity->getName());
    }

    public function testInlineRenameRefreshesThePage(): void
    {
        $entity = ManufacturerFactory::createOne(['name' => 'Old Value']);
        $publicId = $entity->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/manufacturer/' . $publicId . '/inline/name?edit=1')
            ->fillField('inline_field[value]', 'Renamed Value')
            ->click('Save (Enter)')
            ->assertSuccessful()
            ->assertContains('<turbo-stream action="refresh" scroll="preserve"></turbo-stream>')
            ->assertNotContains('Renamed Value')
            ->get('/manufacturer/' . $publicId)
            ->assertSeeIn('nav[aria-label="breadcrumb"]', 'Renamed Value');
    }
}
