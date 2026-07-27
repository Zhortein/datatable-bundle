import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';

const DATATABLE = '#zhortein-datatable-smoke-users';
const HIERARCHY_DATATABLE = '#zhortein-datatable-smoke-orders';
const CHILD_TOGGLE = '[data-zhortein--datatable-bundle--datatable-child-toggle="true"]';
const DATATABLE_NAME = 'data-zhortein--datatable-bundle--datatable-name-value';
const STATE_PARAMETER = 'data-zhortein--datatable-bundle--datatable-state-parameter-value';

async function refreshAfter(page, action) {
    const responsePromise = page.waitForResponse((response) => (
        response.ok()
        && response.url().includes('/_zhortein/datatable/smoke-users/fragments')
    ));

    await action();
    await responsePromise;
}

async function openDatatable(page) {
    await page.goto('/smoke');

    const datatable = page.locator(DATATABLE);

    await expect(datatable).not.toHaveAttribute('aria-busy');
    await expect(datatable.getByText('alice@example.test')).toBeVisible();

    return datatable;
}

async function openHierarchy(page) {
    await page.goto('/smoke');

    const datatable = page.locator(HIERARCHY_DATATABLE);

    await expect(datatable).not.toHaveAttribute('aria-busy');
    await expect(datatable.getByText('SO-101')).toBeVisible();

    return datatable;
}

test('loads the Symfony datatable and meets the accessibility baseline', async ({ page }) => {
    const datatable = await openDatatable(page);
    const results = await new AxeBuilder({ page })
        .include(DATATABLE)
        .disableRules([
            // Page landmarks belong to the host application layout.
            'region',
        ])
        .withTags([
            'wcag2a',
            'wcag2aa',
            'wcag21a',
            'wcag21aa',
            'wcag22aa',
        ])
        .analyze();

    expect(results.violations).toEqual([]);
    await expect(datatable.locator(
        '[data-zhortein--datatable-bundle--datatable-target="summary"]',
    )).toContainText('Showing 1 to 15 of 20 results.');
});

test('supports keyboard sorting, pagination and Bootstrap dropdowns', async ({ page }) => {
    const datatable = await openDatatable(page);
    const sortButton = datatable.getByRole('button', { name: 'Sort by Email' });

    await sortButton.focus();
    await refreshAfter(page, () => page.keyboard.press('Enter'));
    await expect(datatable.locator('tbody tr').first()).toContainText('alice@example.test');

    await sortButton.focus();
    await refreshAfter(page, () => page.keyboard.press('Enter'));
    await expect(datatable.locator('tbody tr').first()).toContainText('user20@example.test');

    const nextButton = datatable.getByRole('button', { name: 'Next' });

    await nextButton.focus();
    await refreshAfter(page, () => page.keyboard.press('Enter'));
    await expect(datatable.locator('tbody tr').first()).toContainText('user05@example.test');

    const actionsButton = datatable.getByRole('button', { name: 'Actions' }).first();

    await actionsButton.click();
    await expect(datatable.getByRole('link', { name: 'View' }).first()).toBeVisible();
});

test('filters rows through real Bootstrap header controls', async ({ page }) => {
    const datatable = await openDatatable(page);
    const search = datatable.getByRole('searchbox', { name: 'Search' });

    await refreshAfter(page, () => search.fill('bob@example.test'));
    await expect(datatable.getByText('bob@example.test')).toBeVisible();
    await expect(datatable.getByText('alice@example.test')).toHaveCount(0);

    await refreshAfter(page, () => search.fill(''));
    await expect(datatable.getByText('alice@example.test')).toBeVisible();

    await datatable.getByRole('button', { name: 'Filter Enabled' }).click();
    await refreshAfter(page, () => datatable.getByRole('combobox', { name: 'Enabled' }).selectOption('0'));

    await expect(datatable.getByText('bob@example.test')).toBeVisible();
    await expect(datatable.getByText('alice@example.test')).toHaveCount(0);
});

test('supports keyboard row selection and modal confirmation', async ({ page }) => {
    const datatable = await openDatatable(page);
    const rowCheckbox = datatable.getByRole('checkbox', { name: 'Select row 1', exact: true });

    await rowCheckbox.focus();
    await page.keyboard.press('Space');

    await expect(datatable.getByText('1 rows selected')).toBeVisible();

    await datatable.getByRole('button', { name: 'Archive selected' }).click();

    const modal = datatable.getByRole('dialog', { name: 'Confirm action' });

    await expect(modal).toBeVisible();
    await expect(modal).toContainText('Archive selected users?');
    await expect(modal).toBeFocused();

    await page.keyboard.press('Escape');
    await expect(modal).toBeHidden();
});

