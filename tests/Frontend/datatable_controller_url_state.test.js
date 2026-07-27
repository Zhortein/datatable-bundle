import { Application } from '@hotwired/stimulus';
import { afterEach, describe, expect, it, vi } from 'vitest';
import DatatableController from '../../assets/controllers/datatable_controller.js';

const CONTROLLER_IDENTIFIER = 'zhortein--datatable-bundle--datatable';

function createDatatableHtml({
    id = 'users-table',
    name = 'users',
    stateParameter = '_zd_state[users-key]',
    autoLoad = true,
    searchBuilder = false,
} = {}) {
    return `
        <div
            id="${id}"
            data-controller="${CONTROLLER_IDENTIFIER}"
            data-${CONTROLLER_IDENTIFIER}-name-value="${name}"
            data-${CONTROLLER_IDENTIFIER}-instance-value="${id}"
            data-${CONTROLLER_IDENTIFIER}-state-parameter-value="${stateParameter}"
            data-${CONTROLLER_IDENTIFIER}-fragments-url-value="/_zhortein/datatable/${name}/fragments?_zd_context=signed"
            data-${CONTROLLER_IDENTIFIER}-export-url-value="/_zhortein/datatable/${name}/export/csv?_zd_context=signed"
            data-${CONTROLLER_IDENTIFIER}-auto-load-value="${autoLoad ? 'true' : 'false'}"
            data-${CONTROLLER_IDENTIFIER}-page-value="1"
            data-${CONTROLLER_IDENTIFIER}-page-size-value="25"
            data-${CONTROLLER_IDENTIFIER}-search-builder-value="${searchBuilder ? 'true' : 'false'}"
        >
            <input
                type="search"
                data-${CONTROLLER_IDENTIFIER}-target="searchInput"
                data-action="input->${CONTROLLER_IDENTIFIER}#search"
            >
            <input
                type="text"
                name="filters[email]"
                data-${CONTROLLER_IDENTIFIER}-filter-control="true"
            >
            <input
                type="date"
                name="filters[createdAt][from]"
                data-${CONTROLLER_IDENTIFIER}-filter-control="true"
            >
            <input
                type="date"
                name="filters[createdAt][to]"
                data-${CONTROLLER_IDENTIFIER}-filter-control="true"
            >
            <select data-${CONTROLLER_IDENTIFIER}-target="pageSizeInput">
                <option value="10">10</option>
                <option value="25" selected>25</option>
                <option value="50">50</option>
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
                data-${CONTROLLER_IDENTIFIER}-column-name="createdAt"
            >
            ${searchBuilder ? createSearchBuilderHtml() : ''}
            <div data-${CONTROLLER_IDENTIFIER}-target="activeFilters" hidden></div>
            <button data-${CONTROLLER_IDENTIFIER}-target="clearFiltersButton" hidden disabled></button>
            <table>
                <thead data-${CONTROLLER_IDENTIFIER}-target="header"></thead>
                <tbody data-${CONTROLLER_IDENTIFIER}-target="body"></tbody>
            </table>
            <div data-${CONTROLLER_IDENTIFIER}-target="pagination"></div>
            <div data-${CONTROLLER_IDENTIFIER}-target="summary"></div>
            <div class="d-none" data-${CONTROLLER_IDENTIFIER}-target="error"></div>
            <div class="d-none" data-${CONTROLLER_IDENTIFIER}-target="loading"></div>
        </div>
    `;
}

function createSearchBuilderHtml() {
    return `
        <div
            data-${CONTROLLER_IDENTIFIER}-target="searchBuilder"
            data-${CONTROLLER_IDENTIFIER}-operators-value='{"text":["eq","contains"],"choice":["eq"]}'
            data-${CONTROLLER_IDENTIFIER}-operator-labels-value='{"eq":"Equals","contains":"Contains"}'
            data-${CONTROLLER_IDENTIFIER}-i18n-value='{"select_operator":"Operator","boolean_yes":"Yes","boolean_no":"No","between_from":"From","between_to":"To"}'
        >
            <div class="zhortein-datatable__search-builder-group zhortein-datatable__search-builder-group--root">
                <div class="zhortein-datatable__search-builder-header">
                    <select class="zhortein-datatable__search-builder-logic">
                        <option value="AND">AND</option>
                        <option value="OR">OR</option>
                    </select>
                </div>
                <div
                    class="zhortein-datatable__search-builder-conditions"
                    data-${CONTROLLER_IDENTIFIER}-target="searchBuilderConditions"
                ></div>
            </div>
            <template data-${CONTROLLER_IDENTIFIER}-target="searchBuilderConditionTemplate">
                <div class="zhortein-datatable__search-builder-condition">
                    <select data-action="change->${CONTROLLER_IDENTIFIER}#onSearchBuilderFieldChange">
                        <option value="">Field</option>
                        <option value="email" data-type="text">Email</option>
                        <option value="status" data-type="choice" data-choices='{"Active":"active"}'>Status</option>
                    </select>
                    <select data-action="change->${CONTROLLER_IDENTIFIER}#onSearchBuilderOperatorChange" disabled></select>
                    <div class="zhortein-datatable__search-builder-value-container"></div>
                </div>
            </template>
            <template data-${CONTROLLER_IDENTIFIER}-target="searchBuilderGroupTemplate">
                <div class="zhortein-datatable__search-builder-group zhortein-datatable__search-builder-group--nested">
                    <div class="zhortein-datatable__search-builder-header">
                        <select class="zhortein-datatable__search-builder-logic">
                            <option value="AND">AND</option>
                            <option value="OR">OR</option>
                        </select>
                    </div>
                    <div class="zhortein-datatable__search-builder-conditions"></div>
                </div>
            </template>
        </div>
    `;
}

