<?php

declare(strict_types=1);

namespace App\Tests\Catalog\UI;

use App\Tests\Shared\Factory\ManufacturerFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class UpdateManufacturerFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

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

    public function testInlineEditUpdatesTheBreadcrumb(): void
    {
        $entity = ManufacturerFactory::createOne(['name' => 'Old Value']);
        $publicId = $entity->getPublicId()->value();
        $frameId = 'inline-edit-manufacturer-' . $publicId . '-name';

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/manufacturer/' . $publicId)
            ->assertSeeElement('nav[aria-label="breadcrumb"] [data-inline-edit-mirror="' . $frameId . '"]')
            ->get('/manufacturer/' . $publicId . '/inline/name?edit=1')
            ->fillField('inline_field[value]', 'Renamed Value')
            ->click('Save (Enter)')
            ->assertSuccessful()
            ->assertContains('targets="[data-inline-edit-mirror=\'' . $frameId . '\']"')
            ->assertContains('<template>Renamed Value</template>');
    }
}
