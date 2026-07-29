import { Application } from '@hotwired/stimulus';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
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
            data-${CONTROLLER_IDENTIFIER}-page-value="4"
            data-${CONTROLLER_IDENTIFIER}-page-size-value="25"
        >
            <input
                type="search"
                value=""
                data-${CONTROLLER_IDENTIFIER}-target="searchInput"
                data-action="input->${CONTROLLER_IDENTIFIER}#search"
            >

            <input
                name="filters[email]"
                type="text"
                value=""
                data-${CONTROLLER_IDENTIFIER}-filter-control="true"
                data-action="input->${CONTROLLER_IDENTIFIER}#changeFilter change->${CONTROLLER_IDENTIFIER}#changeFilter"
            >

            <select
                name="filters[enabled]"
                data-${CONTROLLER_IDENTIFIER}-filter-control="true"
                data-action="change->${CONTROLLER_IDENTIFIER}#changeFilter"
            >
                <option value="">Enabled</option>
                <option value="1">Yes</option>
                <option value="0">No</option>
            </select>

            <input
                name="filters[ignored_empty]"
                type="text"
                value=""
                data-${CONTROLLER_IDENTIFIER}-filter-control="true"
                data-action="input->${CONTROLLER_IDENTIFIER}#changeFilter change->${CONTROLLER_IDENTIFIER}#changeFilter"
            >

            <input
                name="not_a_filter"
                type="text"
                value="must-not-be-serialized"
                data-${CONTROLLER_IDENTIFIER}-filter-control="true"
            >

            <select
                data-${CONTROLLER_IDENTIFIER}-target="pageSizeInput"
                data-action="change->${CONTROLLER_IDENTIFIER}#changePageSize"
            >
                <option value="10">10</option>
                <option value="25" selected>25</option>
                <option value="50">50</option>
            </select>

            <div
                data-${CONTROLLER_IDENTIFIER}-target="activeFilters"
                data-active-filter-count="0"
                hidden
            ></div>

            <button
                type="button"
                data-${CONTROLLER_IDENTIFIER}-target="clearFiltersButton"
                data-action="${CONTROLLER_IDENTIFIER}#clearFilters"
                hidden
                disabled
            >
                Clear filters
            </button>

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
            body: '<tr><td>loaded</td></tr>',
            pagination: '',
            summary: 'loaded',
            page: 1,
            pageSize: 25,
            ...payload,
        }),
    };
}

/**
 * Flush only microtasks. Does NOT use setTimeout, so it works
 * regardless of whether fake timers are active.
 */
