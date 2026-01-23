<?php

namespace App\Shared\Domain\ValueObject;

use App\Pricing\Domain\Model\VatRate\VatRate;

enum ShippingMethod: string
{
    case THREE_DAY = 'THREE_DAY';
    case NEXT_DAY = 'NEXT_DAY';

    public function getName(): string
    {
        return match ($this) {
            self::THREE_DAY => 'Three Day Shipping',
            self::NEXT_DAY => 'Next Day Shipping',
        };
    }

    /**
     * @return numeric-string
     */
    public function getPrice(): string
    {
        return match ($this) {
            self::THREE_DAY => '3.99',
            self::NEXT_DAY => '9.99',
        };
    }

    /**
     * @return numeric-string
     */
    public function getPriceIncVat(VatRate $vatRate): string
    {
        $rate = $vatRate->getRate() ?? '0.00';

        if (1 !== bccomp($rate, '0', 2)) {
            throw new \InvalidArgumentException('VAT Rate must be greater than 0');
        }

        $vatMultiplier = bcadd('1', bcdiv($rate, '100', 4), 4);

        return $this->bcround(bcmul(self::getPrice(), $vatMultiplier, 3), 2);
    }

    public function getDueDate(): \DateTimeImmutable
    {
        return match ($this) {
            self::THREE_DAY => $this->calculateDueDate(3),
            self::NEXT_DAY => $this->calculateDueDate(1),
        };
    }

    public function calculateDueDate(int $days): \DateTimeImmutable
    {
        if ($days < 1) {
            throw new \InvalidArgumentException('Days must be greater than 0');
        }

        try {
            $date = new \DateTimeImmutable(sprintf('+%d days', $days));
        } catch (\Exception) {
            throw new \InvalidArgumentException('Invalid date');
        }

        return $date;
    }

    /**
     * @param numeric-string $number
     *
     * @return numeric-string
     */
    private function bcround(string $number, int $precision): string
    {
        /** @var numeric-string $adjustment @phpstan-ignore varTag.nativeType */
        $adjustment = '0.' . str_repeat('0', $precision) . '5';
        $number = bcadd($number, $adjustment, $precision + 1);

        return bcmul($number, '1', $precision);
    }
}
