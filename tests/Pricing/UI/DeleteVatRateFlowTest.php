<?php

declare(strict_types=1);

namespace App\Tests\Pricing\UI;

use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\Factory\VatRateFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

final class DeleteVatRateFlowTest extends WebTestCase
{
    use HasBrowser;

    public function testDeleteViaConfirmFlow(): void
    {
        $vatRate = VatRateFactory::createOne(['name' => 'To Be Deleted VAT']);
        $publicId = $vatRate->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asSuperAdmin()->create())
            ->get('/vat-rate/' . $publicId . '/delete/confirm')
            ->click('Delete VAT Rate')
            ->assertOn('/vat-rate/')
            ->assertSee('VAT rate deleted');

        VatRateFactory::assert()->notExists(['publicId' => $publicId]);
    }

    public function testStaffIsRefused(): void
    {
        $vatRate = VatRateFactory::createOne();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/vat-rate/' . $vatRate->getPublicId()->value() . '/delete/confirm')
            ->click('Delete VAT Rate')
            ->assertSee('Deleting is disabled for this user.');

        VatRateFactory::assert()->exists(['id' => $vatRate->getId()]);
    }
}
