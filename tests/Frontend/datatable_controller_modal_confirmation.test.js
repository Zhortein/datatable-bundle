import { Application } from '@hotwired/stimulus';
import { afterEach, describe, expect, it, vi } from 'vitest';
import DatatableController from '../../assets/controllers/datatable_controller.js';

const CONTROLLER_IDENTIFIER = 'zhortein--datatable-bundle--datatable';

class FakeBootstrapModal {
    static instances = new Map();

    constructor(element) {
        this.element = element;
        this.show = vi.fn();
        this.hide = vi.fn();

        FakeBootstrapModal.instances.set(element, this);
    }

    static getOrCreateInstance(element) {
        if (!FakeBootstrapModal.instances.has(element)) {
            return new FakeBootstrapModal(element);
        }

        return FakeBootstrapModal.instances.get(element);
    }

    static reset() {
        FakeBootstrapModal.instances.clear();
    }
}

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

            <form
                id="confirmed-form"
                method="post"
                action="/users/42/delete"
                data-${CONTROLLER_IDENTIFIER}-confirmation-message="Delete this user?"
                data-action="submit->${CONTROLLER_IDENTIFIER}#confirmAction"
            >
                <button type="submit">Delete</button>
            </form>

            <div
                id="confirmation-modal"
                data-${CONTROLLER_IDENTIFIER}-target="confirmationModal"
            >
                <p data-${CONTROLLER_IDENTIFIER}-target="confirmationMessage"></p>
                <button
                    type="button"
                    data-${CONTROLLER_IDENTIFIER}-target="confirmationConfirmButton"
                    data-action="${CONTROLLER_IDENTIFIER}#confirmPendingAction"
                >Confirm</button>
            </div>

            <table>
                <thead data-${CONTROLLER_IDENTIFIER}-target="header"><tr><th>Email</th></tr></thead>
                <tbody data-${CONTROLLER_IDENTIFIER}-target="body"><tr><td>No data available.</td></tr></tbody>
            </table>

            <div data-${CONTROLLER_IDENTIFIER}-target="pagination"></div>
            <div data-${CONTROLLER_IDENTIFIER}-target="summary"></div>
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

    return controller;
}

function createPreventableEvent(target) {
    return {
        currentTarget: target,
        preventDefault: vi.fn(),
        stopPropagation: vi.fn(),
    };
}

describe('datatable_controller Bootstrap modal confirmation behavior', () => {
    let application = null;

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        vi.unstubAllGlobals();
        vi.restoreAllMocks();
        FakeBootstrapModal.reset();
        document.body.innerHTML = '';
    });

    it('opens modal and stores pending GET link action', async () => {
        vi.stubGlobal('bootstrap', { Modal: FakeBootstrapModal });

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const controller = await getController(application);
        const event = createPreventableEvent(document.querySelector('#confirmed-link'));

        controller.confirmAction(event);

        const modal = FakeBootstrapModal.getOrCreateInstance(document.querySelector('#confirmation-modal'));

        expect(event.preventDefault).toHaveBeenCalledTimes(1);
        expect(event.stopPropagation).toHaveBeenCalledTimes(1);
        expect(document.querySelector(`[data-${CONTROLLER_IDENTIFIER}-target="confirmationMessage"]`).textContent).toBe('Open this user?');
        expect(modal.show).toHaveBeenCalledTimes(1);
    });

    it('navigates to pending link when modal confirmation is accepted', async () => {
        const assignSpy = vi.fn();

        Object.defineProperty(window, 'location', {
            value: {
                origin: 'https://example.test',
                href: 'https://example.test/current-page',
                assign: assignSpy,
            },
            writable: true,
        });

        vi.stubGlobal('bootstrap', { Modal: FakeBootstrapModal });

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const controller = await getController(application);
        controller.confirmAction(createPreventableEvent(document.querySelector('#confirmed-link')));
        controller.confirmPendingAction({ preventDefault: vi.fn() });

        const modal = FakeBootstrapModal.getOrCreateInstance(document.querySelector('#confirmation-modal'));

        expect(modal.hide).toHaveBeenCalledTimes(1);
        const assignedUrl = new URL(assignSpy.mock.calls[0][0]);

        expect(assignedUrl.pathname).toBe('/users/42');
    });

    it('submits pending form when modal confirmation is accepted', async () => {
        vi.stubGlobal('bootstrap', { Modal: FakeBootstrapModal });

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const controller = await getController(application);
        const form = document.querySelector('#confirmed-form');
        const submitSpy = vi.spyOn(form, 'submit').mockImplementation(() => {});

        controller.confirmAction(createPreventableEvent(form));
        controller.confirmPendingAction({ preventDefault: vi.fn() });

        const modal = FakeBootstrapModal.getOrCreateInstance(document.querySelector('#confirmation-modal'));

        expect(modal.hide).toHaveBeenCalledTimes(1);
        expect(submitSpy).toHaveBeenCalledTimes(1);
    });

    it('executes a pending Ajax form after modal confirmation', async () => {
        const fetchMock = vi.fn(() => Promise.resolve({
            ok: true,
            json: () => Promise.resolve({
                version: 1,
                ok: true,
                message: 'User deleted.',
                errors: [],
                redirect: null,
            }),
        }));
        vi.stubGlobal('fetch', fetchMock);
        vi.stubGlobal('bootstrap', { Modal: FakeBootstrapModal });

        document.body.innerHTML = createDatatableHtml();
        const form = document.querySelector('#confirmed-form');
        form.setAttribute(`data-${CONTROLLER_IDENTIFIER}-ajax-action`, 'true');
        form.setAttribute(`data-${CONTROLLER_IDENTIFIER}-ajax-action-name`, 'delete');
        form.setAttribute(`data-${CONTROLLER_IDENTIFIER}-ajax-success-strategy`, 'none');
        application = startApplication();

        const controller = await getController(application);
        controller.executeAjaxAction(createPreventableEvent(form));
        controller.confirmPendingAction({ preventDefault: vi.fn() });
        await flushPromises();

        const modal = FakeBootstrapModal.getOrCreateInstance(document.querySelector('#confirmation-modal'));

        expect(modal.hide).toHaveBeenCalledTimes(1);
        expect(fetchMock).toHaveBeenCalledTimes(1);
        expect(new URL(fetchMock.mock.calls[0][0]).pathname).toBe('/users/42/delete');
    });

    it('falls back to native confirm when Bootstrap modal is unavailable', async () => {
        const confirmMock = vi.fn(() => false);
        vi.stubGlobal('confirm', confirmMock);
        vi.stubGlobal('bootstrap', undefined);

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const controller = await getController(application);
        const event = createPreventableEvent(document.querySelector('#confirmed-link'));

        controller.confirmAction(event);

        expect(confirmMock).toHaveBeenCalledWith('Open this user?');
        expect(event.preventDefault).toHaveBeenCalledTimes(1);
        expect(event.stopPropagation).toHaveBeenCalledTimes(1);
    });

    it('does nothing when there is no pending action to confirm', async () => {
        vi.stubGlobal('bootstrap', { Modal: FakeBootstrapModal });

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const controller = await getController(application);
        const event = { preventDefault: vi.fn() };

        expect(() => controller.confirmPendingAction(event)).not.toThrow();
        expect(event.preventDefault).toHaveBeenCalledTimes(1);
    });
});
