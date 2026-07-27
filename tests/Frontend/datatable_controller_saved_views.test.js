import { Application } from '@hotwired/stimulus';
import { afterEach, describe, expect, it, vi } from 'vitest';
import DatatableController from '../../assets/controllers/datatable_controller.js';

const CONTROLLER_IDENTIFIER = 'zhortein--datatable-bundle--datatable';
const STATE_PARAMETER = '_zd_state[users]';

function createState(overrides = {}) {
    return {
        version: 1,
        page: 1,
        pageSize: 25,
        search: null,
        sortField: null,
        sortDirection: 'asc',
        filters: {},
        advancedFilters: {},
        visibleColumns: ['email', 'status'],
        hiddenColumns: [],
        ...overrides,
    };
}

function createView(overrides = {}) {
    return {
        id: 'view-1',
        name: 'Active customers',
        revision: '1',
        default: true,
        includePage: false,
        state: createState({
            search: 'alice',
            filters: { status: 'active' },
            visibleColumns: ['email'],
            hiddenColumns: ['status'],
        }),
        ...overrides,
    };
}

function createHtml(id = 'users-table') {
    return `
        <div
            id="${id}"
            data-controller="${CONTROLLER_IDENTIFIER}"
            data-${CONTROLLER_IDENTIFIER}-name-value="users"
            data-${CONTROLLER_IDENTIFIER}-instance-value="${id}"
            data-${CONTROLLER_IDENTIFIER}-state-parameter-value="${STATE_PARAMETER}"
            data-${CONTROLLER_IDENTIFIER}-fragments-url-value="/_zhortein/datatable/users/fragments"
            data-${CONTROLLER_IDENTIFIER}-saved-views-url-value="/_zhortein/datatable/users/views?_zd_instance=${id}&_zd_view_scope=customers&_zd_view_locale=en"
            data-${CONTROLLER_IDENTIFIER}-saved-views-csrf-token-value="csrf-token"
            data-${CONTROLLER_IDENTIFIER}-saved-view-default-suffix-value="(default)"
            data-${CONTROLLER_IDENTIFIER}-saved-view-success-message-value="Saved"
            data-${CONTROLLER_IDENTIFIER}-saved-view-error-message-value="Failed"
            data-${CONTROLLER_IDENTIFIER}-saved-view-conflict-message-value="Conflict"
        >
            <select
                data-${CONTROLLER_IDENTIFIER}-target="savedViewSelect"
                data-action="change->${CONTROLLER_IDENTIFIER}#loadSavedView"
            >
                <option value="">Choose a view</option>
            </select>
            <input data-${CONTROLLER_IDENTIFIER}-target="savedViewName">
            <button data-${CONTROLLER_IDENTIFIER}-target="savedViewAction" disabled></button>
            <span data-${CONTROLLER_IDENTIFIER}-target="savedViewStatus"></span>
            <input type="search" data-${CONTROLLER_IDENTIFIER}-target="searchInput">
            <select
                name="filters[status]"
                data-${CONTROLLER_IDENTIFIER}-filter-control="true"
            >
                <option value=""></option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
            <input
                type="checkbox"
                checked
                data-${CONTROLLER_IDENTIFIER}-column-visibility-control="true"
                data-${CONTROLLER_IDENTIFIER}-column-name="email"
            >
            <input
                type="checkbox"
                checked
                data-${CONTROLLER_IDENTIFIER}-column-visibility-control="true"
                data-${CONTROLLER_IDENTIFIER}-column-name="status"
            >
            <table>
                <thead data-${CONTROLLER_IDENTIFIER}-target="header"></thead>
                <tbody data-${CONTROLLER_IDENTIFIER}-target="body"></tbody>
            </table>
            <div data-${CONTROLLER_IDENTIFIER}-target="pagination"></div>
            <div data-${CONTROLLER_IDENTIFIER}-target="summary"></div>
            <div data-${CONTROLLER_IDENTIFIER}-target="error" class="d-none"></div>
            <div data-${CONTROLLER_IDENTIFIER}-target="loading" class="d-none"></div>
        </div>
    `;
}

