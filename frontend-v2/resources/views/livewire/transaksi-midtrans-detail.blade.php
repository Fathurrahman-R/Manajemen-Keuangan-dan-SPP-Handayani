<div class="flex flex-col gap-y-6">
    @if ($loadError)
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
            <p class="text-danger-600 dark:text-danger-400">
                Gagal memuat data transaksi. Silakan coba lagi.
            </p>
        </div>
    @else
        {{-- Transaction Detail Section --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-950 dark:text-white">
                    Detail Transaksi
                </h2>

                @if (($transaction['status'] ?? '') === 'pending')
                    {{ $this->syncAction }}
                @endif
            </div>

            @if ($transaction)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Order ID</p>
                        <p class="text-sm text-gray-950 dark:text-white font-mono">{{ $transaction['order_id'] ?? '-' }}</p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Kode Tagihan</p>
                        <p class="text-sm text-gray-950 dark:text-white">{{ $transaction['kode_tagihan'] ?? '-' }}</p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Nama Siswa</p>
                        <p class="text-sm text-gray-950 dark:text-white">{{ $transaction['nama_siswa'] ?? '-' }}</p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">NIS</p>
                        <p class="text-sm text-gray-950 dark:text-white">{{ $transaction['nis'] ?? '-' }}</p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</p>
                        <span @class([
                            'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset',
                            'bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30' => in_array($transaction['status'] ?? '', ['settlement', 'capture']),
                            'bg-warning-50 text-warning-700 ring-warning-600/20 dark:bg-warning-400/10 dark:text-warning-400 dark:ring-warning-400/30' => ($transaction['status'] ?? '') === 'pending',
                            'bg-danger-50 text-danger-700 ring-danger-600/20 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30' => in_array($transaction['status'] ?? '', ['deny', 'cancel', 'expire', 'failure']),
                            'bg-info-50 text-info-700 ring-info-600/20 dark:bg-info-400/10 dark:text-info-400 dark:ring-info-400/30' => in_array($transaction['status'] ?? '', ['refund', 'partial_refund']),
                            'bg-gray-50 text-gray-700 ring-gray-600/20 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/30' => !in_array($transaction['status'] ?? '', ['settlement', 'capture', 'pending', 'deny', 'cancel', 'expire', 'failure', 'refund', 'partial_refund']),
                        ])>
                            {{ ucfirst($transaction['status'] ?? '-') }}
                        </span>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Metode Pembayaran</p>
                        <p class="text-sm text-gray-950 dark:text-white">{{ $transaction['payment_type'] ?? '-' }}</p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Jumlah Bayar</p>
                        <p class="text-sm text-gray-950 dark:text-white">Rp {{ number_format($transaction['amount_paid'] ?? 0, 0, ',', '.') }}</p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Biaya Admin</p>
                        <p class="text-sm text-gray-950 dark:text-white">Rp {{ number_format($transaction['fee_amount'] ?? 0, 0, ',', '.') }}</p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total (Gross)</p>
                        <p class="text-sm text-gray-950 dark:text-white font-semibold">Rp {{ number_format($transaction['gross_amount'] ?? 0, 0, ',', '.') }}</p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Mata Uang</p>
                        <p class="text-sm text-gray-950 dark:text-white">{{ $transaction['currency'] ?? 'IDR' }}</p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Snap Token</p>
                        <p class="text-sm text-gray-950 dark:text-white font-mono">{{ $transaction['snap_token'] ?? '-' }}</p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Snap Redirect URL</p>
                        <p class="text-sm text-gray-950 dark:text-white break-all">{{ $transaction['snap_redirect_url'] ?? '-' }}</p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Signature Key</p>
                        <p class="text-sm text-gray-950 dark:text-white font-mono">{{ $transaction['signature_key'] ?? '-' }}</p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Server Key</p>
                        <p class="text-sm text-gray-950 dark:text-white font-mono">{{ $transaction['server_key'] ?? '-' }}</p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Cabang</p>
                        <p class="text-sm text-gray-950 dark:text-white">{{ $transaction['branch']['location'] ?? $transaction['branch_id'] ?? '-' }}</p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Initiator</p>
                        <p class="text-sm text-gray-950 dark:text-white">{{ $transaction['initiator']['username'] ?? $transaction['initiator_user_id'] ?? '-' }}</p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Expired At</p>
                        <p class="text-sm text-gray-950 dark:text-white">{{ $transaction['expired_at'] ? \Carbon\Carbon::parse($transaction['expired_at'])->format('d/m/Y H:i:s') : '-' }}</p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Paid At</p>
                        <p class="text-sm text-gray-950 dark:text-white">{{ isset($transaction['paid_at']) ? \Carbon\Carbon::parse($transaction['paid_at'])->format('d/m/Y H:i:s') : '-' }}</p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Dibuat</p>
                        <p class="text-sm text-gray-950 dark:text-white">{{ isset($transaction['created_at']) ? \Carbon\Carbon::parse($transaction['created_at'])->format('d/m/Y H:i:s') : '-' }}</p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Diperbarui</p>
                        <p class="text-sm text-gray-950 dark:text-white">{{ isset($transaction['updated_at']) ? \Carbon\Carbon::parse($transaction['updated_at'])->format('d/m/Y H:i:s') : '-' }}</p>
                    </div>
                </div>
            @endif
        </div>

        {{-- Audit Log Section --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
            <h2 class="text-xl font-bold text-gray-950 dark:text-white mb-4">
                Audit Log
            </h2>

            @php
                $formattedLogs = $this->getFormattedLogs();
            @endphp

            @if (count($formattedLogs) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-800 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-3">Arah</th>
                                <th class="px-4 py-3">HTTP Status</th>
                                <th class="px-4 py-3">Payload (masked)</th>
                                <th class="px-4 py-3">Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($formattedLogs as $log)
                                <tr class="border-b dark:border-gray-700">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span @class([
                                            'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset',
                                            'bg-blue-50 text-blue-700 ring-blue-600/20' => str_contains($log['direction'], 'Outbound'),
                                            'bg-green-50 text-green-700 ring-green-600/20' => str_contains($log['direction'], 'Inbound'),
                                        ])>
                                            {{ $log['direction'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 font-mono">{{ $log['http_status'] }}</td>
                                    <td class="px-4 py-3">
                                        <code class="text-xs break-all">{{ $log['payload_snippet'] }}</code>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">{{ $log['created_at'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Belum ada log untuk transaksi ini.
                </p>
            @endif
        </div>
    @endif

    <x-filament-actions::modals />
</div>
