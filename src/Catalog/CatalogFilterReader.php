<?php

declare(strict_types=1);

namespace App\Catalog;

use App\Form\CatalogFilterType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * The filters of the catalogue's query string, on top of the page's own (section, productions).
 */
final readonly class CatalogFilterReader
{
    public function __construct(private FormFactoryInterface $formFactory)
    {
    }

    /**
     * A copy of $base with the request's filters; a value the form does not know is left out.
     */
    public function read(Request $request, CatalogFilters $base): CatalogFilters
    {
        $filters = clone $base;
        // Not isValid(): a filter form has nothing to refuse, an unknown value is dropped
        // by the form and the others still apply. GET, no CSRF token.
        $this->formFactory->create(CatalogFilterType::class, $filters)->handleRequest($request);

        return $filters;
    }
}
