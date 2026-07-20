<?php

namespace Tests\Feature\Workflow;

use Illuminate\Support\Carbon;
use Tests\TestCase;

class PengeluaranWorkflowEmailTest extends TestCase
{
    /**
     * Regression for WF-006: a request rejected once and then resubmitted +
     * auto-approved must still show the earlier rejection in "Riwayat Proses"
     * (previously hidden by @elseif), must label the auto-approval as done by
     * "Sistem" rather than the requester (previously showed the requester's
     * name because the log's user_id is the requester for schema reasons),
     * and must NOT mislabel the auto-approval note as "Alasan Penolakan".
     */
    public function test_email_shows_prior_rejection_and_system_auto_approval_correctly(): void
    {
        $pengeluaranRequest = (object) [
            'uraian' => 'Pembelian ATK',
            'jumlah' => 500000,
            'tanggal_kebutuhan' => '2026-08-01',
            'kategori_pengeluaran' => null,
            'created_at' => Carbon::parse('2026-07-01 08:00:00'),
        ];

        $rejectedLog = (object) [
            'note' => null,
            'user' => (object) ['name' => 'Kepala Yayasan', 'username' => 'yayasan'],
            'created_at' => Carbon::parse('2026-07-02 09:00:00'),
        ];

        $approvedLog = (object) [
            'note' => 'Auto-approved: jumlah dalam batas threshold',
            'user' => (object) ['name' => 'Admin Pengaju', 'username' => 'admin_pengaju'],
            'created_at' => Carbon::parse('2026-07-03 10:00:00'),
        ];

        $html = view('emails.notifications.pengeluaran-workflow', [
            'pengeluaranRequest' => $pengeluaranRequest,
            'event' => 'approved',
            'reason' => null,
            'requesterName' => 'Admin Pengaju',
            'title' => 'Request Disetujui',
            'notificationMessage' => 'Request pengeluaran "Pembelian ATK" senilai Rp 500.000 telah disetujui. Silakan lakukan pencairan.',
            'history' => [
                'submitted' => null,
                'approved' => $approvedLog,
                'rejected' => $rejectedLog,
                'disbursed' => null,
            ],
        ])->render();

        $this->assertStringContainsString('Ditolak oleh:', $html);
        $this->assertStringContainsString('Kepala Yayasan', $html);
        $this->assertStringContainsString('Disetujui oleh:', $html);
        $this->assertStringContainsString('Sistem (disetujui otomatis)', $html);
        $this->assertStringNotContainsString('Alasan Penolakan', $html);
    }
}
