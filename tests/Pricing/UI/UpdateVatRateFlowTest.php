<?php

declare(strict_types=1);

namespace App\Tests\Pricing\UI;

use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\Factory\VatRateFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class UpdateVatRateFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testSuccessfulEditViaForm(): void
    {
        $vatRate = VatRateFactory::createOne();
        $publicId = $vatRate->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/vat-rate/' . $publicId . '/edit')
            ->fillField('vat_rate[name]', 'After Edit VAT')
            ->fillField('vat_rate[rate]', '7.25')
            ->click('Update VAT Rate')
            ->assertOn('/vat-rate/' . $publicId)
            ->assertSee('After Edit VAT');
    }

    public function testValidationErrorOnEmptyName(): void
    {
        $vatRate = VatRateFactory::createOne();
        $publicId = $vatRate->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/vat-rate/' . $publicId . '/edit')
            ->fillField('vat_rate[name]', '')
            ->click('Update VAT Rate')
            ->assertOn('/vat-rate/' . $publicId . '/edit')
            ->assertSee('Please enter a VAT rate name');
    }

    public function testInlineEditRejectsOverLengthName(): void
    {
        $entity = VatRateFactory::createOne(['name' => 'Old Value']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/vat-rate/' . $entity->getPublicId()->value() . '/inline/name?edit=1')
            ->fillField('inline_field[value]', str_repeat('a', 256))
            ->click('Save (Enter)')
            ->assertStatus(422)
            ->assertSeeIn('form', 'Max 255 characters') // the field's error, rendered inside the form
            ->assertSeeElement('input[name="inline_field[value]"]');

        self::assertSame('Old Value', $entity->getName());
    }

    public function testInlineEditRejectsBlankName(): void
    {
        $entity = VatRateFactory::createOne(['name' => 'Old Value']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/vat-rate/' . $entity->getPublicId()->value() . '/inline/name?edit=1')
            ->fillField('inline_field[value]', '')
            ->click('Save (Enter)')
            ->assertStatus(422)
            ->assertSeeIn('form', 'Please enter a VAT rate name');

        self::assertSame('Old Value', $entity->getName());
    }

    public function testInlineEditUpdatesTheBreadcrumb(): void
    {
        $entity = VatRateFactory::createOne(['name' => 'Old Value']);
        $publicId = $entity->getPublicId()->value();
        $frameId = 'inline-edit-vat-rate-' . $publicId . '-name';

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/vat-rate/' . $publicId)
            ->assertSeeElement('nav[aria-label="breadcrumb"] [data-inline-edit-mirror="' . $frameId . '"]')
            ->get('/vat-rate/' . $publicId . '/inline/name?edit=1')
            ->fillField('inline_field[value]', 'Renamed Value')
            ->click('Save (Enter)')
            ->assertSuccessful()
            ->assertContains('targets="[data-inline-edit-mirror=\'' . $frameId . '\']"')
            ->assertContains('<template>Renamed Value</template>');
    }
}
