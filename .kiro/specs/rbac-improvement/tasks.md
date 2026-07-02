# Implementation Plan: RBAC Improvement

## Overview

This plan implements a comprehensive RBAC improvement for the Handayani school management system. The implementation progresses from backend infrastructure (seeding, gate bypass, middleware) through authentication migration (Sanctum), to frontend integration (Role Management page, permission-aware UI, auth integration), and finally user management (backend API + Filament page). Each step builds incrementally on the previous, ensuring no orphaned code.

## Tasks

- [x] 1. Backend RBAC Infrastructure Setup
  - [x] 1.1 Create the RoleAndPermissionSeeder
    - Create `backend/database/seeders/RoleAndPermissionSeeder.php`
    - Iterate over all `Permission` enum cases and create permission records using `firstOrCreate`
    - Create superadmin, admin, and user roles from `DefaultRoles` enum using `firstOrCreate`
    - Assign all permissions to superadmin role via `syncPermissions`
    - Assign `PermissionBinding::ADMIN_PERMISSIONS` to admin role via `syncPermissions`
    - Clear spatie permission cache before and after seeding
    - Register the seeder in `DatabaseSeeder.php`
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6_

  - [ ]* 1.2 Write property test for Seeder Idempotency
    - **Property 1: Seeder Idempotency**
    - Run seeder N times (N randomly chosen 1-5), verify DB state matches single-run state
    - Verify permission count equals Permission enum case count
    - Verify role count equals DefaultRoles enum case count
    - Verify role-permission assignments are consistent
    - **Validates: Requirements 1.5**

  - [x] 1.3 Register Gate::before superadmin bypass in AppServiceProvider
    - Add `Gate::before` callback in `backend/app/Providers/AppServiceProvider.php` boot method
    - Return `true` if user has superadmin role (using `DefaultRoles::SUPERADMIN->value`)
    - Return `null` if user is null or does not have superadmin role
    - _Requirements: 2.1, 2.2, 2.3, 2.4_

  - [ ]* 1.4 Write property test for Superadmin Gate Bypass
    - **Property 2: Superadmin Gate Bypass**
    - Generate random ability strings, verify gate returns true for superadmin users
    - Verify gate returns null for non-superadmin users
    - Verify gate returns null for null user
    - **Validates: Requirements 2.1, 2.2**

  - [x] 1.5 Replace custom middleware with spatie middleware
    - Remove `ApiRoleMiddleware` class from `backend/app/Http/Middleware/`
    - Register spatie `RoleMiddleware` as alias "role" in `backend/bootstrap/app.php`
    - Register spatie `PermissionMiddleware` as alias "permission" in `backend/bootstrap/app.php`
    - Register spatie `RoleOrPermissionMiddleware` as alias "role_or_permission" in `backend/bootstrap/app.php`
    - _Requirements: 3.1, 3.2, 3.3_

  - [ ]* 1.6 Write unit tests for middleware registration
    - Verify "role", "permission", and "role_or_permission" aliases resolve to spatie middleware classes
    - Verify 403 response when user lacks required permission
    - Verify 403 response when user lacks required role
    - _Requirements: 3.4, 3.5, 3.6_

