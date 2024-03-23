<?php

namespace App\Entity;

enum PriceModel: string
{
    case NONE = 'NONE';
    case DEFAULT = 'DEFAULT';
    case PRETTY_00 = 'PRETTY_00';
    case PRETTY_10 = 'PRETTY_10';
    case PRETTY_49 = 'PRETTY_49';
    case PRETTY_95 = 'PRETTY_95';
    case PRETTY_99 = 'PRETTY_99';

    public function getName(): string
    {
        return match ($this) {
            self::NONE => 'None',
            self::DEFAULT => 'Default (Cost+)',
            self::PRETTY_00 => 'Pretty 00',
            self::PRETTY_10 => 'Pretty 10',
            self::PRETTY_49 => 'Pretty 49',
            self::PRETTY_95 => 'Pretty 95',
            self::PRETTY_99 => 'Pretty 99',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::NONE => 'No Price Model',
            self::DEFAULT => 'The default Cost+ price model',
            self::PRETTY_00 => 'A pretty price model with .00 rounding',
            self::PRETTY_10 => 'A pretty price model with .10 rounding',
            self::PRETTY_49 => 'A pretty price model with .49 or .99 rounding',
            self::PRETTY_95 => 'A pretty price model with .95 rounding',
            self::PRETTY_99 => 'A pretty price model with .99 rounding',
        };
    }

    public function getPrettyPrice(string $price): string
    {
        if (1 !== bccomp($price, '0', 2)) {
            throw new \InvalidArgumentException('Price must be greater than 0');
        }

        return match ($this) {
            self::NONE, self::DEFAULT => $price,
            self::PRETTY_00 => $this->pretty00($price),
            self::PRETTY_10 => $this->pretty10($price),
            self::PRETTY_49 => $this->pretty49($price),
            self::PRETTY_95 => $this->pretty95($price),
            self::PRETTY_99 => $this->pretty99($price),
        };
    }

    private function pretty00(string $price): string
    {
        $fraction = bccomp(bcsub($price, bcdiv($price, 1, 0), 2), '0.00', 2) > 0 ? '1.00' : '0.00';

        return bcadd(bcmul($price, '1', 0), $fraction, 2);
    }

    private function pretty10(string $price): string
    {
        $fraction = bccomp(bcsub($price, bcdiv($price, 1, 1), 2), '0.00', 2) > 0 ? '0.10' : '0.00';

        return bcadd(bcmul($price, '1', 1), $fraction, 2);
    }

    private function pretty49(string $price): string
    {
        $fraction = bccomp(bcsub($price, bcdiv($price, 1, 0), 2), '0.50', 2) >= 0 ? '0.99' : '0.49';

        return bcadd(bcmul($price, '1', 0), $fraction, 2);
    }

    private function pretty95(string $price): string
    {
        $fraction = bccomp(bcsub($price, bcdiv($price, 1, 0), 2), '0.95', 2) > 0 ? '1.95' : '0.95';

        return bcadd(bcmul($price, '1', 0), $fraction, 2);
    }

    private function pretty99(string $price): string
    {
        return bcadd(bcmul($price, '1', 0), '0.99', 2);
    }
}