function jsonResponse(payload, status = 200) {
    return {
        ok: status >= 200 && status < 300,
        status,
        json: () => Promise.resolve(payload),
    };
}

function fragmentResponse(rawUrl) {
    const url = new URL(rawUrl, window.location.origin);

    return jsonResponse({
        header: '<tr><th>Email</th></tr>',
        body: '<tr><td>Loaded</td></tr>',
        pagination: '',
        summary: 'Loaded',
        page: Number.parseInt(url.searchParams.get('page') ?? '1', 10),
        pageSize: Number.parseInt(url.searchParams.get('pageSize') ?? '25', 10),
    });
}

function createFetchMock(view = createView()) {
    return vi.fn((rawUrl, options = {}) => {
        const url = new URL(rawUrl, window.location.origin);

        if (url.pathname.endsWith(`/views/${view.id}`) && (options.method ?? 'GET') === 'GET') {
            return Promise.resolve(jsonResponse({ version: 1, view }));
        }

        if (url.pathname.endsWith('/views') && (options.method ?? 'GET') === 'GET') {
            return Promise.resolve(jsonResponse({
                version: 1,
                views: [{
                    id: view.id,
                    name: view.name,
                    revision: view.revision,
                    default: view.default,
                }],
            }));
        }

        return Promise.resolve(fragmentResponse(rawUrl));
    });
}

async function flushPromises() {
    for (let index = 0; index < 40; index++) {
        await Promise.resolve();
    }

    await new Promise((resolve) => window.setTimeout(resolve, 0));
}

function startApplication() {
    const application = Application.start();
    application.register(CONTROLLER_IDENTIFIER, DatatableController);

    return application;
}

async function getController(application, id = 'users-table') {
    await flushPromises();
    const element = document.querySelector(`#${id}`);
    const controller = application.getControllerForElementAndIdentifier(element, CONTROLLER_IDENTIFIER);

    expect(controller).toBeInstanceOf(DatatableController);

    return { controller, element };
}

