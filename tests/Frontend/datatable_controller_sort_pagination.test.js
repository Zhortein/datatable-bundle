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
            data-${CONTROLLER_IDENTIFIER}-page-value="1"
            data-${CONTROLLER_IDENTIFIER}-page-size-value="25"
            data-${CONTROLLER_IDENTIFIER}-sort-field-value=""
            data-${CONTROLLER_IDENTIFIER}-sort-direction-value="asc"
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

function createJsonResponse(payload = {}) {
    return {
        ok: true,
        json: () => Promise.resolve({
            body: '<tr><td>loaded</td></tr>',
            pagination: '<nav>pagination</nav>',
            summary: 'loaded',
            page: 1,
            pageSize: 25,
            totalItems: 10,
            filteredItems: 10,
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

function createPreventableEvent(params = {}, shiftKey = false) {
    return {
        params,
        shiftKey,
        preventDefault: vi.fn(),
    };
}

describe('datatable_controller sorting and pagination interactions', () => {
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

    it('sorts a new field ascending, resets page to 1 and refreshes', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(createJsonResponse({ page: 1 })));
        vi.stubGlobal('fetch', fetchMock);

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { controller } = await getController(application);
        controller.pageValue = 4;

        const event = createPreventableEvent({ field: 'e.email' });
        controller.sort(event);

        await flushPromises();

        const url = getLastRequestedUrl(fetchMock);

        expect(event.preventDefault).toHaveBeenCalledTimes(1);
        expect(controller.pageValue).toBe(1);
        expect(controller.sortFieldValue).toBe('e.email');
        expect(controller.sortDirectionValue).toBe('asc');
        expect(url.searchParams.get('page')).toBe('1');
        expect(url.searchParams.get('sortField')).toBe('e.email');
        expect(url.searchParams.get('sortDirection')).toBe('asc');
    });

    it('toggles current sort field from ascending to descending', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(createJsonResponse({ page: 1 })));
        vi.stubGlobal('fetch', fetchMock);

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { controller } = await getController(application);
        controller.pageValue = 3;
        controller.sortFieldValue = 'e.email';
        controller.sortDirectionValue = 'asc';

        controller.sort(createPreventableEvent({ field: 'e.email' }));

        await flushPromises();

        const url = getLastRequestedUrl(fetchMock);

        expect(controller.pageValue).toBe(1);
        expect(controller.sortFieldValue).toBe('e.email');
        expect(controller.sortDirectionValue).toBe('desc');
        expect(url.searchParams.get('sortField')).toBe('e.email');
        expect(url.searchParams.get('sortDirection')).toBe('desc');
    });

    it('toggles current sort field from descending to ascending', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(createJsonResponse({ page: 1 })));
        vi.stubGlobal('fetch', fetchMock);

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { controller } = await getController(application);
        controller.sortFieldValue = 'e.email';
        controller.sortDirectionValue = 'desc';

        controller.sort(createPreventableEvent({ field: 'e.email' }));

        await flushPromises();

        const url = getLastRequestedUrl(fetchMock);

        expect(controller.sortFieldValue).toBe('e.email');
        expect(controller.sortDirectionValue).toBe('asc');
        expect(url.searchParams.get('sortDirection')).toBe('asc');
    });

    it('switches to another sort field and resets direction to ascending', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(createJsonResponse({ page: 1 })));
        vi.stubGlobal('fetch', fetchMock);

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { controller } = await getController(application);
        controller.sortFieldValue = 'e.email';
        controller.sortDirectionValue = 'desc';
        controller.pageValue = 5;

        controller.sort(createPreventableEvent({ field: 'e.displayName' }));

        await flushPromises();

        const url = getLastRequestedUrl(fetchMock);

        expect(controller.pageValue).toBe(1);
        expect(controller.sortFieldValue).toBe('e.displayName');
        expect(controller.sortDirectionValue).toBe('asc');
        expect(url.searchParams.get('sortField')).toBe('e.displayName');
        expect(url.searchParams.get('sortDirection')).toBe('asc');
    });

    it('builds, toggles and removes ordered criteria with Shift activation', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(createJsonResponse({ page: 1 })));
        vi.stubGlobal('fetch', fetchMock);

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { controller } = await getController(application);

        controller.sort(createPreventableEvent({ field: 'e.displayName' }));
        controller.sort(createPreventableEvent({ field: 'e.email' }, true));
        await flushPromises();

        let url = getLastRequestedUrl(fetchMock);

        expect(controller.sortsValue).toEqual([
            { field: 'e.displayName', direction: 'asc' },
            { field: 'e.email', direction: 'asc' },
        ]);
        expect(url.searchParams.get('sorts[0][field]')).toBe('e.displayName');
        expect(url.searchParams.get('sorts[0][direction]')).toBe('asc');
        expect(url.searchParams.get('sorts[1][field]')).toBe('e.email');
        expect(url.searchParams.get('sorts[1][direction]')).toBe('asc');

        controller.sort(createPreventableEvent({ field: 'e.email' }, true));
        await flushPromises();
        url = getLastRequestedUrl(fetchMock);

        expect(controller.sortsValue[1]).toEqual({ field: 'e.email', direction: 'desc' });
        expect(url.searchParams.get('sorts[1][direction]')).toBe('desc');

        controller.sort(createPreventableEvent({ field: 'e.email' }, true));
        await flushPromises();

        expect(controller.sortsValue).toEqual([
            { field: 'e.displayName', direction: 'asc' },
        ]);
    });

    it('replaces a multi-column sort on plain activation', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(createJsonResponse({ page: 1 })));
        vi.stubGlobal('fetch', fetchMock);

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { controller } = await getController(application);
        controller.setSortCriteria([
            { field: 'e.displayName', direction: 'desc' },
            { field: 'e.email', direction: 'desc' },
        ]);

        controller.sort(createPreventableEvent({ field: 'e.email' }));
        await flushPromises();

        expect(controller.sortsValue).toEqual([
            { field: 'e.email', direction: 'asc' },
        ]);
        expect(controller.sortFieldValue).toBe('e.email');
        expect(controller.sortDirectionValue).toBe('asc');
    });

    it('ignores sort event without field param', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(createJsonResponse()));
        vi.stubGlobal('fetch', fetchMock);

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { controller } = await getController(application);
        controller.pageValue = 4;

        controller.sort(createPreventableEvent({ field: '' }));

        await flushPromises();

        expect(fetchMock).not.toHaveBeenCalled();
        expect(controller.pageValue).toBe(4);
        expect(controller.sortFieldValue).toBe('');
    });

    it('goes to requested page and refreshes', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(createJsonResponse({ page: 3 })));
        vi.stubGlobal('fetch', fetchMock);

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { controller } = await getController(application);

        controller.goToPage(createPreventableEvent({ page: '3' }));

        await flushPromises();

        const url = getLastRequestedUrl(fetchMock);

        expect(controller.pageValue).toBe(3);
        expect(url.searchParams.get('page')).toBe('3');
    });

    it('keeps sort state when going to another page', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(createJsonResponse({ page: 2 })));
        vi.stubGlobal('fetch', fetchMock);

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { controller } = await getController(application);
        controller.sortFieldValue = 'e.email';
        controller.sortDirectionValue = 'desc';

        controller.goToPage(createPreventableEvent({ page: 2 }));

        await flushPromises();

        const url = getLastRequestedUrl(fetchMock);

        expect(controller.pageValue).toBe(2);
        expect(url.searchParams.get('page')).toBe('2');
        expect(url.searchParams.get('sortField')).toBe('e.email');
        expect(url.searchParams.get('sortDirection')).toBe('desc');
    });

    it('ignores invalid page values', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(createJsonResponse()));
        vi.stubGlobal('fetch', fetchMock);

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { controller } = await getController(application);
        controller.pageValue = 4;

        controller.goToPage(createPreventableEvent({ page: 'invalid' }));

        await flushPromises();

        expect(fetchMock).not.toHaveBeenCalled();
        expect(controller.pageValue).toBe(4);
    });

    it('ignores page values lower than 1', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(createJsonResponse()));
        vi.stubGlobal('fetch', fetchMock);

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { controller } = await getController(application);
        controller.pageValue = 4;

        controller.goToPage(createPreventableEvent({ page: 0 }));

        await flushPromises();

        expect(fetchMock).not.toHaveBeenCalled();
        expect(controller.pageValue).toBe(4);
    });
});
