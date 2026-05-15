import { Application } from '@hotwired/stimulus';
import { afterEach, describe, expect, it } from 'vitest';
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
            <div class="table-responsive">
                <table>
                    <tbody data-${CONTROLLER_IDENTIFIER}-target="body">
                        <tr>
                            <td>
                                <div class="dropdown zhortein-datatable__row-actions-dropdown">
                                    <button type="button">Actions</button>
                                    <div class="dropdown-menu">Menu</div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

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

describe('datatable_controller dropdown overflow handling', () => {
    let application = null;

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        document.body.innerHTML = '';
    });

    it('temporarily allows overflow on table responsive wrapper when row dropdown opens', async () => {
        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const controller = await getController(application);
        const dropdown = document.querySelector('.zhortein-datatable__row-actions-dropdown');
        const wrapper = document.querySelector('.table-responsive');

        expect(wrapper.classList.contains('overflow-visible')).toBe(false);

        controller.allowDropdownOverflow({ target: dropdown });

        expect(wrapper.classList.contains('overflow-visible')).toBe(true);
        expect(wrapper.dataset.zhorteinDatatableDropdownOverflowAdded).toBe('true');

        controller.restoreDropdownOverflow({ target: dropdown });

        expect(wrapper.classList.contains('overflow-visible')).toBe(false);
        expect(wrapper.dataset.zhorteinDatatableDropdownOverflowAdded).toBeUndefined();
    });

    it('does not remove pre-existing overflow-visible class', async () => {
        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const controller = await getController(application);
        const dropdown = document.querySelector('.zhortein-datatable__row-actions-dropdown');
        const wrapper = document.querySelector('.table-responsive');

        wrapper.classList.add('overflow-visible');

        controller.allowDropdownOverflow({ target: dropdown });

        expect(wrapper.classList.contains('overflow-visible')).toBe(true);
        expect(wrapper.dataset.zhorteinDatatableDropdownOverflowAdded).toBe('false');

        controller.restoreDropdownOverflow({ target: dropdown });

        expect(wrapper.classList.contains('overflow-visible')).toBe(true);
        expect(wrapper.dataset.zhorteinDatatableDropdownOverflowAdded).toBeUndefined();
    });

    it('ignores events outside a table responsive wrapper', async () => {
        document.body.innerHTML = `
            <div
                id="zhortein-datatable-users"
                data-controller="${CONTROLLER_IDENTIFIER}"
                data-${CONTROLLER_IDENTIFIER}-name-value="users"
                data-${CONTROLLER_IDENTIFIER}-auto-load-value="false"
            >
                <div id="dropdown-without-wrapper"></div>
                <tbody data-${CONTROLLER_IDENTIFIER}-target="body"></tbody>
                <div data-${CONTROLLER_IDENTIFIER}-target="pagination"></div>
                <div data-${CONTROLLER_IDENTIFIER}-target="summary"></div>
            </div>
        `;
        application = startApplication();

        const controller = await getController(application);
        const dropdown = document.querySelector('#dropdown-without-wrapper');

        expect(() => controller.allowDropdownOverflow({ target: dropdown })).not.toThrow();
        expect(() => controller.restoreDropdownOverflow({ target: dropdown })).not.toThrow();
    });
});
