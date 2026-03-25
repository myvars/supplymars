<?php

namespace App\Tests\Pricing\Application\Handler\VatRate;

use App\Pricing\Application\Command\VatRate\DeleteVatRate;
use App\Pricing\Application\Handler\VatRate\DeleteVatRateHandler;
use App\Pricing\Domain\Model\VatRate\VatRatePublicId;
use App\Pricing\Domain\Repository\VatRateRepository;
use App\Tests\Shared\Factory\VatRateFactory;
use App\Tests\Shared\Story\SuperAdminUserStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

final class DeleteVatRateHandlerTest extends KernelTestCase
{
    use Factories;

    private DeleteVatRateHandler $handler;

    private VatRateRepository $rates;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(DeleteVatRateHandler::class);
        $this->rates = self::getContainer()->get(VatRateRepository::class);
    }

    #[WithStory(SuperAdminUserStory::class)]
    public function testDeletesExistingVatRate(): void
    {
        $vatRate = VatRateFactory::createOne();
        $publicId = $vatRate->getPublicId();

        $command = new DeleteVatRate($publicId);

        $result = ($this->handler)($command);

        self::assertTrue($result->ok);
        self::assertSame('VAT rate deleted', $result->message);
        self::assertNull($this->rates->getByPublicId($publicId));
    }

    #[WithStory(SuperAdminUserStory::class)]
    public function testFailsWhenVatRateNotFound(): void
    {
        $missingId = VatRatePublicId::new();

        $command = new DeleteVatRate($missingId);

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('VAT rate not found', $result->message);
    }
}
