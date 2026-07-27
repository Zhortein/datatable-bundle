import { Controller } from '@hotwired/stimulus';

const DATATABLE_STATE_VERSION = 1;
const MAX_STATE_PAYLOAD_LENGTH = 32768;

/**
 * Controls a Zhortein datatable.
 *
 * This controller intentionally stays small.
 * It does not render cells manually and only updates server-rendered HTML fragments.
 */
export default class extends Controller {
    static targets = [
        'header',
        'body',
        'pagination',
        'summary',
        'searchInput',
        'pageSizeInput',
        'activeFilters',
        'clearFiltersButton',
        'error',
        'feedback',
        'loading',
        'globalActions',
        'confirmationModal',
        'confirmationMessage',
        'confirmationConfirmButton',
        'selectAllCheckbox',
        'rowCheckbox',
        'selectedCount',
        'bulkToolbar',
        'bulkAction',
        'searchBuilder',
        'searchBuilderConditions',
        'searchBuilderConditionTemplate',
        'searchBuilderGroupTemplate',
        'savedViewSelect',
        'savedViewName',
        'savedViewAction',
        'savedViewStatus',
    ];

    static values = {
        name: String,
        instance: String,
        stateParameter: String,
        fragmentsUrl: String,
        exportUrl: String,
        page: { type: Number, default: 1 },
        pageSize: { type: Number, default: 25 },
        search: { type: String, default: '' },
        sortField: { type: String, default: '' },
        sortDirection: { type: String, default: 'asc' },
        autoLoad: { type: Boolean, default: true },
        filterLayout: String,
        searchBuilder: { type: Boolean, default: false },
        booleanDisplayMode: String,
        paginationSize: { type: String, default: 'default' },
        tableSmall: { type: Boolean, default: false },
        actionSuccessMessage: { type: String, default: 'The action completed successfully.' },
        actionErrorMessage: { type: String, default: 'The action could not be completed.' },
        invalidActionResponseMessage: { type: String, default: 'The action returned an invalid response.' },
        savedViewsUrl: String,
        savedViewsCsrfToken: String,
        savedViewsIncludePage: { type: Boolean, default: false },
        savedViewDefaultSuffix: { type: String, default: '(default)' },
        savedViewDeleteConfirmation: { type: String, default: 'Delete this saved view?' },
        savedViewSuccessMessage: { type: String, default: 'The saved view was updated.' },
        savedViewErrorMessage: { type: String, default: 'The saved view operation failed.' },
        savedViewConflictMessage: { type: String, default: 'The saved view changed in another request. Reload it and try again.' },
    };

    connect() {
        this.abortController = null;
        this.searchDebounceTimeout = null;
        this.filterDebounceTimeout = null;
        this.columnVisibilityDebounceTimeout = null;
        this.pendingConfirmationTarget = null;
        this.pendingConfirmationType = null;
        this.confirmationModalInstance = null;
        this.selectedIds = new Set();
        this.ajaxActionAbortControllers = new Map();
        this.savedViewsAbortController = null;
        this.savedViews = [];
        this.defaultState = this.collectState();
        this.handlePopState = this.restoreStateFromHistory.bind(this);
        this.handleTurboBeforeCache = this.persistStateBeforeTurboCache.bind(this);
        this.stateHistoryEnabled = this.hasStateParameterValue && this.stateParameterValue !== '';

        if (this.stateHistoryEnabled) {
            window.addEventListener('popstate', this.handlePopState);
            document.addEventListener('turbo:before-cache', this.handleTurboBeforeCache);
        }

        const urlState = this.stateHistoryEnabled ? this.readUrlState() : null;

        if (urlState !== null) {
            this.applyStateSnapshot(urlState);
            this.dispatchStateEvent('state:restore', urlState, 'connect');
        }

        this.updateActiveFilterState();

        const hasSavedViews = this.hasSavedViewsUrlValue && this.savedViewsUrlValue !== '';
        const shouldRestoreDefaultView = hasSavedViews && urlState === null;
        const savedViewsPromise = hasSavedViews
            ? this.refreshSavedViewList(shouldRestoreDefaultView)
            : Promise.resolve(null);

        if (this.autoLoadValue && shouldRestoreDefaultView) {
            savedViewsPromise.then(() => this.refresh(null, 'replace'));
        } else if (this.autoLoadValue) {
            this.refresh(null, 'replace');
        }
    }

    disconnect() {
        if (this.stateHistoryEnabled) {
            window.removeEventListener('popstate', this.handlePopState);
            document.removeEventListener('turbo:before-cache', this.handleTurboBeforeCache);
        }
        this.abortPendingRequest();
        this.ajaxActionAbortControllers.forEach((controller) => controller.abort());
        this.ajaxActionAbortControllers.clear();
        this.savedViewsAbortController?.abort();
        this.savedViewsAbortController = null;

        if (this.searchDebounceTimeout !== null) {
            window.clearTimeout(this.searchDebounceTimeout);
        }

        if (this.filterDebounceTimeout !== null) {
            window.clearTimeout(this.filterDebounceTimeout);
        }

        if (this.columnVisibilityDebounceTimeout !== null) {
            window.clearTimeout(this.columnVisibilityDebounceTimeout);
        }
    }

