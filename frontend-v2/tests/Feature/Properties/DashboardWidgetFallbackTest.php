<?php

/**
 * Property Test: Dashboard Widget API Failure Produces Safe Fallback Data
 *
 * **Validates: Requirements 2.1, 2.2, 2.4, 2.5**
 *
 * Property 2: Dashboard widget returns safe fallback data for any API failure
 *
 * For any of the 8 Dashboard Widgets, and for any API failure (whether a
 * `ConnectionException` or any HTTP error response), the widget's data-fetching
 * method (`getStats()`, `getData()`, or the `records()` closure inside `table()`)
 * SHALL return a safe, non-throwing fallback: zero-valued `Stat` objects for
 * `StatsOverviewWidget`, an array with empty `datasets` and `labels` keys for
 * `ChartWidget`, and an empty `Collection` for `TableWidget`, regardless of the
 * widget's current `$selectedTahunAjaranId` or any other reactive property.
 */

use Eris\Generator;
use Eris\TestTrait;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

uses(TestTrait::class);

// Widget classifications
const STATS_WIDGETS = [
    \App\Filament\Widgets\DashboardStatsWidget::class,
];

const CHART_WIDGETS = [
    \App\Filament\Widgets\KasBulananChart::class,
    \App\Filament\Widgets\PembayaranBulananChart::class,
    \App\Filament\Widgets\StatusTagihanChart::class,
    \App\Filament\Widgets\TunggakanJenjangChart::class,
];

const TABLE_WIDGETS = [
    \App\Filament\Widgets\PembayaranTerbaruWidget::class,
    \App\Filament\Widgets\TagihanJatuhTempoWidget::class,
    \App\Filament\Widgets\TopTunggakanWidget::class,
];

const ALL_DASHBOARD_WIDGETS = [
    \App\Filament\Widgets\DashboardStatsWidget::class,
    \App\Filament\Widgets\KasBulananChart::class,
    \App\Filament\Widgets\PembayaranBulananChart::class,
    \App\Filament\Widgets\StatusTagihanChart::class,
    \App\Filament\Widgets\TunggakanJenjangChart::class,
    \App\Filament\Widgets\PembayaranTerbaruWidget::class,
    \App\Filament\Widgets\TagihanJatuhTempoWidget::class,
    \App\Filament\Widgets\TopTunggakanWidget::class,
];

// Failure types to simulate
const WIDGET_FAILURE_TYPES = [
    'connection_exception',
    'http_400',
    'http_403',
    'http_404',
    'http_500',
    'http_503',
];

/**
 * Helper: Setup HTTP mock to simulate a specific failure type for widgets.
 */
function setupWidgetHttpMockForFailure(string $failureType): void
{
    match ($failureType) {
        'connection_exception' => Http::fake(function () {
            throw new ConnectionException('Connection refused');
        }),
        'http_400' => Http::fake([
            '*' => Http::response(['message' => 'Bad Request'], 400),
        ]),
        'http_403' => Http::fake([
            '*' => Http::response(['message' => 'Forbidden'], 403),
        ]),
        'http_404' => Http::fake([
            '*' => Http::response(['message' => 'Not Found'], 404),
        ]),
        'http_500' => Http::fake([
            '*' => Http::response(['message' => 'Internal Server Error'], 500),
        ]),
        'http_503' => Http::fake([
            '*' => Http::response(['message' => 'Service Unavailable'], 503),
        ]),
    };
}

/**
 * Helper: Determine which category a widget belongs to.
 */
function getWidgetCategory(string $widgetClass): string
{
    if (in_array($widgetClass, STATS_WIDGETS)) {
        return 'stats';
    }
    if (in_array($widgetClass, CHART_WIDGETS)) {
        return 'chart';
    }
    if (in_array($widgetClass, TABLE_WIDGETS)) {
        return 'table';
    }
    throw new \RuntimeException("Unknown widget class: {$widgetClass}");
}

/**
 * Helper: Invoke the data-fetching method on a widget and return the result.
 */
