<?php

declare(strict_types=1);

namespace App\Tests\Pricing\UI;

use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\Factory\VatRateFactory;
use App\Tests\Shared\UI\InlineRename;
use App\Tests\Shared\UI\PostedRecordId;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

final class UpdateVatRateFlowTest extends WebTestCase
{
    use HasBrowser;
    use InlineRename;
    use PostedRecordId;

    #[DataProvider('postedRecordIds')]
    public function testEditRefusesAVatRateIdPostedInTheForm(string $postedId): void
    {
        $other = VatRateFactory::createOne(['name' => 'Other']);
        $vatRate = VatRateFactory::createOne(['name' => 'Before Edit']);

        $this->assertRefusesPostedRecordId(
            $this->browser()->actingAs(UserFactory::new()->asStaff()->create()),
            '/vat-rate/' . $vatRate->getPublicId()->value() . '/edit',
            'Update VAT Rate',
            'id',
            $postedId,
            $other->getPublicId()->value(),
            ['name' => 'Renamed'],
        );

        VatRateFactory::assert()->exists(['id' => $other->getId(), 'name' => 'Other']);
        VatRateFactory::assert()->exists(['id' => $vatRate->getId(), 'name' => 'Before Edit']);
    }

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

        $this->assertInlineEditRefused(
            $this->browser()->actingAs(UserFactory::new()->asStaff()->create()),
            '/vat-rate/' . $entity->getPublicId()->value() . '/inline/name',
            str_repeat('a', 256),
            'Max 255 characters',
        );

        self::assertSame('Old Value', $entity->getName());
    }

    public function testInlineEditRejectsBlankName(): void
    {
        $entity = VatRateFactory::createOne(['name' => 'Old Value']);

        $this->assertInlineEditRefused(
            $this->browser()->actingAs(UserFactory::new()->asStaff()->create()),
            '/vat-rate/' . $entity->getPublicId()->value() . '/inline/name',
            '',
            'Please enter a VAT rate name',
        );

        self::assertSame('Old Value', $entity->getName());
    }

    public function testInlineRenameRefreshesThePage(): void
    {
        $entity = VatRateFactory::createOne(['name' => 'Old Value']);
        $publicId = $entity->getPublicId()->value();

        $this->assertInlineRenameRefreshes(
            $this->browser()->actingAs(UserFactory::new()->asStaff()->create()),
            '/vat-rate/' . $publicId . '/inline/name',
            '/vat-rate/' . $publicId,
        );
    }
}
