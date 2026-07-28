const CHILD_CONTENT_ATTRIBUTE = 'data-zhortein--datatable-bundle--datatable-child-content';
const CHILD_INDICATOR_ATTRIBUTE = 'data-zhortein--datatable-bundle--datatable-child-indicator';
const CHILD_ROW_ATTRIBUTE = 'data-zhortein--datatable-bundle--datatable-child-row';
const CHILD_STATE_ATTRIBUTE = 'data-zhortein--datatable-bundle--datatable-child-state';
const CHILD_TARGET_ID_ATTRIBUTE = 'data-zhortein--datatable-bundle--datatable-child-target-id';
const CHILD_TOGGLE_ATTRIBUTE = 'data-zhortein--datatable-bundle--datatable-child-toggle';
const CHILD_URL_ATTRIBUTE = 'data-zhortein--datatable-bundle--datatable-child-url';
const CHILD_EXPAND_LABEL_ATTRIBUTE = 'data-zhortein--datatable-bundle--datatable-child-expand-label';
const CHILD_COLLAPSE_LABEL_ATTRIBUTE = 'data-zhortein--datatable-bundle--datatable-child-collapse-label';
const CHILD_LOADING_LABEL_ATTRIBUTE = 'data-zhortein--datatable-bundle--datatable-child-loading-label';
const CHILD_ERROR_LABEL_ATTRIBUTE = 'data-zhortein--datatable-bundle--datatable-child-error-label';
const CHILD_RETRY_LABEL_ATTRIBUTE = 'data-zhortein--datatable-bundle--datatable-child-retry-label';

export default class ChildDatatableManager {
    constructor(controller) {
        this.controller = controller;
        this.requests = new Map();
    }

    toggle(event) {
        event.preventDefault();

        const toggle = event.currentTarget;

        if (!(toggle instanceof HTMLButtonElement)) {
            return;
        }

        const childRow = this.resolveChildRow(toggle);

        if (childRow === null) {
            return;
        }

        const shouldExpand = toggle.getAttribute('aria-expanded') !== 'true';

        this.setExpanded(toggle, childRow, shouldExpand);

        if (shouldExpand) {
            this.load(toggle, childRow);
        }
    }

    retry(event) {
        event.preventDefault();

        const retryButton = event.currentTarget;

        if (!(retryButton instanceof HTMLButtonElement)) {
            return;
        }

        const targetId = retryButton.getAttribute(CHILD_TARGET_ID_ATTRIBUTE);
        const toggle = this.findToggle(targetId);

        if (toggle === null) {
            return;
        }

        const childRow = this.resolveChildRow(toggle);

        if (childRow === null) {
            return;
        }

        this.setExpanded(toggle, childRow, true);
        this.load(toggle, childRow, true);
    }

    async load(toggle, childRow, retry = false) {
        const state = childRow.getAttribute(CHILD_STATE_ATTRIBUTE) || 'idle';

        if (state === 'loaded' || state === 'loading' || (state === 'error' && !retry)) {
            return;
        }

        const content = childRow.querySelector(`[${CHILD_CONTENT_ATTRIBUTE}="true"]`);

        if (!(content instanceof HTMLElement)) {
            return;
        }

        const url = toggle.getAttribute(CHILD_URL_ATTRIBUTE);

        childRow.setAttribute(CHILD_STATE_ATTRIBUTE, 'loading');
        this.setLoading(toggle, childRow, true);
        this.renderLoading(content, toggle);

        if (typeof url !== 'string' || url.trim() === '') {
            childRow.setAttribute(CHILD_STATE_ATTRIBUTE, 'error');
            this.setLoading(toggle, childRow, false);
            this.renderError(content, toggle);

            return;
        }

        const abortController = new AbortController();

        this.requests.set(childRow, {
            abortController,
            content,
            toggle,
        });

        try {
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    Accept: 'text/html',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: abortController.signal,
            });

            if (!response.ok) {
                throw new Error('Unable to load child datatable.');
            }

            const html = await response.text();

            if (html.trim() === '') {
                throw new Error('Unable to load child datatable.');
            }

            if (
                this.requests.get(childRow)?.abortController !== abortController
                || !childRow.isConnected
            ) {
                return;
            }

            content.innerHTML = html;
            childRow.setAttribute(CHILD_STATE_ATTRIBUTE, 'loaded');
            this.setLoading(toggle, childRow, false);

