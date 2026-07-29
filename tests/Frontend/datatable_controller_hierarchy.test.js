import { Application } from '@hotwired/stimulus';
import { afterEach, describe, expect, it, vi } from 'vitest';
import DatatableController from '../../assets/controllers/datatable_controller.js';

const CONTROLLER_IDENTIFIER = 'zhortein--datatable-bundle--datatable';
const CHILD_ROW_SELECTOR = '[data-zhortein--datatable-bundle--datatable-child-row="true"]';
const CHILD_STATE_ATTRIBUTE = 'data-zhortein--datatable-bundle--datatable-child-state';

function createDatatableHtml() {
    return `
        <div
            id="zhortein-datatable-orders"
            data-controller="${CONTROLLER_IDENTIFIER}"
            data-${CONTROLLER_IDENTIFIER}-name-value="orders"
            data-${CONTROLLER_IDENTIFIER}-instance-value="active-orders"
            data-${CONTROLLER_IDENTIFIER}-auto-load-value="false"
            data-${CONTROLLER_IDENTIFIER}-hidden-class-value="d-none"
            data-${CONTROLLER_IDENTIFIER}-visible-class-value="d-flex"
            data-${CONTROLLER_IDENTIFIER}-status-error-class-value="text-danger"
            data-${CONTROLLER_IDENTIFIER}-status-success-class-value="text-success"
            data-${CONTROLLER_IDENTIFIER}-status-muted-class-value="text-body-secondary"
        >
            <table>
                <thead data-${CONTROLLER_IDENTIFIER}-target="header">
                    <tr><th>Children</th><th>Order</th></tr>
                </thead>
                <tbody data-${CONTROLLER_IDENTIFIER}-target="body">
                    <tr data-${CONTROLLER_IDENTIFIER}-row-identifier="42">
                        <td>
                            <button
                                id="child-toggle"
                                type="button"
                                aria-expanded="false"
                                aria-controls="child-row-42"
                                aria-label="Expand row 42"
                                data-action="click->${CONTROLLER_IDENTIFIER}#toggleChildDatatable"
                                data-${CONTROLLER_IDENTIFIER}-child-toggle="true"
                                data-${CONTROLLER_IDENTIFIER}-child-url="/_zhortein/datatable/order-lines/child?_zd_instance=child-42&amp;_zd_context=signed"
                                data-${CONTROLLER_IDENTIFIER}-child-target-id="child-row-42"
                                data-${CONTROLLER_IDENTIFIER}-child-expand-label="Expand row 42"
                                data-${CONTROLLER_IDENTIFIER}-child-collapse-label="Collapse row 42"
                                data-${CONTROLLER_IDENTIFIER}-child-loading-label="Loading order lines…"
                                data-${CONTROLLER_IDENTIFIER}-child-error-label="Unable to load order lines."
                                data-${CONTROLLER_IDENTIFIER}-child-retry-label="Try again"
                            >
                                <span
                                    aria-hidden="true"
                                    data-${CONTROLLER_IDENTIFIER}-child-indicator="true"
                                >
                                    <span data-${CONTROLLER_IDENTIFIER}-child-expand-icon="true">▸</span>
                                    <span data-${CONTROLLER_IDENTIFIER}-child-collapse-icon="true" hidden>▾</span>
                                </span>
                            </button>
                        </td>
                        <td>Order 42</td>
                    </tr>
                    <tr
                        id="child-row-42"
                        data-${CONTROLLER_IDENTIFIER}-child-row="true"
                        data-${CONTROLLER_IDENTIFIER}-child-state="idle"
                        hidden
                    >
                        <td colspan="2">
                            <div data-${CONTROLLER_IDENTIFIER}-child-content="true"></div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div data-${CONTROLLER_IDENTIFIER}-target="pagination"></div>
            <div data-${CONTROLLER_IDENTIFIER}-target="summary"></div>
            <div data-${CONTROLLER_IDENTIFIER}-target="error" class="d-none"></div>
            <div data-${CONTROLLER_IDENTIFIER}-target="loading" class="d-none"></div>
        </div>
    `;
}

function createHtmlResponse(html, ok = true) {
    return {
        ok,
        text: () => Promise.resolve(html),
    };
}