async function flushPromises() {
    for (let i = 0; i < 20; i++) {
        await Promise.resolve();
    }
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

describe('datatable_controller search, filters and page size interactions', () => {
    let application = null;

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

    it('serializes search value and resets page to 1 after debounce', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(createJsonResponse()));
        vi.stubGlobal('fetch', fetchMock);

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { controller } = await getController(application);
        controller.pageValue = 4;

        const searchInput = document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="searchInput"]`);
        searchInput.value = 'alice';

        // Activate fake timers only right before the debounced action
        vi.useFakeTimers();

        controller.search({ target: searchInput });

        expect(controller.pageValue).toBe(1);
        expect(fetchMock).not.toHaveBeenCalled();

        vi.advanceTimersByTime(300);

        // Switch back to real timers so the fetch promise chain can resolve
        vi.useRealTimers();

        await flushPromises();

        const url = getLastRequestedUrl(fetchMock);

        expect(fetchMock).toHaveBeenCalledTimes(1);
        expect(url.searchParams.get('search')).toBe('alice');
        expect(url.searchParams.get('page')).toBe('1');
        expect(url.searchParams.get('pageSize')).toBe('25');
    });

    it('serializes non-empty filter controls and omits empty filters', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(createJsonResponse()));
        vi.stubGlobal('fetch', fetchMock);

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { controller } = await getController(application);
        controller.pageValue = 3;

        const emailFilter = document.querySelector('[name="filters[email]"]');
        const enabledFilter = document.querySelector('[name="filters[enabled]"]');
        const emptyFilter = document.querySelector('[name="filters[ignored_empty]"]');

        emailFilter.value = 'alice@example.test';
        enabledFilter.value = '1';
        emptyFilter.value = '';

        vi.useFakeTimers();

        controller.changeFilter();

        expect(controller.pageValue).toBe(1);

        vi.advanceTimersByTime(300);
        vi.useRealTimers();

        await flushPromises();

        const url = getLastRequestedUrl(fetchMock);

        expect(fetchMock).toHaveBeenCalledTimes(1);
        expect(url.searchParams.get('filters[email]')).toBe('alice@example.test');
        expect(url.searchParams.get('filters[enabled]')).toBe('1');
        expect(url.searchParams.has('filters[ignored_empty]')).toBe(false);
        expect(url.searchParams.has('not_a_filter')).toBe(false);
        expect(url.searchParams.get('page')).toBe('1');
    });

    it('serializes checked checkbox filters and ignores unchecked checkbox filters', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(createJsonResponse()));
        vi.stubGlobal('fetch', fetchMock);

        document.body.innerHTML = createDatatableHtml();

        const checkedFilter = document.createElement('input');
        checkedFilter.type = 'checkbox';
        checkedFilter.name = 'filters[archived]';
        checkedFilter.value = '1';
        checkedFilter.checked = true;
        checkedFilter.setAttribute(`data-${CONTROLLER_IDENTIFIER}-filter-control`, 'true');

        const uncheckedFilter = document.createElement('input');
        uncheckedFilter.type = 'checkbox';
        uncheckedFilter.name = 'filters[deleted]';
        uncheckedFilter.value = '1';
        uncheckedFilter.checked = false;
        uncheckedFilter.setAttribute(`data-${CONTROLLER_IDENTIFIER}-filter-control`, 'true');

        document.querySelector('#zhortein-datatable-users').append(checkedFilter, uncheckedFilter);

        application = startApplication();

        const { controller } = await getController(application);

        vi.useFakeTimers();

        controller.changeFilter();

        vi.advanceTimersByTime(300);
        vi.useRealTimers();

        await flushPromises();

        const url = getLastRequestedUrl(fetchMock);

        expect(url.searchParams.get('filters[archived]')).toBe('1');
        expect(url.searchParams.has('filters[deleted]')).toBe(false);
    });

    it('updates active filter state before refresh', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(createJsonResponse()));
        vi.stubGlobal('fetch', fetchMock);

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { controller } = await getController(application);

        const emailFilter = document.querySelector('[name="filters[email]"]');
        const activeFilters = document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="activeFilters"]`);
        const clearButton = document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="clearFiltersButton"]`);

        // Le champ "not_a_filter" est présent dans le HTML pour vérifier
        // qu'il n'est pas sérialisé, mais il fausse le décompte des filtres actifs.
        // On le vide pour ce test.
        document.querySelector('[name="not_a_filter"]').value = '';

        emailFilter.value = 'alice';

        controller.changeFilter();

        expect(activeFilters.hidden).toBe(false);
        expect(activeFilters.dataset.activeFilterCount).toBe('1');
        expect(clearButton.hidden).toBe(false);
        expect(clearButton.disabled).toBe(false);
    });

    it('changes page size, resets page to 1 and refreshes immediately', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(createJsonResponse({
            pageSize: 50,
        })));
        vi.stubGlobal('fetch', fetchMock);

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { controller } = await getController(application);
        controller.pageValue = 4;

        const pageSizeInput = document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="pageSizeInput"]`);
        pageSizeInput.value = '50';

        controller.changePageSize({ target: pageSizeInput });

        await flushPromises();

        const url = getLastRequestedUrl(fetchMock);

        expect(fetchMock).toHaveBeenCalledTimes(1);
        expect(controller.pageValue).toBe(1);
        expect(controller.pageSizeValue).toBe(50);
        expect(url.searchParams.get('page')).toBe('1');
        expect(url.searchParams.get('pageSize')).toBe('50');
    });

    it('ignores invalid page size values', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(createJsonResponse()));
        vi.stubGlobal('fetch', fetchMock);

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { controller } = await getController(application);
        controller.pageSizeValue = 25;

        const pageSizeInput = document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="pageSizeInput"]`);
        pageSizeInput.value = 'invalid';

        controller.changePageSize({ target: pageSizeInput });

        await flushPromises();

        expect(fetchMock).not.toHaveBeenCalled();
        expect(controller.pageSizeValue).toBe(25);
    });

    it('clears filters, resets active state and refreshes immediately', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(createJsonResponse()));
        vi.stubGlobal('fetch', fetchMock);

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { controller } = await getController(application);

        const emailFilter = document.querySelector('[name="filters[email]"]');
        const enabledFilter = document.querySelector('[name="filters[enabled]"]');
        const activeFilters = document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="activeFilters"]`);
        const clearButton = document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="clearFiltersButton"]`);

        emailFilter.value = 'alice';
        enabledFilter.value = '1';

        controller.changeFilter();

        expect(activeFilters.hidden).toBe(false);

        controller.clearFilters();

        await flushPromises();

        const url = getLastRequestedUrl(fetchMock);

        expect(emailFilter.value).toBe('');
        expect(enabledFilter.value).toBe('');
        expect(activeFilters.hidden).toBe(true);
        expect(activeFilters.dataset.activeFilterCount).toBe('0');
        expect(clearButton.hidden).toBe(true);
        expect(clearButton.disabled).toBe(true);
        expect(url.searchParams.has('filters[email]')).toBe(false);
        expect(url.searchParams.has('filters[enabled]')).toBe(false);
    });
});