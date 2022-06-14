<?php

declare(strict_types=1);

use PHP_CodeSniffer\Standards\Squiz\Sniffs\Classes\ValidClassNameSniff;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\Commenting\ClassCommentSniff;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\Commenting\FileCommentSniff;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\Commenting\FunctionCommentThrowTagSniff;
use PhpCsFixer\Fixer\ControlStructure\TrailingCommaInMultilineFixer;
use PhpCsFixer\Fixer\Whitespace\ArrayIndentationFixer;
use Symplify\CodingStandard\Fixer\ArrayNotation\ArrayListItemNewlineFixer;
use Symplify\CodingStandard\Fixer\ArrayNotation\ArrayOpenerAndCloserNewlineFixer;
use Symplify\CodingStandard\Fixer\ArrayNotation\StandaloneLineInMultilineArrayFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return static function (ECSConfig $containerConfigurator): void {
    $containerConfigurator->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

    // run and fix, one by one
    $containerConfigurator->import('vendor/whatwedo/php-coding-standard/config/whatwedo-symfony.php');

    $containerConfigurator->skip([
        FileCommentSniff::class,
        ClassCommentSniff::class,
        FunctionCommentThrowTagSniff::class,
        ValidClassNameSniff::class => [
            __DIR__ . '/src/whatwedoCrudBundle.php',
            __DIR__ . '/src/DependencyInjection/whatwedoCrudExtension.php',
        ],
        TrailingCommaInMultilineFixer::class => [
            __DIR__ . '/tests/App/config/bundles.php',
        ],
        ArrayIndentationFixer::class => [
            __DIR__ . '/tests/App/config/bundles.php',
        ],
        ArrayListItemNewlineFixer::class => [
            __DIR__ . '/tests/App/config/bundles.php',
        ],
        StandaloneLineInMultilineArrayFixer::class => [
            __DIR__ . '/tests/App/config/bundles.php',
        ],
        ArrayOpenerAndCloserNewlineFixer::class => [
            __DIR__ . '/tests/App/config/bundles.php',
        ],
    ]);

    $containerConfigurator->parallel();
};