describe('datatable_controller named saved views', () => {
    let application = null;

    afterEach(async () => {
        if (application !== null) {
            application.stop();
            application = null;
        }

        document.body.innerHTML = '';
        vi.unstubAllGlobals();
        window.history.replaceState(null, '', '/');
        await flushPromises();
    });

    it('restores the default view before the initial fragments request', async () => {
        const fetchMock = createFetchMock();
        vi.stubGlobal('fetch', fetchMock);
        document.body.innerHTML = createHtml();
        application = startApplication();

        const { controller, element } = await getController(application);
        const fragmentCall = fetchMock.mock.calls.find(([url]) => new URL(url, window.location.origin).pathname.endsWith('/fragments'));
        const fragmentUrl = new URL(fragmentCall[0], window.location.origin);

        expect(controller.searchValue).toBe('alice');
        expect(element.querySelector('[name="filters[status]"]').value).toBe('active');
        expect(element.querySelector('[data-zhortein--datatable-bundle--datatable-column-name="status"]').checked).toBe(false);
        expect(fragmentUrl.searchParams.get('search')).toBe('alice');
        expect(fragmentUrl.searchParams.get('filters[status]')).toBe('active');
        expect(controller.defaultState.search).toBe('alice');
        expect(fetchMock.mock.calls.map(([url]) => new URL(url, window.location.origin).pathname)).toEqual([
            '/_zhortein/datatable/users/views',
            '/_zhortein/datatable/users/views/view-1',
            '/_zhortein/datatable/users/fragments',
        ]);
    });

    it('keeps URL state above the named default view', async () => {
        const url = new URL('/customers', window.location.origin);
        url.searchParams.set(STATE_PARAMETER, JSON.stringify(createState({ search: 'from-url' })));
        window.history.replaceState(null, '', url);

        const fetchMock = createFetchMock();
        vi.stubGlobal('fetch', fetchMock);
        document.body.innerHTML = createHtml();
        application = startApplication();

        const { controller } = await getController(application);

        expect(controller.searchValue).toBe('from-url');
        expect(fetchMock.mock.calls.some(([rawUrl]) => new URL(rawUrl, window.location.origin).pathname.endsWith('/views/view-1'))).toBe(false);
    });

    it('loads a selected view and refreshes the datatable with its complete state', async () => {
        const fetchMock = createFetchMock();
        vi.stubGlobal('fetch', fetchMock);
        document.body.innerHTML = createHtml();
        application = startApplication();

        const { controller, element } = await getController(application);
        const select = element.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="savedViewSelect"]`);
        select.value = 'view-1';

        controller.loadSavedView({ preventDefault: vi.fn() });
        await flushPromises();

        const lastFragmentCall = fetchMock.mock.calls
            .filter(([rawUrl]) => new URL(rawUrl, window.location.origin).pathname.endsWith('/fragments'))
            .at(-1);
        const fragmentUrl = new URL(lastFragmentCall[0], window.location.origin);

        expect(fragmentUrl.searchParams.get('search')).toBe('alice');
        expect(fragmentUrl.searchParams.get('filters[status]')).toBe('active');
        expect(select.value).toBe('view-1');
        expect(element.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="savedViewName"]`).value).toBe('Active customers');
    });

    it('normalizes legacy empty arrays used for saved-view map fields', async () => {
        const view = createView({
            state: createState({
                filters: [],
                advancedFilters: [],
            }),
        });
        const fetchMock = createFetchMock(view);
        vi.stubGlobal('fetch', fetchMock);
        document.body.innerHTML = createHtml();
        application = startApplication();

        const { controller, element } = await getController(application);

        expect(controller.defaultState.filters).toEqual({});
        expect(controller.defaultState.advancedFilters).toEqual({});
        expect(element.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="savedViewStatus"]`).textContent).toBe('');
    });

    it('rejects non-empty arrays used for saved-view map fields', async () => {
        const fetchMock = createFetchMock(createView({ default: false }));
        vi.stubGlobal('fetch', fetchMock);
        document.body.innerHTML = createHtml();
        application = startApplication();

        const { controller } = await getController(application);

        expect(() => controller.normalizeState(createState({
            filters: ['invalid'],
        }))).toThrowError('Invalid datatable URL state.');
        expect(() => controller.normalizeState(createState({
            advancedFilters: ['invalid'],
        }))).toThrowError('Invalid datatable URL state.');
    });

    it('creates a view with CSRF protection and excludes the current page by default', async () => {
        const view = createView({ default: false });
        const fetchMock = vi.fn((rawUrl, options = {}) => {
            const url = new URL(rawUrl, window.location.origin);

            if (url.pathname.endsWith('/views') && options.method === 'POST') {
                return Promise.resolve(jsonResponse({ version: 1, view }, 201));
            }

            if (url.pathname.endsWith('/views')) {
                return Promise.resolve(jsonResponse({ version: 1, views: [] }));
            }

            return Promise.resolve(fragmentResponse(rawUrl));
        });
        vi.stubGlobal('fetch', fetchMock);
        document.body.innerHTML = createHtml();
        application = startApplication();

        const { controller, element } = await getController(application);
        controller.pageValue = 4;
        element.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="savedViewName"]`).value = 'Active customers';
        controller.createSavedView({ preventDefault: vi.fn() });
        await flushPromises();

        const request = fetchMock.mock.calls.find(([, options]) => options.method === 'POST');
        const payload = JSON.parse(request[1].body);

        expect(request[1].headers['X-CSRF-Token']).toBe('csrf-token');
        expect(payload.name).toBe('Active customers');
        expect(payload.state.page).toBe(4);
        expect(payload.includePage).toBe(false);
    });

    it('surfaces optimistic concurrency conflicts without replacing the selected state', async () => {
        const view = createView({ default: false });
        const fetchMock = vi.fn((rawUrl, options = {}) => {
            const url = new URL(rawUrl, window.location.origin);

            if (url.pathname.endsWith(`/views/${view.id}`) && options.method === 'PATCH') {
                return Promise.resolve(jsonResponse({
                    version: 1,
                    error: { code: 'conflict', message: 'stale revision' },
                }, 409));
            }

            if (url.pathname.endsWith('/views')) {
                return Promise.resolve(jsonResponse({
                    version: 1,
                    views: [{
                        id: view.id,
                        name: view.name,
                        revision: view.revision,
                        default: view.default,
                    }],
                }));
            }

            return Promise.resolve(fragmentResponse(rawUrl));
        });
        vi.stubGlobal('fetch', fetchMock);
        document.body.innerHTML = createHtml();
        application = startApplication();

        const { controller, element } = await getController(application);
        const select = element.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="savedViewSelect"]`);
        select.value = view.id;
        controller.updateSavedViewActions();
        controller.updateSavedView({ preventDefault: vi.fn() });
        await flushPromises();

        expect(element.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="savedViewStatus"]`).textContent).toBe('Conflict');
        expect(select.value).toBe(view.id);
    });

    it('sends CSRF and opaque revisions for rename, default and delete mutations', async () => {
        const fetchMock = vi.fn((rawUrl, options = {}) => {
            const url = new URL(rawUrl, window.location.origin);

            if (options.method === 'DELETE') {
                return Promise.resolve(jsonResponse(null, 204));
            }

            if (options.method === 'PATCH') {
                return Promise.resolve(jsonResponse({
                    version: 1,
                    view: createView({ revision: '8' }),
                }));
            }

            if (url.pathname.endsWith('/views')) {
                return Promise.resolve(jsonResponse({ version: 1, views: [] }));
            }

            return Promise.resolve(fragmentResponse(rawUrl));
        });
        vi.stubGlobal('fetch', fetchMock);
        document.body.innerHTML = createHtml();
        application = startApplication();

        const { controller } = await getController(application);
        fetchMock.mockClear();

        await controller.requestSavedView('PATCH', 'view-1', {
            operation: 'rename',
            revision: '7',
            name: 'Renamed',
        });
        await controller.requestSavedView('PATCH', 'view-1', {
            operation: 'set_default',
            revision: '8',
        });
        await controller.requestSavedView('DELETE', 'view-1', {
            revision: '9',
        });

        const mutations = fetchMock.mock.calls.map(([rawUrl, options]) => ({
            url: new URL(rawUrl, window.location.origin),
            options,
            payload: JSON.parse(options.body),
        }));

        expect(mutations.map(({ url }) => url.pathname)).toEqual([
            '/_zhortein/datatable/users/views/view-1',
            '/_zhortein/datatable/users/views/view-1',
            '/_zhortein/datatable/users/views/view-1',
        ]);
        expect(mutations.map(({ options }) => options.headers['X-CSRF-Token'])).toEqual([
            'csrf-token',
            'csrf-token',
            'csrf-token',
        ]);
        expect(mutations.map(({ payload }) => payload.revision)).toEqual(['7', '8', '9']);
        expect(mutations.map(({ payload }) => payload.operation ?? 'delete')).toEqual([
            'rename',
            'set_default',
            'delete',
        ]);
    });

    it('keeps two table instances isolated through their configured view URLs', async () => {
        const fetchMock = createFetchMock(createView({ default: false }));
        vi.stubGlobal('fetch', fetchMock);
        document.body.innerHTML = `${createHtml('open-users')}${createHtml('archived-users')}`;
        application = startApplication();

        await getController(application, 'open-users');
        await getController(application, 'archived-users');

        const listUrls = fetchMock.mock.calls
            .map(([rawUrl]) => new URL(rawUrl, window.location.origin))
            .filter((url) => url.pathname.endsWith('/views'));

        expect(listUrls.map((url) => url.searchParams.get('_zd_instance'))).toEqual([
            'open-users',
            'archived-users',
        ]);
    });
});
