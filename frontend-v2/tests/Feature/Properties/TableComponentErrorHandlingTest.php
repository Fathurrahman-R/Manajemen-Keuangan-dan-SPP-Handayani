<?php

/**
 * Property Test: Table Component Error Handling is Unconditional
 *
 * **Validates: Requirements 1.1, 1.2, 1.4, 1.5**
 *
 * Property 1: Table component records() always returns [] on any API failure
 *
 * For any Table_Component that uses the `HandlesApiErrors` trait, and for any
 * API failure (whether a `ConnectionException`, any HTTP 4xx/5xx response, or
 * any other `Throwable`), the `records()` closure SHALL return an empty array
 * or empty Collection/Paginator for every generated combination.
 */

use Eris\Generator;
use Eris\TestTrait;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

uses(TestTrait::class);

// The 12 Table components that must handle errors gracefully
const TABLE_COMPONENTS = [
    \App\Livewire\DataCategory::class,
    \App\Livewire\DataKelas::class,
    \App\Livewire\DataSiswa::class,
    \App\Livewire\DataWali::class,
    \App\Livewire\BranchManagement::class,
    \App\Livewire\JenisTagihan::class,
    \App\Livewire\UserManagement::class,
    \App\Livewire\RoleManagement::class,
    \App\Livewire\TahunAjaranManagement::class,
    \App\Livewire\KasHarian::class,
    \App\Livewire\RekapBulanan::class,
    \App\Livewire\PengeluaranRequest::class,
];

// Failure types to simulate
const FAILURE_TYPES = [
    'connection_exception',
    'http_400',
    'http_500',
    'runtime_exception',
];

/**
 * Map each component class to its known records() closure parameter signature.
 * This avoids needing to parse the table() method which may contain Actions
 * with Selects that also call the API.
 */
const COMPONENT_RECORDS_PARAMS = [
    // Components that use simple array return: (search, sortColumn, sortDirection)
    \App\Livewire\DataCategory::class => 'simple_search',
    \App\Livewire\DataKelas::class => 'simple_search',
    \App\Livewire\DataWali::class => 'simple_search',
    \App\Livewire\BranchManagement::class => 'simple_search',
    \App\Livewire\JenisTagihan::class => 'simple_search',
    // Components that use paginated return: (search, page, recordsPerPage, sortColumn, sortDirection)
    \App\Livewire\DataSiswa::class => 'paginated_search',
    \App\Livewire\UserManagement::class => 'paginated_search',
    \App\Livewire\RoleManagement::class => 'paginated_search',
    \App\Livewire\TahunAjaranManagement::class => 'paginated_search',
    // Components with filters: (page, recordsPerPage, filters, sortColumn, sortDirection)
    \App\Livewire\KasHarian::class => 'paginated_filters',
    \App\Livewire\RekapBulanan::class => 'paginated_filters',
    \App\Livewire\PengeluaranRequest::class => 'paginated_filters',
];

/**
 * Helper: Setup HTTP mock to simulate a specific failure type.
 */
function setupHttpMockForFailure(string $failureType): void
{
    match ($failureType) {
        'connection_exception' => Http::fake(function () {
            throw new ConnectionException('Connection refused');
        }),
        'http_400' => Http::fake([
            '*' => Http::response(['message' => 'Bad Request', 'errors' => []], 400),
        ]),
        'http_500' => Http::fake([
            '*' => Http::response(['message' => 'Internal Server Error'], 500),
        ]),
        'runtime_exception' => Http::fake(function () {
            throw new \RuntimeException('Unexpected runtime error');
        }),
    };
}

/**
 * Helper: Create a component instance with required default properties.
 */
function createComponentInstance(string $componentClass): object
{
    $component = new $componentClass();

    // Set default public properties that some components need
    if (property_exists($component, 'activeTab')) {
        $component->activeTab = 'KB';
    }
    if (property_exists($component, 'perPage')) {
        $component->perPage = 10;
    }
    if (property_exists($component, 'currentPage')) {
        $component->currentPage = 1;
    }
    if (property_exists($component, 'currentMonthYear')) {
        $component->currentMonthYear = '2025-01';
    }
    if (property_exists($component, 'filterJenjang')) {
        $component->filterJenjang = '';
    }
    if (property_exists($component, 'selectedTahunAjaranId')) {
        $component->selectedTahunAjaranId = null;
    }

    return $component;
}