function createNestedDatatableHtml() {
    return `
        <div
            id="zhortein-datatable-order-lines-child-42"
            data-controller="${CONTROLLER_IDENTIFIER}"
            data-${CONTROLLER_IDENTIFIER}-name-value="order-lines"
            data-${CONTROLLER_IDENTIFIER}-instance-value="child-42"
            data-${CONTROLLER_IDENTIFIER}-auto-load-value="false"
            data-${CONTROLLER_IDENTIFIER}-hidden-class-value="d-none"
            data-${CONTROLLER_IDENTIFIER}-visible-class-value="d-flex"
            data-${CONTROLLER_IDENTIFIER}-status-error-class-value="text-danger"
            data-${CONTROLLER_IDENTIFIER}-status-success-class-value="text-success"
            data-${CONTROLLER_IDENTIFIER}-status-muted-class-value="text-body-secondary"
            data-${CONTROLLER_IDENTIFIER}-page-value="1"
        >
            <table>
                <thead data-${CONTROLLER_IDENTIFIER}-target="header">
                    <tr><th>Product</th></tr>
                </thead>
                <tbody data-${CONTROLLER_IDENTIFIER}-target="body">
                    <tr><td>Keyboard</td></tr>
                </tbody>
            </table>
            <div data-${CONTROLLER_IDENTIFIER}-target="pagination"></div>
            <div data-${CONTROLLER_IDENTIFIER}-target="summary"></div>
        </div>
    `;
}

async function flushPromises() {
    await Promise.resolve();
    await Promise.resolve();
    await new Promise((resolve) => window.setTimeout(resolve, 0));
    await Promise.resolve();
}

function startApplication() {
    const application = Application.start();

    application.register(CONTROLLER_IDENTIFIER, DatatableController);

    return application;
}

