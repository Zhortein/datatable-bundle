import { Application } from '@hotwired/stimulus';
import { afterEach, describe, expect, it, vi } from 'vitest';
import DatatableController from '../../assets/controllers/datatable_controller.js';

const CONTROLLER_IDENTIFIER = 'zhortein--datatable-bundle--datatable';

function createDatatableHtml(attributes = '') {
    return `
        <div
            id="zhortein-datatable-users"
            data-controller="${CONTROLLER_IDENTIFIER}"
            data-${CONTROLLER_IDENTIFIER}-name-value="users"
            data-${CONTROLLER_IDENTIFIER}-fragments-url-value="/_zhortein/datatable/users/fragments"
            data-${CONTROLLER_IDENTIFIER}-export-url-value="/_zhortein/datatable/users/export/csv"
            data-${CONTROLLER_IDENTIFIER}-auto-load-value="false"
            data-${CONTROLLER_IDENTIFIER}-page-value="3"
            data-${CONTROLLER_IDENTIFIER}-page-size-value="25"
            data-${CONTROLLER_IDENTIFIER}-sort-field-value="e.email"
            data-${CONTROLLER_IDENTIFIER}-sort-direction-value="desc"
            ${attributes}
        >
            <input
                type="search"
                value="alice"
                data-${CONTROLLER_IDENTIFIER}-target="searchInput"
            >

            <input
                name="filters[email]"
                type="text"
                value="alice@example.test"
                data-${CONTROLLER_IDENTIFIER}-filter-control="true"
            >

            <select
                name="filters[enabled]"
                data-${CONTROLLER_IDENTIFIER}-filter-control="true"
            >
                <option value="">Enabled</option>
                <option value="1" selected>Yes</option>
                <option value="0">No</option>
            </select>

            <input
                type="checkbox"
                name="columns[e.email]"
                value="1"
                checked
                data-${CONTROLLER_IDENTIFIER}-column-visibility-control="true"
                data-${CONTROLLER_IDENTIFIER}-column-name="e.email"
            >

            <input
                type="checkbox"
                name="columns[e.displayName]"
                value="1"
                data-${CONTROLLER_IDENTIFIER}-column-visibility-control="true"
                data-${CONTROLLER_IDENTIFIER}-column-name="e.displayName"
            >

            <a
                id="xlsx-current-export"
                href="/_zhortein/datatable/users/export/xlsx?mode=current"
                data-action="${CONTROLLER_IDENTIFIER}#export"
                data-${CONTROLLER_IDENTIFIER}-export-mode-param="current"
                data-${CONTROLLER_IDENTIFIER}-export-format-param="xlsx"
                data-${CONTROLLER_IDENTIFIER}-export-url-param="/_zhortein/datatable/users/export/xlsx"
            >
                XLSX current view
            </a>

            <a
                id="xlsx-full-export"
                href="/_zhortein/datatable/users/export/xlsx?mode=full"
                data-action="${CONTROLLER_IDENTIFIER}#export"
                data-${CONTROLLER_IDENTIFIER}-export-mode-param="full"
                data-${CONTROLLER_IDENTIFIER}-export-format-param="xlsx"
                data-${CONTROLLER_IDENTIFIER}-export-url-param="/_zhortein/datatable/users/export/xlsx"
            >
                XLSX full dataset
            </a>

            <a
                id="custom-xlsx-current-export"
                href="/fallback/xlsx?mode=current"
                data-action="${CONTROLLER_IDENTIFIER}#export"
                data-${CONTROLLER_IDENTIFIER}-export-mode-param="current"
                data-${CONTROLLER_IDENTIFIER}-export-format-param="xlsx"
                data-${CONTROLLER_IDENTIFIER}-export-url-param="/custom/users/export/xlsx"
            >
                Custom XLSX current view
            </a>

            <table>
                <thead data-${CONTROLLER_IDENTIFIER}-target="header">
                    <tr><th>Email</th></tr>
                </thead>
                <tbody data-${CONTROLLER_IDENTIFIER}-target="body">
                    <tr><td>No data available.</td></tr>
                </tbody>
            </table>

            <div data-${CONTROLLER_IDENTIFIER}-target="pagination"></div>
            <div data-${CONTROLLER_IDENTIFIER}-target="summary"></div>
            <div class="alert alert-danger d-none" data-${CONTROLLER_IDENTIFIER}-target="error"></div>
            <div class="zhortein-datatable__loading d-none" data-${CONTROLLER_IDENTIFIER}-target="loading"></div>
        </div>
    `;
}

