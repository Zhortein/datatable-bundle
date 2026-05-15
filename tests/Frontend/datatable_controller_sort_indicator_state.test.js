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
        >
            <table>
                <thead data-${CONTROLLER_IDENTIFIER}-target="header">
                    <tr>
                        <th aria-sort="none">
                            <button
                                type="button"
                                data-action="${CONTROLLER_IDENTIFIER}#sort"
                                data-${CONTROLLER_IDENTIFIER}-sort-field-param="email"
                                data-${CONTROLLER_IDENTIFIER}-sort-direction-param="asc"
                            >
                                Email
                                <span class="zhortein-datatable__sort-indicator" aria-hidden="true">↕</span>
                            </button>
                        </th>
                    </tr>
                </thead>
                <tbody data-${CONTROLLER_IDENTIFIER}-target="body">
                    <tr><td>Initial row</td></tr>
                </tbody>
            </table>

            <div data-${CONTROLLER_IDENTIFIER}-target="pagination"></div>
            <div data-${CONTROLLER_IDENTIFIER}-target="summary">Initial summary</div>
        </div>
    `;
}

function createSortedHeaderHtml(direction = 'asc') {
    const nextDirection = direction === 'asc' ? 'desc' : 'asc';
    const indicator = direction === 'asc' ? '↑' : '↓';
    const ariaSort = direction === 'asc' ? 'ascending' : 'descending';

    return `
        <thead data-${CONTROLLER_IDENTIFIER}-target="header">
            <tr>
                <th aria-sort="${ariaSort}">
                    <button
                        type="button"
                        data-action="${CONTROLLER_IDENTIFIER}#sort"
                        data-${CONTROLLER_IDENTIFIER}-sort-field-param="email"
                        data-${CONTROLLER_IDENTIFIER}-sort-direction-param="${nextDirection}"
                    >
                        Email
                        <span class="zhortein-datatable__sort-indicator" aria-hidden="true">${indicator}</span>
                    </button>
                </th>
            </tr>
        </thead>
    `;
}

function createFetchResponse(direction = 'asc') {
    return {
        ok: true,
        json: async () => ({
            header: createSortedHeaderHtml(direction),
            body: '<tbody data-zhortein--datatable-bundle--datatable-target="body"><tr><td>alice@example.test</td></tr></tbody>',
            pagination: '<div data-zhortein--datatable-bundle--datatable-target="pagination"></div>',
            summary: '1 result.',
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

describe('datatable_controller sortable header indicator state', () => {
    let application = null;
    let fetchMock = null;

    beforeEach(() => {
        document.body.innerHTML = createDatatableHtml();

        fetchMock = vi.fn(async () => createFetchResponse('asc'));
        vi.stubGlobal('fetch', fetchMock);

        application = startApplication();
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        vi.unstubAllGlobals();
        vi.restoreAllMocks();
        document.body.innerHTML = '';
    });

    it('replaces header with sorted indicator and aria state after sorting', async () => {
        await flushPromises();

        const sortButton = document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-sort-field-param="email"]`);

        sortButton.dispatchEvent(new MouseEvent('click', {
            bubbles: true,
            cancelable: true,
        }));

        await flushPromises();

        expect(fetchMock).toHaveBeenCalledTimes(1);

        const header = document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="header"]`);
        const refreshedSortButton = document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-sort-field-param="email"]`);

        expect(header.outerHTML).toContain('aria-sort="ascending"');
        expect(header.outerHTML).toContain('zhortein-datatable__sort-indicator');
        expect(header.textContent).toContain('↑');
        expect(header.textContent).not.toContain('↕');
        expect(refreshedSortButton.getAttribute(`data-${CONTROLLER_IDENTIFIER}-sort-direction-param`)).toBe('desc');
    });

    it('can render descending indicator after a descending sorted fragment', async () => {
        fetchMock.mockImplementationOnce(async () => createFetchResponse('desc'));

        await flushPromises();

        const sortButton = document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-sort-field-param="email"]`);

        sortButton.dispatchEvent(new MouseEvent('click', {
            bubbles: true,
            cancelable: true,
        }));

        await flushPromises();

        const header = document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="header"]`);
        const refreshedSortButton = document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-sort-field-param="email"]`);

        expect(header.outerHTML).toContain('aria-sort="descending"');
        expect(header.textContent).toContain('↓');
        expect(header.textContent).not.toContain('↕');
        expect(refreshedSortButton.getAttribute(`data-${CONTROLLER_IDENTIFIER}-sort-direction-param`)).toBe('asc');
    });
});
