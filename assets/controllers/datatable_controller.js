import { Controller } from '@hotwired/stimulus';

/**
 * Controls a Zhortein datatable.
 *
 * This controller intentionally stays small.
 * It does not render cells manually and only updates server-rendered HTML fragments.
 */
export default class extends Controller {
    static targets = [
        'body',
        'pagination',
        'summary',
        'searchInput',
        'pageSizeInput',
        'error',
        'loading',
        'globalActions',
    ];

    static values = {
        name: String,
        fragmentsUrl: String,
        page: { type: Number, default: 1 },
        pageSize: { type: Number, default: 25 },
        search: { type: String, default: '' },
        sortField: { type: String, default: '' },
        sortDirection: { type: String, default: 'asc' },
    };

    connect() {
        this.abortController = null;
        this.searchDebounceTimeout = null;
    }

    disconnect() {
        this.abortPendingRequest();

        if (this.searchDebounceTimeout !== null) {
            window.clearTimeout(this.searchDebounceTimeout);
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

        return url.toString();
    }

    applyFragments(payload) {
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
            this.loadingTarget.hidden = !isLoading;
            this.loadingTarget.setAttribute('aria-hidden', String(!isLoading));
        }
    }

    showError(message) {
        if (this.hasErrorTarget) {
            this.errorTarget.textContent = message;
            this.errorTarget.hidden = false;
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
        this.errorTarget.hidden = true;
        this.errorTarget.setAttribute('aria-hidden', 'true');
    }

    abortPendingRequest() {
        if (this.abortController !== null) {
            this.abortController.abort();
            this.abortController = null;
        }
    }
}