- [x] 2. Checkpoint - Ensure backend infrastructure tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 3. Sanctum Authentication Migration
  - [x] 3.1 Configure Sanctum and update User model
    - Add `HasApiTokens` trait to `backend/app/Models/User.php`
    - Remove `token` from `$fillable` array in User model
    - Set `sanctum.expiration` config value to 480 (minutes) in `config/sanctum.php`
    - Ensure `personal_access_tokens` migration exists (Sanctum default)
    - _Requirements: 5.1, 5.6_

  - [x] 3.2 Create migration to remove token column from users table
    - Create migration file to drop the `token` column from the `users` table
    - _Requirements: 5.6_

  - [x] 3.3 Refactor AuthController for Sanctum token issuance
    - Modify `login()` to gather user permissions via `$user->getAllPermissions()->pluck('name')`
    - Issue Sanctum token with abilities matching user permissions and configured expiration
    - Return token, expiration timestamp, permissions array, and roles in login response
    - Check if user already has an active token and reject with 401 if so
    - Modify `logout()` to revoke current Sanctum token via `currentAccessToken()->delete()`
    - _Requirements: 5.1, 5.2, 5.3, 5.7_

  - [x] 3.4 Replace ApiAuthMiddleware with Sanctum auth middleware
    - Remove `ApiAuthMiddleware` class from `backend/app/Http/Middleware/`
    - Replace all `ApiAuthMiddleware` references in routes with `auth:sanctum`
    - Ensure 401 response for expired/invalid tokens
    - _Requirements: 5.4, 5.5_

  - [ ]* 3.5 Write property test for Token Abilities Match User Permissions
    - **Property 5: Token Abilities Match User Permissions**
    - Create users with random role/permission combinations
    - Issue token and verify abilities array matches user's combined permissions
    - **Validates: Requirements 5.7**

  - [ ]* 3.6 Write unit tests for AuthController Sanctum integration
    - Test successful login returns token, expires_at, permissions, and roles
    - Test invalid credentials return 401
    - Test already-logged-in user returns 401
    - Test logout revokes token (subsequent request gets 401)
    - Test expired token returns 401
    - Test token abilities contain user's permission strings
    - _Requirements: 5.1, 5.2, 5.3, 5.5, 5.7, 5.8_

- [x] 4. Granular Permission-Based Route Authorization
  - [x] 4.1 Apply permission middleware to all resource routes
    - Update `backend/routes/api.php` to apply `permission:{permission-name}` middleware to each route
    - Siswa routes: view-siswa, create-siswa, read-siswa, update-siswa, delete-siswa
    - Kelas routes: view-kelas, create-kelas, read-kelas, update-kelas, delete-kelas
    - Kategori routes: view-kategori, create-kategori, read-kategori, update-kategori, delete-kategori
    - Pembayaran routes: view-pembayaran, delete-pembayaran, print-kwitansi
    - Pengeluaran routes: view-pengeluaran, create-pengeluaran, read-pengeluaran, update-pengeluaran, delete-pengeluaran
    - Laporan routes: view-kas-harian, view-rekap-bulanan, export-laporan
    - Role management routes: view-roles, create-role, update-role, delete-role, attach-role, detach-role, view-permissions, attach-permissions, detach-permissions
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 4.8, 4.9, 4.10_

  - [ ]* 4.2 Write property test for Route Authorization Permission Enforcement
    - **Property 3: Route Authorization Permission Enforcement**
    - Generate users with random permission subsets
    - Verify access is allowed only when user has the required permission
    - Verify 403 response when user lacks the required permission
    - **Validates: Requirements 3.5, 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 4.8**

  - [ ]* 4.3 Write property test for Invalid Token Rejection
    - **Property 4: Invalid Token Rejection**
    - Generate random strings that are not valid Sanctum tokens
    - Verify all authenticated endpoints return 401 for invalid/expired tokens
    - **Validates: Requirements 5.5**

  - [x]* 4.4 Write unit tests for route authorization
    - Test unauthenticated request returns 401
    - Test authenticated user with correct permission gets 200
    - Test authenticated user without permission gets 403
    - Test 403 response body contains error message with required permission name
    - Test superadmin bypasses all permission checks
    - _Requirements: 4.8, 4.9, 4.10_

