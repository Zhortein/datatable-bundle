import { Controller } from '@hotwired/stimulus';

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
    ];

    static values = {
        name: String,
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

        this.updateActiveFilterState();

        if (this.autoLoadValue) {
            this.refresh();
        }
    }

    disconnect() {
        this.abortPendingRequest();

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

    refresh(event = null) {
        if (event !== null) {
            event.preventDefault();
        }

        if (!this.hasFragmentsUrlValue || this.fragmentsUrlValue === '') {
            this.showError('The datatable fragments URL is missing.');

            return;
        }

        this.abortPendingRequest();
        this.abortController = new AbortController();
        this.setLoading(true);
        this.clearError();

        fetch(this.buildFragmentsUrl(), {
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
            })
            .catch((error) => {
                if (error.name === 'AbortError') {
                    return;
                }

                this.showError(error.message);
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

        const message = this.resolveConfirmationMessage(event.currentTarget);

        if (message !== null) {
            this.confirmAction(event);

            return;
        }

        this.injectSelectedIds(event.currentTarget);
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

    addSearchBuilderCondition(event) {
        if (event) event.preventDefault();

        if (!this.hasSearchBuilderConditionsTarget || !this.hasSearchBuilderConditionTemplateTarget) {
            return;
        }

        const template = this.searchBuilderConditionTemplateTarget.content.cloneNode(true);
        this.searchBuilderConditionsTarget.appendChild(template);
    }

    removeSearchBuilderCondition(event) {
        if (event) event.preventDefault();

        const condition = event.target.closest('.zhortein-datatable__search-builder-condition');
        if (condition) {
            condition.remove();
            this.refresh();
        }
    }

    clearSearchBuilder(event) {
        if (event) event.preventDefault();

        if (this.hasSearchBuilderConditionsTarget) {
            this.searchBuilderConditionsTarget.innerHTML = '';
            this.refresh();
        }
    }

    updateSearchBuilderLogic() {
        this.refresh();
    }

    onSearchBuilderFieldChange(event) {
        const select = event.target;
        const condition = select.closest('.zhortein-datatable__search-builder-condition');
        const operatorSelect = condition.querySelector('select[data-action*="onSearchBuilderOperatorChange"]');
        const valueContainer = condition.querySelector('.zhortein-datatable__search-builder-value-container');

        const selectedOption = select.options[select.selectedIndex];
        const type = selectedOption.dataset.type;

        if (!type) {
            operatorSelect.disabled = true;
            operatorSelect.innerHTML = `<option value="">${this.searchBuilderTarget.dataset.zhorteinDatatableBundleDatatableSelectOperatorPlaceholder || 'Select operator'}</option>`;
            valueContainer.innerHTML = '<input type="text" class="form-control form-control-sm" disabled>';
            return;
        }

        operatorSelect.disabled = false;
        const operators = JSON.parse(this.searchBuilderTarget.dataset.zhorteinDatatableBundleDatatableOperatorsValue)[type] || [];
        const operatorLabels = JSON.parse(this.searchBuilderTarget.dataset.zhorteinDatatableBundleDatatableOperatorLabelsValue);

        operatorSelect.innerHTML = `<option value="">${this.searchBuilderTarget.dataset.zhorteinDatatableBundleDatatableSelectOperatorPlaceholder || 'Select operator'}</option>` +
            operators.map((op) => `<option value="${op}">${operatorLabels[op] || op}</option>`).join('');

        this.updateSearchBuilderValueInput(condition, type, selectedOption.dataset.choices);
    }

    onSearchBuilderOperatorChange() {
        this.refresh();
    }

    updateSearchBuilderValueInput(condition, type, choicesJson) {
        const valueContainer = condition.querySelector('.zhortein-datatable__search-builder-value-container');
        const operatorSelect = condition.querySelector('select[data-action*="onSearchBuilderOperatorChange"]');
        const operator = operatorSelect.value;

        if (operator === 'is_null' || operator === 'is_not_null') {
            valueContainer.innerHTML = '';
            this.refresh();

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
                <option value="1">Yes</option>
                <option value="0">No</option>
            </select>`;
        } else if (operator === 'between') {
            const inputType = (type === 'date' || type === 'date_range') ? 'date' : 'number';
            html = `<div class="d-flex gap-2">
                <input type="${inputType}" class="form-control form-control-sm" placeholder="From" data-action="input->zhortein--datatable-bundle--datatable#refresh">
                <input type="${inputType}" class="form-control form-control-sm" placeholder="To" data-action="input->zhortein--datatable-bundle--datatable#refresh">
            </div>`;
        } else {
            const inputType = (type === 'date' || type === 'date_range') ? 'date' : (type === 'number' ? 'number' : 'text');
            html = `<input type="${inputType}" class="form-control form-control-sm" data-action="input->zhortein--datatable-bundle--datatable#refresh">`;
        }

        valueContainer.innerHTML = html;
        this.refresh();
    }

    appendAdvancedFilterParameters(searchParams) {
        if (!this.hasSearchBuilderTarget) {
            return;
        }

        const conditions = Array.from(this.searchBuilderConditionsTarget.querySelectorAll('.zhortein-datatable__search-builder-condition'));
        if (conditions.length === 0) {
            return;
        }

        const logic = this.searchBuilderTarget.querySelector('select[data-action*="updateSearchBuilderLogic"]').value;

        searchParams.set('advancedFilters[logic]', logic);

        conditions.forEach((condition, index) => {
            const fieldSelect = condition.querySelector('select[data-action*="onSearchBuilderFieldChange"]');
            const field = fieldSelect.value;
            const operator = condition.querySelector('select[data-action*="onSearchBuilderOperatorChange"]').value;

            if (!field || !operator) {
                return;
            }

            searchParams.set(`advancedFilters[children][${index}][field]`, field);
            searchParams.set(`advancedFilters[children][${index}][operator]`, operator);

            const valueContainer = condition.querySelector('.zhortein-datatable__search-builder-value-container');
            const inputs = valueContainer.querySelectorAll('input, select');

            if (inputs.length === 1) {
                const input = inputs[0];
                if (input instanceof HTMLSelectElement && input.multiple) {
                    Array.from(input.selectedOptions).forEach((opt, optIndex) => {
                        searchParams.append(`advancedFilters[children][${index}][value][${optIndex}]`, opt.value);
                    });
                } else {
                    searchParams.set(`advancedFilters[children][${index}][value]`, input.value);
                }
            } else if (inputs.length === 2) {
                // Between
                searchParams.set(`advancedFilters[children][${index}][value][from]`, inputs[0].value);
                searchParams.set(`advancedFilters[children][${index}][value][to]`, inputs[1].value);
            }
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
