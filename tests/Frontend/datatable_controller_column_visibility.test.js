import { Application } from '@hotwired/stimulus';
import { afterEach, describe, expect, it, vi } from 'vitest';
import DatatableController from '../../assets/controllers/datatable_controller.js';

const CONTROLLER_IDENTIFIER = 'zhortein--datatable-bundle--datatable';

function createDatatableHtml() {
    return `
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
            data-${CONTROLLER_IDENTIFIER}-page-value="3"
            data-${CONTROLLER_IDENTIFIER}-page-size-value="25"
        >
            <div class="zhortein-datatable__column-visibility">
                <input
                    type="checkbox"
                    name="columns[e.email]"
                    value="1"
                    checked
                    data-${CONTROLLER_IDENTIFIER}-column-visibility-control="true"
                    data-${CONTROLLER_IDENTIFIER}-column-name="e.email"
                    data-action="change->${CONTROLLER_IDENTIFIER}#changeColumnVisibility"
                >

                <input
                    type="checkbox"
                    name="columns[e.displayName]"
                    value="1"
                    data-${CONTROLLER_IDENTIFIER}-column-visibility-control="true"
                    data-${CONTROLLER_IDENTIFIER}-column-name="e.displayName"
                    data-action="change->${CONTROLLER_IDENTIFIER}#changeColumnVisibility"
                >

                <input
                    type="checkbox"
                    name="columns[e.id]"
                    value="1"
                    checked
                    data-${CONTROLLER_IDENTIFIER}-column-visibility-control="true"
                    data-${CONTROLLER_IDENTIFIER}-column-name="e.id"
                    data-${CONTROLLER_IDENTIFIER}-definition-hidden="true"
                    data-action="change->${CONTROLLER_IDENTIFIER}#changeColumnVisibility"
                >
            </div>

            <table>
                <thead data-${CONTROLLER_IDENTIFIER}-target="header">
                    <tr><th>Email</th><th>Display name</th></tr>
                </thead>
                <tbody data-${CONTROLLER_IDENTIFIER}-target="body">
                    <tr><td>No data available.</td></tr>
                </tbody>
            </table>

            <div data-${CONTROLLER_IDENTIFIER}-target="pagination"></div>
            <div data-${CONTROLLER_IDENTIFIER}-target="summary"></div>

            <div
                class="alert alert-danger d-none"
                data-${CONTROLLER_IDENTIFIER}-target="error"
                aria-hidden="true"
            ></div>

            <div
                class="zhortein-datatable__loading d-none"
                data-${CONTROLLER_IDENTIFIER}-target="loading"
                aria-hidden="true"
            ></div>
        </div>
    `;
}

