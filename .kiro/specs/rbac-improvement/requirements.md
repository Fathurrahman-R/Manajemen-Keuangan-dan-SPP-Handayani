# Requirements Document

## Introduction

This feature improves the existing RBAC (Role-Based Access Control) implementation in the Handayani school management system. The backend (Laravel 12 with spatie/laravel-permission ^7.4) has a partially implemented RBAC system with several security and architectural issues: permissions are defined but never seeded or enforced, authentication uses insecure plain-text tokens instead of Sanctum, and custom middleware duplicates spatie's built-in functionality. The frontend (Filament 4) needs Role/Permission management pages and permission-aware UI rendering.

## Glossary

- **Backend**: The Laravel 12 API application located at `backend/`, responsible for data persistence, authentication, and authorization
- **Frontend**: The Filament 4 application located at `frontend-v2/`, responsible for the admin panel UI
- **Permission_Enum**: The PHP enum `App\Enum\Permission` defining all granular permission values (e.g., view-siswa, create-kelas)
- **Spatie_Permission**: The `spatie/laravel-permission` package (v7.4) providing role and permission management via database
- **Sanctum**: Laravel Sanctum (v4.0), a token-based authentication package supporting token abilities and expiration
- **Superadmin**: A role that automatically bypasses all permission checks via Gate::before
- **Admin**: A role assigned specific permissions based on the PermissionBinding configuration
- **Route_Authorization**: The middleware layer that checks whether an authenticated user has the required permission to access an API endpoint
- **Permission_Seeder**: The database seeder that creates all permission records from the Permission_Enum into the permissions table
- **Filament_Panel**: The Filament 4 admin panel that renders pages, navigation, and action buttons

## Requirements

### Requirement 1: Permission Seeding

**User Story:** As a system administrator, I want all permissions from the Permission_Enum to be seeded into the database, so that roles can be assigned granular permissions.

#### Acceptance Criteria

1. WHEN the RoleAndPermissionSeeder is executed, THE Backend SHALL create a permission record in the permissions table for each value defined in the Permission enum (App\Enum\Permission), using the string value of each enum case as the permission name
2. WHEN the RoleAndPermissionSeeder is executed, THE Backend SHALL create the superadmin, admin, and user roles as defined in the DefaultRoles enum
3. WHEN the RoleAndPermissionSeeder is executed, THE Backend SHALL assign all permissions defined in PermissionBinding::ADMIN_PERMISSIONS to the admin role
4. WHEN the RoleAndPermissionSeeder is executed, THE Backend SHALL assign all permissions from the Permission enum to the superadmin role
5. IF a permission or role already exists in the database, THEN THE RoleAndPermissionSeeder SHALL skip creation of that record without raising an error or creating a duplicate
6. WHEN the RoleAndPermissionSeeder is executed, THE Backend SHALL clear the spatie permission cache both before seeding begins and after seeding completes

### Requirement 2: Superadmin Gate Bypass

**User Story:** As a superadmin, I want to automatically have access to all permissions without explicit assignment, so that I can manage the entire system without restriction.

#### Acceptance Criteria

1. WHILE a user has the superadmin role, THE Backend SHALL return true from the Gate::before callback for every Gate ability and policy check, bypassing individual permission evaluation
2. IF the user is null or does not have the superadmin role, THEN THE Backend SHALL return null from the Gate::before callback, allowing normal permission evaluation to proceed
3. THE Backend SHALL register the Gate::before callback in the AuthServiceProvider boot method so that it is evaluated before any policy or ability check
4. THE Backend SHALL identify the superadmin role using the value defined in the DefaultRoles enum ('superadmin')

### Requirement 3: Replace Custom Middleware with Spatie Middleware

**User Story:** As a developer, I want to use spatie's built-in middleware for authorization, so that the codebase avoids duplication and leverages well-tested authorization logic.

#### Acceptance Criteria