test('downloads the current CSV export through the real Symfony route', async ({ page }) => {
    const datatable = await openDatatable(page);

    await datatable.getByRole('button', { name: 'Export' }).click();

    const downloadPromise = page.waitForEvent('download');

    await datatable.getByRole('link', { name: 'CSV current view' }).click();

    const download = await downloadPromise;

    expect(download.suggestedFilename()).toMatch(/\.csv$/);
});

test('loads isolated Array and Doctrine child datatables once with keyboard access', async ({ page }) => {
    const childRequests = [];

    page.on('request', (request) => {
        const url = new URL(request.url());

        if (url.pathname === '/_zhortein/datatable/smoke-order-lines/child') {
            childRequests.push(url);
        }
    });

    const orders = await openHierarchy(page);
    const orderToggle = orders.locator(CHILD_TOGGLE).first();

    await expect(orderToggle).toHaveAttribute('aria-label', 'Expand row 101');
    await orderToggle.focus();
    await page.keyboard.press('Enter');

    const orderLines = orders.locator(`[${DATATABLE_NAME}="smoke-order-lines"]`);

    await expect(orderLines).not.toHaveAttribute('aria-busy');
    await expect(orderLines.getByText('Mechanical keyboard')).toBeVisible();
    await expect(orderLines.getByText('Wireless mouse')).toBeVisible();
    await expect(orderLines.getByText('External SSD')).toHaveCount(0);
    await expect(orderToggle).toBeFocused();
    await expect(orderToggle).toHaveAttribute('aria-expanded', 'true');
    await expect(orderToggle).toHaveAttribute('aria-label', 'Collapse row 101');

    expect(childRequests).toHaveLength(1);
    expect(childRequests[0].searchParams.get('_zd_context')).toBeTruthy();
    expect(childRequests[0].searchParams.get('_zd_instance')).toBeTruthy();

    const parentStateParameter = await orders.getAttribute(STATE_PARAMETER);
    const childStateParameter = await orderLines.getAttribute(STATE_PARAMETER);

    expect(parentStateParameter).toBeTruthy();
    expect(childStateParameter).toBeTruthy();
    expect(childStateParameter).not.toBe(parentStateParameter);

    const lineToggle = orderLines.locator(CHILD_TOGGLE).first();

    await expect(lineToggle).toHaveAttribute('aria-label', 'Expand row 1');
    await lineToggle.click();

    const lineEvents = orderLines.locator(`[${DATATABLE_NAME}="smoke-line-events"]`);

    await expect(lineEvents).not.toHaveAttribute('aria-busy');
    await expect(lineEvents.getByText('Added to order')).toBeVisible();
    await expect(lineEvents.getByText('Quality checked')).toBeVisible();
    await expect(lineEvents.getByText('Packed separately')).toHaveCount(0);

    const results = await new AxeBuilder({ page })
        .include(HIERARCHY_DATATABLE)
        .disableRules([
            // Page landmarks belong to the host application layout.
            'region',
        ])
        .withTags([
            'wcag2a',
            'wcag2aa',
            'wcag21a',
            'wcag21aa',
            'wcag22aa',
        ])
        .analyze();

    expect(results.violations).toEqual([]);

    await orderToggle.focus();
    await page.keyboard.press('Enter');
    await expect(orderToggle).toHaveAttribute('aria-expanded', 'false');
    await page.keyboard.press('Enter');
    await expect(orderToggle).toHaveAttribute('aria-expanded', 'true');
    await expect(orderLines.getByText('Mechanical keyboard')).toBeVisible();
    expect(childRequests).toHaveLength(1);
});

test('keeps a failed child stable until an explicit accessible retry', async ({ page }) => {
    let failed = false;

    await page.route('**/*', async (route) => {
        const url = new URL(route.request().url());

        if (!failed && url.pathname === '/_zhortein/datatable/smoke-order-lines/child') {
            failed = true;
            await route.fulfill({
                status: 503,
                contentType: 'text/plain',
                body: 'Temporary smoke failure',
            });

            return;
        }

        await route.continue();
    });

    const orders = await openHierarchy(page);
    const orderToggle = orders.locator(CHILD_TOGGLE).first();

    await orderToggle.click();

    const alert = orders.locator('[role="alert"]').filter({
        hasText: 'Unable to load child rows.',
    });
    const retry = alert.getByRole('button', { name: 'Retry' });

    await expect(alert).toBeVisible();
    await expect(retry).toBeVisible();
    await expect(orderToggle).toHaveAttribute('aria-expanded', 'true');
    await expect(orderToggle).not.toHaveAttribute('aria-busy');

    await retry.focus();
    await retry.click();

    const orderLines = orders.locator(`[${DATATABLE_NAME}="smoke-order-lines"]`);

    await expect(orderLines.getByText('Mechanical keyboard')).toBeVisible();
    await expect(orderToggle).toBeFocused();
});
