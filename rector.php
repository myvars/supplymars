<?php

use Rector\Config\RectorConfig;
use Rector\Symfony\Set\SymfonySetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests'
    ])
    ->withRootFiles()
    ->withPhpSets(php83: true)
    ->withPhpPolyfill()
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true
    )
    ->withImportNames(importShortClasses: false, removeUnusedImports: true)
    ->withSets([
        SymfonySetList::SYMFONY_71,
        SymfonySetList::SYMFONY_CODE_QUALITY,
        SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,
    ]);

/*use Zenstruck\Foundry\Utils\Rector\FoundrySetList;

return RectorConfig::configure()
    ->withPaths([
        // add all paths where your factories are defined and where Foundry is used
        'src/Factory',
        'src/Story',
        'src/DataFixtures',
        'tests'
    ])
    ->withSets([FoundrySetList::UP_TO_FOUNDRY_2])
;*/