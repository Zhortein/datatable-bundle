import { Application } from '@hotwired/stimulus';
import { afterEach, describe, expect, it, vi } from 'vitest';
import DatatableController from '../../assets/controllers/datatable_controller.js';

const CONTROLLER_IDENTIFIER = 'zhortein--datatable-bundle--datatable';

function createDatatableHtml(attributes = '', exportUrl = '/_zhortein/datatable/users/export/csv') {
    return `
        <div
            id="zhortein-datatable-users"
            data-controller="${CONTROLLER_IDENTIFIER}"
            data-${CONTROLLER_IDENTIFIER}-name-value="users"
            data-${CONTROLLER_IDENTIFIER}-fragments-url-value="/_zhortein/datatable/users/fragments"
            data-${CONTROLLER_IDENTIFIER}-export-url-value="${exportUrl}"
            data-${CONTROLLER_IDENTIFIER}-auto-load-value="false"
            data-${CONTROLLER_IDENTIFIER}-hidden-class-value="d-none"
            data-${CONTROLLER_IDENTIFIER}-visible-class-value="d-flex"
            data-${CONTROLLER_IDENTIFIER}-status-error-class-value="text-danger"
            data-${CONTROLLER_IDENTIFIER}-status-success-class-value="text-success"
            data-${CONTROLLER_IDENTIFIER}-status-muted-class-value="text-body-secondary"
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
                name="filters[empty]"
                type="text"
                value=""
                data-${CONTROLLER_IDENTIFIER}-filter-control="true"
            >

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

            <input
                type="checkbox"
                name="columns[e.id]"
                value="1"
                checked
                data-${CONTROLLER_IDENTIFIER}-column-visibility-control="true"
                data-${CONTROLLER_IDENTIFIER}-column-name="e.id"
                data-${CONTROLLER_IDENTIFIER}-definition-hidden="true"
            >

            <a
                href="/_zhortein/datatable/users/export/csv?mode=current"
                data-action="${CONTROLLER_IDENTIFIER}#export"
                data-${CONTROLLER_IDENTIFIER}-export-mode-param="current"
                data-${CONTROLLER_IDENTIFIER}-export-format-param="csv"
            >
                CSV current view
            </a>

            <a
                href="/_zhortein/datatable/users/export/csv?mode=full"
                data-action="${CONTROLLER_IDENTIFIER}#export"
                data-${CONTROLLER_IDENTIFIER}-export-mode-param="full"
                data-${CONTROLLER_IDENTIFIER}-export-format-param="csv"
            >
                CSV full dataset
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

function getAssignedLocationUrl(assignSpy) {
    const rawUrl = assignSpy.mock.calls.at(-1)[0];

    return new URL(rawUrl, window.location.origin);
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

describe('datatable_controller export URL generation', () => {
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

    it('builds current export URL with current pagination and datatable state', async () => {
        const assignSpy = mockWindowLocationAssign();

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { controller } = await getController(application);

        controller.searchValue = 'alice';
        controller.pageValue = 3;
        controller.pageSizeValue = 25;
        controller.setSortCriteria([
            { field: 'e.email', direction: 'desc' },
            { field: 'e.displayName', direction: 'asc' },
        ]);

        const link = document.querySelector('[data-zhortein--datatable-bundle--datatable-export-mode-param="current"]');
        const event = createExportEvent(link, { exportMode: 'current', exportFormat: 'csv' });

        controller.export(event);

        const url = getAssignedLocationUrl(assignSpy);

        expect(event.preventDefault).toHaveBeenCalledTimes(1);
        expect(url.pathname).toBe('/_zhortein/datatable/users/export/csv');
        expect(url.searchParams.get('mode')).toBe('current');
        expect(url.searchParams.get('page')).toBe('3');
        expect(url.searchParams.get('pageSize')).toBe('25');
        expect(url.searchParams.get('search')).toBe('alice');
        expect(url.searchParams.get('sortField')).toBe('e.email');
        expect(url.searchParams.get('sortDirection')).toBe('desc');
        expect(url.searchParams.get('sorts[0][field]')).toBe('e.email');
        expect(url.searchParams.get('sorts[1][field]')).toBe('e.displayName');
        expect(url.searchParams.get('sorts[1][direction]')).toBe('asc');
        expect(url.searchParams.get('filters[email]')).toBe('alice@example.test');
        expect(url.searchParams.get('filters[enabled]')).toBe('1');
        expect(url.searchParams.has('filters[empty]')).toBe(false);
        expect(url.searchParams.getAll('visibleColumns[]')).toEqual(['e.email']);
        expect(url.searchParams.getAll('hiddenColumns[]')).toEqual(['e.displayName']);
        expect(url.searchParams.getAll('visibleColumns[]')).not.toContain('e.id');
    });

    it('builds full export URL without pagination but keeps filters search sort and visibility', async () => {
        const assignSpy = mockWindowLocationAssign();

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { controller } = await getController(application);

        controller.searchValue = 'alice';
        controller.pageValue = 4;
        controller.pageSizeValue = 10;
        controller.sortFieldValue = 'e.email';
        controller.sortDirectionValue = 'asc';

        const link = document.querySelector('[data-zhortein--datatable-bundle--datatable-export-mode-param="full"]');
        const event = createExportEvent(link, { exportMode: 'full', exportFormat: 'csv' });

        controller.export(event);

        const url = getAssignedLocationUrl(assignSpy);

        expect(url.pathname).toBe('/_zhortein/datatable/users/export/csv');
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

    it('uses custom export URL value when configured on the datatable root', async () => {
        const assignSpy = mockWindowLocationAssign();

        document.body.innerHTML = createDatatableHtml('', '/custom/users/export');
        application = startApplication();

        const { controller } = await getController(application);

        const link = document.querySelector('[data-zhortein--datatable-bundle--datatable-export-mode-param="current"]');
        const event = createExportEvent(link, { exportMode: 'current', exportFormat: 'csv' });

        controller.export(event);

        const url = getAssignedLocationUrl(assignSpy);

        expect(url.pathname).toBe('/custom/users/export');
        expect(url.searchParams.get('mode')).toBe('current');
    });

    it('preserves signed context parameters in export URLs', async () => {
        const assignSpy = mockWindowLocationAssign();

        document.body.innerHTML = createDatatableHtml(
            '',
            '/_zhortein/datatable/users/export/csv?_zd_instance=french-table&_zd_context=signed-token',
        );
        application = startApplication();

        const { controller } = await getController(application);
        const link = document.querySelector('[data-zhortein--datatable-bundle--datatable-export-mode-param="current"]');

        controller.export(createExportEvent(link, { exportMode: 'current', exportFormat: 'csv' }));

        const url = getAssignedLocationUrl(assignSpy);

        expect(url.searchParams.get('_zd_instance')).toBe('french-table');
        expect(url.searchParams.get('_zd_context')).toBe('signed-token');
        expect(url.searchParams.get('mode')).toBe('current');
    });

    it('falls back to link href when export URL value is missing', async () => {
        const assignSpy = mockWindowLocationAssign();

        document.body.innerHTML = `
            <div
                id="zhortein-datatable-users"
                data-controller="${CONTROLLER_IDENTIFIER}"
                data-${CONTROLLER_IDENTIFIER}-name-value="users"
                data-${CONTROLLER_IDENTIFIER}-fragments-url-value="/_zhortein/datatable/users/fragments"
                data-${CONTROLLER_IDENTIFIER}-auto-load-value="false"
            data-${CONTROLLER_IDENTIFIER}-hidden-class-value="d-none"
            data-${CONTROLLER_IDENTIFIER}-visible-class-value="d-flex"
            data-${CONTROLLER_IDENTIFIER}-status-error-class-value="text-danger"
            data-${CONTROLLER_IDENTIFIER}-status-success-class-value="text-success"
            data-${CONTROLLER_IDENTIFIER}-status-muted-class-value="text-body-secondary"
                data-${CONTROLLER_IDENTIFIER}-page-value="1"
                data-${CONTROLLER_IDENTIFIER}-page-size-value="25"
            >
                <a
                    href="/fallback/export/csv?existing=1"
                    data-action="${CONTROLLER_IDENTIFIER}#export"
                    data-${CONTROLLER_IDENTIFIER}-export-mode-param="current"
                >
                    CSV current view
                </a>
                <tbody data-${CONTROLLER_IDENTIFIER}-target="body"></tbody>
                <div data-${CONTROLLER_IDENTIFIER}-target="pagination"></div>
                <div data-${CONTROLLER_IDENTIFIER}-target="summary"></div>
            </div>
        `;

        application = startApplication();

        const { controller } = await getController(application);

        const link = document.querySelector('a');
        const event = createExportEvent(link, { exportMode: 'current' });

        controller.export(event);

        const url = getAssignedLocationUrl(assignSpy);

        expect(url.pathname).toBe('/fallback/export/csv');
        expect(url.searchParams.get('existing')).toBe('1');
        expect(url.searchParams.get('mode')).toBe('current');
    });

    it('ignores export event when target is not an anchor', async () => {
        const assignSpy = mockWindowLocationAssign();

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { controller } = await getController(application);

        const event = createExportEvent(document.createElement('button'), { exportMode: 'current' });

        controller.export(event);

        expect(event.preventDefault).toHaveBeenCalledTimes(1);
        expect(assignSpy).not.toHaveBeenCalled();
    });

    it('uses current mode by default when mode param is missing', async () => {
        const assignSpy = mockWindowLocationAssign();

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { controller } = await getController(application);

        const link = document.querySelector('[data-zhortein--datatable-bundle--datatable-export-mode-param="current"]');
        const event = createExportEvent(link, {});

        controller.export(event);

        const url = getAssignedLocationUrl(assignSpy);

        expect(url.searchParams.get('mode')).toBe('current');
        expect(url.searchParams.has('page')).toBe(true);
        expect(url.searchParams.has('pageSize')).toBe(true);
    });
});