- [x] 5. RoleRequest Validation Improvement
  - [x] 5.1 Update RoleRequest validation rules
    - Modify `backend/app/Http/Requests/RoleRequest.php`
    - Add `'permissions' => 'required|array|min:1'` rule
    - Add `'permissions.*' => 'required|string|exists:permissions,name'` rule
    - Ensure `name` field has `required|string|min:1|max:255` rule
    - _Requirements: 6.1, 6.2, 6.3, 6.4_

  - [ ]* 5.2 Write property test for RoleRequest Validation
    - **Property 6: RoleRequest Validation Rejects Invalid Permissions**
    - Generate random permission arrays (mix of valid DB names and random strings)
    - Verify validation passes only when all permissions exist in DB, array is non-empty, and each element is a non-empty string
    - **Validates: Requirements 6.1, 6.2, 6.3, 6.4**

- [x] 6. Checkpoint - Ensure all backend tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 7. Filament Role Management Page
  - [x] 7.1 Create RoleManagement Filament page
    - Create `frontend-v2/app/Filament/Pages/RoleManagement.php`
    - Add page to navigation menu within a dedicated navigation group ("Manajemen Akses")
    - Implement table listing all roles with name and assigned permissions (fetched from backend API)
    - _Requirements: 7.1, 7.2_

  - [x] 7.2 Implement role create/edit forms with permission checkboxes
    - Add create form with role name input (1-255 chars) and permission checkboxes
    - Group permissions by domain (users, siswa, kelas, kategori, pembayaran, pengeluaran, laporan, roles)
    - Send POST to backend roles API on create, PUT on edit
    - Handle error responses (duplicate name, not found) with error notifications
    - _Requirements: 7.3, 7.4, 7.5, 7.7, 7.8_

  - [ ] 7.3 Implement role deletion
    - Add delete action with confirmation dialog
    - Send DELETE request to backend roles API
    - Handle error responses (not found) with error notifications
    - _Requirements: 7.6, 7.7_

  - [x]* 7.4 Write unit tests for RoleManagement page
    - Test table displays roles with permissions
    - Test create form sends correct API request
    - Test edit form sends correct API request
    - Test delete action sends correct API request
    - Test error handling for duplicate name and not found
    - _Requirements: 7.1, 7.2, 7.3, 7.5, 7.6_

- [x] 8. Permission-Aware UI Rendering
  - [x] 8.1 Store permissions in session on login
    - Modify frontend login flow to store permissions array from backend response into `session('data.permissions')`
    - Handle API unreachable/error scenario by denying access and displaying error
    - _Requirements: 8.1, 8.7_

  - [x] 8.2 Implement permission-based navigation visibility
    - Update `AdminPanelProvider.php` navigation items to use `->visible()` with permission checks from session
    - Hide navigation items when user lacks corresponding view permission
    - Use session permissions without additional API calls per page navigation
    - _Requirements: 8.2, 8.8_

  - [x] 8.3 Implement permission-based action button visibility
    - Hide create buttons when user lacks create permission for the resource
    - Hide edit buttons when user lacks update permission for the resource
    - Hide delete buttons when user lacks delete permission for the resource
    - _Requirements: 8.3, 8.4, 8.5_

  - [x] 8.4 Implement direct URL access protection
    - Add authorization check in page `mount()` or middleware
    - Redirect to 403 Forbidden page when user navigates directly to a URL without required permission
    - _Requirements: 8.6_

  - [ ]* 8.5 Write property test for UI Element Visibility
    - **Property 7: UI Element Visibility Matches Session Permissions**
    - Test navigation items visible/hidden based on session permissions
    - Test action buttons visible/hidden based on session permissions
    - **Validates: Requirements 8.2, 8.3, 8.4, 8.5**

  - [ ]* 8.6 Write property test for Direct URL Access Denied Without Permission
    - **Property 8: Direct URL Access Denied Without Permission**
    - Test direct URL access redirects to 403 without required view permission
    - **Validates: Requirements 8.6**

