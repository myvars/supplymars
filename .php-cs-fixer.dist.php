<?php

declare(strict_types=1);

use PhpCsFixer\Finder;
use PhpCsFixer\Config;

$finder = new Finder()
    ->in(__DIR__)
    ->exclude('var')
    ->notPath([
        'config/bundles.php',
        'config/reference.php',
        'importmap.php',
    ]);

return new Config()
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        'declare_strict_types' => true,
        'yoda_style' => false,
        'concat_space' => ['spacing' => 'one']
    ])
    ->setFinder($finder);
