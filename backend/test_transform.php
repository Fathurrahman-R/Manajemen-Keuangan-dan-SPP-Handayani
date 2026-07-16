<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Simulate Livewire request
// Just testing the payload transformation inside save()
$state = [
    'reminder_days_before' => ['7', '3', '1']
];

$reminderDays = [];
$rawReminderDays = $state['reminder_days_before'] ?? [];

if (is_string($rawReminderDays)) {
    $decoded = json_decode($rawReminderDays, true);
    $rawReminderDays = is_array($decoded) ? $decoded : explode(',', $rawReminderDays);
}

if (is_array($rawReminderDays) && !empty($rawReminderDays)) {
    foreach ($rawReminderDays as $v) {
        $val = intval(preg_replace('/[^0-9]/', '', (string) $v));
        if ($val > 0 && $val <= 90) {
            $reminderDays[] = $val;
        }
    }
}

if (empty($reminderDays)) {
    $reminderDays = [7, 3, 1];
} else {
    $reminderDays = array_unique($reminderDays);
    rsort($reminderDays);
}

dump($reminderDays);