- [x] 9. Frontend-Backend Auth Integration with Sanctum
  - [x] 9.1 Implement Filament login page with backend API authentication
    - Create/modify `frontend-v2/app/Filament/Pages/Auth/Login.php`
    - Send POST to backend `/api/login` with username and password
    - On success, store token, permissions, roles, id, username in server-side session
    - On failure, display error message from API response without creating session
    - Implement rate limiting (reject after 5 attempts with notification)
    - _Requirements: 9.1, 9.2, 9.3, 9.7_

  - [x] 9.2 Implement Authorization header injection for API requests
    - Configure frontend HTTP client to include `Authorization: Bearer {token}` header from session
    - Ensure all subsequent API requests include the stored token
    - _Requirements: 9.4_

  - [x] 9.3 Implement CustomAuthentication middleware for session validation
    - Create/modify `CustomAuthentication` middleware to check session for valid token
    - Redirect to login page when session contains no token (null session data or null token)
    - _Requirements: 9.5_

  - [x] 9.4 Implement logout flow
    - Send DELETE request to backend `/api/logout` with token in Authorization header
    - Clear local session data after successful logout
    - Redirect user to login page
    - _Requirements: 9.6_

  - [x]* 9.5 Write property test for Frontend API Requests Include Authorization Header
    - **Property 9: Frontend API Requests Include Authorization Header**
    - Verify all API requests after login include `Authorization: Bearer {token}` header
    - **Validates: Requirements 9.4**

  - [ ]* 9.6 Write unit tests for frontend auth integration
    - Test successful login stores token and permissions in session
    - Test failed login displays error without session
    - Test rate limiting after 5 attempts
    - Test logout sends DELETE and clears session
    - Test CustomAuthentication middleware redirects when no token
    - Test API requests include Authorization header
    - Test token stored exclusively in server-side session (not exposed to browser)
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5, 9.6, 9.7_

- [x] 10. Checkpoint - Ensure all tests pass (Requirements 1-9)
  - Ensure all tests pass, ask the user if questions arise.

