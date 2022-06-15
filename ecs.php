<?php

use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use PhpCsFixer\Fixer\ControlStructure\YodaStyleFixer;
use PhpCsFixer\Fixer\Operator\NotOperatorWithSuccessorSpaceFixer;
use PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer;
use PhpCsFixer\Fixer\Whitespace\MethodChainingIndentationFixer;
use Symplify\CodingStandard\Fixer\Spacing\MethodChainingNewlineFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ECSConfig $ecsConfig): void {
    $ecsConfig->ruleWithConfiguration(ArraySyntaxFixer::class, [
        'syntax' => 'short',
    ]);

    $ecsConfig->import(SetList::PSR_12);
    $ecsConfig->import(SetList::PHP_CS_FIXER);
    $ecsConfig->import(SetList::PHP_CS_FIXER_RISKY);
    $ecsConfig->import(SetList::SYMPLIFY);
    $ecsConfig->import(SetList::SYMFONY);
    $ecsConfig->import(SetList::SYMFONY_RISKY);
    $ecsConfig->import(SetList::COMMON);
    $ecsConfig->import(SetList::CLEAN_CODE);

    $ecsConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests'
    ]);

    $ecsConfig->skip([
        MethodChainingIndentationFixer::class => [
            __DIR__ . '/src/DependencyInjection/Configuration.php',
        ],
        MethodChainingNewlineFixer::class => [
            __DIR__ . '/src/DependencyInjection/Configuration.php',
        ],
        YodaStyleFixer::class => [
            __DIR__,
        ],
        NotOperatorWithSuccessorSpaceFixer::class => [
            __DIR__,
        ],
        DeclareStrictTypesFixer::class => [
            __DIR__,
        ],
    ]);
};
