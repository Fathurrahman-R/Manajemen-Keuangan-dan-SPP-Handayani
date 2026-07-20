<?php

namespace Tests\Feature\Workflow;

use App\Models\Branch;
use App\Models\EmailOptOut;
use App\Models\PengeluaranRequest;
use App\Models\User;
use App\Notifications\PengeluaranWorkflowNotification;
use App\Services\WorkflowNotificationService;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WorkflowEmailOptOutTest extends TestCase
{
    protected function tearDown(): void
    {
        // TestCase::setUp()'s cleanup list predates these tables.
        \Illuminate\Support\Facades\DB::table('email_opt_outs')->delete();
        \Illuminate\Support\Facades\DB::table('notification_logs')->delete();
        \Illuminate\Support\Facades\DB::table('pengeluaran_requests')->delete();

        parent::tearDown();
    }

    /**
     * Regression for WF-009: WorkflowNotificationService never checked
     * EmailOptOut, so a requester who opted out of "workflow" emails (via the
     * unsubscribe link) still received every approval/rejection/disbursement
     * notification.
     */
    public function test_requester_who_opted_out_of_workflow_emails_is_not_notified(): void
    {
        Notification::fake();

        $branch = Branch::factory()->create();
        $requester = User::factory()->admin()->create([
            'branch_id' => $branch->id,
            'email' => 'requester@example.com',
        ]);

        EmailOptOut::create([
            'email' => 'requester@example.com',
            'notification_type' => 'workflow',
            'token' => 'test-token-'.uniqid(),
        ]);

        $request = PengeluaranRequest::create([
            'uraian' => 'Beli ATK',
            'jumlah' => 100000,
            'tanggal_kebutuhan' => now()->addDays(3)->toDateString(),
            'status' => 'submitted',
            'requester_id' => $requester->id,
            'branch_id' => $branch->id,
        ]);

        app(WorkflowNotificationService::class)->notifyRequester($request, 'approved');

        Notification::assertNothingSent();

        // Regression: notifyRequester() used to bulk-filter opted-out
        // recipients THEN early-return with only a file Log::info() when the
        // resulting list was empty — no notification_logs row at all. That
        // made an opted-out requester's next reject/approve/disburse look like
        // it silently failed to send with zero trace on the Log Notifikasi
        // page. It must show up as `skipped`/`opted_out`, same as an
        // opted-out approver already does in notifyApprovers().
        $this->assertDatabaseHas('notification_logs', [
            'pengeluaran_request_id' => $request->id,
            'recipient_email' => 'requester@example.com',
            'notification_type' => 'workflow',
            'workflow_event' => 'approved',
            'status' => 'skipped',
            'reason' => 'opted_out',
        ]);
    }

    public function test_requester_without_opt_out_is_notified(): void
    {
        Notification::fake();

        $branch = Branch::factory()->create();
        $requester = User::factory()->admin()->create([
            'branch_id' => $branch->id,
            'email' => 'notopted@example.com',
        ]);

        $request = PengeluaranRequest::create([
            'uraian' => 'Beli ATK',
            'jumlah' => 100000,
            'tanggal_kebutuhan' => now()->addDays(3)->toDateString(),
            'status' => 'submitted',
            'requester_id' => $requester->id,
            'branch_id' => $branch->id,
        ]);

        app(WorkflowNotificationService::class)->notifyRequester($request, 'approved');

        Notification::assertSentOnDemand(PengeluaranWorkflowNotification::class);
    }
}
