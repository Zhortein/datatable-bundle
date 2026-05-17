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
            <div data-${CONTROLLER_IDENTIFIER}-target="bulkToolbar" hidden>
                <span data-${CONTROLLER_IDENTIFIER}-target="selectedCount">0</span>
                <button data-${CONTROLLER_IDENTIFIER}-target="bulkAction" disabled>Delete</button>
            </div>

            <table>
                <thead data-${CONTROLLER_IDENTIFIER}-target="header">
                    <tr>
                        <th>
                            <input
                                type="checkbox"
                                data-${CONTROLLER_IDENTIFIER}-target="selectAllCheckbox"
                                data-action="change->${CONTROLLER_IDENTIFIER}#selectAll"
                            >
                        </th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody data-${CONTROLLER_IDENTIFIER}-target="body">
                    <tr>
                        <td>
                            <input
                                type="checkbox"
                                value="1"
                                data-${CONTROLLER_IDENTIFIER}-target="rowCheckbox"
                                data-action="change->${CONTROLLER_IDENTIFIER}#selectRow"
                            >
                        </td>
                        <td>alice@example.test</td>
                    </tr>
                    <tr>
                        <td>
                            <input
                                type="checkbox"
                                value="2"
                                data-${CONTROLLER_IDENTIFIER}-target="rowCheckbox"
                                data-action="change->${CONTROLLER_IDENTIFIER}#selectRow"
                            >
                        </td>
                        <td>bob@example.test</td>
                    </tr>
                </tbody>
            </table>
        </div>
    `;
}

function createJsonResponse(payload = {}) {
    return {
        ok: true,
        json: () => Promise.resolve({
            body: `
                <tr>
                    <td>
                        <input
                            type="checkbox"
                            value="3"
                            data-${CONTROLLER_IDENTIFIER}-target="rowCheckbox"
                            data-action="change->${CONTROLLER_IDENTIFIER}#selectRow"
                        >
                    </td>
                    <td>charlie@example.test</td>
                </tr>
            `,
            ...payload,
        }),
    };
}

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

    return { element, controller };
}

describe('datatable_controller row selection', () => {
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

    it('tracks single row selection', async () => {
        document.body.innerHTML = createDatatableHtml();
        application = startApplication();
        const { controller } = await getController(application);

        const checkboxes = document.querySelectorAll(`[data-${CONTROLLER_IDENTIFIER}-target="rowCheckbox"]`);
        const selectAllCheckbox = document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="selectAllCheckbox"]`);
        const selectedCount = document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="selectedCount"]`);

        // Select first row
        checkboxes[0].checked = true;
        checkboxes[0].dispatchEvent(new Event('change'));

        expect(controller.selectedIds.has('1')).toBe(true);
        expect(controller.selectedIds.size).toBe(1);
        expect(selectedCount.textContent).toBe('1');
        expect(selectAllCheckbox.checked).toBe(false);
        expect(selectAllCheckbox.indeterminate).toBe(true);

        // Select second row
        checkboxes[1].checked = true;
        checkboxes[1].dispatchEvent(new Event('change'));

        expect(controller.selectedIds.has('2')).toBe(true);
        expect(controller.selectedIds.size).toBe(2);
        expect(selectedCount.textContent).toBe('2');
        expect(selectAllCheckbox.checked).toBe(true);
        expect(selectAllCheckbox.indeterminate).toBe(false);

        // Unselect first row
        checkboxes[0].checked = false;
        checkboxes[0].dispatchEvent(new Event('change'));

        expect(controller.selectedIds.has('1')).toBe(false);
        expect(controller.selectedIds.size).toBe(1);
        expect(selectedCount.textContent).toBe('1');
        expect(selectAllCheckbox.checked).toBe(false);
        expect(selectAllCheckbox.indeterminate).toBe(true);
    });

    it('selects all visible rows via header checkbox', async () => {
        document.body.innerHTML = createDatatableHtml();
        application = startApplication();
        const { controller } = await getController(application);

        const selectAllCheckbox = document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="selectAllCheckbox"]`);
        const checkboxes = document.querySelectorAll(`[data-${CONTROLLER_IDENTIFIER}-target="rowCheckbox"]`);
        const selectedCount = document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="selectedCount"]`);

        // Select all
        selectAllCheckbox.checked = true;
        selectAllCheckbox.dispatchEvent(new Event('change'));

        expect(checkboxes[0].checked).toBe(true);
        expect(checkboxes[1].checked).toBe(true);
        expect(controller.selectedIds.has('1')).toBe(true);
        expect(controller.selectedIds.has('2')).toBe(true);
        expect(controller.selectedIds.size).toBe(2);
        expect(selectedCount.textContent).toBe('2');
        expect(selectAllCheckbox.indeterminate).toBe(false);

        // Unselect all
        selectAllCheckbox.checked = false;
        selectAllCheckbox.dispatchEvent(new Event('change'));

        expect(checkboxes[0].checked).toBe(false);
        expect(checkboxes[1].checked).toBe(false);
        expect(controller.selectedIds.size).toBe(0);
        expect(selectedCount.textContent).toBe('0');
        expect(selectAllCheckbox.indeterminate).toBe(false);
    });

    it('resets selection on refresh', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(createJsonResponse()));
        vi.stubGlobal('fetch', fetchMock);

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();
        const { controller } = await getController(application);

        const checkboxes = document.querySelectorAll(`[data-${CONTROLLER_IDENTIFIER}-target="rowCheckbox"]`);
        const selectedCount = document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="selectedCount"]`);

        checkboxes[0].checked = true;
        checkboxes[0].dispatchEvent(new Event('change'));

        expect(controller.selectedIds.size).toBe(1);
        expect(selectedCount.textContent).toBe('1');

        await controller.refresh();
        await flushPromises();

        expect(controller.selectedIds.size).toBe(0);
        expect(selectedCount.textContent).toBe('0');
        
        const newCheckboxes = document.querySelectorAll(`[data-${CONTROLLER_IDENTIFIER}-target="rowCheckbox"]`);
        expect(newCheckboxes.length).toBe(1);
        expect(newCheckboxes[0].value).toBe('3');
        expect(newCheckboxes[0].checked).toBe(false);
    });

    it('toggles bulk toolbar and buttons based on selection', async () => {
        document.body.innerHTML = createDatatableHtml();
        application = startApplication();
        const { controller } = await getController(application);

        const checkboxes = document.querySelectorAll(`[data-${CONTROLLER_IDENTIFIER}-target="rowCheckbox"]`);
        const bulkToolbar = document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="bulkToolbar"]`);
        const bulkAction = document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="bulkAction"]`);

        expect(bulkToolbar.hidden).toBe(true);
        expect(bulkAction.disabled).toBe(true);

        // Select first row
        checkboxes[0].checked = true;
        checkboxes[0].dispatchEvent(new Event('change'));

        expect(bulkToolbar.hidden).toBe(false);
        expect(bulkAction.disabled).toBe(false);

        // Unselect first row
        checkboxes[0].checked = false;
        checkboxes[0].dispatchEvent(new Event('change'));

        expect(bulkToolbar.hidden).toBe(true);
        expect(bulkAction.disabled).toBe(true);
    });
});