            if (retry) {
                toggle.focus();
            }
        } catch (error) {
            const wasAborted = error !== null
                && typeof error === 'object'
                && error.name === 'AbortError';

            if (wasAborted || !childRow.isConnected) {
                return;
            }

            childRow.setAttribute(CHILD_STATE_ATTRIBUTE, 'error');
            this.setLoading(toggle, childRow, false);
            this.renderError(content, toggle);
        } finally {
            if (this.requests.get(childRow)?.abortController === abortController) {
                this.requests.delete(childRow);
            }
        }
    }

    abortAll(reset = false) {
        this.requests.forEach(({ abortController, content, toggle }, childRow) => {
            abortController.abort();

            if (!reset) {
                return;
            }

            childRow.setAttribute(CHILD_STATE_ATTRIBUTE, 'idle');
            this.setLoading(toggle, childRow, false);
            content.replaceChildren();
        });
        this.requests.clear();
    }

    resolveChildRow(toggle) {
        const targetId = toggle.getAttribute(CHILD_TARGET_ID_ATTRIBUTE)
            || toggle.getAttribute('aria-controls');
        const parentRow = toggle.closest('tr');
        const childRow = parentRow?.nextElementSibling;

        if (
            typeof targetId !== 'string'
            || !(childRow instanceof HTMLTableRowElement)
            || childRow.id !== targetId
            || childRow.getAttribute(CHILD_ROW_ATTRIBUTE) !== 'true'
        ) {
            return null;
        }

        return childRow;
    }

    findToggle(targetId) {
        if (typeof targetId !== 'string' || targetId === '') {
            return null;
        }

        return Array.from(this.controller.element.querySelectorAll(`[${CHILD_TOGGLE_ATTRIBUTE}="true"]`))
            .find((toggle) => (
                toggle instanceof HTMLButtonElement
                && toggle.getAttribute(CHILD_TARGET_ID_ATTRIBUTE) === targetId
            )) ?? null;
    }

    setExpanded(toggle, childRow, expanded) {
        const shouldRestoreFocus = !expanded && childRow.contains(document.activeElement);

        childRow.hidden = !expanded;
        toggle.setAttribute('aria-expanded', String(expanded));
        toggle.setAttribute(
            'aria-label',
            toggle.getAttribute(expanded ? CHILD_COLLAPSE_LABEL_ATTRIBUTE : CHILD_EXPAND_LABEL_ATTRIBUTE)
                || toggle.getAttribute('aria-label')
                || '',
        );

        const indicator = toggle.querySelector(`[${CHILD_INDICATOR_ATTRIBUTE}="true"]`);

        if (indicator instanceof HTMLElement) {
            const expandIcon = indicator.querySelector('[data-zhortein--datatable-bundle--datatable-child-expand-icon="true"]');
            const collapseIcon = indicator.querySelector('[data-zhortein--datatable-bundle--datatable-child-collapse-icon="true"]');

            if (expandIcon instanceof HTMLElement && collapseIcon instanceof HTMLElement) {
                expandIcon.hidden = expanded;
                collapseIcon.hidden = !expanded;
            } else {
                indicator.textContent = expanded ? '▾' : '▸';
            }
        }

        if (shouldRestoreFocus) {
            toggle.focus();
        }
    }

    setLoading(toggle, childRow, loading) {
        toggle.toggleAttribute('aria-busy', loading);
        childRow.toggleAttribute('aria-busy', loading);
        childRow.classList.toggle('is-loading', loading);
    }

    renderLoading(content, toggle) {
        const status = document.createElement('div');
        const spinner = document.createElement('span');
        const label = document.createElement('span');

        status.className = 'd-flex align-items-center gap-2 text-body-secondary small';
        status.setAttribute('role', 'status');
        spinner.className = 'spinner-border spinner-border-sm';
        spinner.setAttribute('aria-hidden', 'true');
        label.textContent = toggle.getAttribute(CHILD_LOADING_LABEL_ATTRIBUTE) || 'Loading child rows…';
        status.append(spinner, label);
        content.replaceChildren(status);
    }

    renderError(content, toggle) {
        const alert = document.createElement('div');
        const message = document.createElement('span');
        const retryButton = document.createElement('button');
        const targetId = toggle.getAttribute(CHILD_TARGET_ID_ATTRIBUTE) || '';

        alert.className = 'alert alert-danger d-flex flex-wrap align-items-center gap-2 m-0';
        alert.setAttribute('role', 'alert');
        message.textContent = toggle.getAttribute(CHILD_ERROR_LABEL_ATTRIBUTE) || 'Unable to load child rows.';
        retryButton.type = 'button';
        retryButton.className = 'btn btn-sm btn-outline-danger';
        retryButton.textContent = toggle.getAttribute(CHILD_RETRY_LABEL_ATTRIBUTE) || 'Retry';
        retryButton.setAttribute(CHILD_TARGET_ID_ATTRIBUTE, targetId);
        retryButton.setAttribute('data-action', `click->${this.controller.identifier}#retryChildDatatable`);
        alert.append(message, retryButton);
        content.replaceChildren(alert);
    }
}
