import { Application } from '@hotwired/stimulus';
import { afterEach, describe, expect, it, vi } from 'vitest';
import DatatableController from '../../assets/controllers/datatable_controller.js';

const CONTROLLER_IDENTIFIER = 'zhortein--datatable-bundle--datatable';

async function getController(application) {
    await flushPromises();

    const element = document.querySelector('#zhortein-datatable-users');
    const controller = application.getControllerForElementAndIdentifier(element, CONTROLLER_IDENTIFIER);

    expect(controller).toBeInstanceOf(DatatableController);

    return { element, controller };
}

function createDatatableHtml(attributes = '') {
    return `
        <div
            id="zhortein-datatable-users"
            data-controller="${CONTROLLER_IDENTIFIER}"
            data-${CONTROLLER_IDENTIFIER}-name-value="users"
            data-${CONTROLLER_IDENTIFIER}-fragments-url-value="/_zhortein/datatable/users/fragments"
            data-${CONTROLLER_IDENTIFIER}-auto-load-value="false"
            data-${CONTROLLER_IDENTIFIER}-page-value="1"
            data-${CONTROLLER_IDENTIFIER}-page-size-value="25"
            ${attributes}
        >
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

            <select data-${CONTROLLER_IDENTIFIER}-target="pageSizeInput">
                <option value="10">10</option>
                <option value="25" selected>25</option>
                <option value="50">50</option>
            </select>

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

function createJsonResponse(payload) {
    return {
        ok: true,
        json: () => Promise.resolve(payload),
    };
}

function createPayload(overrides = {}) {
    return {
        header: `<thead data-${CONTROLLER_IDENTIFIER}-target="header"><tr><th>Loaded Email</th><th>Name</th></tr></thead>`,
        body: '<tr><td>alice@example.test</td><td>Alice</td></tr>',
        pagination: '<nav class="pagination-test">Pagination loaded</nav>',
        summary: 'Showing 1 to 1 of 1 result.',
        page: 2,
        pageSize: 50,
        totalItems: 1,
        filteredItems: 1,
        totalPages: 1,
        ...overrides,
    };
}

async function flushPromises() {
    await Promise.resolve();
    await Promise.resolve();
    await new Promise((resolve) => window.setTimeout(resolve, 0));
}

function startApplication() {
    const application = Application.start();
    application.register(CONTROLLER_IDENTIFIER, DatatableController);

    return application;
}

describe('datatable_controller Ajax fragment application', () => {
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

    it('applies header body pagination and summary fragments', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(createJsonResponse(createPayload())));
        vi.stubGlobal('fetch', fetchMock);

        document.body.innerHTML = createDatatableHtml();

        application = startApplication();

        const { element, controller } = await getController(application);

        controller.refresh();

        await flushPromises();

        expect(document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="header"]`).outerHTML).toContain('Loaded Email');
        expect(document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="header"]`).outerHTML).toContain('Name');
        expect(document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="body"]`).innerHTML).toContain('alice@example.test');
        expect(document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="body"]`).innerHTML).toContain('Alice');
        expect(document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="pagination"]`).innerHTML).toContain('Pagination loaded');
        expect(document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="summary"]`).textContent).toBe('Showing 1 to 1 of 1 result.');
    });

    it('updates page and page size values from payload state', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(createJsonResponse(createPayload({
            page: 3,
            pageSize: 10,
        }))));
        vi.stubGlobal('fetch', fetchMock);

        document.body.innerHTML = createDatatableHtml();

        application = startApplication();

        const { element, controller } = await getController(application);

        controller.refresh();

        await flushPromises();

        expect(controller.pageValue).toBe(3);
        expect(controller.pageSizeValue).toBe(10);
        expect(document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="pageSizeInput"]`).value).toBe('10');
    });

    it('ignores invalid page and page size values from payload state', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(createJsonResponse(createPayload({
            page: 0,
            pageSize: -1,
        }))));
        vi.stubGlobal('fetch', fetchMock);

        document.body.innerHTML = createDatatableHtml();

        application = startApplication();

        const { element, controller } = await getController(application);

        controller.pageValue = 5;
        controller.pageSizeValue = 25;

        controller.refresh();

        await flushPromises();

        expect(controller.pageValue).toBe(5);
        expect(controller.pageSizeValue).toBe(25);
        expect(document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="pageSizeInput"]`).value).toBe('25');
    });

    it('does not fail when optional fragments are missing from payload', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(createJsonResponse({
            body: '<tr><td>body only</td></tr>',
        })));
        vi.stubGlobal('fetch', fetchMock);

        document.body.innerHTML = createDatatableHtml();

        application = startApplication();

        const { element, controller } = await getController(application);

        controller.refresh();

        await flushPromises();

        expect(document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="body"]`).innerHTML).toContain('body only');
        expect(document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="header"]`).outerHTML).toContain('Email');
        expect(document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="pagination"]`).innerHTML).toBe('');
        expect(document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="summary"]`).textContent).toBe('');
    });

    it('shows an error when the fragment request fails', async () => {
        const fetchMock = vi.fn(() => Promise.resolve({
            ok: false,
            json: () => Promise.resolve({}),
        }));
        vi.stubGlobal('fetch', fetchMock);

        document.body.innerHTML = createDatatableHtml();

        application = startApplication();

        const { element, controller } = await getController(application);

        controller.refresh();

        await flushPromises();

        const errorTarget = document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="error"]`);

        expect(errorTarget.textContent).toBe('Unable to refresh datatable "users".');
        expect(errorTarget.classList.contains('d-none')).toBe(false);
        expect(errorTarget.getAttribute('aria-hidden')).toBe(null);
    });

    it('toggles loading state during refresh', async () => {
        let resolveFetch;
        const fetchPromise = new Promise((resolve) => {
            resolveFetch = resolve;
        });

        const fetchMock = vi.fn(() => fetchPromise);
        vi.stubGlobal('fetch', fetchMock);

        document.body.innerHTML = createDatatableHtml();

        application = startApplication();

        const { element, controller } = await getController(application);
        const loadingTarget = document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="loading"]`);

        controller.refresh();

        expect(element.hasAttribute('aria-busy')).toBe(true);
        expect(loadingTarget.classList.contains('d-flex')).toBe(true);
        expect(loadingTarget.classList.contains('d-none')).toBe(false);

        resolveFetch(createJsonResponse(createPayload()));

        await flushPromises();

        expect(element.hasAttribute('aria-busy')).toBe(false);
        expect(loadingTarget.classList.contains('d-none')).toBe(true);
        expect(loadingTarget.classList.contains('d-flex')).toBe(false);
    });

    it('applies header fragment if a non-input element (like a button) is focused in the header', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(createJsonResponse(createPayload({
            header: `<thead data-${CONTROLLER_IDENTIFIER}-target="header"><tr><th>Updated Header</th></tr></thead>`,
        }))));
        vi.stubGlobal('fetch', fetchMock);

        document.body.innerHTML = createDatatableHtml();
        const header = document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="header"]`);
        header.innerHTML = '<tr><th><button id="focusable-button">Email</button></th></tr>';

        application = startApplication();
        const { controller } = await getController(application);

        const button = document.getElementById('focusable-button');
        button.focus();
        expect(document.activeElement).toBe(button);

        controller.refresh();
        await flushPromises();

        expect(document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="header"]`).outerHTML).toContain('Updated Header');
        expect(document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="header"]`).outerHTML).not.toContain('Email');
    });
});
