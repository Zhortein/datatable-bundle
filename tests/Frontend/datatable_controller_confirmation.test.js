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
        >
            <a
                id="confirmed-link"
                href="/users/42"
                data-${CONTROLLER_IDENTIFIER}-confirmation-message="Open this user?"
                data-action="click->${CONTROLLER_IDENTIFIER}#confirmAction"
            >View</a>

            <a
                id="blank-confirmation-link"
                href="/users/43"
                data-${CONTROLLER_IDENTIFIER}-confirmation-message=" "
                data-action="click->${CONTROLLER_IDENTIFIER}#confirmAction"
            >Blank</a>

            <a
                id="plain-link"
                href="/users/44"
                data-action="click->${CONTROLLER_IDENTIFIER}#confirmAction"
            >Plain</a>

            <form
                id="confirmed-form"
                method="post"
                action="/users/42/delete"
                data-${CONTROLLER_IDENTIFIER}-confirmation-message="Delete this user?"
                data-action="submit->${CONTROLLER_IDENTIFIER}#confirmAction"
            >
                <button type="submit">Delete</button>
            </form>

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
            <div class="alert alert-danger d-none" data-${CONTROLLER_IDENTIFIER}-target="error"></div>
            <div class="zhortein-datatable__loading d-none" data-${CONTROLLER_IDENTIFIER}-target="loading"></div>
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

    expect(controller).toBeInstanceOf(DatatableController);

    return { element, controller };
}

function createPreventableEvent(target) {
    return {
        currentTarget: target,
        preventDefault: vi.fn(),
        stopPropagation: vi.fn(),
    };
}

describe('datatable_controller action confirmation behavior', () => {
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

    it('does nothing when confirmation metadata is missing', async () => {
        const confirmMock = vi.fn(() => false);
        vi.stubGlobal('confirm', confirmMock);

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { controller } = await getController(application);
        const event = createPreventableEvent(document.querySelector('#plain-link'));

        controller.confirmAction(event);

        expect(confirmMock).not.toHaveBeenCalled();
        expect(event.preventDefault).not.toHaveBeenCalled();
        expect(event.stopPropagation).not.toHaveBeenCalled();
    });

    it('does nothing when confirmation metadata is blank', async () => {
        const confirmMock = vi.fn(() => false);
        vi.stubGlobal('confirm', confirmMock);

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { controller } = await getController(application);
        const event = createPreventableEvent(document.querySelector('#blank-confirmation-link'));

        controller.confirmAction(event);

        expect(confirmMock).not.toHaveBeenCalled();
        expect(event.preventDefault).not.toHaveBeenCalled();
        expect(event.stopPropagation).not.toHaveBeenCalled();
    });

    it('allows link action when user confirms', async () => {
        const confirmMock = vi.fn(() => true);
        vi.stubGlobal('confirm', confirmMock);

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { controller } = await getController(application);
        const event = createPreventableEvent(document.querySelector('#confirmed-link'));

        controller.confirmAction(event);

        expect(confirmMock).toHaveBeenCalledTimes(1);
        expect(confirmMock).toHaveBeenCalledWith('Open this user?');
        expect(event.preventDefault).toHaveBeenCalledTimes(1);
        expect(event.stopPropagation).toHaveBeenCalledTimes(1);
    });

    it('prevents link action when user cancels', async () => {
        const confirmMock = vi.fn(() => false);
        vi.stubGlobal('confirm', confirmMock);

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { controller } = await getController(application);
        const event = createPreventableEvent(document.querySelector('#confirmed-link'));

        controller.confirmAction(event);

        expect(confirmMock).toHaveBeenCalledTimes(1);
        expect(confirmMock).toHaveBeenCalledWith('Open this user?');
        expect(event.preventDefault).toHaveBeenCalledTimes(1);
        expect(event.stopPropagation).toHaveBeenCalledTimes(1);
    });

    it('allows form submission when user confirms', async () => {
        const confirmMock = vi.fn(() => true);
        vi.stubGlobal('confirm', confirmMock);

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { controller } = await getController(application);
        const event = createPreventableEvent(document.querySelector('#confirmed-form'));

        controller.confirmAction(event);

        expect(confirmMock).toHaveBeenCalledTimes(1);
        expect(confirmMock).toHaveBeenCalledWith('Delete this user?');
        expect(event.preventDefault).toHaveBeenCalledTimes(1);
        expect(event.stopPropagation).toHaveBeenCalledTimes(1);
    });

    it('prevents form submission when user cancels', async () => {
        const confirmMock = vi.fn(() => false);
        vi.stubGlobal('confirm', confirmMock);

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { controller } = await getController(application);
        const event = createPreventableEvent(document.querySelector('#confirmed-form'));

        controller.confirmAction(event);

        expect(confirmMock).toHaveBeenCalledTimes(1);
        expect(confirmMock).toHaveBeenCalledWith('Delete this user?');
        expect(event.preventDefault).toHaveBeenCalledTimes(1);
        expect(event.stopPropagation).toHaveBeenCalledTimes(1);
    });

    it('ignores non HTMLElement event targets', async () => {
        const confirmMock = vi.fn(() => false);
        vi.stubGlobal('confirm', confirmMock);

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { controller } = await getController(application);
        const event = createPreventableEvent(null);

        controller.confirmAction(event);

        expect(confirmMock).not.toHaveBeenCalled();
        expect(event.preventDefault).not.toHaveBeenCalled();
        expect(event.stopPropagation).not.toHaveBeenCalled();
    });
});
