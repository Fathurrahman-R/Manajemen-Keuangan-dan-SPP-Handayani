<?php

namespace Tests\Feature\Workflow;

use App\Models\Branch;
use App\Models\PengeluaranRequest;
use App\Models\PermissionEndpoint;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PengeluaranRequestCrudTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['pengeluaran.view', 'pengeluaran.create', 'pengeluaran.update', 'pengeluaran.delete'] as $key) {
            PermissionEndpoint::updateOrCreate(
                ['resource_key' => $key],
                ['permission_id' => null, 'is_active' => true],
            );
        }
    }

    protected function tearDown(): void
    {
        \Illuminate\Support\Facades\DB::table('pengeluaran_requests')->delete();

        parent::tearDown();
    }

    private function actingAsUser(): User
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->admin()->create(['branch_id' => $branch->id]);
        Sanctum::actingAs($user, $user->getAllPermissions()->pluck('name')->toArray());

        return $user;
    }

    public function test_store_with_lampiran_saves_file_and_exposes_lampiran_url(): void
    {
        Storage::fake('public');
        $user = $this->actingAsUser();

        $response = $this->post('/api/pengeluaran-request', [
            'uraian' => 'Beli ATK',
            'jumlah' => 100000,
            'tanggal_kebutuhan' => now()->addDays(3)->toDateString(),
            'lampiran' => UploadedFile::fake()->create('nota.pdf', 100, 'application/pdf'),
        ])->assertCreated();

        $lampiranPath = $response->json('data.lampiran');
        Storage::disk('public')->assertExists($lampiranPath);
        $this->assertNotNull($response->json('data.lampiran_url'));
    }

    public function test_update_by_non_requester_is_forbidden(): void
    {
        $user = $this->actingAsUser();
        $otherUser = User::factory()->admin()->create(['branch_id' => $user->branch_id, 'username' => 'other-'.uniqid()]);

        $request = PengeluaranRequest::create([
            'uraian' => 'Beli ATK',
            'jumlah' => 100000,
            'tanggal_kebutuhan' => now()->addDays(3)->toDateString(),
            'status' => 'draft',
            'requester_id' => $otherUser->id,
            'branch_id' => $user->branch_id,
        ]);

        $this->putJson("/api/pengeluaran-request/{$request->id}", ['uraian' => 'Diubah'])
            ->assertForbidden();
    }

    public function test_destroy_non_draft_request_is_rejected(): void
    {
        $user = $this->actingAsUser();

        $request = PengeluaranRequest::create([
            'uraian' => 'Beli ATK',
            'jumlah' => 100000,
            'tanggal_kebutuhan' => now()->addDays(3)->toDateString(),
            'status' => 'submitted',
            'requester_id' => $user->id,
            'branch_id' => $user->branch_id,
        ]);

        $this->deleteJson("/api/pengeluaran-request/{$request->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('pengeluaran_requests', ['id' => $request->id]);
    }

    public function test_destroy_draft_request_succeeds(): void
    {
        $user = $this->actingAsUser();

        $request = PengeluaranRequest::create([
            'uraian' => 'Beli ATK',
            'jumlah' => 100000,
            'tanggal_kebutuhan' => now()->addDays(3)->toDateString(),
            'status' => 'draft',
            'requester_id' => $user->id,
            'branch_id' => $user->branch_id,
        ]);

        $this->deleteJson("/api/pengeluaran-request/{$request->id}")
            ->assertOk();

        $this->assertDatabaseMissing('pengeluaran_requests', ['id' => $request->id]);
    }

    public function test_destroy_rejected_request_succeeds(): void
    {
        $user = $this->actingAsUser();

        $request = PengeluaranRequest::create([
            'uraian' => 'Beli ATK',
            'jumlah' => 100000,
            'tanggal_kebutuhan' => now()->addDays(3)->toDateString(),
            'status' => 'rejected',
            'requester_id' => $user->id,
            'branch_id' => $user->branch_id,
        ]);

        $this->deleteJson("/api/pengeluaran-request/{$request->id}")
            ->assertOk();

        $this->assertDatabaseMissing('pengeluaran_requests', ['id' => $request->id]);
    }

    public function test_destroy_by_non_requester_is_forbidden(): void
    {
        $user = $this->actingAsUser();
        $otherUser = User::factory()->admin()->create(['branch_id' => $user->branch_id, 'username' => 'other-'.uniqid()]);

        $request = PengeluaranRequest::create([
            'uraian' => 'Beli ATK',
            'jumlah' => 100000,
            'tanggal_kebutuhan' => now()->addDays(3)->toDateString(),
            'status' => 'draft',
            'requester_id' => $otherUser->id,
            'branch_id' => $user->branch_id,
        ]);

        $this->deleteJson("/api/pengeluaran-request/{$request->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('pengeluaran_requests', ['id' => $request->id]);
    }
}
