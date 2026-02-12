<?php

namespace App\Tests\Shared\UI\Http\Validation;

use App\Shared\UI\Http\Validation\DateRange;
use App\Shared\UI\Http\Validation\DateRangeValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<DateRangeValidator>
 */
#[AllowMockObjectsWithoutExpectations]
final class DateRangeValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): DateRangeValidator
    {
        return new DateRangeValidator();
    }

    private function makeCriteria(?string $start, ?string $end): TestSearchCriteria
    {
        $criteria = new TestSearchCriteria();
        $criteria->startDate = $start;
        $criteria->endDate = $end;

        return $criteria;
    }

    public function testNoViolationWhenDatesAreValid(): void
    {
        $constraint = new DateRange();
        $this->validator->validate($this->makeCriteria('2024-01-01', '2024-01-31'), $constraint);

        $this->assertNoViolation();
    }

    public function testNoViolationWhenDatesMissing(): void
    {
        $constraint = new DateRange();

        $this->validator->validate($this->makeCriteria(null, '2024-01-31'), $constraint);
        $this->assertNoViolation();

        $this->validator->validate($this->makeCriteria('2024-01-01', null), $constraint);
        $this->assertNoViolation();

        $this->validator->validate($this->makeCriteria(null, null), $constraint);
        $this->assertNoViolation();
    }

    public function testViolationWhenStartAfterEnd(): void
    {
        $constraint = new DateRange();
        $this->validator->validate($this->makeCriteria('2024-02-01', '2024-01-31'), $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path.startDate')
            ->assertRaised();
    }

    public function testThrowsOnWrongValueType(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $constraint = new DateRange();
        $this->validator->validate(new \stdClass(), $constraint);
    }
}
