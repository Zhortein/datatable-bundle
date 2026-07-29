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
            data-${CONTROLLER_IDENTIFIER}-hidden-class-value="d-none"
            data-${CONTROLLER_IDENTIFIER}-visible-class-value="d-flex"
            data-${CONTROLLER_IDENTIFIER}-status-error-class-value="text-danger"
            data-${CONTROLLER_IDENTIFIER}-status-success-class-value="text-success"
            data-${CONTROLLER_IDENTIFIER}-status-muted-class-value="text-body-secondary"
            data-${CONTROLLER_IDENTIFIER}-search-builder-value="true"
        >
            <div
                data-${CONTROLLER_IDENTIFIER}-target="searchBuilder"
                data-${CONTROLLER_IDENTIFIER}-operators-value='{"text":["eq","neq","contains"],"choice":["eq","neq","in"],"number":["eq","gt","between"],"boolean":["eq"]}'
                data-${CONTROLLER_IDENTIFIER}-operator-labels-value='{"eq":"Equals","neq":"Not Equals","contains":"Contains","in":"In","gt":"Greater than","between":"Between"}'
                data-${CONTROLLER_IDENTIFIER}-i18n-value='{"select_operator":"Select operator","boolean_yes":"Yes","boolean_no":"No","between_from":"From","between_to":"To"}'
                data-${CONTROLLER_IDENTIFIER}-input-class="theme-input"
                data-${CONTROLLER_IDENTIFIER}-select-class="theme-select"
                data-${CONTROLLER_IDENTIFIER}-between-class="theme-between"
            >
                <div class="zhortein-datatable__search-builder-group zhortein-datatable__search-builder-group--root">
                    <div class="zhortein-datatable__search-builder-header">
                        <select class="zhortein-datatable__search-builder-logic" data-action="change->${CONTROLLER_IDENTIFIER}#updateSearchBuilderLogic">
                            <option value="AND">AND</option>
                            <option value="OR">OR</option>
                        </select>
                        <button data-action="${CONTROLLER_IDENTIFIER}#clearSearchBuilder">Clear</button>
                    </div>
                    <div class="zhortein-datatable__search-builder-conditions" data-${CONTROLLER_IDENTIFIER}-target="searchBuilderConditions"></div>
                    <button data-action="${CONTROLLER_IDENTIFIER}#addSearchBuilderCondition">Add</button>
                    <button data-action="${CONTROLLER_IDENTIFIER}#addSearchBuilderSubgroup">Add subgroup</button>
                </div>

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

                <template data-${CONTROLLER_IDENTIFIER}-target="searchBuilderGroupTemplate">
                    <div class="zhortein-datatable__search-builder-group zhortein-datatable__search-builder-group--nested">
                        <div class="zhortein-datatable__search-builder-header">
                            <select class="zhortein-datatable__search-builder-logic" data-action="change->${CONTROLLER_IDENTIFIER}#updateSearchBuilderLogic">
                                <option value="AND">AND</option>
                                <option value="OR">OR</option>
                            </select>
                            <button data-action="${CONTROLLER_IDENTIFIER}#removeSearchBuilderSubgroup">Remove group</button>
                        </div>
                        <div class="zhortein-datatable__search-builder-conditions"></div>
                        <button data-action="${CONTROLLER_IDENTIFIER}#addSearchBuilderCondition">Add</button>
                        <button data-action="${CONTROLLER_IDENTIFIER}#addSearchBuilderSubgroup">Add subgroup</button>
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

function getRootAddConditionButton(element) {
    const rootGroup = element.querySelector('.zhortein-datatable__search-builder-group--root');

    return rootGroup.querySelector(':scope > button[data-action$="#addSearchBuilderCondition"]');
}

function getRootAddSubgroupButton(element) {
    const rootGroup = element.querySelector('.zhortein-datatable__search-builder-group--root');

    return rootGroup.querySelector(':scope > button[data-action$="#addSearchBuilderSubgroup"]');
}