1. THE Backend SHALL remove the custom ApiRoleMiddleware class and register spatie's RoleMiddleware as a named middleware alias "role" in the application's middleware configuration
2. THE Backend SHALL register spatie's PermissionMiddleware as a named middleware alias "permission" in the application's middleware configuration
3. THE Backend SHALL register spatie's RoleOrPermissionMiddleware as a named middleware alias "role_or_permission" in the application's middleware configuration
4. WHEN a route requires a specific permission, THE Backend SHALL apply the "permission" middleware with the Permission enum's string value as the parameter (e.g., middleware("permission:view-user"))
5. IF an authenticated user lacks the required role or permission for a route protected by spatie middleware, THEN THE Backend SHALL return a 403 HTTP response with a JSON body containing an error message indicating access is denied
6. WHEN routes previously protected by ApiRoleMiddleware are migrated, THE Backend SHALL preserve the same access restrictions by applying the "role" middleware alias with the identical role parameter (e.g., "role:superadmin" for role management routes, "role:admin" for resource management routes)

### Requirement 4: Granular Permission-Based Route Authorization

**User Story:** As an administrator, I want API routes to check specific permissions instead of only roles, so that access control is fine-grained and configurable per role.

#### Acceptance Criteria

1. WHEN a user sends a request to a siswa endpoint, THE Route_Authorization SHALL verify the user has the permission matching the action: view-siswa for listing, create-siswa for creating, read-siswa for retrieving a single record, update-siswa for updating, or delete-siswa for deleting
2. WHEN a user sends a request to a kelas endpoint, THE Route_Authorization SHALL verify the user has the permission matching the action: view-kelas for listing, create-kelas for creating, read-kelas for retrieving a single record, update-kelas for updating, or delete-kelas for deleting
3. WHEN a user sends a request to a kategori endpoint, THE Route_Authorization SHALL verify the user has the permission matching the action: view-kategori for listing, create-kategori for creating, read-kategori for retrieving a single record, update-kategori for updating, or delete-kategori for deleting
4. WHEN a user sends a request to a pembayaran endpoint, THE Route_Authorization SHALL verify the user has the permission matching the action: view-pembayaran for listing, delete-pembayaran for deleting, or print-kwitansi for generating a payment receipt
5. WHEN a user sends a request to a pengeluaran endpoint, THE Route_Authorization SHALL verify the user has the permission matching the action: view-pengeluaran for listing, create-pengeluaran for creating, read-pengeluaran for retrieving a single record, update-pengeluaran for updating, or delete-pengeluaran for deleting
6. WHEN a user sends a request to a laporan endpoint, THE Route_Authorization SHALL verify the user has the permission matching the action: view-kas-harian for viewing the daily cash report, view-rekap-bulanan for viewing the monthly recap, or export-laporan for exporting reports
7. WHEN a user sends a request to a role management endpoint, THE Route_Authorization SHALL verify the user has the permission matching the action: view-roles for listing roles, create-role for creating, update-role for updating, delete-role for deleting, attach-role for assigning a role to a user, detach-role for removing a role from a user, view-permissions for listing permissions, attach-permissions for assigning permissions to a role, or detach-permissions for removing permissions from a role
8. IF a user lacks the required permission for the requested action, THEN THE Route_Authorization SHALL reject the request with a 403 HTTP status code and a JSON response body containing an error message indicating the permission that was required
9. THE Route_Authorization SHALL verify that the user is authenticated before checking permissions, ensuring that unauthenticated requests are handled by the authentication middleware before permission evaluation occurs
10. WHEN a user has a role with dynamically assigned permissions, THE Route_Authorization SHALL evaluate the user's current set of permissions at request time rather than relying on a static role-to-permission mapping

### Requirement 5: Sanctum Token-Based Authentication

**User Story:** As a system administrator, I want authentication to use Laravel Sanctum tokens with expiration, so that the API is secured with industry-standard token management.

#### Acceptance Criteria