- [x] 11. Backend User Management API
  - [x] 11.1 Create UserRequest form request with conditional validation
    - Create/update `backend/app/Http/Requests/UserRequest.php`
    - For POST (create): require username (string, max:100, unique:users), password (string, min:8, max:100), branch_id (integer, exists:branches,id), roles (array, min:1, each exists:roles,name)
    - For PUT/PATCH (update): all fields optional with `sometimes` rule, username unique except self using `Rule::unique()->ignore($userId)`
    - _Requirements: 10.4, 10.5, 10.6, 10.7, 10.10, 10.11, 10.12, 10.17_

  - [x] 11.2 Implement UserController CRUD methods
    - Update `backend/app/Http/Controllers/UserController.php`
    - Implement `index()`: paginated list with `per_page` query param (default 10, max 100), eager load branch and roles, support `branch_id` and `role` query filters
    - Implement `store()`: create user with hashed password, sync roles, return UserResource with 201 status
    - Implement `show(int $id)`: return user with branch and roles, or 404 if not found
    - Implement `update(UserRequest $request, int $id)`: update only provided fields, sync roles if provided, return UserResource or 404
    - Implement `destroy(int $id)`: revoke all Sanctum tokens, remove role assignments, delete user, return success message or 404
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.8, 10.9, 10.10, 10.12, 10.13, 10.14_

  - [x] 11.3 Update UserResource to include branch and roles
    - Modify `backend/app/Http/Resources/UserResource.php`
    - Return id, username, branch (id and location) using `whenLoaded`, and roles array using `whenLoaded` with `getRoleNames()`
    - _Requirements: 10.1, 10.4, 10.8, 10.10_

  - [x] 11.4 Register user management routes with permission middleware
    - Add user CRUD routes in `backend/routes/api.php` within the `auth:sanctum` middleware group
    - GET /users → `permission:view-user` → UserController@index
    - POST /users → `permission:create-user` → UserController@store
    - GET /users/{id} → `permission:read-user` → UserController@show (with `where('id', '[0-9]+')`)
    - PUT /users/{id} → `permission:update-user` → UserController@update (with `where('id', '[0-9]+')`)
    - DELETE /users/{id} → `permission:delete-user` → UserController@destroy (with `where('id', '[0-9]+')`)
    - Ensure existing GET /users/current and PATCH /users/current routes are placed before the new group to avoid route conflicts
    - _Requirements: 10.15, 10.16_

  - [ ]* 11.5 Write property test for User List Pagination
    - **Property 10: User List Pagination**
    - Generate random per_page values (1-100), verify response contains at most per_page items
    - Verify default page size is 10 when per_page is not specified
    - **Validates: Requirements 10.1**

  - [ ]* 11.6 Write property test for Branch Filter
    - **Property 11: Branch Filter Returns Only Matching Users**
    - Create users across multiple branches, filter by random branch_id
    - Verify all returned users belong to the specified branch
    - **Validates: Requirements 10.2**

  - [ ]* 11.7 Write property test for Role Filter
    - **Property 12: Role Filter Returns Only Matching Users**
    - Create users with various roles, filter by random role name
    - Verify all returned users have that role assigned
    - **Validates: Requirements 10.3**

  - [x]* 11.8 Write property test for User CRUD Round-Trip
    - **Property 13: User CRUD Round-Trip**
    - Generate random valid user data, create user via POST, retrieve via GET /users/{id}
    - Verify returned username, branch, and roles match creation payload
    - **Validates: Requirements 10.4, 10.8**

  - [ ]* 11.9 Write property test for Username Uniqueness Enforcement
    - **Property 14: Username Uniqueness Enforcement**
    - Create user with random username, attempt duplicate create → verify 422
    - Attempt to update a different user's username to the same value → verify 422
    - **Validates: Requirements 10.5, 10.11**

  - [ ]* 11.10 Write property test for Role Sync
    - **Property 15: Role Sync Matches Provided Array**
    - Create user with random roles, update with different random roles
    - Verify user's assigned roles exactly match the provided array
    - **Validates: Requirements 10.12**

  - [ ]* 11.11 Write property test for User Deletion Cleanup
    - **Property 16: User Deletion Cleanup**
    - Create users with Sanctum tokens and role assignments, delete user
    - Verify zero tokens, zero role assignments, and user record no longer exists
    - **Validates: Requirements 10.13**

  - [ ]* 11.12 Write unit tests for UserController CRUD
    - Test GET /users returns paginated list with branch and roles
    - Test GET /users?branch_id=X filters by branch
    - Test GET /users?role=X filters by role
    - Test POST /users creates user with roles and returns 201
    - Test POST /users with duplicate username returns 422
    - Test POST /users with invalid branch_id returns 422
    - Test POST /users with invalid role name returns 422
    - Test GET /users/{id} returns user or 404
    - Test PUT /users/{id} updates fields and syncs roles
    - Test PUT /users/{id} with duplicate username returns 422
    - Test DELETE /users/{id} revokes tokens, removes roles, deletes user
    - Test DELETE /users/{id} with non-existent id returns 404
    - Test 403 response when user lacks required permission
    - Test 401 response for unauthenticated requests
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5, 10.6, 10.7, 10.8, 10.9, 10.10, 10.11, 10.12, 10.13, 10.14, 10.15, 10.16, 10.17_

