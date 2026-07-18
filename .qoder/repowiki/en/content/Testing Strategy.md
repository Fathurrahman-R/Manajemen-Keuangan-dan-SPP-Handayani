# Testing Strategy

<cite>
**Referenced Files in This Document**
- [phpunit.xml](file://backend/phpunit.xml)
- [TestCase.php](file://backend/tests/TestCase.php)
- [composer.json](file://backend/composer.json)
- [MidtransClient.php](file://backend/app/Services/Midtrans/MidtransClient.php)
- [MidtransInitiationService.php](file://backend/app/Services/Midtrans/MidtransInitiationService.php)
- [MidtransNotificationService.php](file://backend/app/Services/Midtrans/MidtransNotificationService.php)
- [MidtransStatusSyncService.php](file://backend/app/Services/Midtrans/MidtransStatusSyncService.php)
- [MidtransTransactionController.php](file://backend/app/Http/Controllers/MidtransTransactionController.php)
- [MidtransNotificationController.php](file://backend/app/Http/Controllers/MidtransNotificationController.php)
- [PembayaranTest.php](file://backend/tests/Feature/PembayaranTest.php)
- [TagihanTest.php](file://backend/tests/Feature/TagihanTest.php)
- [SiswaTest.php](file://backend/tests/Feature/SiswaTest.php)
- [RoleTest.php](file://backend/tests/Feature/RoleTest.php)
- [MidtransInternalStatusTest.php](file://backend/tests/Unit/Services/Midtrans/MidtransInternalStatusTest.php)
- [SignatureVerifierTest.php](file://backend/tests/Unit/Services/Midtrans/SignatureVerifierTest.php)
- [StatusMapperTest.php](file://backend/tests/Unit/Services/Midtrans/StatusMapperTest.php)
- [StatusTransitionGuardTest.php](file://backend/tests/Unit/Services/Midtrans/StatusTransitionGuardTest.php)
- [FakeMidtransClient.php](file://backend/tests/Stubs/FakeMidtransClient.php)
- [PublicConfigTest.php](file://frontend-v2/tests/Feature/PublicConfigTest.php)
- [PublicPageTest.php](file://frontend-v2/tests/Feature/PublicPageTest.php)
- [handayani-public.php](file://frontend-v2/config/handayani-public.php)
- [PublicPageController.php](file://frontend-v2/app/Http/Controllers/PublicPageController.php)
- [web.php](file://frontend-v2/routes/web.php)
</cite>

## Update Summary
**Changes Made**
- Added comprehensive public portal testing coverage for frontend-v2 application
- Integrated configuration validation tests for handayani-public settings
- Added extensive landing page functionality tests covering HTTP responses, routing, content rendering, SEO elements, and asset loading
- Updated testing architecture to include dual-application support (backend and frontend-v2)

## Table of Contents
1. [Introduction](#introduction)
2. [Project Structure](#project-structure)
3. [Core Components](#core-components)
4. [Architecture Overview](#architecture-overview)
5. [Detailed Component Analysis](#detailed-component-analysis)
6. [Dependency Analysis](#dependency-analysis)
7. [Performance Considerations](#performance-considerations)
8. [Troubleshooting Guide](#troubleshooting-guide)
9. [Conclusion](#conclusion)
10. [Appendices](#appendices)

## Introduction
This document defines the comprehensive testing strategy for the Handayani system, focusing on unit tests, feature tests, and integration tests using PHPUnit across both backend and frontend-v2 applications. It explains test data management with factories and seeders, patterns for testing controllers, services, models, and external integrations (notably Midtrans), asynchronous operations, suite organization, continuous integration setup, code coverage requirements, performance and load testing strategies, and best practices to maintain code quality. The strategy now encompasses the new public portal functionality with dedicated configuration validation and landing page testing.

## Project Structure
The Handayani system consists of two Laravel applications: a backend API application and a frontend-v2 application with Filament admin panel and public portal. Each has its own PHPUnit configuration and test suites with clear separation between Unit and Feature tests. The test suites are configured to run against isolated databases and use array-based cache/session/mail drivers for speed and isolation.

```mermaid
graph TB
A["Backend PHPUnit Config<br/>backend/phpunit.xml"] --> B["Backend Testsuite: Unit<br/>backend/tests/Unit"]
A --> C["Backend Testsuite: Feature<br/>backend/tests/Feature"]
D["Frontend-v2 PHPUnit Config<br/>frontend-v2/phpunit.xml"] --> E["Frontend-v2 Testsuite: Unit<br/>frontend-v2/tests/Unit"]
D --> F["Frontend-v2 Testsuite: Feature<br/>frontend-v2/tests/Feature"]
G["Base TestCase<br/>backend/tests/TestCase.php"] --> H["Factory & Seeder Usage"]
I["Composer Scripts<br/>composer.json"] --> J["Run Tests via Artisan"]
K["Public Portal Tests<br/>PublicConfigTest.php, PublicPageTest.php"] --> L["Configuration Validation"]
M["Public Page Tests<br/>HTTP Response & Content Validation"] --> N["Landing Page Functionality"]
```

**Diagram sources**
- [phpunit.xml:1-36](file://backend/phpunit.xml#L1-L36)
- [composer.json:44-79](file://backend/composer.json#L44-L79)
- [PublicConfigTest.php:1-48](file://frontend-v2/tests/Feature/PublicConfigTest.php#L1-48)
- [PublicPageTest.php:1-118](file://frontend-v2/tests/Feature/PublicPageTest.php#L1-118)

**Section sources**
- [phpunit.xml:1-36](file://backend/phpunit.xml#L1-L36)
- [composer.json:44-79](file://backend/composer.json#L44-L79)

## Core Components
- Test suites and environment:
  - Two main applications with separate test suites: backend and frontend-v2.
  - Environment variables configure dedicated testing databases, null broadcast, array cache/session/mail, sync queue, and disabled telemetry features for fast execution.
- Base test case:
  - Centralized cleanup of critical tables at setUp to ensure test isolation.
  - Permission cache reset to avoid role leakage across tests.
  - Reusable scenario builders for common domain objects (students, classes, categories, invoices, payments, expenditures).
- Composer scripts:
  - Provides convenient test scripts that clear config and run tests for each application.

Updated public portal testing components:
- Configuration validation tests ensuring handayani-public config integrity.
- Comprehensive landing page tests covering HTTP responses, routing, content rendering, SEO elements, and asset loading.
- Interactive component testing including Alpine.js mobile navigation functionality.

Practical implications:
- Use the base TestCase helpers to set up realistic scenarios quickly.
- Keep tests isolated by relying on the provided cleanup and permission cache reset.
- Prefer factories and seeders for deterministic test data.
- Leverage public portal tests for validating configuration-driven content and user experience.

**Section sources**
- [phpunit.xml:7-34](file://backend/phpunit.xml#L7-L34)
- [TestCase.php:19-39](file://backend/tests/TestCase.php#L19-L39)
- [composer.json:57-60](file://backend/composer.json#L57-L60)
- [PublicConfigTest.php:1-48](file://frontend-v2/tests/Feature/PublicConfigTest.php#L1-48)
- [PublicPageTest.php:1-118](file://frontend-v2/tests/Feature/PublicPageTest.php#L1-118)

## Architecture Overview
The testing architecture spans three layers across both applications:
- Unit tests: Focus on pure logic and service methods without HTTP or DB side effects.
- Feature tests: Exercise full request/response cycles through routes/controllers, asserting state changes and responses.
- Integration tests: Validate interactions with external systems (e.g., Midtrans) using stubs/fakes and controlled environments.

Updated architecture now includes public portal testing:
- Configuration validation ensuring all required settings are present and properly typed.
- Landing page functionality testing covering complete user journey from HTTP response to interactive elements.
- Asset loading verification ensuring proper separation between public portal and admin assets.

```mermaid
graph TB
subgraph "Backend Application"
B1["Unit Tests<br/>Service Logic & Helpers"]
B2["Feature Tests<br/>API Controllers & Auth"]
B3["Integration Tests<br/>Midtrans Stubs & Queue Jobs"]
end
subgraph "Frontend-v2 Application"
F1["Unit Tests<br/>Branding & Permissions"]
F2["Feature Tests<br/>Public Portal & Admin Pages"]
F3["Configuration Tests<br/>handayani-public Settings"]
F4["Landing Page Tests<br/>HTTP Responses & Content"]
end
B1 --> B2
B2 --> B3
F1 --> F2
F2 --> F3
F2 --> F4
```

[No sources needed since this diagram shows conceptual workflow, not actual code structure]

## Detailed Component Analysis

### Unit Testing Strategy
Focus areas:
- Service layer logic (e.g., Midtrans internal status mapping, signature verification, status transition guard).
- Pure functions and helpers.
- Deterministic assertions without network calls.
- Branding configuration and permission helper logic in frontend-v2.

Recommended patterns:
- Mock external dependencies using interfaces or class mocks.
- Use small, focused datasets via factories or inline arrays.
- Assert both outcomes and side effects (e.g., logs, events).

Example targets:
- Internal status mapping and transitions.
- Signature verification for webhooks.
- Status mapper behavior under various inputs.
- Branding configuration validation.
- Permission helper functionality.

**Section sources**
- [MidtransInternalStatusTest.php](file://backend/tests/Unit/Services/Midtrans/MidtransInternalStatusTest.php)
- [SignatureVerifierTest.php](file://backend/tests/Unit/Services/Midtrans/SignatureVerifierTest.php)
- [StatusMapperTest.php](file://backend/tests/Unit/Services/Midtrans/StatusMapperTest.php)
- [StatusTransitionGuardTest.php](file://backend/tests/Unit/Services/Midtrans/StatusTransitionGuardTest.php)
- [AdminPanelBrandingTest.php](file://frontend-v2/tests/Unit/AdminPanelBrandingTest.php)
- [BrandingConfigTest.php](file://frontend-v2/tests/Unit/BrandingConfigTest.php)
- [PermissionHelperTest.php](file://frontend-v2/tests/Unit/PermissionHelperTest.php)

### Feature Testing Strategy
Focus areas:
- End-to-end API flows for core domains: students, invoices, payments, roles, etc.
- Authorization and permissions enforcement.
- Request validation and response shapes.
- Public portal functionality including configuration validation and landing page rendering.

Updated public portal feature testing:
- Configuration system integrity validation ensuring all required keys exist and have proper types.
- Landing page comprehensive testing covering HTTP responses, route verification, content rendering, SEO elements, and asset loading.
- Interactive component testing including Alpine.js mobile navigation functionality.

Recommended patterns:
- Use base TestCase scenario builders to prepare minimal but sufficient data.
- Authenticate requests using tokens or Sanctum as appropriate.
- Assert HTTP status codes, JSON structures, and database state changes.
- For public portal tests, validate configuration-driven content and user experience.

Example targets:
- Student CRUD and relationships.
- Invoice listing, search, and mass operations.
- Payment recording and reconciliation.
- Role and permission boundaries.
- Public portal configuration validation.
- Landing page content rendering and SEO optimization.

**Section sources**
- [SiswaTest.php](file://backend/tests/Feature/SiswaTest.php)
- [TagihanTest.php](file://backend/tests/Feature/TagihanTest.php)
- [PembayaranTest.php](file://backend/tests/Feature/PembayaranTest.php)
- [RoleTest.php](file://backend/tests/Feature/RoleTest.php)
- [TestCase.php:44-392](file://backend/tests/TestCase.php#L44-L392)
- [PublicConfigTest.php:1-48](file://frontend-v2/tests/Feature/PublicConfigTest.php#L1-48)
- [PublicPageTest.php:1-118](file://frontend-v2/tests/Feature/PublicPageTest.php#L1-118)

### Integration Testing Strategy (External Integrations)
Focus areas:
- External payment gateway (Midtrans) interactions.
- Webhook processing and signature verification.
- Transaction status synchronization.
- Public portal controller integration with view rendering.

Recommended patterns:
- Replace real client with a fake/stub implementation to control responses deterministically.
- Simulate webhook payloads and verify controller handling and state transitions.
- Ensure idempotency and error paths are covered.
- For public portal, test controller-view integration and asset loading.

```mermaid
sequenceDiagram
participant Client as "Test Case"
participant Controller as "MidtransTransactionController"
participant InitSvc as "MidtransInitiationService"
participant ClientStub as "FakeMidtransClient"
participant DB as "Database"
Client->>Controller : "POST /api/midtrans/initiate"
Controller->>InitSvc : "initiatePayment(data)"
InitSvc->>ClientStub : "createTransaction(params)"
ClientStub-->>InitSvc : "transaction token/status"
InitSvc->>DB : "persist transaction record"
InitSvc-->>Controller : "response payload"
Controller-->>Client : "HTTP 200 + initiation result"
```

**Diagram sources**
- [MidtransTransactionController.php](file://backend/app/Http/Controllers/MidtransTransactionController.php)
- [MidtransInitiationService.php](file://backend/app/Services/Midtrans/MidtransInitiationService.php)
- [FakeMidtransClient.php](file://backend/tests/Stubs/FakeMidtransClient.php)

**Section sources**
- [FakeMidtransClient.php](file://backend/tests/Stubs/FakeMidtransClient.php)
- [MidtransInitiationService.php](file://backend/app/Services/Midtrans/MidtransInitiationService.php)
- [MidtransTransactionController.php](file://backend/app/Http/Controllers/MidtransTransactionController.php)

### Public Portal Testing Strategy
New comprehensive testing approach for the public portal functionality:

Configuration validation tests:
- Verify handayani-public configuration file exists and is properly structured.
- Validate all required configuration keys are present and non-null.
- Ensure default values match reference site configuration.
- Confirm all configuration values are strings with proper typing.

Landing page functionality tests:
- HTTP response validation ensuring 200 status codes.
- Route verification confirming PublicPageController@index usage.
- Content rendering checks for hero section, site name, and contact information.
- SEO element validation including section anchors and meta information.
- Asset loading verification ensuring proper Vite public assets and exclusion of Filament/Livewire assets.
- Interactive component testing for Alpine.js mobile navigation functionality.

```mermaid
flowchart TD
A["Public Portal Test Suite"] --> B["Configuration Validation"]
A --> C["Landing Page Testing"]
B --> D["Config File Existence"]
B --> E["Required Keys Validation"]
B --> F["Default Values Check"]
B --> G["Type Safety Verification"]
C --> H["HTTP Response Validation"]
C --> I["Route Verification"]
C --> J["Content Rendering Checks"]
C --> K["SEO Element Validation"]
C --> L["Asset Loading Verification"]
C --> M["Interactive Component Testing"]
```

**Diagram sources**
- [PublicConfigTest.php:1-48](file://frontend-v2/tests/Feature/PublicConfigTest.php#L1-48)
- [PublicPageTest.php:1-118](file://frontend-v2/tests/Feature/PublicPageTest.php#L1-118)
- [handayani-public.php:1-13](file://frontend-v2/config/handayani-public.php#L1-13)
- [PublicPageController.php:1-12](file://frontend-v2/app/Http/Controllers/PublicPageController.php#L1-12)

**Section sources**
- [PublicConfigTest.php:1-48](file://frontend-v2/tests/Feature/PublicConfigTest.php#L1-48)
- [PublicPageTest.php:1-118](file://frontend-v2/tests/Feature/PublicPageTest.php#L1-118)
- [handayani-public.php:1-13](file://frontend-v2/config/handayani-public.php#L1-13)
- [PublicPageController.php:1-12](file://frontend-v2/app/Http/Controllers/PublicPageController.php#L1-12)
- [web.php:1-26](file://frontend-v2/routes/web.php#L1-26)

### Asynchronous Operations Testing
Recommendations:
- Use sync queue driver in tests for predictable execution order.
- Dispatch jobs within tests and assert their effects immediately after dispatch.
- For time-sensitive jobs, consider mocking time or using queued job assertions.

Environment alignment:
- Queue connection is set to sync in the test environment, ensuring jobs run synchronously during tests.

**Section sources**
- [phpunit.xml:29](file://backend/phpunit.xml#L29)

### Test Data Management
Guidelines:
- Prefer factories for creating consistent, valid entities.
- Use seeders for static reference data when necessary.
- Leverage base TestCase helper methods to assemble complex scenarios efficiently.
- Always clean up shared tables in setUp to prevent cross-test pollution.

Key helpers available in base TestCase:
- Scenario builders for students (MI/TK/KB), kelas, kategori, jenis tagihan, tagihan, pembayaran, kas harian, rekap bulanan, and more.
- Payload builders for valid and invalid inputs to drive validation tests.

**Section sources**
- [TestCase.php:44-392](file://backend/tests/TestCase.php#L44-L392)

### Controllers Testing Patterns
Approach:
- Route-level tests should assert request validation, authorization, business flow, and response shape.
- For Midtrans-related endpoints, use FakeMidtransClient to simulate success, failure, and edge cases.
- Verify database state changes post-request (e.g., transaction records persisted).
- For public portal controllers, test view rendering and configuration-driven content.

**Section sources**
- [MidtransTransactionController.php](file://backend/app/Http/Controllers/MidtransTransactionController.php)
- [MidtransNotificationController.php](file://backend/app/Http/Controllers/MidtransNotificationController.php)
- [PublicPageController.php:1-12](file://frontend-v2/app/Http/Controllers/PublicPageController.php#L1-12)

### Services Testing Patterns
Approach:
- Isolate service logic from HTTP concerns; test inputs and outputs directly.
- For services depending on external clients (e.g., MidtransClient), inject fakes or mocks.
- Cover happy paths, error conditions, and boundary values.

Examples:
- Midtrans initiation service orchestrates client calls and persistence.
- Notification service handles email/webhook notifications (use array mailer in tests).
- Branding service manages public portal branding configuration.

**Section sources**
- [MidtransInitiationService.php](file://backend/app/Services/Midtrans/MidtransInitiationService.php)
- [MidtransNotificationService.php](file://backend/app/Services/Midtrans/MidtransNotificationService.php)
- [MidtransClient.php](file://backend/app/Services/Midtrans/MidtransClient.php)

### Models Testing Patterns
Approach:
- Validate model rules, casts, accessors/mutators, and relationships.
- Use factories to create related entities and assert relationship integrity.
- Combine with feature tests to validate model behavior in request context.

**Section sources**
- [TestCase.php:44-392](file://backend/tests/TestCase.php#L44-L392)

### External Integrations (Midtrans) Testing Patterns
Approach:
- Replace MidtransClient with FakeMidtransClient to control responses deterministically.
- Simulate webhook signatures and statuses to exercise notification and status sync flows.
- Assert idempotent updates and correct state transitions.

```mermaid
flowchart TD
Start(["Webhook Received"]) --> VerifySig["Verify Signature"]
VerifySig --> Valid{"Valid?"}
Valid --> |No| Reject["Reject Request"]
Valid --> |Yes| MapStatus["Map External -> Internal Status"]
MapStatus --> Guard["Check Transition Guard"]
Guard --> Allowed{"Allowed?"}
Allowed --> |No| Error["Return Error"]
Allowed --> |Yes| Update["Update Transaction State"]
Update --> Log["Persist Log Entry"]
Log --> Done(["Done"])
```

**Diagram sources**
- [MidtransNotificationService.php](file://backend/app/Services/Midtrans/MidtransNotificationService.php)
- [MidtransStatusSyncService.php](file://backend/app/Services/Midtrans/MidtransStatusSyncService.php)

**Section sources**
- [MidtransNotificationService.php](file://backend/app/Services/Midtrans/MidtransNotificationService.php)
- [MidtransStatusSyncService.php](file://backend/app/Services/Midtrans/MidtransStatusSyncService.php)

## Dependency Analysis
Testing dependencies and tools:
- PHPUnit for running tests.
- Mockery for mocking.
- Faker for generating test data.
- Laravel Sanctum for API authentication in tests.
- Spatie Permission for role/permission checks.
- Pest PHP for expressive testing syntax in frontend-v2.

```mermaid
graph TB
P["PHPUnit"] --> T["Tests"]
M["Mockery"] --> T
F["Faker"] --> T
S["Sanctum"] --> T
R["Spatie Permission"] --> T
PE["Pest PHP"] --> TF["Frontend-v2 Tests"]
```

**Diagram sources**
- [composer.json:23-31](file://backend/composer.json#L23-L31)

**Section sources**
- [composer.json:23-31](file://backend/composer.json#L23-L31)

## Performance Considerations
- Use array cache/session/mail drivers in tests for speed.
- Keep database transactions small; rely on setUp cleanup to isolate tests.
- Avoid heavy file I/O; prefer in-memory assertions where possible.
- Run only relevant test subsets locally to reduce feedback loops.
- Public portal tests are lightweight and focus on configuration and rendering validation.

## Troubleshooting Guide
Common issues and resolutions:
- Permission cache leakage: Ensure permission cache is cleared at setUp; the base TestCase already resets it.
- Database contamination: Rely on setUp deletions for key tables; add missing tables if new ones are introduced.
- Authentication failures: Use base TestCase helpers to create authenticated users with tokens.
- External dependency flakiness: Replace with FakeMidtransClient and assert deterministic outcomes.
- Public portal configuration issues: Verify handayani-public config file exists and contains all required keys.
- Asset loading problems: Ensure Vite build process generates proper public assets and exclude admin-specific assets from public portal.

**Section sources**
- [TestCase.php:21-39](file://backend/tests/TestCase.php#L21-L39)
- [PublicConfigTest.php:1-48](file://frontend-v2/tests/Feature/PublicConfigTest.php#L1-48)
- [PublicPageTest.php:72-91](file://frontend-v2/tests/Feature/PublicPageTest.php#L72-91)

## Conclusion
A robust testing strategy for Handayani combines well-structured unit, feature, and integration tests across both backend and frontend-v2 applications, leveraging factories, seeders, and stubs to ensure reliability and speed. The addition of comprehensive public portal testing ensures configuration integrity and landing page functionality. By isolating external dependencies, enforcing strict test data hygiene, following consistent patterns for controllers, services, and models, and validating public portal configuration and rendering, the team can maintain high code quality and confidence in deployments.

## Appendices

### Running Tests and CI Setup
- Local execution:
  - Clear config and run tests via composer script for each application.
- Continuous integration:
  - Configure CI to install dependencies, migrate the testing database, and run the test suite for both applications.
  - Cache vendor and node modules to speed up builds.
  - Optionally generate and upload code coverage reports.
- Public portal specific:
  - Ensure public portal configuration is properly set up in test environment.
  - Verify Vite build process for public assets during CI pipeline.

**Section sources**
- [composer.json:57-60](file://backend/composer.json#L57-L60)

### Code Coverage Requirements
- Define minimum coverage thresholds per directory or globally.
- Exclude generated or third-party code from coverage.
- Integrate coverage reporting into CI to block merges below thresholds.
- Include public portal tests in coverage calculations for comprehensive validation.

### Performance and Load Testing Strategies
- Use lightweight unit tests for algorithmic performance checks.
- For load testing, consider dedicated tools outside PHPUnit (e.g., k6, Artillery) targeting API endpoints.
- Simulate realistic payloads and concurrency levels; measure latency and throughput.
- Public portal pages are lightweight and suitable for basic load testing to ensure responsive user experience.

### Public Portal Testing Best Practices
- Configuration-driven testing: Always validate that configuration changes don't break expected behavior.
- Asset separation: Ensure public portal doesn't load unnecessary admin assets.
- SEO validation: Regularly test SEO elements and meta information for optimal search engine visibility.
- Mobile responsiveness: Test Alpine.js interactive components for mobile navigation functionality.
- Cross-browser compatibility: Consider adding browser-specific tests for public portal rendering.

**Section sources**
- [PublicConfigTest.php:1-48](file://frontend-v2/tests/Feature/PublicConfigTest.php#L1-48)
- [PublicPageTest.php:1-118](file://frontend-v2/tests/Feature/PublicPageTest.php#L1-118)
- [handayani-public.php:1-13](file://frontend-v2/config/handayani-public.php#L1-13)