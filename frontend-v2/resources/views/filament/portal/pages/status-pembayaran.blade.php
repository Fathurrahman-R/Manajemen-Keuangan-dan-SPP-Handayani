<x-filament-panels::page>
    <div
        @if($this->isPolling)
            wire:poll.5s="pollStatus"
        @endif
        class="space-y-6"
    >
        {{-- Status Card --}}
        <x-filament::section>
            <div class="text-center py-6">
                {{-- Status Icon --}}
                @if($this->isSuccess())
                    <div class="mx-auto w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                @elseif($status === 'pending')
                    <div class="mx-auto w-16 h-16 bg-yellow-100 dark:bg-yellow-900/30 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-yellow-600 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                @elseif(in_array($status, ['deny', 'failure']))
                    <div class="mx-auto w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </div>
                @else
                    <div class="mx-auto w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                @endif

                {{-- Status Badge --}}
                @php
                    $colorMap = [
                        'yellow' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                        'green' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                        'red' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                        'gray' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                    ];
                    $badgeClass = $colorMap[$this->getStatusColor()] ?? $colorMap['gray'];
                @endphp
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $badgeClass }}">
                    {{ $this->getDisplayStatus() }}
                </span>

                {{-- Order ID --}}
                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                    Order ID: <span class="font-mono">{{ $order_id }}</span>
                </p>

                {{-- Polling indicator --}}
                @if($this->isPolling && $status === 'pending')
                    <p class="mt-2 text-xs text-gray-400 animate-pulse">
                        Mengecek status pembayaran...
                    </p>
                @endif

                {{-- Timeout message --}}
                @if(!$this->isPolling && $status === 'pending' && $pollCount >= 24)
                    <p class="mt-3 text-sm text-yellow-700 dark:text-yellow-400">
                        {{ __('midtrans.check_history', [], 'id') }}
                    </p>
                @endif
            </div>
        </x-filament::section>

        {{-- Transaction Details --}}
        @if(!empty($transaction))
            <x-filament::section>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Detail Transaksi</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('midtrans.payment_amount', [], 'id') }}</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            Rp {{ number_format($transaction['amount_paid'] ?? 0, 0, '', '.') }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('midtrans.admin_fee', [], 'id') }}</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            Rp {{ number_format($transaction['fee_amount'] ?? 0, 0, '', '.') }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('midtrans.total_payment', [], 'id') }}</p>
                        <p class="text-sm font-semibold text-primary-600">
                            Rp {{ number_format($transaction['gross_amount'] ?? 0, 0, '', '.') }}
                        </p>
                    </div>
                    @if(!empty($transaction['payment_type']))
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Metode Pembayaran</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ ucfirst(str_replace('_', ' ', $transaction['payment_type'])) }}
                            </p>
                        </div>
                    @endif
                </div>
            </x-filament::section>
        @endif

        {{-- Actions --}}
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            @if($this->isSuccess() && !empty($transaction['kode_pembayaran']))
                <a
                    href="{{ env('API_URL') }}/pembayaran/kwitansi/{{ $transaction['kode_pembayaran'] }}?token={{ session('data.token') }}"
                    target="_blank"
                    class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    {{ __('midtrans.download_receipt', [], 'id') }}
                </a>
            @endif

            <a
                href="/{{ config('handayani.portal.path', 'portal') }}/riwayat-pembayaran"
                class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition"
            >
                Lihat Riwayat Pembayaran
            </a>
        </div>

        {{-- Error State --}}
        @if($loadError)
            <x-filament::section>
                <div class="text-center text-red-600 dark:text-red-400">
                    <p class="text-sm">Gagal memuat status transaksi. Silakan refresh halaman.</p>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