    refresh(event = null, historyMode = null) {
        if (event !== null) {
            event.preventDefault();
        }

        if (!this.hasFragmentsUrlValue || this.fragmentsUrlValue === '') {
            this.showError('The datatable fragments URL is missing.');

            return Promise.resolve(null);
        }

        this.abortPendingRequest();
        this.abortController = new AbortController();
        this.setLoading(true);
        this.clearError();
        const resolvedHistoryMode = historyMode ?? this.resolveHistoryMode(event);

        return fetch(this.buildFragmentsUrl(), {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            signal: this.abortController.signal,
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`Unable to refresh datatable "${this.nameValue}".`);
                }

                return response.json();
            })
            .then((payload) => {
                this.applyFragments(payload);
                this.applyState(payload);
                this.updateActiveFilterState();
                this.clearError();
                this.commitUrlState(resolvedHistoryMode);

                return payload;
            })
            .catch((error) => {
                if (error.name === 'AbortError') {
                    return null;
                }

                this.showError(error.message);

                return null;
            })
            .finally(() => {
                this.setLoading(false);
                this.abortController = null;
                this.updateActiveFilterState();
            });
    }

    confirmAction(event) {
        const message = this.resolveConfirmationMessage(event.currentTarget);

        if (message === null) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        if (!this.openConfirmationModal(event.currentTarget, message)) {
            if (!window.confirm(message)) {
                return;
            }

            this.executeConfirmedTarget(event.currentTarget);
        }
    }

    openConfirmationModal(target, message) {
        if (!this.hasConfirmationModalTarget || !this.hasConfirmationMessageTarget) {
            return false;
        }

        if (typeof window.bootstrap === 'undefined' || typeof window.bootstrap.Modal === 'undefined') {
            return false;
        }

        this.pendingConfirmationTarget = target;
        this.pendingConfirmationType = target instanceof HTMLFormElement ? 'form' : 'link';
        this.confirmationMessageTarget.textContent = message;
        this.confirmationModalInstance = window.bootstrap.Modal.getOrCreateInstance(this.confirmationModalTarget);
        this.confirmationModalInstance.show();

        return true;
    }

    confirmPendingAction(event) {
        if (event !== null && typeof event.preventDefault === 'function') {
            event.preventDefault();
        }

        if (this.confirmationModalInstance !== null) {
            this.confirmationModalInstance.hide();
        }

        if (this.pendingConfirmationTarget === null) {
            return;
        }

        const target = this.pendingConfirmationTarget;

        this.pendingConfirmationTarget = null;
        this.pendingConfirmationType = null;

        this.executeConfirmedTarget(target);
    }

    executeConfirmedTarget(target) {
        if (this.isAjaxActionTarget(target)) {
            if (target instanceof HTMLFormElement && target.hasAttribute('data-zhortein--datatable-bundle--datatable-selected-rows-parameter-name')) {
                this.injectSelectedIds(target);
            }

            this.executeAjaxTarget(target);

            return;
        }

        if (target instanceof HTMLFormElement) {
            if (target.hasAttribute('data-zhortein--datatable-bundle--datatable-selected-rows-parameter-name')) {
                this.injectSelectedIds(target);
            }

            target.submit();

            return;
        }

        if (target instanceof HTMLAnchorElement) {
            window.location.assign(target.href);
        }
    }

    submitBulkAction(event) {
        if (this.selectedIds.size === 0) {
            event.preventDefault();

            return;
        }

        this.injectSelectedIds(event.currentTarget);

        if (this.isAjaxActionTarget(event.currentTarget)) {
            this.executeAjaxAction(event);

            return;
        }

        const message = this.resolveConfirmationMessage(event.currentTarget);

        if (message !== null) {
            this.confirmAction(event);

            return;
        }

    }

    executeAjaxAction(event) {
        const target = event.currentTarget;

        if (!this.isAjaxActionTarget(target)) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        if (typeof event.stopImmediatePropagation === 'function') {
            event.stopImmediatePropagation();
        }

        if (this.ajaxActionAbortControllers.has(target)) {
            return;
        }

        const message = this.resolveConfirmationMessage(target);

        if (message !== null) {
            if (!this.openConfirmationModal(target, message) && window.confirm(message)) {
                this.executeAjaxTarget(target);
            }

            return;
        }

        this.executeAjaxTarget(target);
    }

    async executeAjaxTarget(target) {
        if (!this.isAjaxActionTarget(target) || this.ajaxActionAbortControllers.has(target)) {
            return;
        }

        const abortController = new AbortController();
        const detail = this.createAjaxActionEventDetail(target);
        const beforeEvent = this.dispatch('action:before', {
            detail,
            prefix: 'zhortein-datatable',
            cancelable: true,
        });

        if (beforeEvent.defaultPrevented) {
            return;
        }

        this.ajaxActionAbortControllers.set(target, abortController);
        this.setAjaxActionLoading(target, true);
        this.clearError();
        this.clearActionFeedback();
        let response = null;
        let payload = null;

        try {
            response = await fetch(this.resolveAjaxActionUrl(target), this.createAjaxActionRequestOptions(target, abortController.signal));
            payload = await this.parseAjaxActionResponse(response);

            if (!response.ok || payload.ok !== true) {
                throw this.createAjaxActionError(payload);
            }

            await this.applyAjaxActionSuccessStrategy(detail.strategy, target, payload, detail.rowIdentifiers);

            const message = this.resolveAjaxActionMessage(payload, this.actionSuccessMessageValue);
            this.showActionFeedback(message);
            this.dispatch('action:success', {
                detail: { ...detail, payload, response },
                prefix: 'zhortein-datatable',
            });
        } catch (error) {
            if (error instanceof DOMException && error.name === 'AbortError') {
                return;
            }

            const message = error instanceof Error && error.message !== ''
                ? error.message
                : this.actionErrorMessageValue;

            this.showError(message);
            this.dispatch('action:error', {
                detail: { ...detail, error, payload, response },
                prefix: 'zhortein-datatable',
            });
        } finally {
            this.setAjaxActionLoading(target, false);
            this.ajaxActionAbortControllers.delete(target);
            this.dispatch('action:complete', {
                detail,
                prefix: 'zhortein-datatable',
            });
        }
    }

    isAjaxActionTarget(target) {
        return (target instanceof HTMLAnchorElement || target instanceof HTMLFormElement)
            && target.getAttribute('data-zhortein--datatable-bundle--datatable-ajax-action') === 'true';
    }

    resolveAjaxActionUrl(target) {
        if (target instanceof HTMLAnchorElement) {
            return target.href;
        }

        return target.action;
    }

    createAjaxActionRequestOptions(target, signal) {
        const headers = {
            Accept: 'application/vnd.zhortein.datatable-action+json; version=1, application/json',
            'X-Requested-With': 'XMLHttpRequest',
        };

        if (target instanceof HTMLAnchorElement) {
            return {
                method: 'GET',
                headers,
                signal,
            };
        }

        return {
            method: target.method.toUpperCase(),
            headers,
            body: new FormData(target),
            signal,
        };
    }

    async parseAjaxActionResponse(response) {
        let payload;

        try {
            payload = await response.json();
        } catch (error) {
            throw new Error(this.invalidActionResponseMessageValue, { cause: error });
        }

        if (
            payload === null
            || typeof payload !== 'object'
            || Array.isArray(payload)
            || payload.version !== 1
            || typeof payload.ok !== 'boolean'
        ) {
            throw new Error(this.invalidActionResponseMessageValue);
        }

        return payload;
    }

    createAjaxActionError(payload) {
        return new Error(this.resolveAjaxActionMessage(payload, this.actionErrorMessageValue));
    }

    resolveAjaxActionMessage(payload, fallback) {
        if (typeof payload.message === 'string' && payload.message.trim() !== '') {
            return payload.message;
        }

        if (Array.isArray(payload.errors)) {
            const firstError = payload.errors.find((error) => (
                error !== null
                && typeof error === 'object'
                && typeof error.message === 'string'
                && error.message.trim() !== ''
            ));

            if (firstError) {
                return firstError.message;
            }
        }

        return fallback;
    }

    createAjaxActionEventDetail(target) {
        return {
            action: target.getAttribute('data-zhortein--datatable-bundle--datatable-ajax-action-name') || '',
            strategy: target.getAttribute('data-zhortein--datatable-bundle--datatable-ajax-success-strategy') || 'refresh_table',
            target,
            selectedIds: Array.from(this.selectedIds),
            rowIdentifiers: this.resolveAffectedRowIdentifiers(target),
        };
    }

    resolveAffectedRowIdentifiers(target) {
        const row = target.closest('tr[data-zhortein--datatable-bundle--datatable-row-identifier]');

        if (row instanceof HTMLTableRowElement) {
            const identifier = row.getAttribute('data-zhortein--datatable-bundle--datatable-row-identifier');

            return identifier === null ? [] : [identifier];
        }

        return Array.from(this.selectedIds);
    }

    async applyAjaxActionSuccessStrategy(strategy, target, payload, rowIdentifiers) {
        switch (strategy) {
            case 'refresh_table':
                await this.refreshTableAfterAction();
                break;
            case 'refresh_row':
                await this.refreshRows(rowIdentifiers);
                break;
            case 'remove_row':
                this.removeAffectedRows(target, rowIdentifiers);
                break;
            case 'none':
                break;
            case 'redirect':
                if (typeof payload.redirect !== 'string' || payload.redirect.trim() === '') {
                    throw new Error(this.invalidActionResponseMessageValue);
                }

                window.location.assign(new URL(payload.redirect, window.location.origin).toString());
                break;
            default:
                throw new Error(this.invalidActionResponseMessageValue);
        }
    }

    async refreshRows(rowIdentifiers) {
        if (rowIdentifiers.length === 0 || !this.hasFragmentsUrlValue || this.fragmentsUrlValue === '') {
            await this.refreshTableAfterAction();

            return;
        }

        const response = await fetch(this.buildFragmentsUrl(), {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error(`Unable to refresh datatable "${this.nameValue}".`);
        }

        const payload = await response.json();

        if (typeof payload.body !== 'string') {
            throw new Error(this.invalidActionResponseMessageValue);
        }

        const replacementBody = document.createElement('tbody');
        replacementBody.innerHTML = payload.body;
        const currentRows = Array.from(this.bodyTarget.querySelectorAll('tr[data-zhortein--datatable-bundle--datatable-row-identifier]'));
        const replacementRows = Array.from(replacementBody.querySelectorAll('tr[data-zhortein--datatable-bundle--datatable-row-identifier]'));

        for (const identifier of rowIdentifiers) {
            const currentRow = currentRows.find((row) => row.getAttribute('data-zhortein--datatable-bundle--datatable-row-identifier') === identifier);
            const replacementRow = replacementRows.find((row) => row.getAttribute('data-zhortein--datatable-bundle--datatable-row-identifier') === identifier);

            if (!(currentRow instanceof HTMLTableRowElement) || !(replacementRow instanceof HTMLTableRowElement)) {
                await this.refreshTableAfterAction();

                return;
            }

            currentRow.replaceWith(replacementRow);
            this.selectedIds.delete(identifier);
        }

        this.updateSelectionUI();
    }

    async refreshTableAfterAction() {
        const payload = await this.refresh();

        if (payload === null) {
            throw new Error(`Unable to refresh datatable "${this.nameValue}".`);
        }
    }

    removeAffectedRows(target, rowIdentifiers) {
        const targetRow = target.closest('tr');

        if (targetRow instanceof HTMLTableRowElement) {
            targetRow.remove();
        } else {
            Array.from(this.bodyTarget.querySelectorAll('tr[data-zhortein--datatable-bundle--datatable-row-identifier]'))
                .filter((row) => rowIdentifiers.includes(row.getAttribute('data-zhortein--datatable-bundle--datatable-row-identifier')))
                .forEach((row) => row.remove());
        }

        rowIdentifiers.forEach((identifier) => this.selectedIds.delete(identifier));
        this.updateSelectionUI();
    }

    setAjaxActionLoading(target, isLoading) {
        target.toggleAttribute('aria-busy', isLoading);
        target.classList.toggle('is-loading', isLoading);

        if (target instanceof HTMLAnchorElement) {
            target.classList.toggle('disabled', isLoading);
            target.setAttribute('aria-disabled', String(isLoading));

            return;
        }

        target.querySelectorAll('button, input[type="submit"]').forEach((control) => {
            if (!(control instanceof HTMLButtonElement || control instanceof HTMLInputElement)) {
                return;
            }

            if (isLoading) {
                control.setAttribute(
                    'data-zhortein--datatable-bundle--datatable-was-disabled',
                    String(control.disabled),
                );
                control.disabled = true;

                return;
            }

            control.disabled = control.getAttribute('data-zhortein--datatable-bundle--datatable-was-disabled') === 'true';
            control.removeAttribute('data-zhortein--datatable-bundle--datatable-was-disabled');
        });
    }

    injectSelectedIds(form) {
        const parameterName = form.getAttribute('data-zhortein--datatable-bundle--datatable-selected-rows-parameter-name') || 'ids';

        form.querySelectorAll(`input[name="${parameterName}[]"]`).forEach((input) => input.remove());

        this.selectedIds.forEach((id) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = `${parameterName}[]`;
            input.value = id;
            form.appendChild(input);
        });
    }

    search(event) {
        this.searchValue = event.target.value;
        this.pageValue = 1;

        if (this.searchDebounceTimeout !== null) {
            window.clearTimeout(this.searchDebounceTimeout);
        }

        this.searchDebounceTimeout = window.setTimeout(() => {
            this.refresh();
        }, 300);
    }

    changeFilter() {
        this.pageValue = 1;
        this.updateActiveFilterState();

        if (this.filterDebounceTimeout !== null) {
            window.clearTimeout(this.filterDebounceTimeout);
        }

        this.filterDebounceTimeout = window.setTimeout(() => {
            this.refresh();
        }, 300);
    }

    clearFilters(event = null) {
        if (event !== null) {
            event.preventDefault();
        }

        this.getFilterControls().forEach((control) => {
            if (control instanceof HTMLInputElement && (control.type === 'checkbox' || control.type === 'radio')) {
                control.checked = false;

                return;
            }

            control.value = '';
        });

        this.pageValue = 1;
        this.updateActiveFilterState();
        this.refresh();
    }

    clearFilter(event) {
        event.preventDefault();

        const filterName = event.params.filter;

        if (!filterName) {
            return;
        }

        this.getFilterControls().forEach((control) => {
            if (control.name === `filters[${filterName}]` || control.name.startsWith(`filters[${filterName}][`)) {
                if (control instanceof HTMLInputElement && (control.type === 'checkbox' || control.type === 'radio')) {
                    control.checked = false;

                    return;
                }

                control.value = '';
            }
        });

        this.pageValue = 1;
        this.updateActiveFilterState();
        this.refresh();
    }

    changeColumnVisibility() {
        this.pageValue = 1;

        if (this.columnVisibilityDebounceTimeout !== null) {
            window.clearTimeout(this.columnVisibilityDebounceTimeout);
        }

        this.columnVisibilityDebounceTimeout = window.setTimeout(() => {
            this.refresh();
        }, 150);
    }

    changePageSize(event) {
        const pageSize = Number.parseInt(event.target.value, 10);

        if (Number.isNaN(pageSize) || pageSize < 1) {
            return;
        }

        this.pageSizeValue = pageSize;
        this.pageValue = 1;
        this.refresh();
    }

    sort(event) {
        event.preventDefault();

        const field = event.params.field;

        if (!field) {
            return;
        }

        if (this.sortFieldValue === field) {
            this.sortDirectionValue = this.sortDirectionValue === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortFieldValue = field;
            this.sortDirectionValue = 'asc';
        }

        this.pageValue = 1;
        this.refresh();
    }

    goToPage(event) {
        event.preventDefault();

        const page = Number.parseInt(event.params.page, 10);

        if (Number.isNaN(page) || page < 1) {
            return;
        }

        this.pageValue = page;
        this.refresh();
    }

    selectRow(event) {
        const checkbox = event.target;
        const id = checkbox.value;

        if (checkbox.checked) {
            this.selectedIds.add(id);
        } else {
            this.selectedIds.delete(id);
        }

        this.updateSelectionUI();
    }

    selectAll(event) {
        const checkbox = event.target;
        const isChecked = checkbox.checked;

        this.rowCheckboxTargets.forEach((rowCheckbox) => {
            rowCheckbox.checked = isChecked;
            const id = rowCheckbox.value;

            if (isChecked) {
                this.selectedIds.add(id);
            } else {
                this.selectedIds.delete(id);
            }
        });

        this.updateSelectionUI();
    }

    updateSelectionUI() {
        const selectedCount = this.selectedIds.size;
        const visibleRowsCount = this.rowCheckboxTargets.length;
        const selectedVisibleRowsCount = this.rowCheckboxTargets.filter((cb) => cb.checked).length;

        if (this.hasSelectAllCheckboxTarget) {
            this.selectAllCheckboxTarget.checked = visibleRowsCount > 0 && selectedVisibleRowsCount === visibleRowsCount;
            this.selectAllCheckboxTarget.indeterminate = selectedVisibleRowsCount > 0 && selectedVisibleRowsCount < visibleRowsCount;
        }

        if (this.hasSelectedCountTarget) {
            this.selectedCountTargets.forEach((target) => {
                target.textContent = String(selectedCount);
            });
        }

        if (this.hasBulkToolbarTarget) {
            this.bulkToolbarTarget.hidden = selectedCount === 0;
        }

        if (this.hasBulkActionTarget) {
            this.bulkActionTargets.forEach((target) => {
                target.disabled = selectedCount === 0;
            });
        }
    }

    collectState() {
        const rootSearchBuilderGroup = this.hasSearchBuilderTarget
            ? this.getRootSearchBuilderGroupElement()
            : null;
        const advancedFilters = rootSearchBuilderGroup === null
            ? null
            : this.serializeSearchBuilderGroup(rootSearchBuilderGroup);
        const columns = this.collectColumnVisibilityState();

        return {
            version: DATATABLE_STATE_VERSION,
            page: this.pageValue,
            pageSize: this.pageSizeValue,
            search: this.searchValue === '' ? null : this.searchValue,
            sortField: this.sortFieldValue === '' ? null : this.sortFieldValue,
            sortDirection: this.sortDirectionValue,
            filters: this.collectFilterState(),
            advancedFilters: advancedFilters !== null && advancedFilters.conditions.length > 0
                ? advancedFilters
                : {},
            visibleColumns: columns.visibleColumns,
            hiddenColumns: columns.hiddenColumns,
        };
    }

    refreshSavedViewList(restoreDefault = false, preferredIdentifier = null) {
        return this.requestSavedView('GET')
            .then((payload) => {
                if (
                    !this.isStateMap(payload)
                    || payload.version !== 1
                    || !Array.isArray(payload.views)
                ) {
                    throw new TypeError('Invalid named datatable view list.');
                }

                this.savedViews = payload.views.map((view) => this.normalizeSavedViewMetadata(view));
                this.renderSavedViewOptions(preferredIdentifier);

                if (!restoreDefault) {
                    return null;
                }

                const defaultView = this.savedViews.find((view) => view.default);

                if (typeof defaultView === 'undefined') {
                    return null;
                }

                return this.fetchSavedView(defaultView.id).then((view) => {
                    this.applySavedView(view, 'default');
                    this.defaultState = view.state;

                    return view;
                });
            })
            .catch((error) => {
                this.handleSavedViewError(error);

                return null;
            });
    }

    loadSavedView(event) {
        event.preventDefault();
        const identifier = this.savedViewSelectTarget.value;

        this.updateSavedViewActions();

        if (identifier === '') {
            return;
        }

        this.fetchSavedView(identifier)
            .then((view) => {
                this.applySavedView(view, 'selection');

                return this.refresh(null, 'push');
            })
            .catch((error) => this.handleSavedViewError(error));
    }

    createSavedView(event) {
        event.preventDefault();
        const name = this.readSavedViewName();

        if (name === null) {
            return;
        }

        this.requestSavedView('POST', null, {
            name,
            state: this.collectState(),
            includePage: this.savedViewsIncludePageValue,
        })
            .then((payload) => {
                const view = this.normalizeSavedView(payload);
                this.showSavedViewStatus(this.savedViewSuccessMessageValue);
                this.dispatchSavedViewEvent('view:create', view);

                return this.refreshSavedViewList(false, view.id);
            })
            .catch((error) => this.handleSavedViewError(error));
    }

    updateSavedView(event) {
        event.preventDefault();
        const view = this.getSelectedSavedView();

        if (view === null) {
            return;
        }

        this.requestSavedView('PATCH', view.id, {
            operation: 'update',
            revision: view.revision,
            state: this.collectState(),
            includePage: this.savedViewsIncludePageValue,
        })
            .then((payload) => {
                const updatedView = this.normalizeSavedView(payload);
                this.showSavedViewStatus(this.savedViewSuccessMessageValue);
                this.dispatchSavedViewEvent('view:update', updatedView);

                return this.refreshSavedViewList(false, updatedView.id);
            })
            .catch((error) => this.handleSavedViewError(error));
    }

    renameSavedView(event) {
        event.preventDefault();
        const view = this.getSelectedSavedView();
        const name = this.readSavedViewName();

        if (view === null || name === null) {
            return;
        }

        this.requestSavedView('PATCH', view.id, {
            operation: 'rename',
            revision: view.revision,
            name,
        })
            .then((payload) => {
                const renamedView = this.normalizeSavedView(payload);
                this.showSavedViewStatus(this.savedViewSuccessMessageValue);
                this.dispatchSavedViewEvent('view:rename', renamedView);

                return this.refreshSavedViewList(false, renamedView.id);
            })
            .catch((error) => this.handleSavedViewError(error));
    }

    setDefaultSavedView(event) {
        event.preventDefault();
        const view = this.getSelectedSavedView();

        if (view === null) {
            return;
        }

        this.requestSavedView('PATCH', view.id, {
            operation: 'set_default',
            revision: view.revision,
        })
            .then((payload) => {
                const defaultView = this.normalizeSavedView(payload);
                this.showSavedViewStatus(this.savedViewSuccessMessageValue);
                this.dispatchSavedViewEvent('view:default', defaultView);

                return this.refreshSavedViewList(false, defaultView.id);
            })
            .catch((error) => this.handleSavedViewError(error));
    }

    deleteSavedView(event) {
        event.preventDefault();
        const view = this.getSelectedSavedView();

        if (view === null || !window.confirm(this.savedViewDeleteConfirmationValue)) {
            return;
        }

        this.requestSavedView('DELETE', view.id, {
            revision: view.revision,
        })
            .then(() => {
                this.showSavedViewStatus(this.savedViewSuccessMessageValue);
                this.dispatchSavedViewEvent('view:delete', view);

                return this.refreshSavedViewList();
            })
            .catch((error) => this.handleSavedViewError(error));
    }

    fetchSavedView(identifier) {
        return this.requestSavedView('GET', identifier)
            .then((payload) => this.normalizeSavedView(payload));
    }

    applySavedView(view, source) {
        this.applyStateSnapshot(view.state);
        this.selectSavedView(view.id);
        this.dispatchSavedViewEvent('view:load', view, { source });
    }

    normalizeSavedView(payload) {
        if (
            !this.isStateMap(payload)
            || payload.version !== 1
            || !this.isStateMap(payload.view)
            || !this.isStateMap(payload.view.state)
            || typeof payload.view.includePage !== 'boolean'
        ) {
            throw new TypeError('Invalid named datatable view response.');
        }

        return {
            ...this.normalizeSavedViewMetadata(payload.view),
            includePage: payload.view.includePage,
            state: this.normalizeState(payload.view.state),
        };
    }

    normalizeSavedViewMetadata(view) {
        if (
            !this.isStateMap(view)
            || typeof view.id !== 'string'
            || view.id === ''
            || typeof view.name !== 'string'
            || view.name === ''
            || typeof view.revision !== 'string'
            || view.revision === ''
            || typeof view.default !== 'boolean'
        ) {
            throw new TypeError('Invalid named datatable view metadata.');
        }

        return {
            id: view.id,
            name: view.name,
            revision: view.revision,
            default: view.default,
        };
    }

    renderSavedViewOptions(preferredIdentifier = null) {
        if (!this.hasSavedViewSelectTarget) {
            return;
        }

        const placeholder = this.savedViewSelectTarget.options[0]?.textContent ?? '';
        this.savedViewSelectTarget.replaceChildren();
        this.savedViewSelectTarget.append(new Option(placeholder, ''));

        this.savedViews.forEach((view) => {
            const suffix = view.default ? ` ${this.savedViewDefaultSuffixValue}` : '';
            this.savedViewSelectTarget.append(new Option(`${view.name}${suffix}`, view.id));
        });

        this.selectSavedView(preferredIdentifier);
    }

    selectSavedView(identifier = null) {
        if (!this.hasSavedViewSelectTarget) {
            return;
        }

        this.savedViewSelectTarget.value = identifier ?? '';
        const view = this.getSelectedSavedView();

        if (this.hasSavedViewNameTarget) {
            this.savedViewNameTarget.value = view?.name ?? '';
        }

        this.updateSavedViewActions();
    }

    updateSavedViewActions() {
        const enabled = this.getSelectedSavedView() !== null;

        this.savedViewActionTargets.forEach((target) => {
            target.disabled = !enabled;
        });
    }

    getSelectedSavedView() {
        if (!this.hasSavedViewSelectTarget || this.savedViewSelectTarget.value === '') {
            return null;
        }

        return this.savedViews.find((view) => view.id === this.savedViewSelectTarget.value) ?? null;
    }

    readSavedViewName() {
        if (!this.hasSavedViewNameTarget) {
            return null;
        }

        const name = this.savedViewNameTarget.value.trim();

        if (name === '' || name.length > 120) {
            this.handleSavedViewError(new TypeError(this.savedViewErrorMessageValue));

            return null;
        }

        return name;
    }

    requestSavedView(method, identifier = null, payload = null) {
        if (!this.hasSavedViewsUrlValue || this.savedViewsUrlValue === '') {
            return Promise.reject(new Error(this.savedViewErrorMessageValue));
        }

        this.savedViewsAbortController?.abort();
        const abortController = new AbortController();
        this.savedViewsAbortController = abortController;
        const url = this.buildSavedViewUrl(identifier);
        const headers = {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        };
        const options = {
            method,
            headers,
            signal: abortController.signal,
        };

        if (payload !== null) {
            headers['Content-Type'] = 'application/json';
            headers['X-CSRF-Token'] = this.savedViewsCsrfTokenValue;
            options.body = JSON.stringify(payload);
        }

        return fetch(url, options)
            .then(async (response) => {
                if (response.status === 204) {
                    return null;
                }

                const responsePayload = await response.json();

                if (!response.ok) {
                    const error = new Error(responsePayload?.error?.message ?? this.savedViewErrorMessageValue);
                    error.code = responsePayload?.error?.code ?? 'unknown';
                    throw error;
                }

                return responsePayload;
            })
            .finally(() => {
                if (this.savedViewsAbortController === abortController) {
                    this.savedViewsAbortController = null;
                }
            });
    }

    buildSavedViewUrl(identifier = null) {
        const url = new URL(this.savedViewsUrlValue, window.location.origin);

        if (identifier !== null) {
            url.pathname = `${url.pathname.replace(/\/+$/, '')}/${encodeURIComponent(identifier)}`;
        }

        return url.toString();
    }

    handleSavedViewError(error) {
        if (error?.name === 'AbortError') {
            return;
        }

        const message = error?.code === 'conflict'
            ? this.savedViewConflictMessageValue
            : this.savedViewErrorMessageValue;

        this.showSavedViewStatus(message, true);
        this.dispatchSavedViewEvent('view:error', null, {
            code: error?.code ?? 'unknown',
            message: error?.message ?? message,
        });
    }

    showSavedViewStatus(message, error = false) {
        if (!this.hasSavedViewStatusTarget) {
            return;
        }

        this.savedViewStatusTarget.textContent = message;
        this.savedViewStatusTarget.classList.toggle('text-danger', error);
        this.savedViewStatusTarget.classList.toggle('text-success', !error);
        this.savedViewStatusTarget.classList.toggle('text-body-secondary', message === '');
    }

    dispatchSavedViewEvent(name, view = null, extra = {}) {
        this.dispatch(name, {
            detail: { view, ...extra },
            prefix: 'zhortein-datatable',
        });
    }

    collectFilterState() {
        const filters = {};

        this.getFilterControls().forEach((control) => {
            if (
                control instanceof HTMLInputElement
                && (control.type === 'checkbox' || control.type === 'radio')
                && !control.checked
            ) {
                return;
            }

            const path = this.parseFilterControlPath(control.name);
            const value = control.value.trim();

            if (path.length === 0 || value === '') {
                return;
            }

            this.setNestedValue(filters, path, value);
        });

        return filters;
    }

    collectColumnVisibilityState() {
        const visibleColumns = [];
        const hiddenColumns = [];

        this.getColumnVisibilityControls().forEach((control) => {
            if (control.getAttribute('data-zhortein--datatable-bundle--datatable-definition-hidden') === 'true') {
                return;
            }

            const columnName = control.getAttribute('data-zhortein--datatable-bundle--datatable-column-name');

            if (!columnName) {
                return;
            }

            (control.checked ? visibleColumns : hiddenColumns).push(columnName);
        });

        return { visibleColumns, hiddenColumns };
    }

    applyStateSnapshot(state) {
        this.pageValue = state.page;
        this.pageSizeValue = state.pageSize;
        this.searchValue = state.search ?? '';
        this.sortFieldValue = state.sortField ?? '';
        this.sortDirectionValue = state.sortDirection;

        if (this.hasSearchInputTarget) {
            this.searchInputTarget.value = this.searchValue;
        }

        if (this.hasPageSizeInputTarget) {
            this.pageSizeInputTarget.value = String(this.pageSizeValue);
        }

        this.applyFilterState(state.filters);
        this.applyColumnVisibilityState(state.visibleColumns, state.hiddenColumns);
        this.applyAdvancedFilterState(state.advancedFilters);
        this.updateActiveFilterState();
    }

    applyFilterState(filters) {
        this.getFilterControls().forEach((control) => {
            const value = this.getNestedValue(filters, this.parseFilterControlPath(control.name));

            if (control instanceof HTMLInputElement && (control.type === 'checkbox' || control.type === 'radio')) {
                const values = Array.isArray(value) ? value : [value];
                control.checked = values.some((item) => String(item ?? '') === control.value);

                return;
            }

            if (control instanceof HTMLSelectElement && control.multiple) {
                const values = Array.isArray(value) ? value.map(String) : [String(value ?? '')];

                Array.from(control.options).forEach((option) => {
                    option.selected = values.includes(option.value);
                });

                return;
            }

            control.value = typeof value === 'string' || typeof value === 'number'
                ? String(value)
                : '';
        });
    }

    applyColumnVisibilityState(visibleColumns, hiddenColumns) {
        const visibleSet = new Set(visibleColumns);
        const hiddenSet = new Set(hiddenColumns);
        const hasWhitelist = visibleSet.size > 0;

        this.getColumnVisibilityControls().forEach((control) => {
            if (control.getAttribute('data-zhortein--datatable-bundle--datatable-definition-hidden') === 'true') {
                return;
            }

            const columnName = control.getAttribute('data-zhortein--datatable-bundle--datatable-column-name');

            if (!columnName) {
                return;
            }

            control.checked = (!hasWhitelist || visibleSet.has(columnName)) && !hiddenSet.has(columnName);
        });
    }

    parseFilterControlPath(name) {
        if (typeof name !== 'string' || !name.startsWith('filters[')) {
            return [];
        }

        return Array.from(name.matchAll(/\[([^\]]+)]/g), (match) => match[1])
            .filter((part) => part !== '');
    }

    setNestedValue(target, path, value) {
        let cursor = target;

        path.forEach((part, index) => {
            if (index === path.length - 1) {
                cursor[part] = value;

                return;
            }

            if (cursor[part] === null || typeof cursor[part] !== 'object' || Array.isArray(cursor[part])) {
                cursor[part] = {};
            }

            cursor = cursor[part];
        });
    }

    getNestedValue(source, path) {
        let cursor = source;

        for (const part of path) {
            if (cursor === null || typeof cursor !== 'object' || !Object.hasOwn(cursor, part)) {
                return null;
            }

            cursor = cursor[part];
        }

        return cursor;
    }

    readUrlState() {
        if (!this.hasStateParameterValue || this.stateParameterValue === '') {
            return null;
        }

        const payload = new URL(window.location.href).searchParams.get(this.stateParameterValue);

        if (payload === null || payload === '' || payload.length > MAX_STATE_PAYLOAD_LENGTH) {
            return null;
        }

        try {
            return this.normalizeState(JSON.parse(payload));
        } catch (error) {
            return null;
        }
    }

    normalizeState(state) {
        if (
            state === null
            || typeof state !== 'object'
            || Array.isArray(state)
            || state.version !== DATATABLE_STATE_VERSION
            || !Number.isInteger(state.page)
            || state.page < 1
            || !Number.isInteger(state.pageSize)
            || state.pageSize < 1
            || (state.search !== null && typeof state.search !== 'string')
            || (state.sortField !== null && typeof state.sortField !== 'string')
            || !['asc', 'desc'].includes(state.sortDirection)
            || !this.isStringList(state.visibleColumns)
            || !this.isStringList(state.hiddenColumns)
        ) {
            throw new TypeError('Invalid datatable URL state.');
        }

        const filters = this.normalizeStateMap(state.filters);
        const advancedFilters = this.normalizeStateMap(state.advancedFilters);

        return {
            version: DATATABLE_STATE_VERSION,
            page: state.page,
            pageSize: state.pageSize,
            search: state.search,
            sortField: state.sortField,
            sortDirection: state.sortDirection,
            filters,
            advancedFilters,
            visibleColumns: Array.from(new Set(state.visibleColumns)),
            hiddenColumns: Array.from(new Set(state.hiddenColumns)),
        };
    }

    isStateMap(value) {
        return value !== null && typeof value === 'object' && !Array.isArray(value);
    }

    normalizeStateMap(value) {
        if (this.isStateMap(value)) {
            return value;
        }

        if (Array.isArray(value) && value.length === 0) {
            return {};
        }

        throw new TypeError('Invalid datatable URL state.');
    }

    isStringList(value) {
        return Array.isArray(value) && value.every((item) => typeof item === 'string' && item !== '');
    }

    serializeState(state) {
        const payload = JSON.stringify(state);

        return payload.length <= MAX_STATE_PAYLOAD_LENGTH ? payload : null;
    }

    resolveHistoryMode(event) {
        if (!this.hasStateParameterValue || this.stateParameterValue === '') {
            return 'none';
        }

        return event !== null && event.type === 'input' ? 'replace' : 'push';
    }

    commitUrlState(historyMode = 'replace') {
        if (
            historyMode === 'none'
            || !this.hasStateParameterValue
            || this.stateParameterValue === ''
        ) {
            return;
        }

        const state = this.collectState();
        const payload = this.serializeState(state);

        if (payload === null) {
            return;
        }

        const url = new URL(window.location.href);
        const defaultPayload = this.serializeState(this.defaultState);

        if (payload === defaultPayload) {
            url.searchParams.delete(this.stateParameterValue);
        } else {
            url.searchParams.set(this.stateParameterValue, payload);
        }

        if (url.toString() === window.location.href) {
            return;
        }

        this.updateBrowserHistory(historyMode, url);

        this.dispatchStateEvent('state:change', state, historyMode, url.toString());
    }

    updateBrowserHistory(historyMode, url) {
        const currentState = this.isStateMap(window.history.state)
            ? window.history.state
            : {};
        const turboHistory = window.Turbo?.navigator?.history;
        const turboMethod = historyMode === 'push' ? turboHistory?.push : turboHistory?.replace;

        if (typeof turboMethod === 'function') {
            turboMethod.call(turboHistory, url);

            const turboState = this.isStateMap(window.history.state)
                ? window.history.state
                : {};

            window.history.replaceState(
                { ...currentState, ...turboState },
                '',
                url,
            );

            return;
        }

        const browserMethod = historyMode === 'push'
            ? window.history.pushState
            : window.history.replaceState;

        browserMethod.call(window.history, currentState, '', url);
    }

    restoreStateFromHistory() {
        const state = this.readUrlState() ?? this.defaultState;

        this.applyStateSnapshot(state);
        this.dispatchStateEvent('state:restore', state, 'popstate');

        if (typeof window.Turbo?.navigator?.history === 'undefined') {
            this.refresh(null, 'none');
        }
    }

    persistStateBeforeTurboCache() {
        this.commitUrlState('replace');
    }

    dispatchStateEvent(name, state, source, url = window.location.href) {
        this.dispatch(name, {
            detail: { state, source, url },
            prefix: 'zhortein-datatable',
        });
    }

    buildFragmentsUrl() {
        const url = new URL(this.fragmentsUrlValue, window.location.origin);

        url.searchParams.set('page', String(this.pageValue));
        url.searchParams.set('pageSize', String(this.pageSizeValue));

        if (this.searchValue !== '') {
            url.searchParams.set('search', this.searchValue);
        }

        if (this.sortFieldValue !== '') {
            url.searchParams.set('sortField', this.sortFieldValue);
            url.searchParams.set('sortDirection', this.sortDirectionValue);
        }

        url.searchParams.set('filterLayout', this.filterLayoutValue);

        if (this.hasBooleanDisplayModeValue) {
            url.searchParams.set('booleanDisplayMode', this.booleanDisplayModeValue);
        }

        if (this.paginationSizeValue !== 'default') {
            url.searchParams.set('paginationSize', this.paginationSizeValue);
        }

        if (this.tableSmallValue) {
            url.searchParams.set('tableSmall', 'true');
        }

        this.appendFilterParameters(url.searchParams);
        this.appendAdvancedFilterParameters(url.searchParams);
        this.appendColumnVisibilityParameters(url.searchParams);

        return url.toString();
    }

    appendFilterParameters(searchParams) {
        this.getFilterControls().forEach((control) => {
            if (!control.name || !control.name.startsWith('filters[')) {
                return;
            }

            if (control instanceof HTMLInputElement && (control.type === 'checkbox' || control.type === 'radio') && !control.checked) {
                return;
            }

            const value = control.value.trim();

            if (value === '') {
                return;
            }

            searchParams.set(control.name, value);
        });
    }

    appendColumnVisibilityParameters(searchParams) {
        this.getColumnVisibilityControls().forEach((control) => {
            if (control.getAttribute('data-zhortein--datatable-bundle--datatable-definition-hidden') === 'true') {
                return;
            }

            const columnName = control.getAttribute('data-zhortein--datatable-bundle--datatable-column-name');

            if (!columnName) {
                return;
            }

            if (control.checked) {
                searchParams.append('visibleColumns[]', columnName);
            } else {
                searchParams.append('hiddenColumns[]', columnName);
            }
        });
    }

    updateActiveFilterState() {
        const activeCount = this.getActiveFilterCount();

        if (this.hasActiveFiltersTarget) {
            this.activeFiltersTarget.hidden = activeCount === 0;
            this.activeFiltersTarget.dataset.activeFilterCount = String(activeCount);
        }

        if (this.hasClearFiltersButtonTarget) {
            this.clearFiltersButtonTarget.hidden = activeCount === 0;
            this.clearFiltersButtonTarget.disabled = activeCount === 0;
        }
    }

    getActiveFilterCount() {
        let activeCount = 0;

        this.getFilterControls().forEach((control) => {
            if (control instanceof HTMLInputElement && (control.type === 'checkbox' || control.type === 'radio')) {
                if (control.checked) {
                    activeCount += 1;
                }

                return;
            }

            if (control.value.trim() !== '') {
                activeCount += 1;
            }
        });

        return activeCount;
    }

    getFilterControls() {
        return Array.from(this.element.querySelectorAll('[data-zhortein--datatable-bundle--datatable-filter-control="true"]'))
            .filter((control) => control instanceof HTMLInputElement || control instanceof HTMLSelectElement);
    }

    getColumnVisibilityControls() {
        return Array.from(this.element.querySelectorAll('[data-zhortein--datatable-bundle--datatable-column-visibility-control="true"]'))
            .filter((control) => control instanceof HTMLInputElement && control.type === 'checkbox');
    }

    applyAdvancedFilterState(advancedFilters) {
        if (!this.hasSearchBuilderConditionsTarget) {
            return;
        }

        this.searchBuilderConditionsTarget.innerHTML = '';

        if (
            !this.isStateMap(advancedFilters)
            || !Array.isArray(advancedFilters.conditions)
            || advancedFilters.conditions.length === 0
        ) {
            const rootGroup = this.getRootSearchBuilderGroupElement();
            const logicSelect = rootGroup?.querySelector(':scope > .zhortein-datatable__search-builder-header select.zhortein-datatable__search-builder-logic');

            if (logicSelect instanceof HTMLSelectElement) {
                logicSelect.value = 'AND';
            }

            return;
        }

        const rootGroup = this.getRootSearchBuilderGroupElement();

        if (rootGroup === null) {
            return;
        }

        this.restoreSearchBuilderGroup(rootGroup, advancedFilters, 1, { remaining: 100 });
    }

    restoreSearchBuilderGroup(groupElement, groupState, depth, budget) {
        if (depth > 3 || budget.remaining < 1 || !Array.isArray(groupState.conditions)) {
            return;
        }

        const logicSelect = groupElement.querySelector(':scope > .zhortein-datatable__search-builder-header select.zhortein-datatable__search-builder-logic')
            || groupElement.querySelector(':scope > select.zhortein-datatable__search-builder-logic');
        const logic = typeof groupState.logic === 'string' ? groupState.logic.toUpperCase() : 'AND';

        if (logicSelect instanceof HTMLSelectElement && ['AND', 'OR'].includes(logic)) {
            logicSelect.value = logic;
        }

        const container = groupElement.querySelector(':scope > .zhortein-datatable__search-builder-conditions');

        if (!(container instanceof HTMLElement)) {
            return;
        }

        groupState.conditions.forEach((conditionState) => {
            if (budget.remaining < 1 || !this.isStateMap(conditionState)) {
                return;
            }

            if (Array.isArray(conditionState.conditions)) {
                if (depth >= 3 || !this.hasSearchBuilderGroupTemplateTarget) {
                    return;
                }

                const subgroup = this.searchBuilderGroupTemplateTarget.content.firstElementChild?.cloneNode(true);

                if (!(subgroup instanceof HTMLElement)) {
                    return;
                }

                budget.remaining -= 1;
                container.appendChild(subgroup);
                this.restoreSearchBuilderGroup(subgroup, conditionState, depth + 1, budget);

                return;
            }

            if (!this.hasSearchBuilderConditionTemplateTarget) {
                return;
            }

            const condition = this.searchBuilderConditionTemplateTarget.content.firstElementChild?.cloneNode(true);

            if (!(condition instanceof HTMLElement)) {
                return;
            }

            container.appendChild(condition);

            if (!this.restoreSearchBuilderCondition(condition, conditionState)) {
                condition.remove();

                return;
            }

            budget.remaining -= 1;
        });
    }

    restoreSearchBuilderCondition(condition, conditionState) {
        const fieldSelect = condition.querySelector('select[data-action*="onSearchBuilderFieldChange"]');
        const operatorSelect = condition.querySelector('select[data-action*="onSearchBuilderOperatorChange"]');

        if (
            !(fieldSelect instanceof HTMLSelectElement)
            || !(operatorSelect instanceof HTMLSelectElement)
            || typeof conditionState.field !== 'string'
            || typeof conditionState.operator !== 'string'
        ) {
            return false;
        }

        fieldSelect.value = conditionState.field;

        if (fieldSelect.value !== conditionState.field) {
            return false;
        }

        this.onSearchBuilderFieldChange({ target: fieldSelect }, false);
        operatorSelect.value = conditionState.operator;

        if (operatorSelect.value !== conditionState.operator) {
            return false;
        }

        const selectedOption = fieldSelect.options[fieldSelect.selectedIndex];
        this.updateSearchBuilderValueInput(
            condition,
            selectedOption.dataset.type,
            selectedOption.dataset.choices,
            false,
        );
        this.restoreSearchBuilderValue(condition, conditionState.value);

        return true;
    }

    restoreSearchBuilderValue(condition, value) {
        const valueContainer = condition.querySelector('.zhortein-datatable__search-builder-value-container');

        if (!(valueContainer instanceof HTMLElement)) {
            return;
        }

        const inputs = valueContainer.querySelectorAll('input, select');

        if (inputs.length === 1) {
            const input = inputs[0];

            if (input instanceof HTMLSelectElement && input.multiple) {
                const values = Array.isArray(value) ? value.map(String) : [String(value ?? '')];

                Array.from(input.options).forEach((option) => {
                    option.selected = values.includes(option.value);
                });

                return;
            }

            input.value = value === null || typeof value === 'undefined' ? '' : String(value);

            return;
        }

        if (inputs.length === 2) {
            const values = Array.isArray(value)
                ? value
                : [value?.from ?? '', value?.to ?? ''];

            inputs[0].value = String(values[0] ?? '');
            inputs[1].value = String(values[1] ?? '');
        }
    }

    addSearchBuilderCondition(event) {
        if (event) event.preventDefault();

        if (!this.hasSearchBuilderConditionTemplateTarget) {
            return;
        }

        const container = this.resolveSearchBuilderConditionsContainer(event);
        if (container === null) {
            return;
        }

        const template = this.searchBuilderConditionTemplateTarget.content.cloneNode(true);
        container.appendChild(template);
    }

    addSearchBuilderSubgroup(event) {
        if (event) event.preventDefault();

        if (!this.hasSearchBuilderGroupTemplateTarget) {
            return;
        }

        const container = this.resolveSearchBuilderConditionsContainer(event);
        if (container === null) {
            return;
        }

        const template = this.searchBuilderGroupTemplateTarget.content.cloneNode(true);
        container.appendChild(template);
    }

    removeSearchBuilderCondition(event) {
        if (event) event.preventDefault();

        const condition = event.currentTarget.closest('.zhortein-datatable__search-builder-condition');
        if (condition) {
            condition.remove();
            this.refresh();
        }
    }

    removeSearchBuilderSubgroup(event) {
        if (event) event.preventDefault();

        const group = event.currentTarget.closest('.zhortein-datatable__search-builder-group--nested');
        if (group) {
            group.remove();
            this.refresh();
        }
    }

    clearSearchBuilder(event) {
        if (event) event.preventDefault();

        if (this.hasSearchBuilderConditionsTarget) {
            this.searchBuilderConditionsTarget.innerHTML = '';
        }

        const rootGroup = this.getRootSearchBuilderGroupElement();
        if (rootGroup !== null) {
            const logicSelect = rootGroup.querySelector(':scope > .zhortein-datatable__search-builder-header select.zhortein-datatable__search-builder-logic')
                || rootGroup.querySelector('select.zhortein-datatable__search-builder-logic')
                || (this.hasSearchBuilderTarget ? this.searchBuilderTarget.querySelector('select[data-action*="updateSearchBuilderLogic"]') : null);
            if (logicSelect) {
                logicSelect.value = 'AND';
            }
        }

        this.refresh();
    }

    updateSearchBuilderLogic() {
        this.refresh();
    }

    resolveSearchBuilderConditionsContainer(event) {
        if (event && event.currentTarget instanceof Element) {
            const group = event.currentTarget.closest('.zhortein-datatable__search-builder-group');
            if (group) {
                const container = group.querySelector(':scope > .zhortein-datatable__search-builder-conditions');
                if (container) {
                    return container;
                }
            }
        }

        return this.hasSearchBuilderConditionsTarget ? this.searchBuilderConditionsTarget : null;
    }

    getRootSearchBuilderGroupElement() {
        if (!this.hasSearchBuilderTarget) {
            return null;
        }

        return this.searchBuilderTarget.querySelector('.zhortein-datatable__search-builder-group--root')
            || this.searchBuilderTarget;
    }

    onSearchBuilderFieldChange(event, shouldRefresh = true) {
        const select = event.target;
        const condition = select.closest('.zhortein-datatable__search-builder-condition');
        const operatorSelect = condition.querySelector('select[data-action*="onSearchBuilderOperatorChange"]');
        const valueContainer = condition.querySelector('.zhortein-datatable__search-builder-value-container');

        const selectedOption = select.options[select.selectedIndex];
        const type = selectedOption.dataset.type;

        const i18n = JSON.parse(this.searchBuilderTarget.getAttribute('data-zhortein--datatable-bundle--datatable-i18n-value'));

        if (!type) {
            operatorSelect.disabled = true;
            operatorSelect.innerHTML = `<option value="">${i18n.select_operator}</option>`;
            valueContainer.innerHTML = '<input type="text" class="form-control form-control-sm" disabled>';
            return;
        }

        operatorSelect.disabled = false;
        const typeOperators = JSON.parse(this.searchBuilderTarget.getAttribute('data-zhortein--datatable-bundle--datatable-operators-value'))[type] || [];
        const operatorLabels = JSON.parse(this.searchBuilderTarget.getAttribute('data-zhortein--datatable-bundle--datatable-operator-labels-value'));

        let allowedOperators = null;
        const rawAllowed = selectedOption.dataset.allowedOperators;
        if (typeof rawAllowed === 'string' && rawAllowed !== '') {
            try {
                const parsed = JSON.parse(rawAllowed);
                if (Array.isArray(parsed)) {
                    allowedOperators = parsed;
                }
            } catch (e) {
                allowedOperators = null;
            }
        }

        const operators = allowedOperators === null
            ? typeOperators
            : typeOperators.filter((op) => allowedOperators.includes(op));

        operatorSelect.innerHTML = `<option value="">${i18n.select_operator}</option>` +
            operators.map((op) => `<option value="${op}">${operatorLabels[op] || op}</option>`).join('');

        this.updateSearchBuilderValueInput(condition, type, selectedOption.dataset.choices, shouldRefresh);
    }

    onSearchBuilderOperatorChange() {
        this.refresh();
    }

    updateSearchBuilderValueInput(condition, type, choicesJson, shouldRefresh = true) {
        const valueContainer = condition.querySelector('.zhortein-datatable__search-builder-value-container');
        const operatorSelect = condition.querySelector('select[data-action*="onSearchBuilderOperatorChange"]');
        const operator = operatorSelect.value;
        const i18n = JSON.parse(this.searchBuilderTarget.getAttribute('data-zhortein--datatable-bundle--datatable-i18n-value'));

        if (operator === 'is_null' || operator === 'is_not_null') {
            valueContainer.innerHTML = '';
            if (shouldRefresh) {
                this.refresh();
            }

            return;
        }

        let html = '';
        if (type === 'choice' && choicesJson) {
            const choices = JSON.parse(choicesJson);
            const isMultiple = operator === 'in' || operator === 'not_in';
            html = `<select class="form-select form-select-sm" ${isMultiple ? 'multiple' : ''} data-action="change->zhortein--datatable-bundle--datatable#refresh">`;
            for (const [label, value] of Object.entries(choices)) {
                html += `<option value="${value}">${label}</option>`;
            }
            html += '</select>';
        } else if (type === 'boolean') {
            html = `<select class="form-select form-select-sm" data-action="change->zhortein--datatable-bundle--datatable#refresh">
                <option value="1">${i18n.boolean_yes}</option>
                <option value="0">${i18n.boolean_no}</option>
            </select>`;
        } else if (operator === 'between') {
            const inputType = (type === 'date' || type === 'date_range') ? 'date' : 'number';
            html = `<div class="d-flex gap-2">
                <input type="${inputType}" class="form-control form-control-sm" placeholder="${i18n.between_from}" data-action="input->zhortein--datatable-bundle--datatable#refresh">
                <input type="${inputType}" class="form-control form-control-sm" placeholder="${i18n.between_to}" data-action="input->zhortein--datatable-bundle--datatable#refresh">
            </div>`;
        } else {
            const inputType = (type === 'date' || type === 'date_range') ? 'date' : (type === 'number' ? 'number' : 'text');
            html = `<input type="${inputType}" class="form-control form-control-sm" data-action="input->zhortein--datatable-bundle--datatable#refresh">`;
        }

        valueContainer.innerHTML = html;
        if (shouldRefresh) {
            this.refresh();
        }
    }

    appendAdvancedFilterParameters(searchParams) {
        if (!this.hasSearchBuilderTarget) {
            return;
        }

        const rootGroup = this.getRootSearchBuilderGroupElement();
        if (rootGroup === null) {
            return;
        }

        const serialized = this.serializeSearchBuilderGroup(rootGroup);
        if (serialized === null || serialized.conditions.length === 0) {
            return;
        }

        this.appendSearchBuilderEntries(searchParams, 'advancedFilters', serialized);
    }

    serializeSearchBuilderGroup(groupElement) {
        const logicSelect = groupElement.querySelector(':scope > .zhortein-datatable__search-builder-header select.zhortein-datatable__search-builder-logic')
            || groupElement.querySelector(':scope > select.zhortein-datatable__search-builder-logic')
            || groupElement.querySelector(':scope > select[data-action*="updateSearchBuilderLogic"]')
            || (groupElement.classList.contains('zhortein-datatable__search-builder-group--root') && this.hasSearchBuilderTarget
                ? this.searchBuilderTarget.querySelector('select[data-action*="updateSearchBuilderLogic"]')
                : null);

        const logicValue = (logicSelect && typeof logicSelect.value === 'string' && logicSelect.value !== '')
            ? logicSelect.value
            : 'AND';

        const conditionsContainer = groupElement.querySelector(':scope > .zhortein-datatable__search-builder-conditions')
            || (groupElement.classList.contains('zhortein-datatable__search-builder-group--root') && this.hasSearchBuilderConditionsTarget
                ? this.searchBuilderConditionsTarget
                : null);

        const conditions = [];

        if (conditionsContainer) {
            Array.from(conditionsContainer.children).forEach((child) => {
                if (child.classList.contains('zhortein-datatable__search-builder-condition')) {
                    const serialized = this.serializeSearchBuilderCondition(child);
                    if (serialized !== null) {
                        conditions.push(serialized);
                    }
                } else if (child.classList.contains('zhortein-datatable__search-builder-group')) {
                    const serialized = this.serializeSearchBuilderGroup(child);
                    if (serialized !== null && serialized.conditions.length > 0) {
                        conditions.push(serialized);
                    }
                }
            });
        }

        return { logic: String(logicValue).toLowerCase(), conditions };
    }

    serializeSearchBuilderCondition(conditionElement) {
        const fieldSelect = conditionElement.querySelector('select[data-action*="onSearchBuilderFieldChange"]');
        const operatorSelect = conditionElement.querySelector('select[data-action*="onSearchBuilderOperatorChange"]');

        if (!fieldSelect || !operatorSelect) {
            return null;
        }

        const field = fieldSelect.value;
        const operator = operatorSelect.value;

        if (!field || !operator) {
            return null;
        }

        const valueContainer = conditionElement.querySelector('.zhortein-datatable__search-builder-value-container');
        const inputs = valueContainer ? valueContainer.querySelectorAll('input, select') : [];

        let value = null;

        if (operator === 'is_null' || operator === 'is_not_null') {
            value = null;
        } else if (inputs.length === 1) {
            const input = inputs[0];
            if (input instanceof HTMLSelectElement && input.multiple) {
                value = Array.from(input.selectedOptions).map((opt) => opt.value);
            } else {
                value = input.value;
            }
        } else if (inputs.length === 2) {
            value = { from: inputs[0].value, to: inputs[1].value };
        }

        const result = { field, operator };

        if (value !== null) {
            result.value = value;
        }

        return result;
    }

    appendSearchBuilderEntries(searchParams, prefix, payload) {
        if (payload === null || typeof payload !== 'object') {
            searchParams.set(prefix, String(payload ?? ''));
            return;
        }

        if (Array.isArray(payload)) {
            payload.forEach((item, index) => {
                this.appendSearchBuilderEntries(searchParams, `${prefix}[${index}]`, item);
            });
            return;
        }

        Object.entries(payload).forEach(([key, val]) => {
            this.appendSearchBuilderEntries(searchParams, `${prefix}[${key}]`, val);
        });
    }

    resolveConfirmationMessage(target) {
        if (!(target instanceof HTMLElement)) {
            return null;
        }

        const message = target.getAttribute('data-zhortein--datatable-bundle--datatable-confirmation-message');

        if (typeof message !== 'string' || message.trim() === '') {
            return null;
        }

        return message;
    }

    applyFragments(payload) {
        const activeElement = document.activeElement;
        const isInteractingWithHeader =
            (this.headerTarget.contains(activeElement) && (activeElement instanceof HTMLInputElement || activeElement instanceof HTMLSelectElement)) ||
            this.headerTarget.querySelector('[aria-expanded="true"]') !== null;

        if (!isInteractingWithHeader && this.hasHeaderTarget && typeof payload.header === 'string') {
            this.headerTarget.outerHTML = payload.header;
        }

        if (this.hasBodyTarget && typeof payload.body === 'string') {
            this.bodyTarget.innerHTML = payload.body;
            this.selectedIds.clear();
            this.updateSelectionUI();
        }

        if (this.hasPaginationTarget && typeof payload.pagination === 'string') {
            this.paginationTarget.innerHTML = payload.pagination;
        }

        if (this.hasSummaryTarget && typeof payload.summary === 'string') {
            this.summaryTarget.textContent = payload.summary;
        }
    }

    applyState(payload) {
        if (typeof payload.page === 'number' && payload.page >= 1) {
            this.pageValue = payload.page;
        }

        if (typeof payload.pageSize === 'number' && payload.pageSize >= 1) {
            this.pageSizeValue = payload.pageSize;

            if (this.hasPageSizeInputTarget) {
                this.pageSizeInputTarget.value = String(payload.pageSize);
            }
        }
    }

    setLoading(isLoading) {
        this.element.toggleAttribute('aria-busy', isLoading);
        this.element.classList.toggle('is-loading', isLoading);

        if (this.hasLoadingTarget) {
            this.loadingTarget.classList.toggle('d-none', !isLoading);
            this.loadingTarget.classList.toggle('d-flex', isLoading);
            this.loadingTarget.setAttribute('aria-hidden', String(!isLoading));
        }
    }

    showError(message) {
        if (this.hasErrorTarget) {
            this.errorTarget.textContent = message;
            this.errorTarget.classList.remove('d-none');
            this.errorTarget.classList.add('d-flex');
            this.errorTarget.removeAttribute('aria-hidden');

            return;
        }

        // Keep a safe fallback for early integration phases.
        console.error(message);
    }

    clearError() {
        if (!this.hasErrorTarget) {
            return;
        }

        this.errorTarget.textContent = '';
        this.errorTarget.classList.add('d-none');
        this.errorTarget.classList.remove('d-flex');
        this.errorTarget.setAttribute('aria-hidden', 'true');
    }

    showActionFeedback(message) {
        if (!this.hasFeedbackTarget) {
            return;
        }

        this.feedbackTarget.textContent = message;
        this.feedbackTarget.classList.remove('d-none');
        this.feedbackTarget.classList.add('d-flex');
        this.feedbackTarget.removeAttribute('aria-hidden');
    }

    clearActionFeedback() {
        if (!this.hasFeedbackTarget) {
            return;
        }

        this.feedbackTarget.textContent = '';
        this.feedbackTarget.classList.add('d-none');
        this.feedbackTarget.classList.remove('d-flex');
        this.feedbackTarget.setAttribute('aria-hidden', 'true');
    }

    abortPendingRequest() {
        if (this.abortController !== null) {
            this.abortController.abort();
            this.abortController = null;
        }
    }

    export(event) {
        event.preventDefault();

        const target = event.currentTarget;

        if (!(target instanceof HTMLAnchorElement)) {
            return;
        }

        const mode = event.params.exportMode || 'current';
        const exportUrl = event.params.exportUrl
            || (this.hasExportUrlValue && this.exportUrlValue !== ''
                ? this.exportUrlValue
                : target.href);

        const url = new URL(exportUrl, window.location.origin);

        url.searchParams.set('mode', mode);

        this.appendExportStateParameters(url.searchParams, mode);

        window.location.assign(url.toString());
    }

    appendExportStateParameters(searchParams, mode) {
        if (mode === 'current') {
            searchParams.set('page', String(this.pageValue));
            searchParams.set('pageSize', String(this.pageSizeValue));
        }

        if (this.searchValue !== '') {
            searchParams.set('search', this.searchValue);
        }

        if (this.sortFieldValue !== '') {
            searchParams.set('sortField', this.sortFieldValue);
            searchParams.set('sortDirection', this.sortDirectionValue);
        }

        this.appendFilterParameters(searchParams);
        this.appendAdvancedFilterParameters(searchParams);
        this.appendColumnVisibilityParameters(searchParams);
    }

    allowDropdownOverflow(event) {
        const wrapper = this.findDropdownOverflowWrapper(event.target);

        if (wrapper === null) {
            return;
        }

        const wasAlreadyVisible = wrapper.classList.contains('overflow-visible');

        wrapper.dataset.zhorteinDatatableDropdownOverflowAdded = wasAlreadyVisible ? 'false' : 'true';

        if (!wasAlreadyVisible) {
            wrapper.classList.add('overflow-visible');
        }
    }

    restoreDropdownOverflow(event) {
        const wrapper = this.findDropdownOverflowWrapper(event.target);

        if (wrapper === null) {
            return;
        }

        if (wrapper.dataset.zhorteinDatatableDropdownOverflowAdded === 'true') {
            wrapper.classList.remove('overflow-visible');
        }

        delete wrapper.dataset.zhorteinDatatableDropdownOverflowAdded;
    }

    findDropdownOverflowWrapper(target) {
        if (!(target instanceof Element)) {
            return null;
        }

        const wrapper = target.closest('.table-responsive');

        return wrapper instanceof HTMLElement ? wrapper : null;
    }

    stopProp(event) {
        event.stopPropagation();
    }
}
