<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Ayah;
use App\Models\Branch;
use App\Models\Ibu;
use App\Models\JenisTagihan;
use App\Models\Kategori;
use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\TahunAjaran;
use App\Models\Wali;
use App\Services\Notifications\KwitansiPdfService;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class KwitansiPdfServiceTest extends TestCase
{
    /**
     * Regression test untuk bug: attachment PDF kwitansi hilang dari email
     * karena KwitansiPdfService sebelumnya reuse PembayaranController::kwitansi(),
     * yang men-scope query pembayaran dengan Auth::user()->branch_id. Itu selalu
     * null saat KwitansiPembayaranNotification (queued job) dieksekusi oleh queue
     * worker, karena tidak ada user yang login di context tersebut.
     */
    public function test_generate_succeeds_without_authenticated_user()
    {
        $this->assertNull(Auth::user());

        $branch = Branch::factory()->create();
        AppSetting::factory()->create(['branch_id' => $branch->id]);
        $tahunAjaran = TahunAjaran::factory()->aktif()->create(['branch_id' => $branch->id]);
        $ayah = Ayah::factory()->create();
        $ibu = Ibu::factory()->create();
        $wali = Wali::factory()->create();
        $kelas = Kelas::factory()->create(['branch_id' => $branch->id]);
        $kategori = Kategori::factory()->create(['branch_id' => $branch->id]);
        $jt = JenisTagihan::factory()->create([
            'nama' => 'SPP',
            'jumlah' => 100000,
            'branch_id' => $branch->id,
            'tahun_ajaran_id' => $tahunAjaran->id,
        ]);
        $siswa = Siswa::factory()
            ->for($ayah, 'ayah')
            ->for($ibu, 'ibu')
            ->for($wali, 'wali')
            ->for($kelas, 'kelas')
            ->for($kategori, 'kategori')
            ->create([
                'jenjang' => 'MI',
                'branch_id' => $branch->id,
            ]);
        $tagihan = Tagihan::factory()
            ->for($siswa, 'siswa')
            ->for($jt, 'jenis_tagihan')
            ->create([
                'branch_id' => $branch->id,
            ]);
        $pembayaran = Pembayaran::factory()
            ->for($tagihan, 'tagihan')
            ->create([
                'branch_id' => $branch->id,
            ]);

        $this->assertNull(Auth::user());

        $pdfContent = app(KwitansiPdfService::class)->generate($pembayaran);

        $this->assertStringStartsWith('%PDF', $pdfContent);
    }
}
