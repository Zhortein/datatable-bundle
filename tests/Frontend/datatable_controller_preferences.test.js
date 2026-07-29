import { Application } from '@hotwired/stimulus';
import { afterEach, describe, expect, it, vi } from 'vitest';
import DatatableController from '../../assets/controllers/datatable_controller.js';

const CONTROLLER_IDENTIFIER = 'zhortein--datatable-bundle--datatable';

function createHtml() {
    return `
        <div
            id="users-table"
            data-controller="${CONTROLLER_IDENTIFIER}"
            data-${CONTROLLER_IDENTIFIER}-name-value="users"
            data-${CONTROLLER_IDENTIFIER}-instance-value="users-table"
            data-${CONTROLLER_IDENTIFIER}-auto-load-value="false"
            data-${CONTROLLER_IDENTIFIER}-hidden-class-value="d-none"
            data-${CONTROLLER_IDENTIFIER}-visible-class-value="d-flex"
            data-${CONTROLLER_IDENTIFIER}-status-error-class-value="text-danger"
            data-${CONTROLLER_IDENTIFIER}-status-success-class-value="text-success"
            data-${CONTROLLER_IDENTIFIER}-status-muted-class-value="text-body-secondary"
            data-${CONTROLLER_IDENTIFIER}-preferences-url-value="/_zhortein/datatable/users/preferences?_zd_instance=users-table"
            data-${CONTROLLER_IDENTIFIER}-preferences-csrf-token-value="preference-token"
            data-${CONTROLLER_IDENTIFIER}-preference-success-message-value="Saved"
            data-${CONTROLLER_IDENTIFIER}-preference-reset-message-value="Reset"
            data-${CONTROLLER_IDENTIFIER}-preference-error-message-value="Failed"
        >
            <span data-${CONTROLLER_IDENTIFIER}-target="preferenceStatus"></span>
            <select name="filters[status]" data-${CONTROLLER_IDENTIFIER}-filter-control="true">
                <option value=""></option>
                <option value="active" selected>Active</option>
            </select>
            <input
                type="checkbox"
                checked
                data-${CONTROLLER_IDENTIFIER}-column-visibility-control="true"
                data-${CONTROLLER_IDENTIFIER}-column-name="email"
            >
            <input
                type="checkbox"
                data-${CONTROLLER_IDENTIFIER}-column-visibility-control="true"
                data-${CONTROLLER_IDENTIFIER}-column-name="phone"
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

async function flushPromises() {
    for (let index = 0; index < 20; index++) {
        await Promise.resolve();
    }
}

async function startController() {
    document.body.innerHTML = createHtml();
    const application = Application.start();
    application.register(CONTROLLER_IDENTIFIER, DatatableController);
    await flushPromises();
    const element = document.querySelector('#users-table');
    const controller = application.getControllerForElementAndIdentifier(
        element,
        CONTROLLER_IDENTIFIER,
    );

    expect(controller).toBeInstanceOf(DatatableController);

    return { application, controller, element };
}

describe('datatable_controller persistent preferences', () => {
    let application = null;

    afterEach(async () => {
        if (application !== null) {
            application.stop();
            application = null;
        }

        document.body.innerHTML = '';
        vi.unstubAllGlobals();
        await flushPromises();
    });

    it('saves the canonical current state with CSRF protection and emits an event', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(jsonResponse({
            version: 1,
            preference: {
                pageSize: 50,
                sorts: [{ field: 'email', direction: 'desc' }],
                visibleColumns: ['email'],
                hiddenColumns: ['phone'],
                filters: { status: 'active' },
            },
        })));
        vi.stubGlobal('fetch', fetchMock);
        const started = await startController();
        application = started.application;
        const { controller, element } = started;
        const eventListener = vi.fn();
        element.addEventListener('zhortein-datatable:preference:save', eventListener);
        controller.pageSizeValue = 50;
        controller.setSortCriteria([{ field: 'email', direction: 'desc' }]);

        controller.savePreference({ preventDefault: vi.fn() });
        await flushPromises();

        const [, options] = fetchMock.mock.calls[0];
        const payload = JSON.parse(options.body);

        expect(options.method).toBe('POST');
        expect(options.headers['X-CSRF-Token']).toBe('preference-token');
        expect(payload.state.pageSize).toBe(50);
        expect(payload.state.sorts).toEqual([{ field: 'email', direction: 'desc' }]);
        expect(payload.state.filters).toEqual({ status: 'active' });
        expect(payload.state.visibleColumns).toEqual(['email']);
        expect(payload.state.hiddenColumns).toEqual(['phone']);
        expect(element.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="preferenceStatus"]`).textContent).toBe('Saved');
        expect(eventListener).toHaveBeenCalledOnce();
    });

    it('resets the scoped preference without mutating the current table state', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(jsonResponse(null, 204)));
        vi.stubGlobal('fetch', fetchMock);
        const started = await startController();
        application = started.application;
        const { controller, element } = started;
        const eventListener = vi.fn();
        element.addEventListener('zhortein-datatable:preference:reset', eventListener);
        controller.pageSizeValue = 100;

        controller.resetPreference({ preventDefault: vi.fn() });
        await flushPromises();

        const [, options] = fetchMock.mock.calls[0];

        expect(options.method).toBe('DELETE');
        expect(options.headers['X-CSRF-Token']).toBe('preference-token');
        expect(options.body).toBeUndefined();
        expect(controller.pageSizeValue).toBe(100);
        expect(element.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="preferenceStatus"]`).textContent).toBe('Reset');
        expect(eventListener).toHaveBeenCalledOnce();
    });

    it('reports storage failures through the preference error contract', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(jsonResponse({
            version: 1,
            error: {
                code: 'storage_unavailable',
                message: 'cache down',
            },
        }, 503)));
        vi.stubGlobal('fetch', fetchMock);
        const started = await startController();
        application = started.application;
        const { controller, element } = started;
        const eventListener = vi.fn();
        element.addEventListener('zhortein-datatable:preference:error', eventListener);

        controller.savePreference({ preventDefault: vi.fn() });
        await flushPromises();

        expect(element.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="preferenceStatus"]`).textContent).toBe('Failed');
        expect(eventListener).toHaveBeenCalledOnce();
        expect(eventListener.mock.calls[0][0].detail.code).toBe('storage_unavailable');
    });
});
