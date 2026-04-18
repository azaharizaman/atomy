# Users And Roles Workflows

This file covers the operational business flow for the Users and Roles domain. It does not describe user state semantics; that belongs in [lifecycle.md](./lifecycle.md).

## Workflow Access

- All routes are tenant-scoped and require the auth context.
- The surface is for administrators or equivalent tenant staff; the controller itself does not try to infer frontend permissions.

## Workflow 001 - List And Filter Users

### Trigger

An administrator opens the users table or applies a status filter.

### Steps

1. The API reads the tenant ID from the auth context.
2. The controller builds a tenant-scoped search criteria array.
3. The identity query layer returns the matching user records.
4. The controller resolves the role list for each user and serializes the table rows.

### Outputs

- Paginated user list
- Primary role label per user
- Last-login metadata when available

### Failure Handling

- Missing tenant context is a hard failure.
- Empty result sets return an empty page rather than an error.

## Workflow 002 - Invite A User

### Trigger

An administrator submits an invitation form.

### Steps

1. The API validates the email and display name.
2. The controller lowercases the email and checks whether a tenant user with that email already exists.
3. The password hasher generates a random placeholder credential for the pending account.
4. The controller stores the user with `status = pending_activation` and `role = user`.
5. The created user is serialized and returned to the client.

### Outputs

- New user record
- Pending activation status
- Tenant-scoped user payload

### Failure Handling

- Duplicate email returns `409`.
- Validation errors return field-level messages.

## Workflow 003 - Suspend And Reactivate A User

### Trigger

An administrator changes the lifecycle state of a tenant user.

### Steps

1. The controller confirms the target user belongs to the current tenant.
2. Suspend updates the user status to `suspended`.
3. Reactivate updates the user status back to `active`.
4. The updated user record is serialized and returned.

### Outputs

- Updated user payload
- New account status

### Failure Handling

- Cross-tenant lookups return `404`.
- The controller does not expose a separate transfer or deletion flow here.

## Workflow 004 - Read Roles

### Trigger

An administrator opens the role picker or user detail panel.

### Steps

1. The API reads the tenant ID from the auth context.
2. The role query layer returns all roles visible to that tenant.
3. The controller serializes the role metadata for the client.

### Outputs

- Tenant-aware role list
- System-role flag per item

### Failure Handling

- Missing tenant context is a hard failure.

## Workflow 005 - Deferred Account Governance

### Trigger

The client asks for user update, delegation rules, or authority limits.

### Steps

1. The controller receives the request.
2. The controller returns an explicit `501` placeholder response that the surface is not implemented in alpha.

### Outputs

- Placeholder response with tenant context

### Failure Handling

- These routes are intentionally unavailable and should not be treated as working account-governance features.

## Domains Involved

- Users and Roles
- Auth
- Tenant and Company
