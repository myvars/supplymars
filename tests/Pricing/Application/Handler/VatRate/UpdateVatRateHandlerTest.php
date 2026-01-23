<?php

namespace App\Tests\Pricing\Application\Handler\VatRate;

use App\Pricing\Application\Command\VatRate\UpdateVatRate;
use App\Pricing\Application\Handler\VatRate\UpdateVatRateHandler;
use App\Pricing\Domain\Model\VatRate\VatRatePublicId;
use App\Pricing\Domain\Repository\VatRateRepository;
use App\Tests\Shared\Factory\VatRateFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

final class UpdateVatRateHandlerTest extends KernelTestCase
{
    use Factories;

    private UpdateVatRateHandler $handler;

    private VatRateRepository $rates;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(UpdateVatRateHandler::class);
        $this->rates = self::getContainer()->get(VatRateRepository::class);
    }

    public function testHandleUpdatesVatRate(): void
    {
        $vatRate = VatRateFactory::createOne();

        $command = new UpdateVatRate(
            id: $vatRate->getPublicId(),
            name: 'Updated VAT',
            rate: '17.50',
        );

        $result = ($this->handler)($command);

        self::assertTrue($result->ok);

        $persisted = $this->rates->getByPublicId($vatRate->getPublicId());
        self::assertSame('Updated VAT', $persisted->getName());
        self::assertSame('17.50', $persisted->getRate());
    }

    public function testFailsWhenVatRateNotFound(): void
    {
        $missingId = VatRatePublicId::new();

        $command = new UpdateVatRate(
            id: $missingId,
            name: 'X',
            rate: '5.00',
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('VAT rate not found', $result->message);
    }

    public function testFailsOnValidationErrors(): void
    {
        $vatRate = VatRateFactory::createOne();

        $command = new UpdateVatRate(
            id: $vatRate->getPublicId(),
            name: 'Valid Name',
            rate: '', // @phpstan-ignore argument.type (intentionally testing invalid input)
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Please enter a VAT rate %', $result->message);
    }
}
