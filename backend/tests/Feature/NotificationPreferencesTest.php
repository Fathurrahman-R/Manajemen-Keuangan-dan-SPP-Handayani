<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\EmailOptOut;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationPreferencesTest extends TestCase
{
    protected function tearDown(): void
    {
        \Illuminate\Support\Facades\DB::table('email_opt_outs')->delete();

        parent::tearDown();
    }

    private function actingAsUser(?string $email = 'staff@example.com'): User
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->admin()->create(['branch_id' => $branch->id, 'email' => $email]);
        Sanctum::actingAs($user, $user->getAllPermissions()->pluck('name')->toArray());

        return $user;
    }

    /**
     * EditProfile.php (staff/admin) only ever sends `workflow` — the other 4
     * tagihan-type keys are `sometimes`, not `required`, precisely so this
     * payload doesn't 422.
     */
    public function test_staff_can_toggle_workflow_preference_alone(): void
    {
        $user = $this->actingAsUser();

        $this->putJson('/api/users/current/notification-preferences', [
            'workflow' => false,
        ])->assertOk();

        $this->assertDatabaseHas('email_opt_outs', [
            'email' => $user->email,
            'notification_type' => 'workflow',
        ]);

        // Re-enabling removes the opt-out row rather than leaving a stale one.
        $this->putJson('/api/users/current/notification-preferences', [
            'workflow' => true,
        ])->assertOk();

        $this->assertDatabaseMissing('email_opt_outs', [
            'email' => $user->email,
            'notification_type' => 'workflow',
        ]);
    }

    /**
     * PortalProfilPage.php (siswa) sends the 4 tagihan-type keys and never
     * sends `workflow` — that must not be required, and must not get an
     * opt-out row created for it as a side effect of the other 4 toggles.
     */
    public function test_portal_style_payload_without_workflow_key_does_not_touch_workflow(): void
    {
        $user = $this->actingAsUser('siswa@example.com');

        $this->putJson('/api/users/current/notification-preferences', [
            'tagihan_baru' => false,
            'reminder' => true,
            'kwitansi' => true,
            'overdue' => true,
        ])->assertOk();

        $this->assertDatabaseHas('email_opt_outs', [
            'email' => $user->email,
            'notification_type' => 'tagihan_baru',
        ]);
        $this->assertDatabaseMissing('email_opt_outs', [
            'email' => $user->email,
            'notification_type' => 'workflow',
        ]);
    }

    public function test_get_preferences_includes_workflow_key(): void
    {
        $user = $this->actingAsUser();

        EmailOptOut::create([
            'email' => $user->email,
            'notification_type' => 'workflow',
            'token' => \Illuminate\Support\Str::random(32),
        ]);

        $response = $this->getJson('/api/users/current/notification-preferences')
            ->assertOk();

        $response->assertJsonPath('data.workflow', false);
        $response->assertJsonPath('data.tagihan_baru', true);
    }
}
