<?php

namespace App\Service\Product;

use App\Enum\PriceModel;

class MarkupCalculator
{
    public function calculateMarkupFromSellPrice(
        string $cost,
        string $sellPrice
    ): string {
        if (1 !== bccomp($cost, '0', 2)) {
            throw new \InvalidArgumentException('Cost must be greater than 0.');
        }

        if (1 !== bccomp($sellPrice, $cost, 2)) {
            throw new \InvalidArgumentException('Sell price must be greater than cost.');
        }

        $markupDecimal = bcdiv(bcsub($sellPrice, $cost, 2), $cost, 8);

        return $this->bcround(bcmul($markupDecimal, '100', 4), 3);
    }

    public function calculateSellPrice(
        string $cost,
        string $markup
    ): string {
        if (1 !== bccomp($cost, '0', 2)) {
            throw new \InvalidArgumentException('Cost must be greater than 0.');
        }

        if (-1 === bccomp($markup, '0', 2)) {
            throw new \InvalidArgumentException('Markup must not be negative.');
        }

        $markupDecimal = bcadd('1', bcdiv($markup, '100', 8), 8);

        return $this->bcround(bcmul($cost, $markupDecimal, 3), 2);
    }

    public function calculateSellPriceIncVat(
        string $cost,
        string $markup,
        string $vatRate
    ): string {
        if (-1 === bccomp($vatRate, '0', 2)) {
            throw new \InvalidArgumentException('VAT rate must not be negative.');
        }

        $sellPrice = $this->calculateSellPrice($cost, $markup);
        $vatMultiplier = $this->getVatMultiplier($vatRate);

        return $this->bcround(bcmul($sellPrice, $vatMultiplier, 3), 2);
    }

    public function calculateSellPriceBeforeVat(
        string $sellPriceIncVat,
        string $vatRate
    ): string {
        if (1 !== bccomp($sellPriceIncVat, '0', 2)) {
            throw new \InvalidArgumentException('Sell price (inc VAT) must be greater than 0.');
        }

        if (-1 === bccomp($vatRate, '0', 2)) {
            throw new \InvalidArgumentException('VAT rate must not be negative.');
        }

        $vatMultiplier = $this->getVatMultiplier($vatRate);

        return $this->bcround(bcdiv($sellPriceIncVat, $vatMultiplier, 3), 2);
    }

    public function calculatePrettyPrice(
        string $cost,
        string $markup,
        string $vatRate,
        PriceModel $priceModel
    ): string {
        $sellPriceIncVat = $this->calculateSellPriceIncVat($cost, $markup, $vatRate);

        return $priceModel->getPrettyPrice($sellPriceIncVat);
    }

    public function calculateCustomMarkup(
        string $cost,
        string $sellPriceIncVat,
        string $vatRate
    ): string {
        $sellPriceBeforeVat = $this->calculateSellPriceBeforeVat($sellPriceIncVat, $vatRate);

        return $this->calculateMarkupFromSellPrice($cost, $sellPriceBeforeVat);
    }

    private function getVatMultiplier(string $vatRate): string
    {
        return bcadd('1', bcdiv($vatRate, '100', 4), 4);
    }

    private function bcround(string $number, int $precision): string
    {
        $adjustment = '0.'.str_repeat('0', $precision).'5';
        $number = bcadd($number, $adjustment, $precision + 1);

        return bcmul($number, '1', $precision);
    }
}
