<?php

namespace App\Purchasing\Domain\Model\Supplier;

enum SupplierColourScheme: string
{
    case Violet = 'violet';
    case Amber = 'amber';
    case Teal = 'teal';
    case Rose = 'rose';

    public function cssPrefix(): string
    {
        return match ($this) {
            self::Violet => 'supplier1',
            self::Amber => 'supplier2',
            self::Teal => 'supplier3',
            self::Rose => 'supplier4',
        };
    }

    public function chartColor(): string
    {
        return match ($this) {
            self::Violet => '#8b5cf6',
            self::Amber => '#f59e0b',
            self::Teal => '#14b8a6',
            self::Rose => '#f43f5e',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Violet => 'Violet',
            self::Amber => 'Amber',
            self::Teal => 'Teal',
            self::Rose => 'Rose',
        };
    }

    public static function getDefault(): self
    {
        return self::Violet;
    }
}
