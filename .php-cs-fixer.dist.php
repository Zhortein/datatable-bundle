<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->append([
        __FILE__,
    ])
;

return new Config()
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        '@PHP84Migration' => true,
        'declare_strict_types' => true,
        'final_class' => true,
        'phpdoc_to_comment' => false,
        'php_unit_method_casing' => [
            'case' => 'snake_case',
        ],
        'php_unit_test_class_requires_covers' => false,
    ])
    ->setFinder($finder)
;