1. WHEN a user logs in successfully, THE Backend SHALL issue a Sanctum personal access token with an expiration time configured via the `sanctum.expiration` config value, defaulting to 480 minutes (8 hours) if not explicitly set
2. WHEN a user logs in successfully, THE Backend SHALL return the plain-text token value and the token expiration timestamp in the login response body
3. WHEN a user logs out, THE Backend SHALL revoke the current Sanctum token so that subsequent requests using that token receive a 401 HTTP response
4. THE Backend SHALL replace the custom ApiAuthMiddleware with Sanctum's auth:sanctum middleware for route protection
5. WHEN a request contains an expired or invalid Sanctum token, THE Backend SHALL return a 401 HTTP response with a JSON body containing an error message indicating the request is unauthorized
6. THE Backend SHALL remove the plain-text token column from the users table in favor of Sanctum's personal_access_tokens table
7. WHEN a Sanctum token is issued, THE Backend SHALL assign token abilities corresponding to all permission strings retrieved from the user's Spatie roles and direct permissions at the time of token creation
8. IF a request requires a permission that is not present in the current token's abilities, THEN THE Backend SHALL return a 403 HTTP response with a JSON body containing an error message indicating insufficient permissions

### Requirement 6: RoleRequest Validation Improvement

**User Story:** As a developer, I want the RoleRequest to validate that submitted permissions exist in the database, so that invalid permission names are rejected at the request level.

#### Acceptance Criteria

1. WHEN a role creation or update request is submitted, THE Backend SHALL validate that each permission in the permissions array exists in the permissions database table by name
2. IF a role creation or update request contains a non-existent permission name, THEN THE Backend SHALL return a 422 HTTP response with a validation error message identifying the invalid permission
3. THE Backend SHALL validate that the permissions field is a required array with at least one element when creating a role
4. THE Backend SHALL validate that each element in the permissions array is a non-empty string

### Requirement 7: Filament Role Management Page

**User Story:** As an administrator, I want a Role Management page in the Filament panel, so that I can create, view, edit, and delete roles with their associated permissions through the UI.

#### Acceptance Criteria

1. THE Filament_Panel SHALL provide a Role Management page accessible from the navigation menu within a dedicated navigation group
2. WHEN an administrator views the Role Management page, THE Filament_Panel SHALL display a table listing all roles with their name and assigned permission names, retrieved from the Backend API roles endpoint
3. WHEN an administrator submits the create role form with a role name (1 to 255 characters) and at least one selected permission, THE Filament_Panel SHALL send a POST request to the Backend roles API with the role name and selected permissions array
4. IF the Backend API returns an error indicating the role name already exists, THEN THE Filament_Panel SHALL display an error notification to the administrator and preserve the form input
5. WHEN an administrator submits the edit role form with an updated role name and permissions, THE Filament_Panel SHALL send a PUT request to the Backend roles API with the updated role name and permissions array
6. WHEN an administrator confirms deletion of a role, THE Filament_Panel SHALL send a DELETE request to the Backend roles API with the role identifier
7. IF the Backend API returns an error indicating the role is not found during edit or delete, THEN THE Filament_Panel SHALL display an error notification to the administrator
8. THE Filament_Panel SHALL display available permissions grouped by domain (users, siswa, kelas, kategori, pembayaran, pengeluaran, laporan, roles) as checkboxes for selection during role creation and editing

### Requirement 8: Permission-Aware UI Rendering

**User Story:** As an administrator, I want the Filament panel to show or hide navigation items and action buttons based on my permissions, so that I only see functionality I am authorized to use.

#### Acceptance Criteria

