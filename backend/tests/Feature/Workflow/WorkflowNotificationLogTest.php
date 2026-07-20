<?php

namespace Tests\Feature\Workflow;

use App\Models\Branch;
use App\Models\NotificationLog;
use App\Models\PengeluaranRequest;
use App\Models\User;
use App\Notifications\PengeluaranWorkflowNotification;
use App\Services\Notifications\NotificationService;
use App\Services\WorkflowNotificationService;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WorkflowNotificationLogTest extends TestCase
{
    protected function tearDown(): void
    {
        \Illuminate\Support\Facades\DB::table('notification_logs')->delete();
        \Illuminate\Support\Facades\DB::table('pengeluaran_requests')->delete();
        \Illuminate\Support\Facades\DB::table('email_opt_outs')->delete();

        parent::tearDown();
    }

    private function makeRequest(): array
    {
        $branch = Branch::factory()->create();
        $requester = User::factory()->admin()->create([
            'branch_id' => $branch->id,
            'email' => 'requester@example.com',
        ]);

        $request = PengeluaranRequest::create([
            'uraian' => 'Beli ATK',
            'jumlah' => 100000,
            'tanggal_kebutuhan' => now()->addDays(3)->toDateString(),
            'status' => 'submitted',
            'requester_id' => $requester->id,
            'branch_id' => $branch->id,
        ]);

        return [$branch, $requester, $request];
    }

    /**
     * Regression for WF-008 (visibility): WorkflowNotificationService never
     * called NotificationLog::create() — the Log Notifikasi page was always
     * empty for approval-workflow events.
     */
    public function test_notify_requester_logs_sent_notification(): void
    {
        Notification::fake();
        [, , $request] = $this->makeRequest();

        app(WorkflowNotificationService::class)->notifyRequester($request, 'approved');

        $this->assertDatabaseHas('notification_logs', [
            'pengeluaran_request_id' => $request->id,
            'workflow_event' => 'approved',
            'notification_type' => 'workflow',
            'status' => 'sent',
            'recipient_email' => 'requester@example.com',
        ]);
    }

    /**
     * PengeluaranWorkflowNotification implements ShouldQueue — dispatch
     * succeeding only means the job was queued, not that mail delivery
     * succeeded. Previously the log was always written `sent` right after
     * dispatch and nothing ever corrected it if the queued job itself later
     * failed (e.g. SMTP unreachable) after exhausting its 3 retries — that
     * failure only ever reached Laravel's `failed_jobs` table, invisible on
     * the Log Notifikasi page. `withLogId()` + `failed()` closes that gap;
     * this simulates what the queue worker does when a job's retries are
     * exhausted (calls the notification's `failed()` with the exception).
     */
    public function test_queued_send_failure_corrects_log_from_sent_to_failed(): void
    {
        [, , $request] = $this->makeRequest();

        $log = NotificationLog::create([
            'branch_id' => $request->branch_id,
            'recipient_email' => 'requester@example.com',
            'notification_type' => 'workflow',
            'pengeluaran_request_id' => $request->id,
            'workflow_event' => 'approved',
            'status' => 'sent',
        ]);

        $notification = (new PengeluaranWorkflowNotification($request, 'approved'))
            ->withLogId($log->id);

        $notification->failed(new \RuntimeException('Connection could not be established with host smtp.example.com'));

        $this->assertDatabaseHas('notification_logs', [
            'id' => $log->id,
            'status' => 'failed',
            'error_message' => 'Connection could not be established with host smtp.example.com',
        ]);
    }

    /**
     * failed() must not touch a log row that isn't `sent` — e.g. one a
     * concurrent path already marked `failed`/`skipped` for a different
     * reason, or (defensively) if notificationLogId is somehow stale.
     */
    public function test_queued_send_failure_does_not_overwrite_non_sent_log(): void
    {
        [, , $request] = $this->makeRequest();

        $log = NotificationLog::create([
            'branch_id' => $request->branch_id,
            'recipient_email' => 'requester@example.com',
            'notification_type' => 'workflow',
            'pengeluaran_request_id' => $request->id,
            'workflow_event' => 'approved',
            'status' => 'skipped',
            'reason' => 'opted_out',
        ]);

        $notification = (new PengeluaranWorkflowNotification($request, 'approved'))
            ->withLogId($log->id);

        $notification->failed(new \RuntimeException('should not apply'));

        $this->assertDatabaseHas('notification_logs', [
            'id' => $log->id,
            'status' => 'skipped',
            'reason' => 'opted_out',
        ]);
    }

    public function test_notify_requester_logs_failed_notification(): void
    {
        [, , $request] = $this->makeRequest();

        // AnonymousNotifiable::notify() resolves the Dispatcher contract
        // directly (Notification::route() is a plain static method, not a
        // facade-proxied call — Notification::fake()/shouldReceive() can't
        // intercept it), so simulate a send failure at the Dispatcher itself.
        $dispatcher = \Mockery::mock(\Illuminate\Contracts\Notifications\Dispatcher::class);
        $dispatcher->shouldReceive('send')
            ->once()
            ->andThrow(new \RuntimeException('mail server down'));
        $this->app->instance(\Illuminate\Contracts\Notifications\Dispatcher::class, $dispatcher);

        app(WorkflowNotificationService::class)->notifyRequester($request, 'approved');

        $this->assertDatabaseHas('notification_logs', [
            'pengeluaran_request_id' => $request->id,
            'workflow_event' => 'approved',
            'notification_type' => 'workflow',
            'status' => 'failed',
        ]);
    }

    /**
     * Regression for WF-008 (retry / TC-WF-017): retryFailed() had no case
     * for the 'workflow' notification type, so the Retry button silently did
     * nothing for approval-workflow log entries.
     */
    public function test_retry_failed_resends_workflow_notification(): void
    {
        Notification::fake();
        [$branch, , $request] = $this->makeRequest();

        $log = NotificationLog::create([
            'branch_id' => $branch->id,
            'recipient_email' => 'requester@example.com',
            'notification_type' => 'workflow',
            'pengeluaran_request_id' => $request->id,
            'workflow_event' => 'approved',
            'status' => 'failed',
            'error_message' => 'mail server down',
        ]);

        $retried = app(NotificationService::class)->retryFailed([$log->id]);

        $this->assertSame(1, $retried);
        Notification::assertSentOnDemand(PengeluaranWorkflowNotification::class);
        $this->assertDatabaseHas('notification_logs', [
            'id' => $log->id,
            'status' => 'sent',
        ]);
    }
}
