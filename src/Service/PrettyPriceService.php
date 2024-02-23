<?php

namespace App\Service;

class PrettyPriceService
{
    CONST string DEFAULT = 'NONE';
    CONST string PRETTY_00 = 'PRETTY_00';
    CONST string PRETTY_10 = 'PRETTY_10';
    CONST string PRETTY_49 = 'PRETTY_49';
    CONST string PRETTY_95 = 'PRETTY_95';
    CONST string PRETTY_99 = 'PRETTY_99';


    public function fromSellPriceIncVat(string $price, string $modelTag = self::DEFAULT): string
    {
        if (bccomp($price, '0', 2) !== 1) {
            throw new \InvalidArgumentException('Price must be greater than 0');
        }

        switch ($modelTag) {
            case self::DEFAULT:
                // No rounding
                $adjustedPrice = $price;
                break;
            case self::PRETTY_00:
                // Target the next £xx.00
                $fraction = bccomp(bcsub($price, bcdiv($price, 1, 0), 2), '0.00', 2) > 0 ? '1.00' : '0.00';
                $adjustedPrice = bcadd(bcmul($price, '1', 0), $fraction, 2);
                break;
            case self::PRETTY_10:
                // Target the next xx.10
                $fraction = bccomp(bcsub($price, bcdiv($price, 1, 1), 2), '0.00', 2) > 0 ? '0.10' : '0.00';
                $adjustedPrice = bcadd(bcmul($price, '1', 1), $fraction, 2);
                break;
            case self::PRETTY_49:
                // Determine if we should target xx.49 or xx.99 based on the current fraction
                $fraction = bccomp(bcsub($price, bcdiv($price, 1, 0), 2), '0.50', 2) >= 0 ? '0.99' : '0.49';
                $adjustedPrice = bcadd(bcmul($price, '1', 0), $fraction, 2);
                break;
            case self::PRETTY_95:
                // Target the next £xx.95
                $fraction = bccomp(bcsub($price, bcdiv($price, 1, 0), 2), '0.95', 2) >= 0 ? '0.95' : '1.95';
                $adjustedPrice = bcadd(bcmul($price, '1', 0), $fraction, 2);
                break;
            case self::PRETTY_99:
                // Target the next £xx.99
                $adjustedPrice = bcadd(bcmul($price, '1', 0), '0.99', 2);
                break;
            default:
                throw new \LogicException('Unexpected price model');
        }

        return $adjustedPrice;
    }
}