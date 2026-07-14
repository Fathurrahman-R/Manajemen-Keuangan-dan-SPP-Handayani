<?php

namespace App\Services;

use App\Models\BatchPromosi;
use App\Models\BatchPromosiDetail;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\TahunAjaran;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KenaikanKelasService
{
    /**
     * Get the next Kelas in the hierarchy based on level.
     *
     * Finds the Kelas with the same jenjang and branch_id that has the
     * smallest level value strictly greater than the current Kelas's level.
     */
    public function getNextKelas(Kelas $currentKelas): ?Kelas
    {
        if ($currentKelas->level === null) {
            return null;
        }

        return Kelas::where('jenjang', $currentKelas->jenjang)
            ->where('branch_id', $currentKelas->branch_id)
            ->where('level', '>', $currentKelas->level)
            ->whereNotNull('level')
            ->orderBy('level', 'asc')
            ->first();
    }

    /**
     * Check if the given Kelas is the highest level in its jenjang and branch.
     *
     * Returns true if no other Kelas in the same jenjang and branch_id
     * has a higher level value.
     */
    public function isKelasTertinggi(Kelas $kelas): bool
    {
        if ($kelas->level === null) {
            return false;
        }

        return ! Kelas::where('jenjang', $kelas->jenjang)
            ->where('branch_id', $kelas->branch_id)
            ->where('level', '>', $kelas->level)
            ->whereNotNull('level')
            ->exists();
    }

    /**
     * Get eligible students for promotion from a specific kelas and tahun ajaran.
     *
     * Returns active students (status = 'Aktif') who have a SiswaKelas
     * record in the specified kelas for the given tahun ajaran.
     */
    public function getEligibleStudents(int $kelasId, int $sourceTahunAjaranId): Collection
    {
        return Siswa::where('status', 'Aktif')
            ->whereHas('siswaKelas', function ($query) use ($kelasId, $sourceTahunAjaranId) {
                $query->where('kelas_id', $kelasId)
                    ->where('tahun_ajaran_id', $sourceTahunAjaranId);
            })
            ->get();
    }

    /**
     * Validate if a jenjang transition is allowed.
     *
     * Only the following transitions are permitted:
     * - KB → TK
     * - TK → MI
     */
    public function validateAllowedTransition(string $fromJenjang, string $toJenjang): bool
    {
        $allowedTransitions = [
            'KB' => 'TK',
            'TK' => 'MI',
        ];

        return isset($allowedTransitions[$fromJenjang])
            && $allowedTransitions[$fromJenjang] === $toJenjang;
    }

    /**
     * Process an individual promotion for a specific student.
     *
     * Moves a single student to a specified target class for the target period.
     * Supports cross-jenjang transfers when isPindahJenjang is true.
     *
     *
     * @throws HttpResponseException
     */
    public function processIndividualPromotion(
        int $siswaId,
        int $targetKelasId,
        int $targetTahunAjaranId,
        bool $isPindahJenjang,
        int $userId,
        int $branchId
    ): array {
        // 1. Find Siswa and verify belongs to branch
        $siswa = Siswa::where('id', $siswaId)
            ->where('branch_id', $branchId)
            ->first();

        if (! $siswa) {
            throw new HttpResponseException(
                response()->json([
                    'errors' => ['siswa_id' => ['Siswa tidak ditemukan atau bukan milik branch Anda.']],
                ], 422)
            );
        }

        // 2. Find target Kelas and verify belongs to branch
        $targetKelas = Kelas::where('id', $targetKelasId)
            ->where('branch_id', $branchId)
            ->first();

        if (! $targetKelas) {
            throw new HttpResponseException(
                response()->json([
                    'errors' => ['target_kelas_id' => ['Kelas tujuan tidak ditemukan atau bukan milik branch Anda.']],
                ], 422)
            );
        }

        // 3. Get active TahunAjaran (source period)
        $activePeriod = TahunAjaran::getAktif($branchId);

        if (! $activePeriod) {
            throw new HttpResponseException(
                response()->json([
                    'errors' => ['tahun_ajaran_id' => ['Tidak ada tahun ajaran aktif untuk branch ini.']],
                ], 422)
            );
        }

        // 4. Validate target period ≠ source period
        if ($targetTahunAjaranId === $activePeriod->id) {
            throw new HttpResponseException(
                response()->json([
                    'errors' => ['tahun_ajaran_id' => ['Periode tujuan harus berbeda dari periode sumber.']],
                ], 422)
            );
        }

        // 5. Get siswa's current kelas from SiswaKelas for source period
        $sourceSiswaKelas = SiswaKelas::where('siswa_id', $siswaId)
            ->where('tahun_ajaran_id', $activePeriod->id)
            ->first();

        $sourceKelasId = $sourceSiswaKelas ? $sourceSiswaKelas->kelas_id : $siswa->kelas_id;

        // 6. If not pindah jenjang: validate target kelas jenjang matches siswa's current jenjang
        if (! $isPindahJenjang) {
            $sourceKelas = $sourceSiswaKelas
                ? Kelas::find($sourceSiswaKelas->kelas_id)
                : null;

            $siswaJenjang = $sourceKelas ? $sourceKelas->jenjang : $siswa->jenjang;

            if ($targetKelas->jenjang !== $siswaJenjang) {
                throw new HttpResponseException(
                    response()->json([
                        'errors' => ['target_kelas_id' => ['Jenjang kelas tujuan harus sama dengan jenjang siswa.']],
                    ], 422)
                );
            }
        }

        // 7. Wrap in DB::transaction
        $result = DB::transaction(function () use (
            $siswa, $siswaId, $targetKelasId, $targetTahunAjaranId,
            $sourceKelasId, $activePeriod, $userId, $branchId
        ) {
            // a. Create BatchPromosi
            $batch = BatchPromosi::create([
                'batch_type' => 'individual_promotion',
                'source_tahun_ajaran_id' => $activePeriod->id,
                'target_tahun_ajaran_id' => $targetTahunAjaranId,
                'kelas_id' => $sourceKelasId,
                'processed_by' => $userId,
                'processed_at' => now(),
                'status' => 'completed',
                'branch_id' => $branchId,
            ]);

            // b. Upsert SiswaKelas: updateOrCreate where siswa_id + tahun_ajaran_id = target
            SiswaKelas::updateOrCreate(
                [
                    'siswa_id' => $siswaId,
                    'tahun_ajaran_id' => $targetTahunAjaranId,
                ],
                [
                    'kelas_id' => $targetKelasId,
                ]
            );

            // c. Create BatchPromosiDetail
            BatchPromosiDetail::create([
                'batch_id' => $batch->id,
                'siswa_id' => $siswaId,
                'action' => 'naik_kelas',
                'source_kelas_id' => $sourceKelasId,
                'target_kelas_id' => $targetKelasId,
                'previous_status' => $siswa->status,
                'previous_jenjang' => null,
            ]);

            // d. If target period is active period: sync siswa.kelas_id
            $targetPeriod = TahunAjaran::find($targetTahunAjaranId);
            if ($targetPeriod && $targetPeriod->status === 'Aktif') {
                $siswa->update(['kelas_id' => $targetKelasId]);
            }

            return [
                'batch_id' => $batch->id,
                'siswa_id' => $siswaId,
                'source_kelas_id' => $sourceKelasId,
                'target_kelas_id' => $targetKelasId,
                'tahun_ajaran_id' => $targetTahunAjaranId,
            ];
        });

        return $result;
    }

    /**
     * Process retention (tinggal kelas) for specified students.
     *
     * Keeps specified students in the same class for the target tahun ajaran period.
     * Creates SiswaKelas records with the same kelas_id from the source period.
     *
     *
     * @throws HttpResponseException
     */
    public function processRetention(array $siswaIds, int $targetTahunAjaranId, int $userId, int $branchId): array
    {
        // 1. Get active TahunAjaran (source period)
        $sourceTahunAjaran = TahunAjaran::getAktif($branchId);

        if (! $sourceTahunAjaran) {
            throw new HttpResponseException(
                response()->json([
                    'errors' => ['message' => ['Tidak ada tahun ajaran aktif untuk branch ini.']],
                ], 422)
            );
        }

        // 2. Validate target period ≠ source period
        if ($targetTahunAjaranId === $sourceTahunAjaran->id) {
            throw new HttpResponseException(
                response()->json([
                    'errors' => ['tahun_ajaran_id' => ['Periode tujuan harus berbeda dari periode sumber.']],
                ], 422)
            );
        }

        // 3. Wrap in DB::transaction
        $result = DB::transaction(function () use (
            $siswaIds,
            $sourceTahunAjaran,
            $targetTahunAjaranId,
            $userId,
            $branchId
        ) {
            // 3a. Create BatchPromosi record
            $batch = BatchPromosi::create([
                'id' => Str::uuid()->toString(),
                'batch_type' => 'tinggal_kelas',
                'source_tahun_ajaran_id' => $sourceTahunAjaran->id,
                'target_tahun_ajaran_id' => $targetTahunAjaranId,
                'kelas_id' => null,
                'processed_by' => $userId,
                'processed_at' => now(),
                'status' => 'completed',
                'branch_id' => $branchId,
            ]);

            // 3b. Initialize counters
            $totalSuccess = 0;
            $totalSkipped = 0;
            $skipped = [];

            // Check if target period is the active period
            $isTargetActivePeriod = ($targetTahunAjaranId === TahunAjaran::getAktif($branchId)?->id);

            // 3c. Loop each siswa_id
            foreach ($siswaIds as $siswaId) {
                // Find Siswa and verify belongs to branch
                $siswa = Siswa::where('id', $siswaId)
                    ->where('branch_id', $branchId)
                    ->first();

                if (! $siswa) {
                    $totalSkipped++;
                    $skipped[] = [
                        'siswa_id' => $siswaId,
                        'nama' => null,
                        'nis' => null,
                        'reason' => 'Siswa tidak ditemukan atau bukan milik branch Anda',
                    ];

                    continue;
                }

                // Get SiswaKelas for source period
                $sourceSiswaKelas = SiswaKelas::where('siswa_id', $siswa->id)
                    ->where('tahun_ajaran_id', $sourceTahunAjaran->id)
                    ->first();

                if (! $sourceSiswaKelas) {
                    $totalSkipped++;
                    $skipped[] = [
                        'siswa_id' => $siswa->id,
                        'nama' => $siswa->nama,
                        'nis' => $siswa->nis,
                        'reason' => 'Tidak memiliki kelas di periode sumber',
                    ];

                    continue;
                }

                // Get source kelas_id
                $sourceKelasId = $sourceSiswaKelas->kelas_id;

                // Upsert SiswaKelas: updateOrCreate for target period
                SiswaKelas::updateOrCreate(
                    [
                        'siswa_id' => $siswa->id,
                        'tahun_ajaran_id' => $targetTahunAjaranId,
                    ],
                    [
                        'kelas_id' => $sourceKelasId,
                    ]
                );

                // Create BatchPromosiDetail
                BatchPromosiDetail::create([
                    'batch_id' => $batch->id,
                    'siswa_id' => $siswa->id,
                    'action' => 'tinggal_kelas',
                    'source_kelas_id' => $sourceKelasId,
                    'target_kelas_id' => $sourceKelasId,
                    'previous_status' => $siswa->status,
                    'previous_jenjang' => null,
                ]);

                // Sync siswas.kelas_id if target is the active period
                if ($isTargetActivePeriod) {
                    $siswa->update(['kelas_id' => $sourceKelasId]);
                }

                $totalSuccess++;
            }

            return [
                'batch_id' => $batch->id,
                'total_processed' => count($siswaIds),
                'total_success' => $totalSuccess,
                'total_skipped' => $totalSkipped,
                'skipped' => $skipped,
            ];
        });

        return $result;
    }

    /**
     * Process bulk promotion for all eligible students in a kelas.
     *
     * Promotes all active students from the source kelas to the next kelas
     * in the hierarchy for the target tahun ajaran period.
     *
     *
     * @throws HttpResponseException
     */
    public function processBulkPromotion(int $kelasId, int $targetTahunAjaranId, int $userId, int $branchId): array
    {
        // 1. Find the source Kelas and verify it belongs to the branch
        $kelas = Kelas::where('id', $kelasId)
            ->where('branch_id', $branchId)
            ->first();

        if (! $kelas) {
            throw new HttpResponseException(
                response()->json([
                    'errors' => ['kelas_id' => ['Kelas tidak ditemukan atau bukan milik branch Anda.']],
                ], 422)
            );
        }

        // 2. Get the active TahunAjaran for the branch (source period)
        $sourceTahunAjaran = TahunAjaran::getAktif($branchId);

        if (! $sourceTahunAjaran) {
            throw new HttpResponseException(
                response()->json([
                    'errors' => ['message' => ['Tidak ada tahun ajaran aktif untuk branch ini.']],
                ], 422)
            );
        }

        // 3. Validate target period ≠ source period
        if ($targetTahunAjaranId === $sourceTahunAjaran->id) {
            throw new HttpResponseException(
                response()->json([
                    'errors' => ['tahun_ajaran_id' => ['Periode tujuan harus berbeda dari periode sumber.']],
                ], 422)
            );
        }

        // 4. Get next kelas in hierarchy
        $nextKelas = $this->getNextKelas($kelas);

        if (! $nextKelas) {
            throw new HttpResponseException(
                response()->json([
                    'errors' => ['kelas_id' => ['Tidak ada kelas berikutnya dalam hierarki. Siswa berada di kelas tertinggi, gunakan kelulusan atau pindah jenjang.']],
                ], 422)
            );
        }

        // 5. Get eligible students
        $eligibleStudents = $this->getEligibleStudents($kelasId, $sourceTahunAjaran->id);

        // 6. If no eligible students, return early with zeros
        if ($eligibleStudents->isEmpty()) {
            throw new HttpResponseException(
                response()->json([
                    'errors' => ['kelas_id' => ['Tidak ada siswa yang memenuhi syarat untuk promosi di kelas ini.']],
                ], 422)
            );
        }

        // 7. Wrap in DB::transaction
        $result = DB::transaction(function () use (
            $kelas,
            $nextKelas,
            $eligibleStudents,
            $sourceTahunAjaran,
            $targetTahunAjaranId,
            $userId,
            $branchId
        ) {
            // 7a. Create BatchPromosi record
            $batch = BatchPromosi::create([
                'id' => Str::uuid()->toString(),
                'batch_type' => 'bulk_promotion',
                'source_tahun_ajaran_id' => $sourceTahunAjaran->id,
                'target_tahun_ajaran_id' => $targetTahunAjaranId,
                'kelas_id' => $kelas->id,
                'processed_by' => $userId,
                'processed_at' => now(),
                'status' => 'completed',
                'branch_id' => $branchId,
            ]);

            $totalSuccess = 0;
            $totalSkipped = 0;
            $skipped = [];

            // Check if target period is the active period
            $isTargetActivePeriod = ($targetTahunAjaranId === TahunAjaran::getAktif($branchId)?->id);

            // 7b. Loop eligible students
            foreach ($eligibleStudents as $siswa) {
                // Check if SiswaKelas already exists for this siswa + target period
                $existingSiswaKelas = SiswaKelas::where('siswa_id', $siswa->id)
                    ->where('tahun_ajaran_id', $targetTahunAjaranId)
                    ->exists();

                if ($existingSiswaKelas) {
                    // Skip this student
                    $totalSkipped++;
                    $skipped[] = [
                        'siswa_id' => $siswa->id,
                        'nama' => $siswa->nama,
                        'nis' => $siswa->nis,
                        'reason' => 'Sudah memiliki penempatan kelas untuk periode tujuan',
                    ];

                    continue;
                }

                // Create SiswaKelas for target period
                SiswaKelas::create([
                    'siswa_id' => $siswa->id,
                    'kelas_id' => $nextKelas->id,
                    'tahun_ajaran_id' => $targetTahunAjaranId,
                ]);

                // Create BatchPromosiDetail
                BatchPromosiDetail::create([
                    'batch_id' => $batch->id,
                    'siswa_id' => $siswa->id,
                    'action' => 'naik_kelas',
                    'source_kelas_id' => $kelas->id,
                    'target_kelas_id' => $nextKelas->id,
                    'previous_status' => $siswa->status,
                    'previous_jenjang' => null,
                ]);

                // Sync siswas.kelas_id if target is the active period
                if ($isTargetActivePeriod) {
                    $siswa->update(['kelas_id' => $nextKelas->id]);
                }

                $totalSuccess++;
            }

            return [
                'batch_id' => $batch->id,
                'total_processed' => $eligibleStudents->count(),
                'total_success' => $totalSuccess,
                'total_skipped' => $totalSkipped,
                'skipped' => $skipped,
            ];
        });

        return $result;
    }

    /**
     * Process graduation for specified students.
     *
     * Graduates students who are in the highest class of their jenjang.
     * Sets their status to "Lulus" and kelas_id to NULL.
     * Does NOT create SiswaKelas records for the target period.
     *
     *
     * @throws HttpResponseException
     */
    public function processGraduation(array $siswaIds, int $targetTahunAjaranId, int $userId, int $branchId): array
    {
        // 1. Get active TahunAjaran (source period)
        $sourceTahunAjaran = TahunAjaran::getAktif($branchId);

        if (! $sourceTahunAjaran) {
            throw new HttpResponseException(
                response()->json([
                    'errors' => ['message' => ['Tidak ada tahun ajaran aktif untuk branch ini.']],
                ], 422)
            );
        }

        // 2. Validate target period ≠ source period
        if ($targetTahunAjaranId === $sourceTahunAjaran->id) {
            throw new HttpResponseException(
                response()->json([
                    'errors' => ['tahun_ajaran_id' => ['Periode tujuan harus berbeda dari periode sumber.']],
                ], 422)
            );
        }

        // 3. Wrap in DB::transaction
        $result = DB::transaction(function () use (
            $siswaIds,
            $sourceTahunAjaran,
            $targetTahunAjaranId,
            $userId,
            $branchId
        ) {
            // Determine kelas_id from first siswa for batch record
            $firstSiswa = Siswa::find($siswaIds[0]);
            $kelasId = $firstSiswa?->kelas_id;

            // 3a. Create BatchPromosi record
            $batch = BatchPromosi::create([
                'id' => Str::uuid()->toString(),
                'batch_type' => 'kelulusan',
                'source_tahun_ajaran_id' => $sourceTahunAjaran->id,
                'target_tahun_ajaran_id' => $targetTahunAjaranId,
                'kelas_id' => $kelasId,
                'processed_by' => $userId,
                'processed_at' => now(),
                'status' => 'completed',
                'branch_id' => $branchId,
            ]);

            $totalGraduated = 0;
            $totalSkipped = 0;
            $skipped = [];

            // 3b. Loop each siswa_id
            foreach ($siswaIds as $siswaId) {
                // Find Siswa and verify belongs to branch
                $siswa = Siswa::where('id', $siswaId)
                    ->where('branch_id', $branchId)
                    ->first();

                if (! $siswa) {
                    $totalSkipped++;
                    $skipped[] = [
                        'siswa_id' => $siswaId,
                        'reason' => 'siswa tidak ditemukan',
                    ];

                    continue;
                }

                // Skip if siswa.status ≠ "Aktif"
                if ($siswa->status !== 'Aktif') {
                    $totalSkipped++;
                    $skipped[] = [
                        'siswa_id' => $siswa->id,
                        'nama' => $siswa->nama,
                        'nis' => $siswa->nis,
                        'reason' => 'status tidak aktif',
                    ];

                    continue;
                }

                // Get siswa's current kelas from SiswaKelas for source period
                $siswaKelas = SiswaKelas::where('siswa_id', $siswa->id)
                    ->where('tahun_ajaran_id', $sourceTahunAjaran->id)
                    ->first();

                $currentKelas = $siswaKelas ? Kelas::find($siswaKelas->kelas_id) : null;

                // Skip if current kelas is null or not kelas tertinggi
                if (! $currentKelas || ! $this->isKelasTertinggi($currentKelas)) {
                    $totalSkipped++;
                    $skipped[] = [
                        'siswa_id' => $siswa->id,
                        'nama' => $siswa->nama,
                        'nis' => $siswa->nis,
                        'reason' => 'bukan kelas tertinggi',
                    ];

                    continue;
                }

                // For eligible: set status = "Lulus", set kelas_id = NULL
                $siswa->status = 'Lulus';
                $siswa->kelas_id = null;
                $siswa->save();

                // Create BatchPromosiDetail (target_kelas_id = NULL)
                BatchPromosiDetail::create([
                    'batch_id' => $batch->id,
                    'siswa_id' => $siswa->id,
                    'action' => 'lulus',
                    'source_kelas_id' => $currentKelas->id,
                    'target_kelas_id' => null,
                    'previous_status' => 'Aktif',
                    'previous_jenjang' => null,
                ]);

                $totalGraduated++;
            }

            return [
                'batch_id' => $batch->id,
                'total_processed' => count($siswaIds),
                'total_graduated' => $totalGraduated,
                'total_skipped' => $totalSkipped,
                'skipped' => $skipped,
            ];
        });

        return $result;
    }

    /**
     * Process a cross-level transfer (Pindah Jenjang) for a single student.
     *
     * Transfers a graduated student from one jenjang to the next (KB→TK, TK→MI).
     * Updates the student's jenjang, status, and creates placement records.
     *
     *
     * @throws HttpResponseException
     */
    public function processCrossLevelTransfer(int $siswaId, int $targetKelasId, int $targetTahunAjaranId, int $userId, int $branchId): array
    {
        // 1. Find Siswa and verify belongs to branch
        $siswa = Siswa::where('id', $siswaId)
            ->where('branch_id', $branchId)
            ->first();

        if (! $siswa) {
            throw new HttpResponseException(response()->json([
                'message' => 'Siswa tidak ditemukan',
            ], 404));
        }

        // 2. Validate siswa status = "Lulus"
        if ($siswa->status !== 'Lulus') {
            throw new HttpResponseException(response()->json([
                'message' => 'Siswa harus berstatus Lulus untuk pindah jenjang',
            ], 422));
        }

        // 3. Find target Kelas and verify belongs to branch
        $targetKelas = Kelas::where('id', $targetKelasId)
            ->where('branch_id', $branchId)
            ->first();

        if (! $targetKelas) {
            throw new HttpResponseException(response()->json([
                'message' => 'Kelas tujuan tidak ditemukan',
            ], 404));
        }

        // 4. Validate allowed transition (KB→TK, TK→MI)
        if (! $this->validateAllowedTransition($siswa->jenjang, $targetKelas->jenjang)) {
            throw new HttpResponseException(response()->json([
                'message' => 'Transisi jenjang tidak diperbolehkan',
            ], 422));
        }

        // 5. Get active TahunAjaran (source period)
        $activeTahunAjaran = TahunAjaran::getAktif($branchId);

        if (! $activeTahunAjaran) {
            throw new HttpResponseException(response()->json([
                'message' => 'Tidak ada tahun ajaran aktif',
            ], 422));
        }

        // 6. Validate target period ≠ source period
        if ($targetTahunAjaranId === $activeTahunAjaran->id) {
            throw new HttpResponseException(response()->json([
                'message' => 'Periode tujuan tidak boleh sama dengan periode aktif',
            ], 422));
        }

        // 7. Get siswa's previous kelas from SiswaKelas for source period
        $previousSiswaKelas = SiswaKelas::where('siswa_id', $siswaId)
            ->where('tahun_ajaran_id', $activeTahunAjaran->id)
            ->first();

        $previousKelasId = $previousSiswaKelas ? $previousSiswaKelas->kelas_id : null;

        // 8. Wrap in DB::transaction
        $result = DB::transaction(function () use ($siswa, $targetKelas, $targetKelasId, $targetTahunAjaranId, $userId, $branchId, $activeTahunAjaran, $previousKelasId) {
            // a. Create BatchPromosi
            $batch = BatchPromosi::create([
                'batch_type' => 'pindah_jenjang',
                'source_tahun_ajaran_id' => $activeTahunAjaran->id,
                'target_tahun_ajaran_id' => $targetTahunAjaranId,
                'kelas_id' => $previousKelasId,
                'processed_by' => $userId,
                'processed_at' => now(),
                'status' => 'completed',
                'branch_id' => $branchId,
            ]);

            // b. Store previous_jenjang
            $previousJenjang = $siswa->jenjang;

            // c. Update siswa: jenjang and status
            $siswa->jenjang = $targetKelas->jenjang;
            $siswa->status = 'Aktif';

            // d. If target period is active period: update kelas_id
            $targetTahunAjaran = TahunAjaran::find($targetTahunAjaranId);
            if ($targetTahunAjaran && $targetTahunAjaran->status === 'Aktif') {
                $siswa->kelas_id = $targetKelasId;
            }

            // e. Save siswa
            $siswa->save();

            // f. Create SiswaKelas for target period
            SiswaKelas::create([
                'siswa_id' => $siswa->id,
                'kelas_id' => $targetKelasId,
                'tahun_ajaran_id' => $targetTahunAjaranId,
            ]);

            // g. Create BatchPromosiDetail
            BatchPromosiDetail::create([
                'batch_id' => $batch->id,
                'siswa_id' => $siswa->id,
                'action' => 'pindah_jenjang',
                'source_kelas_id' => $previousKelasId,
                'target_kelas_id' => $targetKelasId,
                'previous_status' => 'Lulus',
                'previous_jenjang' => $previousJenjang,
            ]);

            return [
                'batch_id' => $batch->id,
                'siswa_id' => $siswa->id,
                'previous_jenjang' => $previousJenjang,
                'new_jenjang' => $targetKelas->jenjang,
                'target_kelas_id' => $targetKelasId,
                'tahun_ajaran_id' => $targetTahunAjaranId,
            ];
        });

        // 9. Return summary
        return $result;
    }

    /**
     * Undo a completed batch promotion/graduation operation.
     *
     * Reverses all operations in the batch: deletes created SiswaKelas records
     * for the target period, restores siswa status, jenjang, and kelas_id.
     * Skips students whose SiswaKelas has been manually modified after the batch.
     *
     *
     * @throws HttpResponseException
     */
    public function undoBatch(string $batchId, int $branchId): array
    {
        // 1. Find BatchPromosi by ID and verify belongs to branch
        $batch = BatchPromosi::where('id', $batchId)
            ->where('branch_id', $branchId)
            ->first();

        if (! $batch) {
            throw new HttpResponseException(
                response()->json([
                    'message' => 'Batch tidak ditemukan.',
                ], 404)
            );
        }

        // 2. If status = "undone" → reject
        if ($batch->status === 'undone') {
            throw new HttpResponseException(
                response()->json([
                    'message' => 'Batch sudah dibatalkan sebelumnya.',
                ], 422)
            );
        }

        // 3. If status ≠ "completed" → reject
        if ($batch->status !== 'completed') {
            throw new HttpResponseException(
                response()->json([
                    'message' => 'Hanya batch dengan status completed yang dapat dibatalkan.',
                ], 422)
            );
        }

        // 4. Load batch details with relationships
        $batch->load('details.siswa');

        // 5. Wrap in DB::transaction
        $result = DB::transaction(function () use ($batch) {
            $totalRestored = 0;
            $totalSkipped = 0;
            $skipped = [];

            // Loop each BatchPromosiDetail
            foreach ($batch->details as $detail) {
                if (in_array($detail->action, ['naik_kelas', 'tinggal_kelas', 'pindah_jenjang'])) {
                    // Find SiswaKelas where siswa_id + tahun_ajaran_id = batch.target_tahun_ajaran_id
                    $siswaKelas = SiswaKelas::where('siswa_id', $detail->siswa_id)
                        ->where('tahun_ajaran_id', $batch->target_tahun_ajaran_id)
                        ->first();

                    if ($siswaKelas) {
                        // If kelas_id ≠ detail.target_kelas_id → skip (modified)
                        if ($siswaKelas->kelas_id !== $detail->target_kelas_id) {
                            $totalSkipped++;
                            $skipped[] = [
                                'siswa_id' => $detail->siswa_id,
                                'nama' => $detail->siswa ? $detail->siswa->nama : null,
                                'reason' => 'penempatan kelas sudah diubah',
                            ];

                            continue;
                        }

                        // If kelas_id = detail.target_kelas_id → delete it
                        $siswaKelas->delete();
                    }

                    // Find Siswa and restore
                    $siswa = Siswa::find($detail->siswa_id);
                    if ($siswa) {
                        $siswa->status = $detail->previous_status;

                        if ($detail->previous_jenjang !== null) {
                            $siswa->jenjang = $detail->previous_jenjang;
                        }

                        $siswa->kelas_id = $detail->source_kelas_id;
                        $siswa->save();
                    }

                    $totalRestored++;
                } elseif ($detail->action === 'lulus') {
                    // For graduation: restore siswa status and kelas_id
                    $siswa = Siswa::find($detail->siswa_id);
                    if ($siswa) {
                        $siswa->status = $detail->previous_status;
                        $siswa->kelas_id = $detail->source_kelas_id;
                        $siswa->save();
                    }

                    $totalRestored++;
                }
            }

            // Update batch status to "undone"
            $batch->status = 'undone';
            $batch->save();

            return [
                'batch_id' => $batch->id,
                'total_processed' => $batch->details->count(),
                'total_restored' => $totalRestored,
                'total_skipped' => $totalSkipped,
                'skipped' => $skipped,
            ];
        });

        return $result;
    }
}
