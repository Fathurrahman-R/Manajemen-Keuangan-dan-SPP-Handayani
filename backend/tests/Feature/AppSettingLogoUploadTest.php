<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\User;
use Database\Seeders\PermissionEndpointSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AppSettingLogoUploadTest extends TestCase
{
    public function test_update_setting_with_logo_upload_succeeds_and_persists_path()
    {
        Storage::fake('public');
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(PermissionEndpointSeeder::class);

        $admin = User::factory()->superadmin()->create();
        Sanctum::actingAs($admin, ['*']);

        $setting = AppSetting::create([
            'branch_id' => $admin->branch_id,
            'nama_sekolah' => 'Sekolah A',
            'alamat' => '-',
            'lokasi' => '-',
            'email' => 'a@a.com',
            'telepon' => '0812',
            'kepala_sekolah' => '-',
            'bendahara' => '-',
            'kode_pos' => '12345',
            'logo' => 'logo-sekolah/lama.png',
        ]);

        Storage::disk('public')->put('logo-sekolah/lama.png', 'lama');

        $file = UploadedFile::fake()->image('logo.png', 100, 100);

        $response = $this->post('api/setting/'.$setting->id, [
            'nama_sekolah' => 'Sekolah A',
            'lokasi' => '-',
            'alamat' => '-',
            'email' => 'a@a.com',
            'telepon' => '0812',
            'kepala_sekolah' => '-',
            'bendahara' => '-',
            'kode_pos' => '12345',
            'logo' => $file,
        ]);

        $response->assertOk();

        $setting->refresh();
        $this->assertNotSame('logo-sekolah/lama.png', $setting->logo, 'Logo di DB tidak berubah setelah upload file baru.');
        Storage::disk('public')->assertExists($setting->logo);
        $this->assertSame($setting->logo, $response->json('data.logo'));
    }
}