/**
 * Helper: Build the records closure from a component and invoke it.
 *
 * Instead of calling the full table() method (which builds actions, columns, etc.
 * that may also call the API), we extract only the records() closure by creating
 * a minimal Table and calling table(), then reading the dataSource property.
 *
 * We mock HTTP AFTER the table is configured, ensuring the failure only affects
 * the records() closure invocation.
 */
function extractAndInvokeRecordsClosure(object $component, string $failureType, string $search, int $page, int $perPage): mixed
{
    // Build the table configuration BEFORE setting up the failure mock.
    // This allows Select::make() options etc. to be set up without failing.
    // We need to allow API calls during table building (they won't happen
    // during configuration, only during rendering).
    Http::fake(['*' => Http::response(['data' => [], 'meta' => ['total' => 0]], 200)]);

    $table = new \Filament\Tables\Table($component);
    $configuredTable = $component->table($table);

    // Extract the dataSource (records closure)
    $reflection = new \ReflectionClass($configuredTable);
    $property = $reflection->getProperty('dataSource');
    $property->setAccessible(true);
    $recordsClosure = $property->getValue($configuredTable);

    if (!$recordsClosure instanceof \Closure) {
        throw new \RuntimeException('records() did not return a Closure for ' . get_class($component));
    }

    // NOW set up the failure mock
    setupHttpMockForFailure($failureType);

    // Determine the signature type and invoke accordingly
    $closureReflection = new \ReflectionFunction($recordsClosure);
    $params = $closureReflection->getParameters();
    $paramNames = array_map(fn($p) => $p->getName(), $params);

    // Build arguments based on actual parameter names
    $args = [];
    foreach ($paramNames as $name) {
        $args[] = match ($name) {
            'search' => $search,
            'page' => $page,
            'recordsPerPage' => $perPage,
            'filters' => ['date' => ['bulan' => null, 'tahun' => null], 'status' => ['value' => null]],
            'sortColumn' => null,
            'sortDirection' => null,
            default => null,
        };
    }

    return $recordsClosure->call($component, ...$args);
}

/**
 * Helper: Assert that a result is "empty" - either an empty array,
 * empty Collection, or a LengthAwarePaginator with 0 items.
 */
function assertResultIsEmpty(mixed $result, string $componentClass, string $failureType): void
{
    if ($result instanceof LengthAwarePaginator) {
        expect($result->total())->toBe(0, "Expected 0 total for {$componentClass} on {$failureType}");
        expect($result->items())->toBeEmpty("Expected empty items for {$componentClass} on {$failureType}");
    } elseif ($result instanceof \Illuminate\Support\Collection) {
        expect($result)->toBeEmpty("Expected empty collection for {$componentClass} on {$failureType}");
    } elseif (is_array($result)) {
        expect($result)->toBeEmpty("Expected empty array for {$componentClass} on {$failureType}");
    } else {
        $type = is_object($result) ? get_class($result) : gettype($result);
        throw new \RuntimeException("Unexpected return type {$type} for {$componentClass}");
    }
}

test('Property 1: Table component records() always returns empty on any API failure', function () {
    // Set up a minimal session so ApiService::client() and permissions work
    Session::put('data.token', 'test-token');
    Session::put('data.permissions', []);
    Session::put('data.roles', []);

    $this
        ->forAll(
            Generator\elements(...TABLE_COMPONENTS),
            Generator\elements(...FAILURE_TYPES),
            Generator\suchThat(
                fn($s) => is_string($s) && strlen($s) <= 100,
                Generator\oneOf(
                    Generator\constant(''),
                    Generator\string(),
                    Generator\elements('test', 'siswa', 'admin', '日本語', '   ', 'a')
                )
            ),
            Generator\choose(1, 50),  // page
            Generator\elements(5, 10, 25, 50),  // perPage
        )
        ->withMaxSize(100)
        ->then(function (string $componentClass, string $failureType, string $search, int $page, int $perPage) {
            // Create a fresh component instance
            $component = createComponentInstance($componentClass);

            // Extract the records closure and invoke it with failure mock active
            $result = extractAndInvokeRecordsClosure($component, $failureType, $search, $page, $perPage);

            // Assert result is empty
            assertResultIsEmpty($result, $componentClass, $failureType);
        });
});