function clickWithCurrentTarget(controller, methodName, button) {
    controller[methodName]({ currentTarget: button, preventDefault: () => {} });
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
        const addButton = getRootAddConditionButton(element);
        const conditionsContainer = element.querySelector('.zhortein-datatable__search-builder-group--root > .zhortein-datatable__search-builder-conditions');

        expect(conditionsContainer.children.length).toBe(0);

        clickWithCurrentTarget(controller, 'addSearchBuilderCondition', addButton);
        expect(conditionsContainer.children.length).toBe(1);

        clickWithCurrentTarget(controller, 'addSearchBuilderCondition', addButton);
        expect(conditionsContainer.children.length).toBe(2);

        const removeButton = conditionsContainer.children[0].querySelector('button[data-action$="#removeSearchBuilderCondition"]');
        controller.removeSearchBuilderCondition({ currentTarget: removeButton, preventDefault: () => {} });
        expect(conditionsContainer.children.length).toBe(1);
    });

    it('updates operator choices when field changes', async () => {
        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { element, controller } = await getController(application);
        clickWithCurrentTarget(controller, 'addSearchBuilderCondition', getRootAddConditionButton(element));

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
        clickWithCurrentTarget(controller, 'addSearchBuilderCondition', getRootAddConditionButton(element));

        const condition = element.querySelector('.zhortein-datatable__search-builder-condition');
        const fieldSelect = condition.querySelector('select[data-action$="#onSearchBuilderFieldChange"]');
        const valueContainer = condition.querySelector('.zhortein-datatable__search-builder-value-container');

        // Choice field
        fieldSelect.value = 'status';
        controller.onSearchBuilderFieldChange({ target: fieldSelect });
        expect(valueContainer.querySelector('select')).not.toBeNull();
        expect(valueContainer.querySelector('select').classList.contains('theme-select')).toBe(true);
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
        controller.updateSearchBuilderValueInput(condition, 'number', null);

        expect(valueContainer.querySelectorAll('input').length).toBe(2);
        expect(valueContainer.firstElementChild.classList.contains('theme-between')).toBe(true);
        expect(valueContainer.querySelectorAll('input')[0].classList.contains('theme-input')).toBe(true);
        expect(valueContainer.querySelectorAll('input')[0].placeholder).toBe('From');
    });

    it('renders dynamic labels as text instead of HTML', async () => {
        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { element, controller } = await getController(application);
        clickWithCurrentTarget(controller, 'addSearchBuilderCondition', getRootAddConditionButton(element));

        const condition = element.querySelector('.zhortein-datatable__search-builder-condition');
        const fieldSelect = condition.querySelector('select[data-action$="#onSearchBuilderFieldChange"]');
        const statusOption = fieldSelect.querySelector('option[value="status"]');
        statusOption.dataset.choices = JSON.stringify({
            '<img src=x onerror="window.__themeXss = true">': 'unsafe-label',
        });
        fieldSelect.value = 'status';

        controller.onSearchBuilderFieldChange({ target: fieldSelect });

        const valueContainer = condition.querySelector('.zhortein-datatable__search-builder-value-container');
        expect(valueContainer.querySelector('img')).toBeNull();
        expect(valueContainer.querySelector('option').textContent).toBe('<img src=x onerror="window.__themeXss = true">');
        expect(window.__themeXss).toBeUndefined();
    });

    it('serializes search builder payload in ajax request', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(createJsonResponse()));
        vi.stubGlobal('fetch', fetchMock);

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { element, controller } = await getController(application);
        clickWithCurrentTarget(controller, 'addSearchBuilderCondition', getRootAddConditionButton(element));

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
        expect(url.searchParams.get('advancedFilters[logic]')).toBe('and');
        expect(url.searchParams.get('advancedFilters[conditions][0][field]')).toBe('email');
        expect(url.searchParams.get('advancedFilters[conditions][0][operator]')).toBe('contains');
        expect(url.searchParams.get('advancedFilters[conditions][0][value]')).toBe('example');
    });

    it('restricts operator list to per-field allowed operators', async () => {
        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { element, controller } = await getController(application);
        clickWithCurrentTarget(controller, 'addSearchBuilderCondition', getRootAddConditionButton(element));

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

        clickWithCurrentTarget(controller, 'addSearchBuilderCondition', getRootAddConditionButton(element));
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
        expect(url.searchParams.get('advancedFilters[logic]')).toBe('and');
        expect(url.searchParams.get('advancedFilters[conditions][0][field]')).toBe('email');
        expect(url.searchParams.get('advancedFilters[conditions][0][operator]')).toBe('contains');
        expect(url.searchParams.get('advancedFilters[conditions][0][value]')).toBe('example');
    });

    it('clears search builder and refreshes', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(createJsonResponse()));
        vi.stubGlobal('fetch', fetchMock);

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { element } = await getController(application);
        const addButton = getRootAddConditionButton(element);
        addButton.click();

        const clearButton = element.querySelector('button[data-action$="#clearSearchBuilder"]');
        clearButton.click();

        await flushPromises();

        const url = getLastRequestedUrl(fetchMock);
        expect(element.querySelector('.zhortein-datatable__search-builder-group--root > .zhortein-datatable__search-builder-conditions').children.length).toBe(0);
        expect(url.searchParams.has('advancedFilters[logic]')).toBe(false);
    });

    it('adds and removes a nested subgroup with its own conditions', async () => {
        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { element, controller } = await getController(application);

        clickWithCurrentTarget(controller, 'addSearchBuilderSubgroup', getRootAddSubgroupButton(element));

        const subgroup = element.querySelector('.zhortein-datatable__search-builder-group--nested');
        expect(subgroup).not.toBeNull();

        const subgroupAddCondition = subgroup.querySelector(':scope > button[data-action$="#addSearchBuilderCondition"]');
        clickWithCurrentTarget(controller, 'addSearchBuilderCondition', subgroupAddCondition);

        const conditions = subgroup.querySelectorAll(':scope > .zhortein-datatable__search-builder-conditions > .zhortein-datatable__search-builder-condition');
        expect(conditions.length).toBe(1);

        const removeSubgroupBtn = subgroup.querySelector('button[data-action$="#removeSearchBuilderSubgroup"]');
        controller.removeSearchBuilderSubgroup({ currentTarget: removeSubgroupBtn, preventDefault: () => {} });

        expect(element.querySelector('.zhortein-datatable__search-builder-group--nested')).toBeNull();
    });

    it('serializes nested groups using the conditions key', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(createJsonResponse()));
        vi.stubGlobal('fetch', fetchMock);

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { element, controller } = await getController(application);

        // Root condition: email contains "alice"
        clickWithCurrentTarget(controller, 'addSearchBuilderCondition', getRootAddConditionButton(element));
        const rootCondition = element.querySelector('.zhortein-datatable__search-builder-group--root > .zhortein-datatable__search-builder-conditions > .zhortein-datatable__search-builder-condition');
        const rootFieldSelect = rootCondition.querySelector('select[data-action$="#onSearchBuilderFieldChange"]');
        rootFieldSelect.value = 'email';
        controller.onSearchBuilderFieldChange({ target: rootFieldSelect });
        rootCondition.querySelector('select[data-action$="#onSearchBuilderOperatorChange"]').value = 'contains';
        controller.onSearchBuilderOperatorChange();
        rootCondition.querySelector('.zhortein-datatable__search-builder-value-container input').value = 'alice';

        // Subgroup with logic OR
        clickWithCurrentTarget(controller, 'addSearchBuilderSubgroup', getRootAddSubgroupButton(element));
        const subgroup = element.querySelector('.zhortein-datatable__search-builder-group--nested');
        subgroup.querySelector('select.zhortein-datatable__search-builder-logic').value = 'OR';

        // Condition inside subgroup
        const subAddCondition = subgroup.querySelector(':scope > button[data-action$="#addSearchBuilderCondition"]');
        clickWithCurrentTarget(controller, 'addSearchBuilderCondition', subAddCondition);
        const subCondition = subgroup.querySelector(':scope > .zhortein-datatable__search-builder-conditions > .zhortein-datatable__search-builder-condition');
        const subFieldSelect = subCondition.querySelector('select[data-action$="#onSearchBuilderFieldChange"]');
        subFieldSelect.value = 'enabled';
        controller.onSearchBuilderFieldChange({ target: subFieldSelect });
        subCondition.querySelector('select[data-action$="#onSearchBuilderOperatorChange"]').value = 'eq';
        controller.onSearchBuilderOperatorChange();
        // Boolean field swaps the input for a select with value "1" already
        const booleanValueSelect = subCondition.querySelector('.zhortein-datatable__search-builder-value-container select');
        if (booleanValueSelect) {
            booleanValueSelect.value = '1';
        }

        controller.refresh();
        await flushPromises();

        const url = getLastRequestedUrl(fetchMock);
        expect(url.searchParams.get('advancedFilters[logic]')).toBe('and');
        expect(url.searchParams.get('advancedFilters[conditions][0][field]')).toBe('email');
        expect(url.searchParams.get('advancedFilters[conditions][0][operator]')).toBe('contains');
        expect(url.searchParams.get('advancedFilters[conditions][0][value]')).toBe('alice');
        expect(url.searchParams.get('advancedFilters[conditions][1][logic]')).toBe('or');
        expect(url.searchParams.get('advancedFilters[conditions][1][conditions][0][field]')).toBe('enabled');
        expect(url.searchParams.get('advancedFilters[conditions][1][conditions][0][operator]')).toBe('eq');
    });

    it('changes subgroup logic and triggers refresh', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(createJsonResponse()));
        vi.stubGlobal('fetch', fetchMock);

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { element, controller } = await getController(application);

        clickWithCurrentTarget(controller, 'addSearchBuilderSubgroup', getRootAddSubgroupButton(element));
        const subgroup = element.querySelector('.zhortein-datatable__search-builder-group--nested');

        // Add condition inside subgroup so it is serialized
        const subAddCondition = subgroup.querySelector(':scope > button[data-action$="#addSearchBuilderCondition"]');
        clickWithCurrentTarget(controller, 'addSearchBuilderCondition', subAddCondition);
        const subCondition = subgroup.querySelector(':scope > .zhortein-datatable__search-builder-conditions > .zhortein-datatable__search-builder-condition');
        const subFieldSelect = subCondition.querySelector('select[data-action$="#onSearchBuilderFieldChange"]');
        subFieldSelect.value = 'email';
        controller.onSearchBuilderFieldChange({ target: subFieldSelect });
        subCondition.querySelector('select[data-action$="#onSearchBuilderOperatorChange"]').value = 'eq';
        controller.onSearchBuilderOperatorChange();
        subCondition.querySelector('.zhortein-datatable__search-builder-value-container input').value = 'bob';

        const subgroupLogicSelect = subgroup.querySelector('select.zhortein-datatable__search-builder-logic');
        subgroupLogicSelect.value = 'OR';
        controller.updateSearchBuilderLogic();
        await flushPromises();

        const url = getLastRequestedUrl(fetchMock);
        expect(url.searchParams.get('advancedFilters[conditions][0][logic]')).toBe('or');
    });

    it('clears nested filters when clearing the search builder', async () => {
        const fetchMock = vi.fn(() => Promise.resolve(createJsonResponse()));
        vi.stubGlobal('fetch', fetchMock);

        document.body.innerHTML = createDatatableHtml();
        application = startApplication();

        const { element, controller } = await getController(application);
        clickWithCurrentTarget(controller, 'addSearchBuilderCondition', getRootAddConditionButton(element));
        clickWithCurrentTarget(controller, 'addSearchBuilderSubgroup', getRootAddSubgroupButton(element));

        const rootConditionsContainer = element.querySelector('.zhortein-datatable__search-builder-group--root > .zhortein-datatable__search-builder-conditions');
        expect(rootConditionsContainer.children.length).toBe(2);

        element.querySelector('button[data-action$="#clearSearchBuilder"]').click();
        await flushPromises();

        expect(rootConditionsContainer.children.length).toBe(0);
        const url = getLastRequestedUrl(fetchMock);
        expect(url.searchParams.has('advancedFilters[logic]')).toBe(false);
    });
});