1. WHEN a user logs into the Filament_Panel, THE Filament_Panel SHALL retrieve the user's permissions list from the Backend API and store them in the session before rendering any page
2. WHILE a user's session does not contain the corresponding view permission for a page (e.g., "view-siswa" for Data Master Siswa, "view-pembayaran" for Transaksi Pembayaran), THE Filament_Panel SHALL hide the corresponding navigation menu item from the sidebar
3. WHILE a user's session does not contain the corresponding create permission for a resource (e.g., "create-siswa", "create-kelas"), THE Filament_Panel SHALL hide the create button on that resource's page
4. WHILE a user's session does not contain the corresponding delete permission for a resource (e.g., "delete-siswa", "delete-pengeluaran"), THE Filament_Panel SHALL hide the delete button on that resource's page
5. WHILE a user's session does not contain the corresponding update permission for a resource (e.g., "update-siswa", "update-kelas"), THE Filament_Panel SHALL hide the edit button on that resource's page
6. IF a user navigates directly to a page URL without the required view permission, THEN THE Filament_Panel SHALL redirect the user to a 403 Forbidden page
7. IF the Backend API is unreachable or returns an error when the Filament_Panel attempts to retrieve permissions during login, THEN THE Filament_Panel SHALL deny access and display an error message indicating that permissions could not be loaded
8. WHEN a user's session is active and a page is loaded, THE Filament_Panel SHALL use the permissions stored in the session at login time without requiring an additional API call per page navigation

### Requirement 9: Frontend-Backend Auth Integration with Sanctum

**User Story:** As a developer, I want the Filament frontend to authenticate against the Backend using Sanctum tokens, so that the frontend uses the same secure authentication mechanism as the API.

#### Acceptance Criteria

1. WHEN a user submits credentials on the Filament login page, THE Frontend SHALL send a POST request containing username and password to the Backend login API and, upon a successful response, store the returned token in the server-side session
2. IF the Backend login API returns an authentication error (invalid credentials or account already logged in elsewhere), THEN THE Frontend SHALL display the error message from the API response on the login page without creating a session
3. IF the user exceeds 5 login attempts, THEN THE Frontend SHALL reject further login attempts and display a rate-limit notification indicating the user must wait before retrying
4. WHEN the Frontend makes any subsequent API request to the Backend, THE Frontend SHALL include the stored token in the Authorization header of the HTTP request
5. WHEN the CustomAuthentication middleware detects that the session contains no token (null session data or null token value), THE Frontend SHALL redirect the user to the Filament login page
6. WHEN a user triggers logout from the Filament panel, THE Frontend SHALL send a DELETE request to the Backend logout API with the stored token in the Authorization header, clear the local session data, and redirect the user to the login page
7. THE Frontend SHALL store the Sanctum token exclusively in the server-side session and SHALL NOT expose the token to the browser via cookies or client-side storage


### Requirement 10: Backend User Management API

**User Story:** As an administrator, I want full CRUD API endpoints for managing users, so that I can create, view, update, and delete user accounts with their role and branch assignments.

#### Acceptance Criteria

1. WHEN an authenticated user with the view-user permission sends a GET request to /api/users, THE Backend SHALL return a paginated list of all users including each user's id, username, branch (id and location), and assigned role names, with a default page size of 10 and a maximum page size of 100, configurable via a per_page query parameter
2. WHEN an authenticated user with the view-user permission sends a GET request to /api/users with a branch_id query parameter, THE Backend SHALL return only users belonging to the specified branch
3. WHEN an authenticated user with the view-user permission sends a GET request to /api/users with a role query parameter, THE Backend SHALL return only users assigned to the specified role
4. WHEN an authenticated user with the create-user permission sends a POST request to /api/users with a username (maximum 100 characters), password (minimum 8, maximum 100 characters), branch_id, and roles array (containing at least 1 role name), THE Backend SHALL create a new user record, assign the specified roles, and return the created user resource with a 201 HTTP status code
5. IF a user creation request contains a username that already exists, THEN THE Backend SHALL return a 422 HTTP response with a validation error message identifying the duplicate username
6. IF a user creation request contains an invalid branch_id that does not exist in the branches table, THEN THE Backend SHALL return a 422 HTTP response with a validation error message identifying the invalid branch
7. IF a user creation request contains a role name that does not exist in the roles table, THEN THE Backend SHALL return a 422 HTTP response with a validation error message identifying the invalid role
8. WHEN an authenticated user with the read-user permission sends a GET request to /api/users/{id}, THE Backend SHALL return the user's id, username, branch (id and location), and assigned role names
9. IF a GET request to /api/users/{id} references a user id that does not exist, THEN THE Backend SHALL return a 404 HTTP response with an error message indicating the user was not found
10. WHEN an authenticated user with the update-user permission sends a PUT request to /api/users/{id} with one or more optional fields (username, password, branch_id, or roles array), THE Backend SHALL update only the provided fields and return the updated user resource
11. IF a user update request changes the username to one that already exists for a different user, THEN THE Backend SHALL return a 422 HTTP response with a validation error message identifying the duplicate username
12. WHEN an authenticated user with the update-user permission sends a PUT request to /api/users/{id} with a roles array, THE Backend SHALL sync the user's roles to match the provided array, removing any roles not included and adding any new roles specified
13. WHEN an authenticated user with the delete-user permission sends a DELETE request to /api/users/{id}, THE Backend SHALL revoke all Sanctum tokens for that user, remove all role assignments, delete the user record, and return a 200 HTTP response with a success message
14. IF a DELETE request to /api/users/{id} references a user id that does not exist, THEN THE Backend SHALL return a 404 HTTP response with an error message indicating the user was not found
15. IF an authenticated user without the required permission (view-user, create-user, read-user, update-user, or delete-user) attempts to access a user management endpoint, THEN THE Backend SHALL return a 403 HTTP response with an error message indicating access is denied
16. IF an unauthenticated request is sent to any /api/users endpoint, THEN THE Backend SHALL return a 401 HTTP response with an error message indicating the user is not authenticated
17. IF a user update request contains an invalid branch_id that does not exist in the branches table or a role name that does not exist in the roles table, THEN THE Backend SHALL return a 422 HTTP response with a validation error message identifying the invalid field

