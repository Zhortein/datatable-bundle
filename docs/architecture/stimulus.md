# Stimulus Architecture

The frontend interaction model is powered by a vanilla, theme-neutral Stimulus
controller. It does not depend on jQuery, DataTables.net or a UI framework
module.

## Controller Responsibilities

The `datatable_controller.js` is responsible for:

- **Ajax Refresh**: Fetching server-rendered HTML fragments and updating the DOM targets (`body`, `pagination`, `summary`).
- **State Management**: Managing loading/error displays and versioned per-instance URL state.
- **Interactions**:
    - **Search**: Debounced (300ms) global search input.
    - **Filters**: Serializing and applying user-facing filters.
    - **Pagination**: Handling "Go to page" actions.
    - **Sorting**: Handling single and Shift-modified multi-column sorting.
    - **Page Size**: Handling changes to items-per-page.
    - **Column Visibility**: Toggling and serializing visibility state.
- **Actions**:
    - **Confirmations**: Handling `window.confirm` or a native theme-rendered `<dialog>` before proceeding with an action.
    - **Ajax execution**: Executing explicitly enabled row/global/bulk actions, validating the response contract and applying the declared success strategy.
    - **Lifecycle events**: Dispatching cancellable before, success, error and complete events without requiring a notification library.
- **Exports**: Building the export URL based on the current table state (filters, search, sorting).
- **History**: Restoring namespaced state on connect and `popstate`, while remaining coherent with Turbo page caching.
- **Named views**: Loading and mutating opt-in named views through the versioned JSON contract without owning persistence or authorization.

## Stimulus Targets and Values

The controller interacts with the server-rendered shell using targets:

- `body`: The table body fragment.
- `pagination`: The pagination control fragment.
- `summary`: The data summary text.
- `pageSizeInput`: The items-per-page selector.
- `searchInput`: The global search field.
- `filterControl`: Any user-facing filter input.

And values for synchronization:

- `nameValue`: The unique datatable name.
- `instanceValue`: The unique occurrence on the current page.
- `stateParameterValue`: The server-generated namespaced page URL parameter.
- `fragmentsUrlValue`: The base Ajax fragments URL.
- `pageValue`: Current page number.
- `pageSizeValue`: Current items per page.
- `sortFieldValue`: Current sort column.
- `sortDirectionValue`: Current sort direction.
- `sortsValue`: Ordered sort criteria; the first criterion mirrors the legacy values.
- `savedViewsUrlValue`: Optional named-view endpoint with its server-generated scope.
- `preferencesUrlValue`: Optional scoped save/reset preference endpoint.
- presentation class values: Theme-owned hidden/visible, status, disabled and
  dropdown-overflow classes used when the controller changes state.

## Interaction Model

Every interaction (paging, sorting, filtering) triggers a `refresh()` call. This method:

1. Resets the page to 1 (if the interaction is not pagination itself).
2. Toggles the loading state.
3. Builds the request URL using current values and serialized filter/column states.
4. Performs a `fetch()`.
5. Updates the targets with the server's JSON response.
6. Commits the successful state to browser history.

Ajax business actions use a separate versioned response contract. They reuse
the current controller state for post-success fragment refreshes, but they do
not change or serialize that state. See [Actions and Security](../actions.md).

## Integration

Designed for **AssetMapper** and **Symfony UX Stimulus**. The bundle ships a vanilla JS controller, avoiding the need for a complex Node build pipeline in the host application.

Theme templates provide class names for dynamic Search Builder controls and
hierarchy loading/error states. The controller creates safe DOM nodes and
applies those declared classes; it never generates framework-specific markup.

See [URL state and browser history](../url-state.md) for precedence, events,
payload versioning and privacy boundaries.

See [named saved views](../saved-views.md) for the opt-in endpoint, ownership,
authorization and optimistic-concurrency contracts.
