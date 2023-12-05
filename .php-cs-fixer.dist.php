<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        'no_multiline_whitespace_around_double_arrow' => true
    ])
    ->setFinder($finder)
;
