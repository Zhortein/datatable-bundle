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
        >
            <div data-${CONTROLLER_IDENTIFIER}-target="bulkToolbar">
                <form
                    id="bulk-delete-form"
                    method="post"
                    action="/users/bulk-delete"
                    data-action="submit->${CONTROLLER_IDENTIFIER}#submitBulkAction"
                    data-${CONTROLLER_IDENTIFIER}-selected-rows-parameter-name="ids"
                >
                    <button type="submit" data-${CONTROLLER_IDENTIFIER}-target="bulkAction">Delete</button>
                </form>

                <form
                    id="bulk-archive-form"
                    method="post"
                    action="/users/bulk-archive"
                    data-action="submit->${CONTROLLER_IDENTIFIER}#submitBulkAction"
                    data-${CONTROLLER_IDENTIFIER}-selected-rows-parameter-name="rows"
                    data-${CONTROLLER_IDENTIFIER}-confirmation-message="Archive selected users?"
                >
                    <button type="submit" data-${CONTROLLER_IDENTIFIER}-target="bulkAction">Archive</button>
                </form>
            </div>

            <table>
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
                    </tr>
                </tbody>
            </table>
        </div>
    `;
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

    return { element, controller };
}

function createPreventableEvent(target) {
    return {
        currentTarget: target,
        preventDefault: vi.fn(),
        stopPropagation: vi.fn(),
    };
}

describe('datatable_controller bulk actions', () => {
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

    it('injects selected IDs into the form on submission', async () => {
        document.body.innerHTML = createDatatableHtml();
        application = startApplication();
        const { controller } = await getController(application);

        // Select rows
        controller.selectedIds.add('1');
        controller.selectedIds.add('2');

        const form = document.querySelector('#bulk-delete-form');
        const event = createPreventableEvent(form);

        controller.submitBulkAction(event);

        const inputs = form.querySelectorAll('input[name="ids[]"]');
        expect(inputs.length).toBe(2);
        expect(inputs[0].value).toBe('1');
        expect(inputs[1].value).toBe('2');
        expect(event.preventDefault).not.toHaveBeenCalled();
    });

    it('clears existing injected IDs before injecting new ones', async () => {
        document.body.innerHTML = createDatatableHtml();
        application = startApplication();
        const { controller } = await getController(application);

        controller.selectedIds.add('1');

        const form = document.querySelector('#bulk-delete-form');
        
        // Add an existing input
        const existingInput = document.createElement('input');
        existingInput.type = 'hidden';
        existingInput.name = 'ids[]';
        existingInput.value = 'old';
        form.appendChild(existingInput);

        const event = createPreventableEvent(form);
        controller.submitBulkAction(event);

        const inputs = form.querySelectorAll('input[name="ids[]"]');
        expect(inputs.length).toBe(1);
        expect(inputs[0].value).toBe('1');
    });

    it('uses custom parameter name for selected rows', async () => {
        document.body.innerHTML = createDatatableHtml();
        application = startApplication();
        const { controller } = await getController(application);

        controller.selectedIds.add('42');

        const form = document.querySelector('#bulk-archive-form');
        const event = createPreventableEvent(form);

        // We bypass confirmation for this test by mocking confirm
        vi.stubGlobal('confirm', () => true);
        
        // But submitBulkAction will call confirmAction which will preventDefault
        // and eventually call executeConfirmedTarget.
        // For simplicity, let's test injectSelectedIds directly or handle the flow.
        
        controller.injectSelectedIds(form);

        const inputs = form.querySelectorAll('input[name="rows[]"]');
        expect(inputs.length).toBe(1);
        expect(inputs[0].value).toBe('42');
    });

    it('prevents submission when no rows are selected', async () => {
        document.body.innerHTML = createDatatableHtml();
        application = startApplication();
        const { controller } = await getController(application);

        expect(controller.selectedIds.size).toBe(0);

        const form = document.querySelector('#bulk-delete-form');
        const event = createPreventableEvent(form);

        controller.submitBulkAction(event);

        expect(event.preventDefault).toHaveBeenCalled();
        expect(form.querySelectorAll('input[name="ids[]"]').length).toBe(0);
    });

    it('triggers confirmation before injecting IDs', async () => {
        const confirmMock = vi.fn(() => true);
        vi.stubGlobal('confirm', confirmMock);

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();
        const { controller } = await getController(application);

        controller.selectedIds.add('1');

        const form = document.querySelector('#bulk-archive-form');
        
        // Mock form.submit to avoid errors in JSDOM and verify it's called
        form.submit = vi.fn();

        const event = createPreventableEvent(form);
        controller.submitBulkAction(event);

        expect(confirmMock).toHaveBeenCalledWith('Archive selected users?');
        expect(event.preventDefault).toHaveBeenCalled();
        
        // After confirmation, executeConfirmedTarget should have been called
        // Since we mocked window.confirm to return true, confirmAction should have called executeConfirmedTarget
        expect(form.submit).toHaveBeenCalled();
        const inputs = form.querySelectorAll('input[name="rows[]"]');
        expect(inputs.length).toBe(1);
        expect(inputs[0].value).toBe('1');
    });
});
