<?php

namespace App\Customer\Domain\Model\Address;

enum MarsCity: string
{
    case OLYMPIA = 'Olympia';
    case VALLIS = 'Vallis';
    case GALE = 'Gale';
    case ELYSIUM = 'Elysium';
    case RED_DUNE = 'Red Dune';
    case CRIMSON = 'Crimson';
    case IRONHOLD = 'Ironhold';
    case ARCADIA = 'Arcadia';
    case AMAZONIS = 'Amazonis';
    case HELLAS = 'Hellas';
    case ISIDIS = 'Isidis';
    case NOCTIS = 'Noctis';
    case CYDONIA = 'Cydonia';
    case THARSIS = 'Tharsis';
    case UTOPIA = 'Utopia';

    public function sectorCode(): string
    {
        return match ($this) {
            self::OLYMPIA => 'OM',
            self::VALLIS => 'VM',
            self::GALE => 'GC',
            self::ELYSIUM => 'EP',
            self::RED_DUNE => 'RD',
            self::CRIMSON => 'CP',
            self::IRONHOLD => 'ID',
            self::ARCADIA => 'AP',
            self::AMAZONIS => 'AS',
            self::HELLAS => 'HB',
            self::ISIDIS => 'IP',
            self::NOCTIS => 'NL',
            self::CYDONIA => 'CY',
            self::THARSIS => 'TH',
            self::UTOPIA => 'UP',
        };
    }

    public static function random(): self
    {
        $cases = self::cases();

        return $cases[array_rand($cases)];
    }
}
