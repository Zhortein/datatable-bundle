import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';

const DATATABLE = '#zhortein-datatable-smoke-users';

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
