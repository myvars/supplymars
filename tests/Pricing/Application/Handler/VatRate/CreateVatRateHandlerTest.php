<?php

namespace App\Tests\Pricing\Application\Handler\VatRate;

use App\Pricing\Application\Command\VatRate\CreateVatRate;
use App\Pricing\Application\Handler\VatRate\CreateVatRateHandler;
use App\Pricing\Domain\Repository\VatRateRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CreateVatRateHandlerTest extends KernelTestCase
{
    private CreateVatRateHandler $handler;

    private VatRateRepository $rates;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(CreateVatRateHandler::class);
        $this->rates = self::getContainer()->get(VatRateRepository::class);
    }

    public function testHandleCreatesVatRate(): void
    {
        $command = new CreateVatRate(
            name: 'New VAT',
            rate: '12.50'
        );

        $result = ($this->handler)($command);

        self::assertTrue($result->ok);
        self::assertSame('VAT rate created', $result->message);

        $id = $result->payload;
        $persisted = $this->rates->getByPublicId($id);
        self::assertSame('New VAT', $persisted->getName());
        self::assertSame('12.50', $persisted->getRate());
    }
}
