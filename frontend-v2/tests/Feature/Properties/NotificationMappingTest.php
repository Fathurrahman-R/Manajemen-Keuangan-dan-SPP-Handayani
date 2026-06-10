<?php

/**
 * Property Test: Notification Field Mapping Preserves Required Fields and Correctly Converts `is_read`
 *
 * **Validates: Requirements 11.2, 11.3**
 *
 * Property 3: Notification mapping preserves fields and converts is_read correctly
 *
 * For any backend notification object with fields {id, title, message, is_read, created_at},
 * the NotificationSyncService::syncFromApi() mapping function SHALL produce a
 * DatabaseNotification-compatible record where:
 * - The `data` JSON contains both a `title` key equal to the source `title`
 *   and a `message` key equal to the source `message`
 * - When `is_read` is `true`, the resulting `read_at` SHALL be a non-null timestamp
 * - When `is_read` is `false`, the resulting `read_at` SHALL be `null`
 * - These conditions hold for any string value of `title` and `message` (including empty
 *   strings, Unicode, and very long strings)
 */

use App\Services\NotificationSyncService;
use Eris\Generator;
use Eris\TestTrait;
use Illuminate\Notifications\DatabaseNotification;

uses(TestTrait::class);

/**
 * Helper: Generate a random string including various edge cases (empty, Unicode, long strings).
 */
function notificationStringGenerator(): \Eris\Generator
{
    return Generator\oneOf(
        Generator\constant(''),
        Generator\constant(' '),
        Generator\constant('Simple ASCII text'),
        Generator\constant('日本語テスト'),
        Generator\constant('Tes pesan dengan karakter spesial: <>&"\''),
        Generator\constant('🎉🚀💡 Emoji content'),
        Generator\constant("Multi\nline\nstring"),
        Generator\constant(str_repeat('A', 1000)),
        Generator\constant(str_repeat('你好世界', 250)),
        Generator\string(),
        Generator\constant('Tagihan SPP bulan Juli telah diterbitkan.'),
        Generator\constant('Pembayaran berhasil dikonfirmasi'),
        Generator\constant("Tab\there"),
        Generator\constant('Spésïàl çhàráctérs'),
        Generator\constant('مرحبا بالعالم'),
        Generator\constant('Null bytes: ' . chr(0) . ' test'),
    );
}

/**
 * Helper: Generate a created_at timestamp string.
 */
function notificationCreatedAtGenerator(): \Eris\Generator
{
    return Generator\oneOf(
        Generator\constant('2025-07-01T10:00:00.000000Z'),
        Generator\constant('2025-01-15T08:30:00.000000Z'),
        Generator\constant('2024-12-31T23:59:59.000000Z'),
        Generator\constant('2025-06-15T14:22:33.000000Z'),
        Generator\constant('2025-03-20T00:00:00.000000Z'),
    );
}

test('Property 3: Notification mapping preserves fields and converts is_read correctly', function () {
    // Track all calls to DatabaseNotification::updateOrCreate
    $capturedCalls = [];

    // Mock the DatabaseNotification model to capture updateOrCreate arguments
    $mock = Mockery::mock('alias:' . DatabaseNotification::class);
    $mock->shouldReceive('updateOrCreate')
        ->andReturnUsing(function (array $attributes, array $values) use (&$capturedCalls) {
            $capturedCalls[] = [
                'attributes' => $attributes,
                'values' => $values,
            ];
            // Return a mock model instance
            return new class($attributes, $values) {
                public $id;
                public $data;
                public $read_at;

                public function __construct(array $attributes, array $values)
                {
                    $this->id = $attributes['id'];
                    $this->data = $values['data'] ?? [];
                    $this->read_at = $values['read_at'] ?? null;
                }
            };
        });

    $this
        ->minimumEvaluationRatio(0.5)
        ->forAll(
            Generator\pos(),                        // id (positive int)
            notificationStringGenerator(),          // title
            notificationStringGenerator(),          // message
            Generator\bool(),                       // is_read
            notificationCreatedAtGenerator()        // created_at
        )
        ->withMaxSize(200)
        ->then(function (int $id, string $title, string $message, bool $isRead, string $createdAt) use (&$capturedCalls) {
            // Reset captured calls for this iteration
            $capturedCalls = [];

            $userId = 1;

            // Build the API notification array as the backend would return it
            $apiNotification = [
                'id'         => $id,
                'title'      => $title,
                'message'    => $message,
                'is_read'    => $isRead,
                'created_at' => $createdAt,
            ];

            // Call the service under test
            NotificationSyncService::syncFromApi([$apiNotification], $userId);

            // Verify exactly one call was made
            expect($capturedCalls)->toHaveCount(1,
                "Expected exactly one updateOrCreate call for notification id={$id}"
            );

            $call = $capturedCalls[0];
            $attributes = $call['attributes'];
            $values = $call['values'];

            // Assert the stable ID uses the correct format
            expect($attributes['id'])->toBe('backend-' . $id);

            // Assert data->title equals input title
            expect($values['data']['title'])->toBe($title,
                "Mapped data.title should equal input title for id={$id}"
            );

            // Assert data->message equals input message
            expect($values['data']['message'])->toBe($message,
                "Mapped data.message should equal input message for id={$id}"
            );

            // Assert is_read conversion to read_at
            if ($isRead) {
                // is_read = true ⟹ read_at is non-null
                expect($values['read_at'])->not->toBeNull(
                    "When is_read is true, read_at should be non-null for id={$id}"
                );
            } else {
                // is_read = false ⟹ read_at is null
                expect($values['read_at'])->toBeNull(
                    "When is_read is false, read_at should be null for id={$id}"
                );
            }
        });
});
