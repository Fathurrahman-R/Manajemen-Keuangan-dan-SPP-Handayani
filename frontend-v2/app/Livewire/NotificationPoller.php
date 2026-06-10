<?php

namespace App\Livewire;

use App\Services\NotificationSyncService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class NotificationPoller extends Component
{
    public function mount(): void
    {
        // Sync on first load
        $this->poll();
    }

    public function poll(): void
    {
        try {
            $userId = auth()->id();
            if (!$userId) {
                return;
            }

            // Read directly from the backend's notifications table (shared DB)
            $notifications = DB::table('notifications')
                ->where('user_id', $userId)
                ->orderByDesc('created_at')
                ->limit(50)
                ->get()
                ->map(fn($n) => [
                    'id' => $n->id,
                    'title' => $n->title,
                    'message' => $n->message,
                    'is_read' => (bool) $n->is_read,
                    'created_at' => $n->created_at,
                ])
                ->toArray();

            if (!empty($notifications)) {
                NotificationSyncService::syncFromApi($notifications, $userId);
            }
        } catch (\Throwable $e) {
            // Silent fallback
        }
    }

    public function render()
    {
        return view('livewire.notification-poller');
    }
}
