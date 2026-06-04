<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        'nullable_type_declaration_for_default_null_value' => true,
        'no_multiline_whitespace_around_double_arrow' => true
    ])
    ->setFinder($finder)
;
