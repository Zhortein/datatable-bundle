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
            data-${CONTROLLER_IDENTIFIER}-search-builder-value="true"
        >
            <div
                data-${CONTROLLER_IDENTIFIER}-target="searchBuilder"
                data-${CONTROLLER_IDENTIFIER}-operators-value='{"text":["eq","neq","contains"],"choice":["eq","neq","in"],"number":["eq","gt","between"],"boolean":["eq"]}'
                data-${CONTROLLER_IDENTIFIER}-operator-labels-value='{"eq":"Equals","neq":"Not Equals","contains":"Contains","in":"In","gt":"Greater than","between":"Between"}'
                data-${CONTROLLER_IDENTIFIER}-i18n-value='{"select_operator":"Select operator","boolean_yes":"Yes","boolean_no":"No","between_from":"From","between_to":"To"}'
            >
                <select data-action="change->${CONTROLLER_IDENTIFIER}#updateSearchBuilderLogic">
                    <option value="AND">AND</option>
                    <option value="OR">OR</option>
                </select>
                <button data-action="${CONTROLLER_IDENTIFIER}#clearSearchBuilder">Clear</button>
                <div data-${CONTROLLER_IDENTIFIER}-target="searchBuilderConditions"></div>
                <button data-action="${CONTROLLER_IDENTIFIER}#addSearchBuilderCondition">Add</button>

                <template data-${CONTROLLER_IDENTIFIER}-target="searchBuilderConditionTemplate">
                    <div class="zhortein-datatable__search-builder-condition">
                        <select data-action="change->${CONTROLLER_IDENTIFIER}#onSearchBuilderFieldChange">
                            <option value="">Select field</option>
                            <option value="email" data-type="text">Email</option>
                            <option value="status" data-type="choice" data-choices='{"Active":"active","Inactive":"inactive"}'>Status</option>
                            <option value="age" data-type="number">Age</option>
                            <option value="enabled" data-type="boolean">Enabled</option>
                            <option value="name" data-type="text" data-allowed-operators='["eq","contains"]'>Name (restricted)</option>
                        </select>
                        <select data-action="change->${CONTROLLER_IDENTIFIER}#onSearchBuilderOperatorChange" disabled>
                            <option value="">Select operator</option>
                        </select>
                        <div class="zhortein-datatable__search-builder-value-container">
                            <input type="text" disabled>
                        </div>
                        <button data-action="${CONTROLLER_IDENTIFIER}#removeSearchBuilderCondition">Remove</button>
                    </div>
                </template>
            </div>

            <tbody data-${CONTROLLER_IDENTIFIER}-target="body"></tbody>
            <thead data-${CONTROLLER_IDENTIFIER}-target="header"></thead>
        </div>
    `;
}

function createJsonResponse(payload = {}) {
    return {
        ok: true,
        json: () => Promise.resolve({
            body: '<tr><td>loaded</td></tr>',
            page: 1,
            pageSize: 25,
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

function getLastRequestedUrl(fetchMock) {
    const rawUrl = fetchMock.mock.calls.at(-1)[0];

    return new URL(rawUrl, window.location.origin);
}

describe('datatable_controller search builder interactions', () => {
    let application = null;

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        vi.unstubAllGlobals();
        vi.restoreAllMocks();
        vi.useRealTimers();
        document.body.innerHTML = '';
    });

    it('adds and removes conditions', async () => {
        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { element, controller } = await getController(application);
        const addButton = element.querySelector('button[data-action$="#addSearchBuilderCondition"]');
        const conditionsContainer = element.querySelector('[data-zhortein--datatable-bundle--datatable-target="searchBuilderConditions"]');

        expect(conditionsContainer.children.length).toBe(0);

        addButton.click();
        expect(conditionsContainer.children.length).toBe(1);

        addButton.click();
        expect(conditionsContainer.children.length).toBe(2);

        const removeButton = conditionsContainer.children[0].querySelector('button[data-action$="#removeSearchBuilderCondition"]');
        controller.removeSearchBuilderCondition({ currentTarget: removeButton, preventDefault: () => {} });
        expect(conditionsContainer.children.length).toBe(1);
    });

    it('updates operator choices when field changes', async () => {
        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { element, controller } = await getController(application);
        element.querySelector('button[data-action$="#addSearchBuilderCondition"]').click();

        const condition = element.querySelector('.zhortein-datatable__search-builder-condition');
        const fieldSelect = condition.querySelector('select[data-action$="#onSearchBuilderFieldChange"]');
        const operatorSelect = condition.querySelector('select[data-action$="#onSearchBuilderOperatorChange"]');

        expect(operatorSelect.disabled).toBe(true);

        fieldSelect.value = 'email';
        controller.onSearchBuilderFieldChange({ target: fieldSelect });

        expect(operatorSelect.disabled).toBe(false);
        expect(operatorSelect.options.length).toBe(4); // Select + 3 operators
        expect(operatorSelect.options[1].value).toBe('eq');
        expect(operatorSelect.options[1].textContent).toBe('Equals');
    });

    it('updates value input based on field type and operator', async () => {
        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { element, controller } = await getController(application);
        element.querySelector('button[data-action$="#addSearchBuilderCondition"]').click();

        const condition = element.querySelector('.zhortein-datatable__search-builder-condition');
        const fieldSelect = condition.querySelector('select[data-action$="#onSearchBuilderFieldChange"]');
        const valueContainer = condition.querySelector('.zhortein-datatable__search-builder-value-container');

        // Choice field
        fieldSelect.value = 'status';
        controller.onSearchBuilderFieldChange({ target: fieldSelect });
        expect(valueContainer.querySelector('select')).not.toBeNull();
        expect(valueContainer.querySelector('select').options.length).toBe(2);

        // Boolean field
        fieldSelect.value = 'enabled';
        controller.onSearchBuilderFieldChange({ target: fieldSelect });
        expect(valueContainer.querySelector('select')).not.toBeNull();
        expect(valueContainer.querySelector('select').options.length).toBe(2);
        expect(valueContainer.querySelector('select').options[0].textContent).toBe('Yes');

        // Number field with between operator
        fieldSelect.value = 'age';
        controller.onSearchBuilderFieldChange({ target: fieldSelect });
        const operatorSelect = condition.querySelector('select[data-action$="#onSearchBuilderOperatorChange"]');
        operatorSelect.value = 'between';
        controller.onSearchBuilderOperatorChange();
        controller.updateSearchBuilderValueInput(condition, 'number', null); // Manual trigger because of how test is set up

        expect(valueContainer.querySelectorAll('input').length).toBe(2);
        expect(valueContainer.querySelectorAll('input')[0].placeholder).toBe('From');
    });

    it('serializes search builder payload in ajax request', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(createJsonResponse()));
        vi.stubGlobal('fetch', fetchMock);

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { element, controller } = await getController(application);
        element.querySelector('button[data-action$="#addSearchBuilderCondition"]').click();

        const condition = element.querySelector('.zhortein-datatable__search-builder-condition');
        const fieldSelect = condition.querySelector('select[data-action$="#onSearchBuilderFieldChange"]');
        fieldSelect.value = 'email';
        controller.onSearchBuilderFieldChange({ target: fieldSelect });

        const operatorSelect = condition.querySelector('select[data-action$="#onSearchBuilderOperatorChange"]');
        operatorSelect.value = 'contains';
        controller.onSearchBuilderOperatorChange();

        const valueInput = condition.querySelector('.zhortein-datatable__search-builder-value-container input');
        valueInput.value = 'example';
        
        controller.refresh();

        await flushPromises();

        const url = getLastRequestedUrl(fetchMock);
        expect(url.searchParams.get('advancedFilters[logic]')).toBe('AND');
        expect(url.searchParams.get('advancedFilters[children][0][field]')).toBe('email');
        expect(url.searchParams.get('advancedFilters[children][0][operator]')).toBe('contains');
        expect(url.searchParams.get('advancedFilters[children][0][value]')).toBe('example');
    });

    it('restricts operator list to per-field allowed operators', async () => {
        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { element, controller } = await getController(application);
        element.querySelector('button[data-action$="#addSearchBuilderCondition"]').click();

        const condition = element.querySelector('.zhortein-datatable__search-builder-condition');
        const fieldSelect = condition.querySelector('select[data-action$="#onSearchBuilderFieldChange"]');
        const operatorSelect = condition.querySelector('select[data-action$="#onSearchBuilderOperatorChange"]');

        fieldSelect.value = 'name';
        controller.onSearchBuilderFieldChange({ target: fieldSelect });

        const operatorValues = Array.from(operatorSelect.options).map((o) => o.value);
        expect(operatorValues).toEqual(['', 'eq', 'contains']);
    });

    it('includes advanced filters in export URL', async () => {
        const assignSpy = vi.fn();
        Object.defineProperty(window, 'location', {
            value: {
                origin: 'https://example.test',
                href: 'https://example.test/current-page',
                assign: assignSpy,
            },
            writable: true,
        });

        document.body.innerHTML = createDatatableHtml();
        const exportEl = document.querySelector('#zhortein-datatable-users');
        exportEl.setAttribute(`data-${CONTROLLER_IDENTIFIER}-export-url-value`, '/_zhortein/datatable/users/export');

        application = startApplication();
        const { element, controller } = await getController(application);

        element.querySelector('button[data-action$="#addSearchBuilderCondition"]').click();
        const condition = element.querySelector('.zhortein-datatable__search-builder-condition');
        const fieldSelect = condition.querySelector('select[data-action$="#onSearchBuilderFieldChange"]');
        fieldSelect.value = 'email';
        controller.onSearchBuilderFieldChange({ target: fieldSelect });
        const operatorSelect = condition.querySelector('select[data-action$="#onSearchBuilderOperatorChange"]');
        operatorSelect.value = 'contains';
        controller.onSearchBuilderOperatorChange();
        const valueInput = condition.querySelector('.zhortein-datatable__search-builder-value-container input');
        valueInput.value = 'example';

        const anchor = document.createElement('a');
        anchor.href = '/_zhortein/datatable/users/export';

        controller.export({
            preventDefault: () => {},
            currentTarget: anchor,
            params: { exportMode: 'all' },
        });

        expect(assignSpy).toHaveBeenCalledTimes(1);
        const url = new URL(assignSpy.mock.calls.at(-1)[0]);
        expect(url.searchParams.get('advancedFilters[logic]')).toBe('AND');
        expect(url.searchParams.get('advancedFilters[children][0][field]')).toBe('email');
        expect(url.searchParams.get('advancedFilters[children][0][operator]')).toBe('contains');
        expect(url.searchParams.get('advancedFilters[children][0][value]')).toBe('example');
    });

    it('clears search builder and refreshes', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(createJsonResponse()));
        vi.stubGlobal('fetch', fetchMock);

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { element } = await getController(application);
        element.querySelector('button[data-action$="#addSearchBuilderCondition"]').click();
        
        const clearButton = element.querySelector('button[data-action$="#clearSearchBuilder"]');
        clearButton.click();

        await flushPromises();

        const url = getLastRequestedUrl(fetchMock);
        expect(element.querySelector('[data-zhortein--datatable-bundle--datatable-target="searchBuilderConditions"]').children.length).toBe(0);
        expect(url.searchParams.has('advancedFilters[logic]')).toBe(false);
    });
});