async function flushPromises() {
    await Promise.resolve();
    await Promise.resolve();
}

function startApplication() {
    const application = Application.start();
    application.register(CONTROLLER_IDENTIFIER, DatatableController);

    return application;
}

async function getController(application) {
    await flushPromises();

    const element = document.querySelector('#zhortein-datatable-users');
    const controller = application.getControllerForElementAndIdentifier(element, CONTROLLER_IDENTIFIER);

    expect(controller).toBeInstanceOf(DatatableController);

    return { element, controller };
}

function createExportEvent(target, params = {}) {
    return {
        currentTarget: target,
        params,
        preventDefault: vi.fn(),
    };
}

function mockWindowLocationAssign() {
    const assignSpy = vi.fn();

    Object.defineProperty(window, 'location', {
        value: {
            origin: 'https://example.test',
            href: 'https://example.test/current-page',
            assign: assignSpy,
        },
        writable: true,
    });

    return assignSpy;
}

function getAssignedLocationUrl(assignSpy) {
    const rawUrl = assignSpy.mock.calls.at(-1)[0];

    return new URL(rawUrl, window.location.origin);
}

describe('datatable_controller XLSX export URL generation', () => {
    let application = null;

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        vi.unstubAllGlobals();
        vi.restoreAllMocks();
        document.body.innerHTML = '';
    });

    it('builds XLSX current export URL with pagination and current datatable state', async () => {
        const assignSpy = mockWindowLocationAssign();

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { controller } = await getController(application);

        controller.searchValue = 'alice';
        controller.pageValue = 3;
        controller.pageSizeValue = 25;
        controller.sortFieldValue = 'e.email';
        controller.sortDirectionValue = 'desc';

        const link = document.querySelector('#xlsx-current-export');
        const event = createExportEvent(link, {
            exportMode: 'current',
            exportFormat: 'xlsx',
            exportUrl: '/_zhortein/datatable/users/export/xlsx',
        });

        controller.export(event);

        const url = getAssignedLocationUrl(assignSpy);

        expect(event.preventDefault).toHaveBeenCalledTimes(1);
        expect(url.pathname).toBe('/_zhortein/datatable/users/export/xlsx');
        expect(url.searchParams.get('mode')).toBe('current');
        expect(url.searchParams.get('page')).toBe('3');
        expect(url.searchParams.get('pageSize')).toBe('25');
        expect(url.searchParams.get('search')).toBe('alice');
        expect(url.searchParams.get('sortField')).toBe('e.email');
        expect(url.searchParams.get('sortDirection')).toBe('desc');
        expect(url.searchParams.get('filters[email]')).toBe('alice@example.test');
        expect(url.searchParams.get('filters[enabled]')).toBe('1');
        expect(url.searchParams.getAll('visibleColumns[]')).toEqual(['e.email']);
        expect(url.searchParams.getAll('hiddenColumns[]')).toEqual(['e.displayName']);
    });

    it('builds XLSX full export URL without pagination while keeping datatable state', async () => {
        const assignSpy = mockWindowLocationAssign();

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { controller } = await getController(application);

        controller.searchValue = 'alice';
        controller.pageValue = 4;
        controller.pageSizeValue = 10;
        controller.sortFieldValue = 'e.email';
        controller.sortDirectionValue = 'asc';

        const link = document.querySelector('#xlsx-full-export');
        const event = createExportEvent(link, {
            exportMode: 'full',
            exportFormat: 'xlsx',
            exportUrl: '/_zhortein/datatable/users/export/xlsx',
        });

        controller.export(event);

        const url = getAssignedLocationUrl(assignSpy);

        expect(url.pathname).toBe('/_zhortein/datatable/users/export/xlsx');
        expect(url.searchParams.get('mode')).toBe('full');
        expect(url.searchParams.has('page')).toBe(false);
        expect(url.searchParams.has('pageSize')).toBe(false);
        expect(url.searchParams.get('search')).toBe('alice');
        expect(url.searchParams.get('sortField')).toBe('e.email');
        expect(url.searchParams.get('sortDirection')).toBe('asc');
        expect(url.searchParams.get('filters[email]')).toBe('alice@example.test');
        expect(url.searchParams.get('filters[enabled]')).toBe('1');
        expect(url.searchParams.getAll('visibleColumns[]')).toEqual(['e.email']);
        expect(url.searchParams.getAll('hiddenColumns[]')).toEqual(['e.displayName']);
    });

    it('uses link-specific XLSX export URL param before root export URL value', async () => {
        const assignSpy = mockWindowLocationAssign();

        document.body.innerHTML = createDatatableHtml(`
            data-${CONTROLLER_IDENTIFIER}-export-url-value="/_zhortein/datatable/users/export/csv"
        `);
        application = startApplication();

        const { controller } = await getController(application);

        const link = document.querySelector('#custom-xlsx-current-export');
        const event = createExportEvent(link, {
            exportMode: 'current',
            exportFormat: 'xlsx',
            exportUrl: '/custom/users/export/xlsx',
        });

        controller.export(event);

        const url = getAssignedLocationUrl(assignSpy);

        expect(url.pathname).toBe('/custom/users/export/xlsx');
        expect(url.searchParams.get('mode')).toBe('current');
        expect(url.searchParams.get('page')).toBe('3');
        expect(url.searchParams.get('pageSize')).toBe('25');
    });

    it('falls back to XLSX link href when no root export URL and no URL param are present', async () => {
        const assignSpy = mockWindowLocationAssign();

        document.body.innerHTML = `
            <div
                id="zhortein-datatable-users"
                data-controller="${CONTROLLER_IDENTIFIER}"
                data-${CONTROLLER_IDENTIFIER}-name-value="users"
                data-${CONTROLLER_IDENTIFIER}-fragments-url-value="/_zhortein/datatable/users/fragments"
                data-${CONTROLLER_IDENTIFIER}-auto-load-value="false"
                data-${CONTROLLER_IDENTIFIER}-page-value="1"
                data-${CONTROLLER_IDENTIFIER}-page-size-value="25"
            >
                <a
                    href="/fallback/users/export/xlsx?existing=1"
                    data-action="${CONTROLLER_IDENTIFIER}#export"
                    data-${CONTROLLER_IDENTIFIER}-export-mode-param="current"
                    data-${CONTROLLER_IDENTIFIER}-export-format-param="xlsx"
                >
                    XLSX current view
                </a>
                <tbody data-${CONTROLLER_IDENTIFIER}-target="body"></tbody>
                <div data-${CONTROLLER_IDENTIFIER}-target="pagination"></div>
                <div data-${CONTROLLER_IDENTIFIER}-target="summary"></div>
            </div>
        `;

        application = startApplication();

        const { controller } = await getController(application);

        const link = document.querySelector('a');
        const event = createExportEvent(link, {
            exportMode: 'current',
            exportFormat: 'xlsx',
        });

        controller.export(event);

        const url = getAssignedLocationUrl(assignSpy);

        expect(url.pathname).toBe('/fallback/users/export/xlsx');
        expect(url.searchParams.get('existing')).toBe('1');
        expect(url.searchParams.get('mode')).toBe('current');
        expect(url.searchParams.get('page')).toBe('1');
        expect(url.searchParams.get('pageSize')).toBe('25');
    });
});
