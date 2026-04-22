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
    ]);

return new Config()
    ->setRules([
        '@Symfony' => true,
        'yoda_style' => false,
        'concat_space' => ['spacing' => 'one']
    ])
    ->setFinder($finder);
