<?php

namespace App\Tests\Reporting\Integration;

use App\Reporting\Application\Search\OverdueOrderSearchCriteria;
use App\Reporting\Domain\Metric\SalesDuration;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class OverdueOrderSearchDtoIntegrationTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidOverdueOrderSearchDto(): void
    {
        $dto = new OverdueOrderSearchCriteria();
        $dto->setSort('dueDate');
        $dto->setSortDirection('ASC');
        $dto->setDuration(SalesDuration::LAST_7->value);

        $errors = $this->validator->validate($dto);
        $this->assertCount(0, $errors);
    }
}
