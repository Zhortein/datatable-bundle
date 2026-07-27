<?php

declare(strict_types=1);

if (3 !== $argc) {
    throw new InvalidArgumentException('Expected the base URL and parent fragments response path.');
}

$baseUrl = rtrim($argv[1], '/');
$parentPayload = readJsonFile($argv[2]);
$parentBody = readBody($parentPayload, 'parent');
$orderLinesShell = requestHtml($baseUrl, readChildUrl($parentBody, 'parent'));

assertContains($orderLinesShell, 'zhortein-datatable-smoke-order-lines', 'order lines shell');
assertContains($orderLinesShell, '_zd_context=', 'order lines shell');
assertContains($orderLinesShell, '_zd_instance=', 'order lines shell');

$orderLinesPayload = requestJson($baseUrl, readFragmentsUrl($orderLinesShell, 'order lines'));
$orderLinesBody = readBody($orderLinesPayload, 'order lines');

if (2 !== ($orderLinesPayload['totalItems'] ?? null)) {
    throw new RuntimeException('The order lines fragments response is not scoped to order 101.');
}

assertContains($orderLinesBody, 'Mechanical keyboard', 'order lines fragments');
assertContains($orderLinesBody, 'Wireless mouse', 'order lines fragments');
assertNotContains($orderLinesBody, 'External SSD', 'order lines fragments');

$lineEventsShell = requestHtml($baseUrl, readChildUrl($orderLinesBody, 'order lines'));

assertContains($lineEventsShell, 'zhortein-datatable-smoke-line-events', 'line events shell');

$lineEventsPayload = requestJson($baseUrl, readFragmentsUrl($lineEventsShell, 'line events'));
$lineEventsBody = readBody($lineEventsPayload, 'line events');

if (2 !== ($lineEventsPayload['totalItems'] ?? null)) {
    throw new RuntimeException('The line events fragments response is not scoped to order line 1.');
}

assertContains($lineEventsBody, 'Added to order', 'line events fragments');
assertContains($lineEventsBody, 'Quality checked', 'line events fragments');
assertNotContains($lineEventsBody, 'Packed separately', 'line events fragments');

fwrite(STDOUT, "Hierarchical datatable smoke test passed.\n");

/**
 * @return array<string, mixed>
 */
function readJsonFile(string $path): array
{
    $content = file_get_contents($path);

    if (!is_string($content)) {
        throw new RuntimeException(sprintf('Unable to read JSON response "%s".', $path));
    }

    $payload = json_decode($content, true, flags: JSON_THROW_ON_ERROR);

    if (!is_array($payload)) {
        throw new RuntimeException(sprintf('The JSON response "%s" must contain an object.', $path));
    }

    return $payload;
}

/**
 * @param array<string, mixed> $payload
 */
function readBody(array $payload, string $label): string
{
    $body = $payload['body'] ?? null;

    if (!is_string($body)) {
        throw new RuntimeException(sprintf('The %s fragments response does not contain a body.', $label));
    }

    return $body;
}

function readChildUrl(string $html, string $label): string
{
    if (1 !== preg_match('/data-zhortein--datatable-bundle--datatable-child-url="([^"]+)"/', $html, $matches)) {
        throw new RuntimeException(sprintf('The %s response does not expose a child URL.', $label));
    }

    return html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5);
}

function readFragmentsUrl(string $html, string $label): string
{
    if (1 !== preg_match('/data-zhortein--datatable-bundle--datatable-fragments-url-value="([^"]+)"/', $html, $matches)) {
        throw new RuntimeException(sprintf('The %s shell does not expose a fragments URL.', $label));
    }

    return html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5);
}

/**
 * @return array<string, mixed>
 */
function requestJson(string $baseUrl, string $path): array
{
    $content = requestHtml($baseUrl, $path);
    $payload = json_decode($content, true, flags: JSON_THROW_ON_ERROR);

    if (!is_array($payload)) {
        throw new RuntimeException(sprintf('The response for "%s" must contain a JSON object.', $path));
    }

    return $payload;
}

function requestHtml(string $baseUrl, string $path): string
{
    $content = file_get_contents($baseUrl.$path);

    if (!is_string($content)) {
        throw new RuntimeException(sprintf('Unable to request "%s".', $path));
    }

    return $content;
}

function assertContains(string $content, string $expected, string $label): void
{
    if (!str_contains($content, $expected)) {
        throw new RuntimeException(sprintf('The %s response does not contain "%s".', $label, $expected));
    }
}

function assertNotContains(string $content, string $unexpected, string $label): void
{
    if (str_contains($content, $unexpected)) {
        throw new RuntimeException(sprintf('The %s response unexpectedly contains "%s".', $label, $unexpected));
    }
}
