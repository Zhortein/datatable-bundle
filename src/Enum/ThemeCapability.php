<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Enum;

enum ThemeCapability: string
{
    case Actions = 'actions';
    case Confirmations = 'confirmations';
    case Filters = 'filters';
    case Hierarchy = 'hierarchy';
    case Pagination = 'pagination';
    case SavedViews = 'saved_views';
    case SearchBuilder = 'search_builder';
    case Selection = 'selection';
    case States = 'states';
}
