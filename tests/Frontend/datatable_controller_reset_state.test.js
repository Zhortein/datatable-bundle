import { Application } from '@hotwired/stimulus';
import { afterEach, describe, expect, it, vi } from 'vitest';
import DatatableController from '../../assets/controllers/datatable_controller.js';

const CONTROLLER_IDENTIFIER = 'zhortein--datatable-bundle--datatable';
const STATE_PARAMETER = '_zd_state[users-key]';

function createDatatableHtml() {
    return `
        <div
            id="users-table"
            data-controller="${CONTROLLER_IDENTIFIER}"
            data-${CONTROLLER_IDENTIFIER}-name-value="users"
            data-${CONTROLLER_IDENTIFIER}-instance-value="users-table"
            data-${CONTROLLER_IDENTIFIER}-state-parameter-value="${STATE_PARAMETER}"
            data-${CONTROLLER_IDENTIFIER}-fragments-url-value="/_zhortein/datatable/users/fragments"
            data-${CONTROLLER_IDENTIFIER}-auto-load-value="true"
            data-${CONTROLLER_IDENTIFIER}-page-value="1"
            data-${CONTROLLER_IDENTIFIER}-page-size-value="25"
        >
            <input type="search" data-${CONTROLLER_IDENTIFIER}-target="searchInput">
            <input type="text" name="filters[email]" data-${CONTROLLER_IDENTIFIER}-filter-control="true">
            <select data-${CONTROLLER_IDENTIFIER}-target="pageSizeInput">
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
            <div data-${CONTROLLER_IDENTIFIER}-target="activeFilters" hidden></div>
            <button data-${CONTROLLER_IDENTIFIER}-target="clearFiltersButton" hidden disabled></button>
            <button
                data-${CONTROLLER_IDENTIFIER}-target="resetStateButton"
                data-action="${CONTROLLER_IDENTIFIER}#resetState"
                hidden
                disabled
            ></button>
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

function setUrlState(state) {
    const url = new URL('/customers', window.location.origin);

    if (state !== null) {
        url.searchParams.set(STATE_PARAMETER, JSON.stringify(state));
    }

    window.history.replaceState({}, '', url);
}

function createJsonResponseFromUrl(rawUrl) {
    const url = new URL(rawUrl, window.location.origin);

    return {
        ok: true,
        json: () => Promise.resolve({
            // The header fragment replaces the whole thead, as the renderer writes it, so that the target stays
            header: `<thead data-${CONTROLLER_IDENTIFIER}-target="header"><tr><th>Email</th></tr></thead>`,
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

async function getController(application) {
    await flushPromises();

    const element = document.querySelector('#users-table');
    const controller = application.getControllerForElementAndIdentifier(element, CONTROLLER_IDENTIFIER);

    expect(controller).toBeInstanceOf(DatatableController);

    return { controller, element };
}

describe('datatable_controller reset state', () => {
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

    it('keeps the reset button hidden while the datatable is in its default state', async () => {
        setUrlState(null);
        vi.stubGlobal('fetch', vi.fn((url) => Promise.resolve(createJsonResponseFromUrl(url))));
        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { element } = await getController(application);
        const button = element.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="resetStateButton"]`);

        expect(button.hidden).toBe(true);
        expect(button.disabled).toBe(true);
    });

    it('shows the reset button once the state differs and restores the defaults on reset', async () => {
        setUrlState(createState({
            page: 3,
            pageSize: 50,
            search: 'alice',
            sortField: 'email',
            sortDirection: 'desc',
            sorts: [{ field: 'email', direction: 'desc' }],
            filters: { email: '@example.test' },
            visibleColumns: ['email'],
            hiddenColumns: ['createdAt'],
        }));
        const fetchMock = vi.fn((url) => Promise.resolve(createJsonResponseFromUrl(url)));
        vi.stubGlobal('fetch', fetchMock);
        const pushStateSpy = vi.spyOn(window.history, 'pushState');
        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { controller, element } = await getController(application);
        const button = element.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="resetStateButton"]`);
        const changeEvent = vi.fn();
        element.addEventListener('zhortein-datatable:state:change', changeEvent);

        expect(button.hidden).toBe(false);
        expect(button.disabled).toBe(false);

        await controller.resetState();

        const fragmentsUrl = new URL(fetchMock.mock.calls.at(-1)[0], window.location.origin);

        expect(controller.pageValue).toBe(1);
        expect(controller.pageSizeValue).toBe(25);
        expect(controller.searchValue).toBe('');
        expect(controller.sortsValue).toEqual([]);
        expect(element.querySelector('[name="filters[email]"]').value).toBe('');
        expect(element.querySelector(`[data-${CONTROLLER_IDENTIFIER}-column-name="createdAt"]`).checked).toBe(true);
        expect(fragmentsUrl.searchParams.get('page')).toBe('1');
        expect(fragmentsUrl.searchParams.get('pageSize')).toBe('25');
        expect(fragmentsUrl.searchParams.has('filters[email]')).toBe(false);
        expect(new URL(window.location.href).searchParams.has(STATE_PARAMETER)).toBe(false);
        expect(pushStateSpy).toHaveBeenCalledTimes(1);
        expect(changeEvent).toHaveBeenCalledTimes(1);
        expect(button.hidden).toBe(true);
        expect(button.disabled).toBe(true);
    });
});
