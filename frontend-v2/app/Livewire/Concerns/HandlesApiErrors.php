<?php

namespace App\Livewire\Concerns;

use Filament\Notifications\Notification;

trait HandlesApiErrors
{
    /**
     * Handle API error response by extracting the message and showing a Filament notification.
     */
    protected function handleApiError($response): void
    {
        try {
            $json = $response->json();
            $errors = $json['errors'] ?? [];
            $message = 'Terjadi kesalahan pada server.';

            if (isset($errors['message'])) {
                $msg = $errors['message'];
                $message = is_array($msg) ? $msg[0] : $msg;
            } elseif (! empty($errors)) {
                $firstKey = array_key_first($errors);
                $message = $firstKey
                    ? (is_array($errors[$firstKey]) ? $errors[$firstKey][0] : $errors[$firstKey])
                    : $message;
            } elseif (isset($json['message'])) {
                $message = $json['message'];
            }

            Notification::make()
                ->title($message)
                ->danger()
                ->persistent()
                ->send();
        } catch (\Throwable $e) {
            $this->notifyUnexpectedError();
        }
    }

    /**
     * Show a connection error notification.
     */
    protected function notifyConnectionError(): void
    {
        Notification::make()
            ->title('Server tidak dapat dihubungi')
            ->body('Pastikan server backend berjalan dan coba lagi.')
            ->danger()
            ->persistent()
            ->send();
    }

    /**
     * Show a generic unexpected error notification.
     */
    protected function notifyUnexpectedError(): void
    {
        Notification::make()
            ->title('Terjadi kesalahan yang tidak terduga')
            ->body('Silakan coba lagi atau hubungi support.')
            ->danger()
            ->persistent()
            ->send();
    }
}
