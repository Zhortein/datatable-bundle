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
    };

    connect() {
        this.abortController = null;
        this.searchDebounceTimeout = null;
        this.filterDebounceTimeout = null;
        this.columnVisibilityDebounceTimeout = null;
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
            });
    }

    confirmAction(event) {
        const message = this.resolveConfirmationMessage(event.currentTarget);

        if (message === null) {
            return;
        }

        if (!window.confirm(message)) {
            event.preventDefault();
            event.stopPropagation();
        }
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

        this.appendFilterParameters(url.searchParams);
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
        if (this.hasHeaderTarget && typeof payload.header === 'string') {
            this.headerTarget.outerHTML = payload.header;
        }

        if (this.hasBodyTarget && typeof payload.body === 'string') {
            this.bodyTarget.innerHTML = payload.body;
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

        const mode = event.params.mode || 'current';
        const exportUrl = this.hasExportUrlValue && this.exportUrlValue !== ''
            ? this.exportUrlValue
            : target.href;

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
}