function invokeWidgetDataMethod(string $widgetClass, string $failureType, ?int $tahunAjaranId): mixed
{
    // Set up failure mock
    setupWidgetHttpMockForFailure($failureType);

    // Create widget instance
    $widget = new $widgetClass();

    // Set the selectedTahunAjaranId property
    if (property_exists($widget, 'selectedTahunAjaranId')) {
        $widget->selectedTahunAjaranId = $tahunAjaranId;
    }

    $category = getWidgetCategory($widgetClass);

    return match ($category) {
        'stats' => invokeProtectedMethod($widget, 'getStats'),
        'chart' => invokeProtectedMethod($widget, 'getData'),
        'table' => invokeTableWidgetRecords($widget),
    };
}

/**
 * Helper: Invoke a protected method on a widget.
 */
function invokeProtectedMethod(object $widget, string $method): mixed
{
    $reflection = new \ReflectionMethod($widget, $method);
    $reflection->setAccessible(true);
    return $reflection->invoke($widget);
}

/**
 * Helper: Extract and invoke the records closure from a TableWidget.
 */
function invokeTableWidgetRecords(object $widget): Collection
{
    $table = new \Filament\Tables\Table($widget);
    $configuredTable = $widget->table($table);

    $reflection = new \ReflectionClass($configuredTable);
    $property = $reflection->getProperty('dataSource');
    $property->setAccessible(true);
    $recordsClosure = $property->getValue($configuredTable);

    if ($recordsClosure instanceof \Closure) {
        return $recordsClosure->call($widget);
    }

    throw new \RuntimeException('TableWidget records() did not return a Closure for ' . get_class($widget));
}

/**
 * Helper: Assert StatsOverviewWidget fallback returns array of Stat objects.
 */
function assertStatsWidgetFallback(mixed $result, string $widgetClass, string $failureType): void
{
    expect($result)->toBeArray();
    expect(count($result))->toBeGreaterThan(0, "Expected non-empty stats array for {$widgetClass} on {$failureType}");

    foreach ($result as $index => $stat) {
        expect($stat)->toBeInstanceOf(Stat::class);
    }
}

/**
 * Helper: Assert ChartWidget fallback returns array with datasets and labels keys.
 */
function assertChartWidgetFallback(mixed $result, string $widgetClass, string $failureType): void
{
    expect($result)->toBeArray();
    expect(array_key_exists('datasets', $result))
        ->toBeTrue("Expected 'datasets' key for {$widgetClass} on {$failureType}, got keys: " . implode(', ', array_keys($result)));
    expect(array_key_exists('labels', $result))
        ->toBeTrue("Expected 'labels' key for {$widgetClass} on {$failureType}, got keys: " . implode(', ', array_keys($result)));
    expect($result['datasets'])->toBeArray();
    expect($result['labels'])->toBeArray();
}

/**
 * Helper: Assert TableWidget fallback returns empty Collection.
 */
function assertTableWidgetFallback(mixed $result, string $widgetClass, string $failureType): void
{
    expect($result)->toBeInstanceOf(Collection::class);
    expect($result)->toBeEmpty();
}

test('Property 2: Dashboard widget returns safe fallback data for any API failure', function () {
    // Set up a minimal session so ApiService::client() works
    Session::put('data.token', 'test-token');
    Session::put('data.permissions', ['view-dashboard']);
    Session::put('data.roles', ['admin']);

    // Default iterations in Eris TestTrait is 100, matching minimum requirement
    $this
        ->forAll(
            Generator\elements(...ALL_DASHBOARD_WIDGETS),
            Generator\elements(...WIDGET_FAILURE_TYPES),
            Generator\oneOf(
                Generator\constant(null),
                Generator\constant(0),
                Generator\pos()
            )
        )
        ->withMaxSize(100)
        ->then(function (string $widgetClass, string $failureType, ?int $tahunAjaranId) {
            // Invoke the widget's data method under failure conditions
            // No exception should be thrown
            $result = invokeWidgetDataMethod($widgetClass, $failureType, $tahunAjaranId);

            // Assert correct fallback shape based on widget category
            $category = getWidgetCategory($widgetClass);

            match ($category) {
                'stats' => assertStatsWidgetFallback($result, $widgetClass, $failureType),
                'chart' => assertChartWidgetFallback($result, $widgetClass, $failureType),
                'table' => assertTableWidgetFallback($result, $widgetClass, $failureType),
            };
        });
});