function createJsonResponse(payload = {}) {
    return {
        ok: true,
        json: () => Promise.resolve({
            header: `<thead data-${CONTROLLER_IDENTIFIER}-target="header"><tr><th>Email</th></tr></thead>`,
            body: '<tr><td>alice@example.test</td></tr>',
            pagination: '<nav>pagination</nav>',
            summary: 'loaded',
            page: 1,
            pageSize: 25,
            totalItems: 1,
            filteredItems: 1,
            totalPages: 1,
            ...payload,
        }),
    };
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

function getLastRequestedUrl(fetchMock) {
    const rawUrl = fetchMock.mock.calls.at(-1)[0];

    return new URL(rawUrl, window.location.origin);
}

describe('datatable_controller column visibility interactions', () => {
    let application = null;

    beforeEach(() => {
        vi.useFakeTimers();
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        vi.unstubAllGlobals();
        vi.restoreAllMocks();
        vi.useRealTimers();
        document.body.innerHTML = '';
    });

    it('serializes checked columns as visible columns and unchecked columns as hidden columns', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(createJsonResponse()));
        vi.stubGlobal('fetch', fetchMock);

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { controller } = await getController(application);

        controller.changeColumnVisibility();

        vi.advanceTimersByTime(150);
        await flushPromises();

        const url = getLastRequestedUrl(fetchMock);

        expect(url.searchParams.getAll('visibleColumns[]')).toEqual(['e.email']);
        expect(url.searchParams.getAll('hiddenColumns[]')).toEqual(['e.displayName']);
        expect(url.searchParams.getAll('visibleColumns[]')).not.toContain('e.id');
        expect(url.searchParams.getAll('hiddenColumns[]')).not.toContain('e.id');
    });

    it('resets current page to 1 when column visibility changes', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(createJsonResponse({
            page: 1,
        })));
        vi.stubGlobal('fetch', fetchMock);

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { controller } = await getController(application);

        controller.pageValue = 5;

        controller.changeColumnVisibility();

        expect(controller.pageValue).toBe(1);

        vi.advanceTimersByTime(150);
        await flushPromises();

        const url = getLastRequestedUrl(fetchMock);

        expect(url.searchParams.get('page')).toBe('1');
    });

    it('refreshes fragments when column visibility changes', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(createJsonResponse({
            header: `<thead data-${CONTROLLER_IDENTIFIER}-target="header"><tr><th>Email only</th></tr></thead>`,
            body: '<tr><td>alice@example.test</td></tr>',
            summary: '1 result.',
        })));
        vi.stubGlobal('fetch', fetchMock);

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { controller } = await getController(application);

        controller.changeColumnVisibility();

        expect(fetchMock).not.toHaveBeenCalled();

        vi.advanceTimersByTime(150);
        await flushPromises();

        expect(fetchMock).toHaveBeenCalledTimes(1);

        await flushPromises();

        expect(document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="header"]`).outerHTML).toContain('Email only');
        expect(document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="body"]`).innerHTML).toContain('alice@example.test');
        expect(document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="summary"]`).textContent).toBe('1 result.');
    });

    it('debounces multiple column visibility changes into one refresh', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(createJsonResponse()));
        vi.stubGlobal('fetch', fetchMock);

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { controller } = await getController(application);

        controller.changeColumnVisibility();
        controller.changeColumnVisibility();
        controller.changeColumnVisibility();

        vi.advanceTimersByTime(149);
        await flushPromises();

        expect(fetchMock).not.toHaveBeenCalled();

        vi.advanceTimersByTime(1);
        await flushPromises();

        expect(fetchMock).toHaveBeenCalledTimes(1);
    });

    it('updates serialization when a checkbox state changes', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(createJsonResponse()));
        vi.stubGlobal('fetch', fetchMock);

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { controller } = await getController(application);

        const emailCheckbox = document.querySelector('[data-zhortein--datatable-bundle--datatable-column-name="e.email"]');
        const displayNameCheckbox = document.querySelector('[data-zhortein--datatable-bundle--datatable-column-name="e.displayName"]');

        emailCheckbox.checked = false;
        displayNameCheckbox.checked = true;

        controller.changeColumnVisibility();

        vi.advanceTimersByTime(150);
        await flushPromises();

        const url = getLastRequestedUrl(fetchMock);

        expect(url.searchParams.getAll('visibleColumns[]')).toEqual(['e.displayName']);
        expect(url.searchParams.getAll('hiddenColumns[]')).toEqual(['e.email']);
    });

    it('ignores controls without column name', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(createJsonResponse()));
        vi.stubGlobal('fetch', fetchMock);

        document.body.innerHTML = createDatatableHtml();

        const invalidControl = document.createElement('input');
        invalidControl.type = 'checkbox';
        invalidControl.checked = true;
        invalidControl.setAttribute(`data-${CONTROLLER_IDENTIFIER}-column-visibility-control`, 'true');

        document.querySelector('#zhortein-datatable-users').append(invalidControl);

        application = startApplication();

        const { controller } = await getController(application);

        controller.changeColumnVisibility();

        vi.advanceTimersByTime(150);
        await flushPromises();

        const url = getLastRequestedUrl(fetchMock);

        expect(url.searchParams.getAll('visibleColumns[]')).toEqual(['e.email']);
        expect(url.searchParams.getAll('hiddenColumns[]')).toEqual(['e.displayName']);
    });
});