### Requirement 11: Filament User Management Page

**User Story:** As an administrator, I want a User Management page in the Filament panel, so that I can create, view, edit, and delete user accounts with branch and role assignments through the UI.

#### Acceptance Criteria

1. THE Filament_Panel SHALL provide a User Management page accessible from the navigation menu within the same navigation group as the Role Management page
2. WHEN an administrator views the User Management page, THE Filament_Panel SHALL display a table listing all users with their username, branch location, and assigned role names, retrieved from the Backend API users endpoint
3. WHEN an administrator submits the create user form with a username (1 to 100 characters), password (8 to 100 characters), a selected branch, and at least one selected role, THE Filament_Panel SHALL send a POST request to the Backend users API with the username, password, branch_id, and roles array
4. IF the Backend API returns a validation error indicating the username already exists during user creation, THEN THE Filament_Panel SHALL display an error notification to the administrator and preserve the form input
5. WHEN an administrator submits the edit user form with an updated username (1 to 100 characters), optional new password (8 to 100 characters if provided), a selected branch, and at least one selected role, THE Filament_Panel SHALL send a PUT request to the Backend users API with the updated fields, omitting the password field if left empty
6. WHEN an administrator confirms deletion of a user, THE Filament_Panel SHALL send a DELETE request to the Backend users API with the user identifier
7. IF the Backend API returns an error indicating the user is not found during edit or delete, THEN THE Filament_Panel SHALL display an error notification to the administrator and close the form or action modal
8. WHILE a user's session does not contain the view-user permission, THE Filament_Panel SHALL hide the User Management navigation menu item from the sidebar
9. WHILE a user's session does not contain the create-user permission, THE Filament_Panel SHALL hide the create button on the User Management page
10. WHILE a user's session does not contain the update-user permission, THE Filament_Panel SHALL hide the edit button on the User Management table rows
11. WHILE a user's session does not contain the delete-user permission, THE Filament_Panel SHALL hide the delete button on the User Management table rows
12. IF a user navigates directly to the User Management page URL without the view-user permission, THEN THE Filament_Panel SHALL redirect the user to a 403 Forbidden page
13. IF the Backend API is unreachable or returns a server error during a user create, edit, or delete operation, THEN THE Filament_Panel SHALL display an error notification indicating the operation failed and preserve any form input
14. IF the Backend API returns a validation error indicating the username already exists during user edit, THEN THE Filament_Panel SHALL display an error notification to the administrator and preserve the form input
