# Bulk Actions and Row Selection

Bulk actions allow users to perform operations on multiple rows at once. This involves a selector column (checkboxes), a selection state management in the frontend, and a backend route to handle the submitted IDs.

## Declaring Bulk Actions

Bulk actions are declared in your datatable class using the `addBulkAction` method on the `DatatableDefinition`.

```php
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\ActionIconPosition;

public function buildDatatable(DatatableDefinition $definition): void
{
    $definition->addBulkAction(
        name: 'delete_selected',
        route: 'app_user_bulk_delete',
        label: 'Delete Selected',
        icon: 'bi bi-trash',
        className: 'btn btn-outline-danger',
        confirmationMessage: 'Are you sure you want to delete the selected users?',
        selectedRowsParameterName: 'ids', // Default is 'ids'
    );
}
```

## Selector Column

When at least one bulk action is defined, a **selector column** is automatically prepended to the datatable. 
- The header contains a "Select All" checkbox that toggles all rows on the current page.
- Each row contains a checkbox to select that specific row.

## How it Works

1. **Selection**: Users select rows using checkboxes. The `datatable` Stimulus controller tracks selected IDs in a `Set`.
2. **Bulk Toolbar**: As soon as one or more rows are selected, a bulk action toolbar appears above the table, showing the count of selected rows and available bulk actions.
3. **Submission**: When a bulk action is triggered, the controller injects the selected IDs as hidden inputs into a form and submits it via POST.

### Selected Row Payload

The backend route receives the selected IDs in the request. By default, they are sent as an array named `ids`.

```php
// In your controller
#[Route('/users/bulk-delete', name: 'app_user_bulk_delete', methods: ['POST'])]
public function bulkDelete(Request $request): Response
{
    $ids = $request->request->all('ids');
    
    // Perform bulk operation...
    
    return $this->redirectToRoute('app_user_index');
}
```

You can customize the parameter name using the `selectedRowsParameterName` option:

```php
$definition->addBulkAction(
    name: 'export',
    route: 'app_user_bulk_export',
    selectedRowsParameterName: 'user_ids',
);
```

## Security and CSRF

### CSRF Protection
Bulk actions are always submitted via `POST` (or the configured `httpMethod`). If Symfony's CSRF protection is enabled, the bundle automatically includes a CSRF token in the form. The token ID is `zhortein_datatable_action_{action_name}`.

### Backend Authorization
**CRITICAL**: Visibility checks in the datatable only control whether the action button is rendered. Your backend route **MUST** independently enforce authorization and validate that the user has permission to perform the action on the specific IDs provided.

```php
public function bulkDelete(Request $request): Response
{
    $ids = $request->request->all('ids');
    
    foreach ($ids as $id) {
        $user = $this->userRepository->find($id);
        if ($user && !$this->isGranted('DELETE', $user)) {
            throw $this->createAccessDeniedException();
        }
    }
    
    // ...
}
```

## Confirmation

You can add a confirmation message to any bulk action. It will be displayed using the configured confirmation mechanism (window.confirm or Bootstrap modal) before the action is submitted.

```php
$definition->addBulkAction(
    name: 'activate',
    route: 'app_user_bulk_activate',
    confirmationMessage: 'Activate all selected users?',
);
```

## Current Limitations

- **No "Select All Filtered"**: Currently, you can only select rows that are visible on the current page. There is no "Select all 5000 matching rows" feature yet.
- **No Persistence across pages**: Selection is lost when navigating to another page, changing page size, or refreshing the table.
- **No Async Bulk Jobs**: The bundle submits the IDs to a standard controller route. For long-running tasks, you should implement your own background job processing (e.g., using Symfony Messenger).

## Examples

### Complex Bulk Action

```php
$definition->addBulkAction(
    name: 'change_status',
    route: 'app_user_bulk_status',
    label: 'Mark as Active',
    icon: 'bi bi-check-circle',
    iconPosition: ActionIconPosition::After,
    className: 'btn btn-sm btn-success',
    attributes: [
        'data-custom' => 'value',
    ],
);
```
