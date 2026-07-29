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
            data-${CONTROLLER_IDENTIFIER}-page-value="2"
            data-${CONTROLLER_IDENTIFIER}-page-size-value="50"
            data-${CONTROLLER_IDENTIFIER}-action-success-message-value="Action completed."
            data-${CONTROLLER_IDENTIFIER}-action-error-message-value="Action failed."
            data-${CONTROLLER_IDENTIFIER}-invalid-action-response-message-value="Invalid action response."
        >
            <input
                name="filters[status]"
                value="active"
                data-${CONTROLLER_IDENTIFIER}-filter-control="true"
            >
            <input
                type="checkbox"
                checked
                data-${CONTROLLER_IDENTIFIER}-column-visibility-control="true"
                data-${CONTROLLER_IDENTIFIER}-column-name="email"
            >

            <a
                id="global-ajax-action"
                href="/users/synchronize"
                data-${CONTROLLER_IDENTIFIER}-ajax-action="true"
                data-${CONTROLLER_IDENTIFIER}-ajax-action-name="synchronize"
                data-${CONTROLLER_IDENTIFIER}-ajax-success-strategy="none"
                data-action="click->${CONTROLLER_IDENTIFIER}#executeAjaxAction"
            >Synchronize</a>

            <form
                id="bulk-remove-form"
                method="post"
                action="/users/bulk-delete"
                data-${CONTROLLER_IDENTIFIER}-selected-rows-parameter-name="ids"
                data-${CONTROLLER_IDENTIFIER}-ajax-action="true"
                data-${CONTROLLER_IDENTIFIER}-ajax-action-name="bulk-delete"
                data-${CONTROLLER_IDENTIFIER}-ajax-success-strategy="remove_row"
                data-action="submit->${CONTROLLER_IDENTIFIER}#submitBulkAction"
            >
                <input type="hidden" name="_method" value="DELETE">
                <input type="hidden" name="_token" value="bulk-csrf-token">
                <button type="submit">Delete selected</button>
            </form>

            <table>
                <thead data-${CONTROLLER_IDENTIFIER}-target="header"><tr><th>Email</th></tr></thead>
                <tbody data-${CONTROLLER_IDENTIFIER}-target="body">
                    <tr data-${CONTROLLER_IDENTIFIER}-row-identifier="1">
                        <td>alice@example.test</td>
                        <td>
                            <form
                                id="row-ajax-action"
                                method="post"
                                action="/users/1/archive"
                                data-${CONTROLLER_IDENTIFIER}-ajax-action="true"
                                data-${CONTROLLER_IDENTIFIER}-ajax-action-name="archive"
                                data-${CONTROLLER_IDENTIFIER}-ajax-success-strategy="none"
                                data-action="submit->${CONTROLLER_IDENTIFIER}#executeAjaxAction"
                            >
                                <input type="hidden" name="_method" value="PATCH">
                                <input type="hidden" name="_token" value="row-csrf-token">
                                <button type="submit">Archive</button>
                            </form>
                        </td>
                        <td>
                            <form
                                id="row-refresh-action"
                                method="post"
                                action="/users/1/refresh"
                                data-${CONTROLLER_IDENTIFIER}-ajax-action="true"
                                data-${CONTROLLER_IDENTIFIER}-ajax-action-name="refresh"
                                data-${CONTROLLER_IDENTIFIER}-ajax-success-strategy="refresh_row"
                                data-action="submit->${CONTROLLER_IDENTIFIER}#executeAjaxAction"
                            >
                                <button type="submit">Refresh</button>
                            </form>
                        </td>
                        <td>
                            <form
                                id="row-remove-action"
                                method="post"
                                action="/users/1/delete"
                                data-${CONTROLLER_IDENTIFIER}-ajax-action="true"
                                data-${CONTROLLER_IDENTIFIER}-ajax-action-name="delete"
                                data-${CONTROLLER_IDENTIFIER}-ajax-success-strategy="remove_row"
                                data-action="submit->${CONTROLLER_IDENTIFIER}#executeAjaxAction"
                                data-${CONTROLLER_IDENTIFIER}-confirmation-message="Delete this user?"
                            >
                                <button type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <tr data-${CONTROLLER_IDENTIFIER}-row-identifier="2">
                        <td>bob@example.test</td>
                        <td>
                            <input
                                type="checkbox"
                                value="2"
                                data-${CONTROLLER_IDENTIFIER}-target="rowCheckbox"
                            >
                        </td>
                    </tr>
                </tbody>
            </table>

            <div data-${CONTROLLER_IDENTIFIER}-target="pagination"></div>
            <div data-${CONTROLLER_IDENTIFIER}-target="summary"></div>
            <div class="d-none" data-${CONTROLLER_IDENTIFIER}-target="feedback" aria-hidden="true"></div>
            <div class="d-none" data-${CONTROLLER_IDENTIFIER}-target="error" aria-hidden="true"></div>
        </div>
    `;
}

function createJsonResponse(payload, ok = true) {
    return {
        ok,
        json: () => Promise.resolve(payload),
    };
}

function createSuccessPayload(overrides = {}) {
    return {
        version: 1,
        ok: true,
        message: null,
        errors: [],
        redirect: null,
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

async function getController(application) {
    await flushPromises();

    const element = document.querySelector('#zhortein-datatable-users');
    const controller = application.getControllerForElementAndIdentifier(element, CONTROLLER_IDENTIFIER);

    expect(controller).toBeInstanceOf(DatatableController);

    return { controller, element };
}

function submit(element) {
    const event = new Event('submit', { bubbles: true, cancelable: true });
    element.dispatchEvent(event);

    return event;
}

describe('datatable_controller Ajax actions', () => {
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

    it('submits non-GET forms with method override, CSRF and lifecycle events', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(createJsonResponse(createSuccessPayload({
            message: 'User archived.',
        }))));
        vi.stubGlobal('fetch', fetchMock);
        document.body.innerHTML = createDatatableHtml();
        application = startApplication();
        const { element } = await getController(application);
        const successListener = vi.fn();
        const completeListener = vi.fn();
        element.addEventListener('zhortein-datatable:action:success', successListener);
        element.addEventListener('zhortein-datatable:action:complete', completeListener);

        const form = document.querySelector('#row-ajax-action');
        const event = submit(form);
        await flushPromises();

        expect(event.defaultPrevented).toBe(true);
        expect(fetchMock).toHaveBeenCalledTimes(1);
        expect(new URL(fetchMock.mock.calls[0][0]).pathname).toBe('/users/1/archive');
        expect(fetchMock.mock.calls[0][1].method).toBe('POST');
        expect(fetchMock.mock.calls[0][1].headers.Accept).toContain('version=1');
        expect(fetchMock.mock.calls[0][1].body.get('_method')).toBe('PATCH');
        expect(fetchMock.mock.calls[0][1].body.get('_token')).toBe('row-csrf-token');
        expect(document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="feedback"]`).textContent).toBe('User archived.');
        expect(successListener).toHaveBeenCalledTimes(1);
        expect(successListener.mock.calls[0][0].detail.action).toBe('archive');
        expect(successListener.mock.calls[0][0].detail.payload.version).toBe(1);
        expect(completeListener).toHaveBeenCalledTimes(1);
    });

    it('prevents duplicate submissions and restores the loading state', async () => {
        let resolveFetch;
        const fetchMock = vi.fn(() => new Promise((resolve) => {
            resolveFetch = resolve;
        }));
        vi.stubGlobal('fetch', fetchMock);
        document.body.innerHTML = createDatatableHtml();
        application = startApplication();
        await getController(application);

        const form = document.querySelector('#row-ajax-action');
        const button = form.querySelector('button');
        submit(form);
        submit(form);

        expect(fetchMock).toHaveBeenCalledTimes(1);
        expect(form.getAttribute('aria-busy')).toBe('');
        expect(button.disabled).toBe(true);

        resolveFetch(createJsonResponse(createSuccessPayload()));
        await flushPromises();

        expect(form.hasAttribute('aria-busy')).toBe(false);
        expect(button.disabled).toBe(false);
    });

    it('reports HTTP and business failures through neutral feedback and an event', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(createJsonResponse({
            version: 1,
            ok: false,
            message: null,
            errors: [{ message: 'The protected account cannot be archived.', code: 'protected_account' }],
            redirect: null,
        }, false)));
        vi.stubGlobal('fetch', fetchMock);
        document.body.innerHTML = createDatatableHtml();
        application = startApplication();
        const { element } = await getController(application);
        const errorListener = vi.fn();
        element.addEventListener('zhortein-datatable:action:error', errorListener);

        submit(document.querySelector('#row-ajax-action'));
        await flushPromises();

        expect(document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="error"]`).textContent)
            .toBe('The protected account cannot be archived.');
        expect(errorListener).toHaveBeenCalledTimes(1);
        expect(errorListener.mock.calls[0][0].detail.payload.errors[0].code).toBe('protected_account');
        expect(errorListener.mock.calls[0][0].detail.response.ok).toBe(false);
    });

    it('rejects unversioned responses', async () => {
        vi.stubGlobal('fetch', vi.fn(() => Promise.resolve(createJsonResponse({ ok: true }))));
        document.body.innerHTML = createDatatableHtml();
        application = startApplication();
        await getController(application);

        submit(document.querySelector('#row-ajax-action'));
        await flushPromises();

        expect(document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="error"]`).textContent)
            .toBe('Invalid action response.');
    });

    it('refreshes the complete table with the current state after success', async () => {
        const fetchMock = vi.fn()
            .mockResolvedValueOnce(createJsonResponse(createSuccessPayload()))
            .mockResolvedValueOnce(createJsonResponse({
                header: `<thead data-${CONTROLLER_IDENTIFIER}-target="header"><tr><th>Updated</th></tr></thead>`,
                body: `<tr data-${CONTROLLER_IDENTIFIER}-row-identifier="3"><td>carol@example.test</td></tr>`,
                pagination: '<nav>Updated pagination</nav>',
                summary: 'Updated summary',
                page: 2,
                pageSize: 50,
            }));
        vi.stubGlobal('fetch', fetchMock);
        document.body.innerHTML = createDatatableHtml();
        document.querySelector('#zhortein-datatable-users').setAttribute(
            `data-${CONTROLLER_IDENTIFIER}-fragments-url-value`,
            '/_zhortein/datatable/users/fragments?_zd_instance=french-table&_zd_context=signed-token',
        );
        document.querySelector('#row-ajax-action')
            .setAttribute(`data-${CONTROLLER_IDENTIFIER}-ajax-success-strategy`, 'refresh_table');
        application = startApplication();
        const { controller } = await getController(application);
        controller.searchValue = 'alice';
        controller.sortFieldValue = 'email';
        controller.sortDirectionValue = 'desc';

        submit(document.querySelector('#row-ajax-action'));
        await flushPromises();

        expect(fetchMock).toHaveBeenCalledTimes(2);
        const fragmentsUrl = new URL(fetchMock.mock.calls[1][0]);
        expect(fragmentsUrl.searchParams.get('page')).toBe('2');
        expect(fragmentsUrl.searchParams.get('pageSize')).toBe('50');
        expect(fragmentsUrl.searchParams.get('search')).toBe('alice');
        expect(fragmentsUrl.searchParams.get('sortField')).toBe('email');
        expect(fragmentsUrl.searchParams.get('sortDirection')).toBe('desc');
        expect(fragmentsUrl.searchParams.get('filters[status]')).toBe('active');
        expect(fragmentsUrl.searchParams.getAll('visibleColumns[]')).toEqual(['email']);
        expect(fragmentsUrl.searchParams.get('_zd_instance')).toBe('french-table');
        expect(fragmentsUrl.searchParams.get('_zd_context')).toBe('signed-token');
        expect(document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="body"]`).textContent)
            .toContain('carol@example.test');
    });

    it('refreshes only the affected row from the current fragments', async () => {
        const fetchMock = vi.fn()
            .mockResolvedValueOnce(createJsonResponse(createSuccessPayload()))
            .mockResolvedValueOnce(createJsonResponse({
                body: `
                    <tr data-${CONTROLLER_IDENTIFIER}-row-identifier="1"><td>alice+updated@example.test</td></tr>
                    <tr data-${CONTROLLER_IDENTIFIER}-row-identifier="2"><td>bob+updated@example.test</td></tr>
                `,
            }));
        vi.stubGlobal('fetch', fetchMock);
        document.body.innerHTML = createDatatableHtml();
        application = startApplication();
        await getController(application);

        submit(document.querySelector('#row-refresh-action'));
        await flushPromises();

        const body = document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="body"]`);
        expect(body.textContent).toContain('alice+updated@example.test');
        expect(body.textContent).toContain('bob@example.test');
        expect(body.textContent).not.toContain('bob+updated@example.test');
    });

    it('confirms and removes a row after success', async () => {
        const confirmMock = vi.fn(() => true);
        vi.stubGlobal('confirm', confirmMock);
        vi.stubGlobal('fetch', vi.fn(() => Promise.resolve(createJsonResponse(createSuccessPayload()))));
        document.body.innerHTML = createDatatableHtml();
        application = startApplication();
        await getController(application);

        submit(document.querySelector('#row-remove-action'));
        await flushPromises();

        expect(confirmMock).toHaveBeenCalledWith('Delete this user?');
        expect(document.querySelector(`tr[data-${CONTROLLER_IDENTIFIER}-row-identifier="1"]`)).toBe(null);
        expect(document.querySelector(`tr[data-${CONTROLLER_IDENTIFIER}-row-identifier="2"]`)).not.toBe(null);
    });

    it('sends selected identifiers and removes affected rows for a bulk action', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(createJsonResponse(createSuccessPayload())));
        vi.stubGlobal('fetch', fetchMock);
        document.body.innerHTML = createDatatableHtml();
        application = startApplication();
        const { controller } = await getController(application);
        controller.selectedIds.add('2');

        const event = submit(document.querySelector('#bulk-remove-form'));
        await flushPromises();

        expect(event.defaultPrevented).toBe(true);
        expect(fetchMock.mock.calls[0][1].body.getAll('ids[]')).toEqual(['2']);
        expect(fetchMock.mock.calls[0][1].body.get('_method')).toBe('DELETE');
        expect(fetchMock.mock.calls[0][1].body.get('_token')).toBe('bulk-csrf-token');
        expect(document.querySelector(`tr[data-${CONTROLLER_IDENTIFIER}-row-identifier="2"]`)).toBe(null);
        expect(controller.selectedIds.size).toBe(0);
    });

    it('executes global GET actions and supports cancellation through the before event', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(createJsonResponse(createSuccessPayload())));
        vi.stubGlobal('fetch', fetchMock);
        document.body.innerHTML = createDatatableHtml();
        application = startApplication();
        const { element } = await getController(application);
        const link = document.querySelector('#global-ajax-action');
        const cancel = (event) => event.preventDefault();
        element.addEventListener('zhortein-datatable:action:before', cancel);

        link.click();
        await flushPromises();
        expect(fetchMock).not.toHaveBeenCalled();

        element.removeEventListener('zhortein-datatable:action:before', cancel);
        link.click();
        await flushPromises();

        expect(fetchMock).toHaveBeenCalledTimes(1);
        expect(fetchMock.mock.calls[0][1].method).toBe('GET');
        expect(new URL(fetchMock.mock.calls[0][0]).pathname).toBe('/users/synchronize');
    });

    it('redirects to the URL returned by the versioned response', async () => {
        const assignSpy = vi.fn();
        Object.defineProperty(window, 'location', {
            value: {
                origin: 'https://example.test',
                href: 'https://example.test/users',
                assign: assignSpy,
            },
            writable: true,
        });
        vi.stubGlobal('fetch', vi.fn(() => Promise.resolve(createJsonResponse(createSuccessPayload({
            redirect: '/users/42',
        })))));
        document.body.innerHTML = createDatatableHtml();
        document.querySelector('#row-ajax-action')
            .setAttribute(`data-${CONTROLLER_IDENTIFIER}-ajax-success-strategy`, 'redirect');
        application = startApplication();
        await getController(application);

        submit(document.querySelector('#row-ajax-action'));
        await flushPromises();

        expect(assignSpy).toHaveBeenCalledWith('https://example.test/users/42');
    });
});