describe('datatable_controller hierarchical child loading', () => {
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

    it('loads a child once and reuses it across collapse and expand cycles', async () => {
        let resolveFetch;
        const fetchMock = vi.fn(() => new Promise((resolve) => {
            resolveFetch = resolve;
        }));

        vi.stubGlobal('fetch', fetchMock);
        document.body.innerHTML = createDatatableHtml();
        application = startApplication();
        await flushPromises();

        const toggle = document.querySelector('#child-toggle');
        const childRow = document.querySelector(CHILD_ROW_SELECTOR);

        toggle.focus();
        toggle.click();

        expect(childRow.hidden).toBe(false);
        expect(childRow.getAttribute(CHILD_STATE_ATTRIBUTE)).toBe('loading');
        expect(childRow.hasAttribute('aria-busy')).toBe(true);
        expect(toggle.getAttribute('aria-expanded')).toBe('true');
        expect(toggle.getAttribute('aria-label')).toBe('Collapse row 42');
        expect(toggle.hasAttribute('aria-busy')).toBe(true);
        expect(childRow.querySelector('[role="status"]').textContent).toContain('Loading order lines…');
        expect(fetchMock).toHaveBeenCalledTimes(1);
        expect(fetchMock.mock.calls[0][0]).toContain('_zd_context=signed');
        expect(fetchMock.mock.calls[0][1].headers.Accept).toBe('text/html');

        resolveFetch(createHtmlResponse('<button id="child-focus-target">Loaded order lines</button>'));
        await flushPromises();

        expect(childRow.getAttribute(CHILD_STATE_ATTRIBUTE)).toBe('loaded');
        expect(childRow.hasAttribute('aria-busy')).toBe(false);
        expect(toggle.hasAttribute('aria-busy')).toBe(false);
        expect(document.activeElement).toBe(toggle);
        expect(childRow.textContent).toContain('Loaded order lines');

        toggle.click();

        expect(childRow.hidden).toBe(true);
        expect(toggle.getAttribute('aria-expanded')).toBe('false');
        expect(toggle.getAttribute('aria-label')).toBe('Expand row 42');
        expect(toggle.textContent).toContain('▸');
        expect(toggle.querySelector(`[data-${CONTROLLER_IDENTIFIER}-child-expand-icon="true"]`).hidden).toBe(false);
        expect(toggle.querySelector(`[data-${CONTROLLER_IDENTIFIER}-child-collapse-icon="true"]`).hidden).toBe(true);

        toggle.click();
        await flushPromises();

        expect(childRow.hidden).toBe(false);
        expect(toggle.textContent).toContain('▾');
        expect(toggle.querySelector(`[data-${CONTROLLER_IDENTIFIER}-child-expand-icon="true"]`).hidden).toBe(true);
        expect(toggle.querySelector(`[data-${CONTROLLER_IDENTIFIER}-child-collapse-icon="true"]`).hidden).toBe(false);
        expect(fetchMock).toHaveBeenCalledTimes(1);
        expect(childRow.textContent).toContain('Loaded order lines');
    });

    it('keeps an error stable until an explicit retry succeeds', async () => {
        const fetchMock = vi.fn()
            .mockResolvedValueOnce(createHtmlResponse('', false))
            .mockResolvedValueOnce(createHtmlResponse('<p>Recovered order lines</p>'));

        vi.stubGlobal('fetch', fetchMock);
        document.body.innerHTML = createDatatableHtml();
        application = startApplication();
        await flushPromises();

        const toggle = document.querySelector('#child-toggle');
        const childRow = document.querySelector(CHILD_ROW_SELECTOR);

        toggle.click();
        await flushPromises();

        expect(childRow.getAttribute(CHILD_STATE_ATTRIBUTE)).toBe('error');
        expect(childRow.querySelector('[role="alert"]').textContent).toContain('Unable to load order lines.');
        expect(fetchMock).toHaveBeenCalledTimes(1);

        toggle.click();
        toggle.click();
        await flushPromises();

        expect(fetchMock).toHaveBeenCalledTimes(1);

        const retryButton = childRow.querySelector('button');

        retryButton.focus();
        retryButton.click();
        await flushPromises();

        expect(fetchMock).toHaveBeenCalledTimes(2);
        expect(childRow.getAttribute(CHILD_STATE_ATTRIBUTE)).toBe('loaded');
        expect(childRow.textContent).toContain('Recovered order lines');
        expect(document.activeElement).toBe(toggle);
    });

    it('restores focus to the toggle when focused child content is collapsed', async () => {
        vi.stubGlobal('fetch', vi.fn(() => Promise.resolve(
            createHtmlResponse('<button id="child-focus-target">Child action</button>'),
        )));
        document.body.innerHTML = createDatatableHtml();
        application = startApplication();
        await flushPromises();

        const toggle = document.querySelector('#child-toggle');
        const childRow = document.querySelector(CHILD_ROW_SELECTOR);

        toggle.click();
        await flushPromises();

        const childAction = document.querySelector('#child-focus-target');

        childAction.focus();
        expect(document.activeElement).toBe(childAction);

        toggle.click();

        expect(childRow.hidden).toBe(true);
        expect(document.activeElement).toBe(toggle);
    });

    it('aborts an in-flight child request before parent fragments replace the rows', async () => {
        const fetchMock = vi.fn(() => new Promise(() => {}));

        vi.stubGlobal('fetch', fetchMock);
        document.body.innerHTML = createDatatableHtml();
        application = startApplication();
        await flushPromises();

        const element = document.querySelector('#zhortein-datatable-orders');
        const controller = application.getControllerForElementAndIdentifier(
            element,
            CONTROLLER_IDENTIFIER,
        );

        document.querySelector('#child-toggle').click();

        const signal = fetchMock.mock.calls[0][1].signal;

        expect(signal.aborted).toBe(false);

        controller.applyFragments({
            body: '<tr><td colspan="2">Replacement row</td></tr>',
        });

        expect(signal.aborted).toBe(true);
        expect(document.querySelector(CHILD_ROW_SELECTOR)).toBe(null);
    });

    it('connects nested datatables as isolated controller instances', async () => {
        vi.stubGlobal('fetch', vi.fn(() => Promise.resolve(
            createHtmlResponse(createNestedDatatableHtml()),
        )));
        document.body.innerHTML = createDatatableHtml();
        application = startApplication();
        await flushPromises();

        const parentElement = document.querySelector('#zhortein-datatable-orders');
        const parentController = application.getControllerForElementAndIdentifier(
            parentElement,
            CONTROLLER_IDENTIFIER,
        );

        document.querySelector('#child-toggle').click();
        await flushPromises();

        const childElement = document.querySelector('#zhortein-datatable-order-lines-child-42');
        const childController = application.getControllerForElementAndIdentifier(
            childElement,
            CONTROLLER_IDENTIFIER,
        );

        expect(childController).toBeInstanceOf(DatatableController);
        expect(childController).not.toBe(parentController);
        expect(childController.nameValue).toBe('order-lines');
        expect(childController.instanceValue).toBe('child-42');

        childController.pageValue = 3;

        expect(parentController.pageValue).toBe(1);
        expect(childController.pageValue).toBe(3);
    });
});