function createState(overrides = {}) {
    return {
        version: 1,
        page: 1,
        pageSize: 25,
        search: null,
        sortField: null,
        sortDirection: 'asc',
        sorts: [],
        filters: {},
        advancedFilters: {},
        visibleColumns: ['email', 'createdAt'],
        hiddenColumns: [],
        ...overrides,
    };
}

function setUrlStates(states) {
    const url = new URL('/customers?campaign=summer', window.location.origin);

    Object.entries(states).forEach(([parameter, state]) => {
        url.searchParams.set(parameter, typeof state === 'string' ? state : JSON.stringify(state));
    });

    window.history.replaceState({ turbo: { restorationIdentifier: 'existing' } }, '', url);
}

function createJsonResponseFromUrl(rawUrl) {
    const url = new URL(rawUrl, window.location.origin);

    return {
        ok: true,
        json: () => Promise.resolve({
            header: '<tr><th>Email</th></tr>',
            body: '<tr><td>Loaded</td></tr>',
            pagination: '',
            summary: 'Loaded',
            page: Number.parseInt(url.searchParams.get('page') || '1', 10),
            pageSize: Number.parseInt(url.searchParams.get('pageSize') || '25', 10),
        }),
    };
}

async function flushPromises() {
    for (let index = 0; index < 30; index++) {
        await Promise.resolve();
    }
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

describe('datatable_controller namespaced URL state', () => {
    let application = null;

    afterEach(async () => {
        document.body.innerHTML = '';
        await flushPromises();

        if (application !== null) {
            application.stop();
            application = null;
        }

        vi.unstubAllGlobals();
        window.history.replaceState(null, '', '/');
    });

    it('restores the complete state before the initial fragments request and exports it', async () => {
        const stateParameter = '_zd_state[users-key]';
        const state = createState({
            page: 3,
            pageSize: 50,
            search: 'alice',
            sortField: 'email',
            sortDirection: 'desc',
            sorts: [
                { field: 'email', direction: 'desc' },
                { field: 'createdAt', direction: 'asc' },
            ],
            filters: {
                email: '@example.test',
                createdAt: { from: '2026-01-01', to: '2026-06-30' },
            },
            visibleColumns: ['email'],
            hiddenColumns: ['createdAt'],
        });
        setUrlStates({ [stateParameter]: state });

        const fetchMock = vi.fn((url) => Promise.resolve(createJsonResponseFromUrl(url)));
        vi.stubGlobal('fetch', fetchMock);
        document.body.innerHTML = createDatatableHtml({ stateParameter });
        application = startApplication();

        const { controller, element } = await getController(application);
        const fragmentsUrl = new URL(fetchMock.mock.calls[0][0], window.location.origin);

        expect(controller.pageValue).toBe(3);
        expect(controller.pageSizeValue).toBe(50);
        expect(controller.searchValue).toBe('alice');
        expect(controller.sortFieldValue).toBe('email');
        expect(controller.sortDirectionValue).toBe('desc');
        expect(controller.sortsValue).toEqual([
            { field: 'email', direction: 'desc' },
            { field: 'createdAt', direction: 'asc' },
        ]);
        expect(element.querySelector('[name="filters[email]"]').value).toBe('@example.test');
        expect(element.querySelector('[name="filters[createdAt][from]"]').value).toBe('2026-01-01');
        expect(element.querySelector('[data-zhortein--datatable-bundle--datatable-column-name="createdAt"]').checked).toBe(false);
        expect(fragmentsUrl.searchParams.get('page')).toBe('3');
        expect(fragmentsUrl.searchParams.get('pageSize')).toBe('50');
        expect(fragmentsUrl.searchParams.get('filters[email]')).toBe('@example.test');
        expect(fragmentsUrl.searchParams.get('sorts[1][field]')).toBe('createdAt');
        expect(fragmentsUrl.searchParams.get('_zd_context')).toBe('signed');

        const exportParameters = new URLSearchParams();
        controller.appendExportStateParameters(exportParameters, 'current');

        expect(exportParameters.get('page')).toBe('3');
        expect(exportParameters.get('search')).toBe('alice');
        expect(exportParameters.get('filters[createdAt][to]')).toBe('2026-06-30');
        expect(exportParameters.getAll('hiddenColumns[]')).toContain('createdAt');
        expect(exportParameters.get('sorts[1][direction]')).toBe('asc');
    });

    it('pushes a successful user change while preserving the existing Turbo history state', async () => {
        const stateParameter = '_zd_state[users-key]';
        setUrlStates({});

        const fetchMock = vi.fn((url) => Promise.resolve(createJsonResponseFromUrl(url)));
        vi.stubGlobal('fetch', fetchMock);
        const pushStateSpy = vi.spyOn(window.history, 'pushState');
        document.body.innerHTML = createDatatableHtml({ stateParameter, autoLoad: false });
        application = startApplication();

        const { controller } = await getController(application);
        const stateEvent = vi.fn();
        controller.element.addEventListener('zhortein-datatable:state:change', stateEvent);

        controller.sortFieldValue = 'email';
        controller.sortDirectionValue = 'asc';
        await controller.refresh(null, 'push');

        expect(pushStateSpy).toHaveBeenCalledTimes(1);
        expect(window.history.state).toEqual({ turbo: { restorationIdentifier: 'existing' } });

        const state = JSON.parse(new URL(window.location.href).searchParams.get(stateParameter));

        expect(state.sortField).toBe('email');
        expect(state.sortDirection).toBe('asc');
        expect(state.sorts).toEqual([
            { field: 'email', direction: 'asc' },
        ]);
        expect(new URL(window.location.href).searchParams.get('campaign')).toBe('summer');
        expect(stateEvent).toHaveBeenCalledTimes(1);
        expect(stateEvent.mock.calls[0][0].detail.source).toBe('push');
    });

    it('restores each instance independently on popstate without creating another history entry', async () => {
        const firstParameter = '_zd_state[first-key]';
        const secondParameter = '_zd_state[second-key]';
        setUrlStates({});

        const fetchMock = vi.fn((url) => Promise.resolve(createJsonResponseFromUrl(url)));
        vi.stubGlobal('fetch', fetchMock);
        const pushStateSpy = vi.spyOn(window.history, 'pushState');
        document.body.innerHTML = [
            createDatatableHtml({ id: 'first-table', stateParameter: firstParameter, autoLoad: false }),
            createDatatableHtml({ id: 'second-table', stateParameter: secondParameter, autoLoad: false }),
        ].join('');
        application = startApplication();

        const first = await getController(application, 'first-table');
        const second = await getController(application, 'second-table');
        setUrlStates({
            [firstParameter]: createState({ page: 4, search: 'first' }),
            [secondParameter]: createState({ page: 2, search: 'second' }),
        });

        window.dispatchEvent(new PopStateEvent('popstate', { state: window.history.state }));
        await flushPromises();

        expect(first.controller.pageValue).toBe(4);
        expect(first.controller.searchValue).toBe('first');
        expect(second.controller.pageValue).toBe(2);
        expect(second.controller.searchValue).toBe('second');
        expect(fetchMock).toHaveBeenCalledTimes(2);
        expect(pushStateSpy).not.toHaveBeenCalled();
    });

    it('uses Turbo history when available and preserves non-Turbo application metadata', async () => {
        const stateParameter = '_zd_state[users-key]';
        setUrlStates({});
        window.history.replaceState({
            turbo: { restorationIdentifier: 'old', restorationIndex: 1 },
            application: { sidebar: 'open' },
        }, '', window.location.href);

        const turboPush = vi.fn((url) => {
            window.history.pushState({
                turbo: { restorationIdentifier: 'new', restorationIndex: 2 },
            }, '', url);
        });
        vi.stubGlobal('Turbo', {
            navigator: {
                history: {
                    push: turboPush,
                    replace: vi.fn(),
                },
            },
        });
        const fetchMock = vi.fn((url) => Promise.resolve(createJsonResponseFromUrl(url)));
        vi.stubGlobal('fetch', fetchMock);
        document.body.innerHTML = createDatatableHtml({
            stateParameter,
            autoLoad: false,
        });
        application = startApplication();

        const { controller } = await getController(application);
        controller.searchValue = 'turbo';
        await controller.refresh(null, 'push');

        expect(turboPush).toHaveBeenCalledTimes(1);
        expect(window.history.state).toEqual({
            turbo: { restorationIdentifier: 'new', restorationIndex: 2 },
            application: { sidebar: 'open' },
        });

        setUrlStates({
            [stateParameter]: createState({ page: 5, search: 'restored-by-turbo' }),
        });
        const fetchCountBeforePopstate = fetchMock.mock.calls.length;

        window.dispatchEvent(new PopStateEvent('popstate', { state: window.history.state }));
        await flushPromises();

        expect(controller.pageValue).toBe(5);
        expect(controller.searchValue).toBe('restored-by-turbo');
        expect(fetchMock).toHaveBeenCalledTimes(fetchCountBeforePopstate);
    });

    it('ignores malformed and unsupported state payloads', async () => {
        const stateParameter = '_zd_state[users-key]';
        setUrlStates({ [stateParameter]: JSON.stringify(createState({ version: 99, page: 8 })) });
        document.body.innerHTML = createDatatableHtml({ stateParameter, autoLoad: false });
        application = startApplication();

        const { controller } = await getController(application);

        expect(controller.pageValue).toBe(1);
        expect(controller.pageSizeValue).toBe(25);
        expect(controller.searchValue).toBe('');
    });

    it('accepts legacy version one state without a sorts list', async () => {
        const stateParameter = '_zd_state[users-key]';
        const legacyState = createState({
            sortField: 'email',
            sortDirection: 'desc',
        });
        delete legacyState.sorts;
        setUrlStates({ [stateParameter]: legacyState });
        document.body.innerHTML = createDatatableHtml({ stateParameter, autoLoad: false });
        application = startApplication();

        const { controller } = await getController(application);

        expect(controller.sortsValue).toEqual([
            { field: 'email', direction: 'desc' },
        ]);
    });

    it('ignores version one state with more than eight sort criteria', async () => {
        const stateParameter = '_zd_state[users-key]';
        setUrlStates({
            [stateParameter]: createState({
                page: 8,
                sorts: Array.from({ length: 9 }, (_, index) => ({
                    field: `field_${index}`,
                    direction: 'asc',
                })),
            }),
        });
        document.body.innerHTML = createDatatableHtml({ stateParameter, autoLoad: false });
        application = startApplication();

        const { controller } = await getController(application);

        expect(controller.pageValue).toBe(1);
        expect(controller.sortsValue).toEqual([]);
    });

    it('rebuilds nested advanced filters from URL state for fragments and exports', async () => {
        const stateParameter = '_zd_state[users-key]';
        const advancedFilters = {
            logic: 'or',
            conditions: [
                { field: 'email', operator: 'contains', value: '@example.test' },
                {
                    logic: 'and',
                    conditions: [
                        { field: 'status', operator: 'eq', value: 'active' },
                    ],
                },
            ],
        };
        setUrlStates({
            [stateParameter]: createState({ advancedFilters }),
        });
        document.body.innerHTML = createDatatableHtml({
            stateParameter,
            autoLoad: false,
            searchBuilder: true,
        });
        application = startApplication();

        const { controller, element } = await getController(application);
        const rootGroup = element.querySelector('.zhortein-datatable__search-builder-group--root');
        const subgroup = element.querySelector('.zhortein-datatable__search-builder-group--nested');
        const fragmentsUrl = new URL(controller.buildFragmentsUrl(), window.location.origin);
        const exportParameters = new URLSearchParams();

        controller.appendExportStateParameters(exportParameters, 'full');

        expect(rootGroup.querySelector('.zhortein-datatable__search-builder-logic').value).toBe('OR');
        expect(subgroup.querySelector('.zhortein-datatable__search-builder-logic').value).toBe('AND');
        expect(rootGroup.querySelector('input').value).toBe('@example.test');
        expect(subgroup.querySelector('select.zhortein-datatable__search-builder-value-container, .zhortein-datatable__search-builder-value-container select').value).toBe('active');
        expect(fragmentsUrl.searchParams.get('advancedFilters[conditions][0][field]')).toBe('email');
        expect(fragmentsUrl.searchParams.get('advancedFilters[conditions][1][conditions][0][value]')).toBe('active');
        expect(exportParameters.get('advancedFilters[conditions][0][value]')).toBe('@example.test');
    });
});
