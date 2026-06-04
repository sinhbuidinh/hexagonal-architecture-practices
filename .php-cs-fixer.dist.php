<?php

declare(strict_types=1);

use HexagonPractise\Tools\PhpCsFixer\AlignConsecutiveAssignmentEqualsFixer;
use HexagonPractise\Tools\PhpCsFixer\AlignMultilineNamedArgumentsFixer;
use HexagonPractise\Tools\PhpCsFixer\EnumCaseStyleFixer;

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/pure-php/src',
        __DIR__ . '/pure-php/tests',
        __DIR__ . '/pure-php/public',
        __DIR__ . '/pure-php/bin',
        __DIR__ . '/laravel/app',
        __DIR__ . '/laravel/routes',
        __DIR__ . '/laravel/tests',
        __DIR__ . '/symfony/src',
    ])
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->registerCustomFixers([
        new AlignConsecutiveAssignmentEqualsFixer(),
        new AlignMultilineNamedArgumentsFixer(),
        new EnumCaseStyleFixer(),
    ])
    ->setRules([
        '@PSR12' => true,
        'indentation_type' => true,
        'declare_strict_types' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'single_blank_line_at_eof' => true,
        'no_trailing_whitespace' => true,
        'array_syntax' => ['syntax' => 'short'],
        'binary_operator_spaces' => [
            'default' => 'single_space',
            'operators' => [
                '=' => 'single_space',
                '=>' => 'align',
            ],
        ],
        'single_space_around_construct' => [
            'constructs_followed_by_a_single_space' => ['named_argument'],
        ],
        'HexagonPractise/align_consecutive_assignment_equals' => true,
        'HexagonPractise/align_multiline_named_arguments' => true,
        'HexagonPractise/enum_case_style' => true,
    ])
    ->setFinder($finder);
