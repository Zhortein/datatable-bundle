# Stimulus Architecture

The frontend interaction model is powered by a vanilla Stimulus controller. It does not depend on jQuery or DataTables.net.

## Controller Responsibilities

The `datatable_controller.js` is responsible for:

- **Ajax Refresh**: Fetching server-rendered HTML fragments and updating the DOM targets (`body`, `pagination`, `summary`).
- **State Management**: Managing loading states (`aria-busy`, spinners) and error displays.
- **Interactions**:
    - **Search**: Debounced (300ms) global search input.
    - **Filters**: Serializing and applying user-facing filters.
    - **Pagination**: Handling "Go to page" actions.
    - **Sorting**: Handling header-click sorting.
    - **Page Size**: Handling changes to items-per-page.
    - **Column Visibility**: Toggling and serializing visibility state.
- **Actions**:
    - **Confirmations**: Handling `window.confirm` or Bootstrap modal confirmations before proceeding with an action.
- **Exports**: Building the export URL based on the current table state (filters, search, sorting).

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
- `urlValue`: The base Ajax fragments URL.
- `pageValue`: Current page number.
- `pageSizeValue`: Current items per page.
- `sortFieldValue`: Current sort column.
- `sortDirectionValue`: Current sort direction.

## Interaction Model

Every interaction (paging, sorting, filtering) triggers a `refresh()` call. This method:

1. Resets the page to 1 (if the interaction is not pagination itself).
2. Toggles the loading state.
3. Builds the request URL using current values and serialized filter/column states.
4. Performs a `fetch()`.
5. Updates the targets with the server's JSON response.

## Integration

Designed for **AssetMapper** and **Symfony UX Stimulus**. The bundle ships a vanilla JS controller, avoiding the need for a complex Node build pipeline in the host application.
