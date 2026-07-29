<?php

declare(strict_types=1);

$templateRoot = __DIR__.'/templates/bootstrap';
$requiredTemplates = [
    'datatable.html.twig',
    '_header.html.twig',
    '_body.html.twig',
    '_row.html.twig',
    '_cell.html.twig',
    '_empty.html.twig',
    '_toolbar.html.twig',
    '_bottom_controls.html.twig',
    '_pagination.html.twig',
    '_filters.html.twig',
    '_filter.html.twig',
    '_column_filter.html.twig',
    '_column_visibility.html.twig',
    '_search_builder.html.twig',
    '_actions.html.twig',
    '_action.html.twig',
    '_bulk_actions.html.twig',
    '_row_actions_inline.html.twig',
    '_row_actions_list.html.twig',
    '_row_actions_dropdown.html.twig',
    '_list_action.html.twig',
    '_dropdown_action.html.twig',
    '_export.html.twig',
    '_saved_views.html.twig',
    '_preferences.html.twig',
    '_confirmation_modal.html.twig',
    'cell/default.html.twig',
    'cell/string.html.twig',
    'cell/numeric.html.twig',
    'cell/boolean.html.twig',
    'cell/datetime.html.twig',
    'cell/array.html.twig',
    'cell/enum.html.twig',
];

foreach ($requiredTemplates as $relativePath) {
    $templatePath = $templateRoot.'/'.$relativePath;

    if (!is_file($templatePath)) {
        throw new RuntimeException(sprintf('The external theme is missing required template "%s".', $relativePath));
    }

    $contents = file_get_contents($templatePath);

    if (!is_string($contents)) {
        throw new RuntimeException(sprintf('The external theme template "%s" could not be read.', $relativePath));
    }

    if (str_contains($contents, '@ZhorteinDatatable')) {
        throw new RuntimeException(sprintf('The external theme template "%s" falls back to the core theme namespace.', $relativePath));
    }
}

fwrite(STDOUT, "External theme package contract validated.\n");
