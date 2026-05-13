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

function createDefaultPayload() {
    return {
        header: '<thead data-zhortein--datatable-bundle--datatable-target="header"><tr><th>Loaded Email</th></tr></thead>',
        body: '<tr><td>alice@example.test</td></tr>',
        pagination: '<nav>Pagination</nav>',
        summary: '1 result.',
        page: 1,
        pageSize: 25,
        totalItems: 1,
        filteredItems: 1,
        totalPages: 1,
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

describe('datatable_controller connect and auto-load behavior', () => {
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

    it('connects to the datatable element', async () => {
        document.body.innerHTML = createDatatableHtml(`
            data-${CONTROLLER_IDENTIFIER}-fragments-url-value="/_zhortein/datatable/users/fragments"
            data-${CONTROLLER_IDENTIFIER}-auto-load-value="false"
        `);

        application = startApplication();

        await flushPromises();

        const element = document.querySelector('#zhortein-datatable-users');
        const controller = application.getControllerForElementAndIdentifier(element, CONTROLLER_IDENTIFIER);

        expect(controller).toBeInstanceOf(DatatableController);
        expect(controller.nameValue).toBe('users');
        expect(controller.autoLoadValue).toBe(false);
    });

    it('auto-loads fragments on connect by default', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(createJsonResponse(createDefaultPayload())));
        vi.stubGlobal('fetch', fetchMock);

        document.body.innerHTML = createDatatableHtml(`
            data-${CONTROLLER_IDENTIFIER}-fragments-url-value="/_zhortein/datatable/users/fragments"
        `);

        application = startApplication();

        await flushPromises();

        expect(fetchMock).toHaveBeenCalledTimes(1);
        expect(fetchMock.mock.calls[0][0]).toContain('/_zhortein/datatable/users/fragments');
        expect(fetchMock.mock.calls[0][0]).toContain('page=1');
        expect(fetchMock.mock.calls[0][0]).toContain('pageSize=25');
        expect(document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="body"]`).innerHTML).toContain('alice@example.test');
        expect(document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="summary"]`).textContent).toBe('1 result.');
    });

    it('does not auto-load fragments when autoLoad is false', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(createJsonResponse(createDefaultPayload())));
        vi.stubGlobal('fetch', fetchMock);

        document.body.innerHTML = createDatatableHtml(`
            data-${CONTROLLER_IDENTIFIER}-fragments-url-value="/_zhortein/datatable/users/fragments"
            data-${CONTROLLER_IDENTIFIER}-auto-load-value="false"
        `);

        application = startApplication();

        await flushPromises();

        expect(fetchMock).not.toHaveBeenCalled();
        expect(document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="body"]`).innerHTML).toContain('No data available.');
    });

    it('shows an error when fragments URL is missing and auto-load is enabled', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(createJsonResponse(createDefaultPayload())));
        vi.stubGlobal('fetch', fetchMock);

        document.body.innerHTML = createDatatableHtml('');

        application = startApplication();

        await flushPromises();

        const errorTarget = document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="error"]`);

        expect(fetchMock).not.toHaveBeenCalled();
        expect(errorTarget.textContent).toBe('The datatable fragments URL is missing.');
        expect(errorTarget.classList.contains('d-none')).toBe(false);
        expect(errorTarget.getAttribute('aria-hidden')).toBe(null);
    });

    it('shows an error when fragments URL is empty and auto-load is enabled', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(createJsonResponse(createDefaultPayload())));
        vi.stubGlobal('fetch', fetchMock);

        document.body.innerHTML = createDatatableHtml(`
            data-${CONTROLLER_IDENTIFIER}-fragments-url-value=""
        `);

        application = startApplication();

        await flushPromises();

        const errorTarget = document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="error"]`);

        expect(fetchMock).not.toHaveBeenCalled();
        expect(errorTarget.textContent).toBe('The datatable fragments URL is missing.');
        expect(errorTarget.classList.contains('d-none')).toBe(false);
    });
});
