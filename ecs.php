<?php

use PHP_CodeSniffer\Standards\Generic\Sniffs\Arrays\ArrayIndentSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\NamingConventions\UpperCaseConstantNameSniff;
use PHP_CodeSniffer\Standards\PEAR\Sniffs\Functions\FunctionCallSignatureSniff as FunctionsFunctionCallSignatureSniff;
use PHP_CodeSniffer\Standards\PSR1\Sniffs\Files\SideEffectsSniff;
use PHP_CodeSniffer\Standards\PSR1\Sniffs\Methods\CamelCapsMethodNameSniff;
use PHP_CodeSniffer\Standards\PSR12\Sniffs\ControlStructures\ControlStructureSpacingSniff as PSR12ControlStructureSpacingSniff;
use PHP_CodeSniffer\Standards\PSR12\Sniffs\Properties\ConstantVisibilitySniff;
use PHP_CodeSniffer\Standards\PSR2\Sniffs\ControlStructures\ControlStructureSpacingSniff as PSR2ControlStructureSpacingSniff;
use PHP_CodeSniffer\Standards\PSR2\Sniffs\Methods\FunctionCallSignatureSniff as MethodsFunctionCallSignatureSniff;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\WhiteSpace\OperatorSpacingSniff;
use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use PhpCsFixer\Fixer\ArrayNotation\TrailingCommaInMultilineArrayFixer;
use PhpCsFixer\Fixer\ArrayNotation\TrimArraySpacesFixer;
use PhpCsFixer\Fixer\Comment\MultilineCommentClosingFixer;
use PhpCsFixer\Fixer\Comment\NoEmptyCommentFixer;
use PhpCsFixer\Fixer\Import\FullyQualifiedStrictTypesFixer;
use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\Fixer\Import\OrderedImportsFixer;
use PhpCsFixer\Fixer\Operator\OperatorLinebreakFixer;
use PhpCsFixer\Fixer\Operator\TernaryToElvisOperatorFixer;
use PhpCsFixer\Fixer\Operator\TernaryToNullCoalescingFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitTestAnnotationFixer;
use PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer;
use PhpCsFixer\Fixer\StringNotation\SingleQuoteFixer;
use PhpCsFixer\Fixer\Whitespace\NoExtraBlankLinesFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Option;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ECSConfig $ecsConfig): void {
    $parameters = $ecsConfig->parameters();

    $parameters->set(Option::SETS, [
        SetList::PSR_12,
        SetList::PHPUNIT,
    ]);
    $ecsConfig->paths([
        __DIR__,
    ]);
    $ecsConfig->skip([
        __DIR__ . '/.Build/',
        __DIR__ . '/vendor/',

        // Rules
        DeclareStrictTypesFixer::class,
        HeaderCommentFixer::class => [
            __DIR__ . '/Configuration/*',
        ],
        PSR2ControlStructureSpacingSniff::class,
        FunctionsFunctionCallSignatureSniff::class . '.SpaceAfterCloseBracket',
        MethodsFunctionCallSignatureSniff::class . '.SpaceAfterCloseBracket',
    ]);

    $services = $ecsConfig->services();
    $services->set(FullyQualifiedStrictTypesFixer::class);
    $services->set(NoEmptyCommentFixer::class);
    $services->set(MultilineCommentClosingFixer::class);
    $services->set(PhpUnitTestAnnotationFixer::class)->call('configure', [
        [
            'style' => 'annotation',
        ]
    ]);
    $services->set(NoUnusedImportsFixer::class);
    $services->set(OrderedImportsFixer::class)->call('configure', [
        [
            'sort_algorithm' => 'alpha',
        ]
    ]);
    $services->set(SingleQuoteFixer::class);
    $services->set(ArraySyntaxFixer::class)->call('configure', [
        [
            'syntax' => 'short',
        ]
    ]);
    $services->set(ArrayIndentSniff::class);
    $services->set(TrimArraySpacesFixer::class);
    $services->set(TrailingCommaInMultilineArrayFixer::class);
    $services->set(NoExtraBlankLinesFixer::class);
    $services->set(TernaryToNullCoalescingFixer::class);
    $services->set(TernaryToElvisOperatorFixer::class);

    $services->set(ConstantVisibilitySniff::class);

    $services->set(UpperCaseConstantNameSniff::class);
    $services->set(CamelCapsMethodNameSniff::class);

    // Looks like this doesn't work, it does no longer report issues.
    $services->set(SideEffectsSniff::class);

    $services->set(PSR12ControlStructureSpacingSniff::class);
    $services->set(OperatorSpacingSniff::class)
        ->property('ignoreSpacingBeforeAssignments', false)
        ->property('ignoreNewlines', true);
    $services->set(OperatorLinebreakFixer::class)->call('configure', [
        [
            'position' => 'beginning',
        ],
    ]);
};