- [x] 12. Checkpoint - Ensure backend user management tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 13. Filament User Management Page
  - [x] 13.1 Create UserManagement Filament page with table
    - Create `frontend-v2/app/Filament/Pages/UserManagement.php`
    - Place in the same navigation group as Role Management ("Manajemen Akses") with sort order 2
    - Implement `shouldRegisterNavigation()` to check session for `view-user` permission
    - Implement `mount()` to abort(403) if user lacks `view-user` permission
    - Display a table listing all users with username, branch location, and assigned role names (fetched from backend GET /api/users)
    - _Requirements: 11.1, 11.2, 11.8, 11.12_

  - [x] 13.2 Implement user create form
    - Add create form with username input (1-100 chars), password input (8-100 chars), branch dropdown (fetched from backend), and role checkboxes (fetched from backend roles API)
    - Send POST to backend /api/users on submit
    - Handle 422 validation errors (duplicate username, invalid branch, invalid role) with error notifications preserving form input
    - Handle server errors with error notification preserving form input
    - Conditionally show create button only when session contains `create-user` permission
    - _Requirements: 11.3, 11.4, 11.9, 11.13_

  - [x] 13.3 Implement user edit form
    - Add edit form with username input (1-100 chars), optional password input (8-100 chars, omit if empty), branch dropdown, and role checkboxes
    - Send PUT to backend /api/users/{id} on submit, omitting password field if left empty
    - Handle 404 error (user not found) with error notification and close form
    - Handle 422 validation errors (duplicate username) with error notification preserving form input
    - Handle server errors with error notification preserving form input
    - Conditionally show edit button only when session contains `update-user` permission
    - _Requirements: 11.5, 11.7, 11.10, 11.13, 11.14_

  - [x] 13.4 Implement user deletion
    - Add delete action with confirmation dialog
    - Send DELETE request to backend /api/users/{id}
    - Handle 404 error (user not found) with error notification
    - Handle server errors with error notification
    - Conditionally show delete button only when session contains `delete-user` permission
    - _Requirements: 11.6, 11.7, 11.11, 11.13_

  - [ ]* 13.5 Write unit tests for UserManagement page
    - Test table displays users with username, branch, and roles
    - Test create form sends correct API request with username, password, branch_id, roles
    - Test edit form sends correct API request omitting empty password
    - Test delete action sends correct API request
    - Test error handling for duplicate username (create and edit)
    - Test error handling for user not found (edit and delete)
    - Test error handling for server errors
    - Test navigation item hidden without view-user permission
    - Test create button hidden without create-user permission
    - Test edit button hidden without update-user permission
    - Test delete button hidden without delete-user permission
    - Test direct URL access returns 403 without view-user permission
    - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.5, 11.6, 11.7, 11.8, 11.9, 11.10, 11.11, 11.12, 11.13, 11.14_

- [ ] 14. Final Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design document
- Unit tests validate specific examples and edge cases
- The backend tasks (1-6) should be completed before frontend tasks (7-9) since the frontend depends on backend API endpoints
- The migration to remove the `token` column (3.2) should be run after the Sanctum integration is complete and tested
- Tasks 11.x (Backend User Management API) depend on the permission system (task 1.1), Sanctum auth (tasks 3.1-3.4), and route authorization middleware (tasks 1.5, 4.1) being in place
- Tasks 13.x (Filament User Management Page) depend on the backend user API (tasks 11.x) and the frontend auth integration (tasks 9.x) being complete

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.3", "1.5"] },
    { "id": 1, "tasks": ["1.2", "1.4", "1.6", "3.1"] },
    { "id": 2, "tasks": ["3.2", "3.3"] },
    { "id": 3, "tasks": ["3.4", "3.5", "3.6", "5.1"] },
    { "id": 4, "tasks": ["4.1", "5.2"] },
    { "id": 5, "tasks": ["4.2", "4.3", "4.4"] },
    { "id": 6, "tasks": ["7.1", "9.1", "11.1"] },
    { "id": 7, "tasks": ["7.2", "7.3", "8.1", "9.2", "9.3", "11.2", "11.3"] },
    { "id": 8, "tasks": ["7.4", "8.2", "8.3", "9.4", "11.4"] },
    { "id": 9, "tasks": ["8.4", "8.5", "8.6", "9.5", "9.6", "11.5", "11.6", "11.7"] },
    { "id": 10, "tasks": ["11.8", "11.9", "11.10", "11.11", "11.12", "13.1"] },
    { "id": 11, "tasks": ["13.2", "13.3", "13.4"] },
    { "id": 12, "tasks": ["13.5"] }
  ]
}
```
