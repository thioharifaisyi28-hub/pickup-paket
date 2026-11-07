<?php
/**
 * PrioritySchedulingService.php
 *
 * Implementasi Algoritma Non-Preemptive Priority Scheduling Berbasis
 * Multi-Criteria Weighted Scoring dengan Mekanisme Dynamic Aging
 * untuk Sistem Informasi Pick-Up Paket PT Polo Sport Indonesia.
 *
 * Formula Matematis:
 *   S_i = (w_1 * C_i) + (w_2 * T_i) + (w_3 * V_i) + (w_4 * A_i)
 *
 * Di mana:
 *   w_1 = 0.40 (Urgensi Sisa Waktu Cut-Off Kurir / Time-to-Cutoff)
 *   w_2 = 0.30 (Tingkat SLA Layanan Ekspedisi)
 *   w_3 = 0.15 (Skala Volume Batch Paket Harian)
 *   w_4 = 0.15 (Dynamic Aging Factor / Anti-Starvation)
 */

class PrioritySchedulingService
{
    public const WEIGHT_CUTOFF  = 0.40;
    public const WEIGHT_SERVICE = 0.30;
    public const WEIGHT_VOLUME  = 0.15;
    public const WEIGHT_AGING   = 0.15;

    public const DEFAULT_EKSPEDISI = 'SPX';
    public const DEFAULT_LAYANAN   = 'REGULER';
    public const DEFAULT_CUTOFF    = '17:00:00';

    private Database $db;

    /**
     * Cache profil master_ekspedisi dalam siklus request
     * @var array<string, array<string, mixed>>|null
     */
    private ?array $masterProfilesCache = null;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    /**
     * Memuat parameter referensi dari tabel master_ekspedisi
     *
     * @return array<string, array<string, mixed>>
     */
    public function getMasterProfiles(): array
    {
        if ($this->masterProfilesCache !== null) {
            return $this->masterProfilesCache;
        }

        $profiles = [];
        try {
            $rows = $this->db->fetchAll(
                "SELECT kode_ekspedisi, nama_lengkap, default_layanan, bobot_layanan, cutoff_time
                 FROM master_ekspedisi
                 WHERE is_active = 1"
            );
            foreach ($rows as $row) {
                $key = strtoupper(trim((string)$row['kode_ekspedisi']));
                $profiles[$key] = $row;
            }
        } catch (\Throwable $e) {
            // Fallback statis jika tabel referensi belum terbaca
        }

        if (empty($profiles)) {
            $profiles = [
                'SAMEDAY' => ['kode_ekspedisi' => 'SAMEDAY', 'default_layanan' => 'SAMEDAY', 'bobot_layanan' => 100, 'cutoff_time' => '15:00:00'],
                'INSTANT' => ['kode_ekspedisi' => 'INSTANT', 'default_layanan' => 'INSTANT', 'bobot_layanan' => 100, 'cutoff_time' => '16:00:00'],
                'EXPRESS' => ['kode_ekspedisi' => 'EXPRESS', 'default_layanan' => 'EXPRESS', 'bobot_layanan' => 75,  'cutoff_time' => '16:30:00'],
                'LEX'     => ['kode_ekspedisi' => 'LEX',     'default_layanan' => 'REGULER', 'bobot_layanan' => 50,  'cutoff_time' => '16:30:00'],
                'SPX'     => ['kode_ekspedisi' => 'SPX',     'default_layanan' => 'REGULER', 'bobot_layanan' => 50,  'cutoff_time' => '17:00:00'],
                'SICEPAT' => ['kode_ekspedisi' => 'SICEPAT', 'default_layanan' => 'REGULER', 'bobot_layanan' => 50,  'cutoff_time' => '17:30:00'],
                'NINJA'   => ['kode_ekspedisi' => 'NINJA',   'default_layanan' => 'REGULER', 'bobot_layanan' => 50,  'cutoff_time' => '17:30:00'],
                'ID'      => ['kode_ekspedisi' => 'ID',      'default_layanan' => 'REGULER', 'bobot_layanan' => 50,  'cutoff_time' => '17:30:00'],
                'JNT'     => ['kode_ekspedisi' => 'JNT',     'default_layanan' => 'REGULER', 'bobot_layanan' => 50,  'cutoff_time' => '18:00:00'],
                'JNE'     => ['kode_ekspedisi' => 'JNE',     'default_layanan' => 'REGULER', 'bobot_layanan' => 50,  'cutoff_time' => '18:00:00'],
                'CARGO'   => ['kode_ekspedisi' => 'CARGO',   'default_layanan' => 'CARGO',   'bobot_layanan' => 25,  'cutoff_time' => '19:00:00'],
            ];
        }

        $this->masterProfilesCache = $profiles;
        return $profiles;
    }

    /**
     * Deteksi otomatis ekspedisi & tipe layanan dari kode pesanan / sesi (jika tidak diisi eksplisit)
     *
     * @return array{nama_ekspedisi: string, layanan_ekspedisi: string}
     */
    public function inferExpeditionAndService(?string $noPesanan = '', ?string $kode = '', ?string $sesi = ''): array
    {
        $combined = strtoupper(trim(($noPesanan ?? '') . ' ' . ($kode ?? '') . ' ' . ($sesi ?? '')));

        // Deteksi Layanan Khusus
        $layanan = self::DEFAULT_LAYANAN;
        if (str_contains($combined, 'INSTAN') || str_contains($combined, 'GOSEND') || str_contains($combined, 'GRAB')) {
            $layanan = 'INSTANT';
        } elseif (str_contains($combined, 'SAMEDAY') || str_contains($combined, 'SAME DAY')) {
            $layanan = 'SAMEDAY';
        } elseif (str_contains($combined, 'EXPRESS') || str_contains($combined, 'YES') || str_contains($combined, 'NEXT')) {
            $layanan = 'EXPRESS';
        } elseif (str_contains($combined, 'CARGO') || str_contains($combined, 'JTR') || str_contains($combined, 'GOKIL') || str_contains($combined, 'HEMAT')) {
            $layanan = 'CARGO';
        }

        // Deteksi Kode Ekspedisi berdasarkan prefix nomor resi / kode
        $ekspedisi = self::DEFAULT_EKSPEDISI;
        $cleanResi = strtoupper(trim((string)$noPesanan));
        if (str_starts_with($cleanResi, 'LXAD') || str_contains($combined, 'LEX')) {
            $ekspedisi = 'LEX';
        } elseif (str_starts_with($cleanResi, 'JP') || str_starts_with($cleanResi, 'JZ') || str_starts_with($cleanResi, 'JB') || str_contains($combined, 'JNT') || str_contains($combined, 'J&T')) {
            $ekspedisi = 'JNT';
        } elseif (str_starts_with($cleanResi, 'JNAP') || str_starts_with($cleanResi, 'CM') || str_contains($combined, 'JNE')) {
            $ekspedisi = 'JNE';
        } elseif (str_starts_with($cleanResi, '00') || str_contains($combined, 'SICEPAT')) {
            $ekspedisi = 'SICEPAT';
        } elseif (str_starts_with($cleanResi, 'NLID') || str_contains($combined, 'NINJA')) {
            $ekspedisi = 'NINJA';
        } elseif (str_starts_with($cleanResi, 'IDD') || str_starts_with($cleanResi, 'IDE') || preg_match('/\bID\b/', $combined)) {
            $ekspedisi = 'ID';
        } elseif ($layanan === 'INSTANT') {
            $ekspedisi = 'INSTANT';
        } elseif ($layanan === 'SAMEDAY') {
            $ekspedisi = 'SAMEDAY';
        }

        return [
            'nama_ekspedisi'    => $ekspedisi,
            'layanan_ekspedisi' => $layanan
        ];
    }

    /**
     * Menentukan parameter operasional (ekspedisi, layanan, cutoff_time, bobot SLA)
     *
     * @return array{nama_ekspedisi: string, layanan_ekspedisi: string, cutoff_time: string, service_score: int}
     */
    public function resolveParameters(
        ?string $namaEkspedisi = null,
        ?string $layananEkspedisi = null,
        ?string $customCutoff = null,
        ?string $noPesanan = '',
        ?string $kode = '',
        ?string $sesi = ''
    ): array {
        $profiles = $this->getMasterProfiles();
        $inferred = $this->inferExpeditionAndService($noPesanan, $kode, $sesi);

        $eksp = strtoupper(trim((string)($namaEkspedisi ?: $inferred['nama_ekspedisi'])));
        if ($eksp === '') {
            $eksp = self::DEFAULT_EKSPEDISI;
        }

        $allowedLayanan = ['INSTANT', 'SAMEDAY', 'EXPRESS', 'REGULER', 'CARGO'];
        $layanan = strtoupper(trim((string)($layananEkspedisi ?: '')));
        if (!in_array($layanan, $allowedLayanan, true)) {
            $layanan = $profiles[$eksp]['default_layanan'] ?? $inferred['layanan_ekspedisi'];
        }

        // Tentukan cutoff_time: input eksplisit -> profil layanan khusus (INSTANT/SAMEDAY) -> profil ekspedisi -> default
        $cutoff = trim((string)($customCutoff ?? ''));
        if ($cutoff === '' || !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $cutoff)) {
            if (in_array($layanan, ['INSTANT', 'SAMEDAY'], true) && isset($profiles[$layanan]['cutoff_time'])) {
                $cutoff = (string)$profiles[$layanan]['cutoff_time'];
            } else {
                $cutoff = (string)($profiles[$eksp]['cutoff_time'] ?? self::DEFAULT_CUTOFF);
            }
        } elseif (strlen($cutoff) === 5) {
            $cutoff .= ':00';
        }

        $serviceScore = $this->calculateServiceScore($layanan, $eksp);

        return [
            'nama_ekspedisi'    => $eksp,
            'layanan_ekspedisi' => $layanan,
            'cutoff_time'       => $cutoff,
            'service_score'     => $serviceScore
        ];
    }

    /**
     * Komponen 1: Skor Urgensi Sisa Waktu Cut-Off (C_i)
     *
     *   delta_t <= 30 menit        -> C_i = 100 (KRITIS)
     *   30 < delta_t <= 60 menit   -> C_i = 80  (MENDESAK)
     *   60 < delta_t <= 120 menit  -> C_i = 50  (SIAGA)
     *   delta_t > 120 menit        -> C_i = 20  (AMAN)
     *
     * @return array{score: int, remaining_minutes: int, zone: string}
     */
    public function calculateCutoffScore(
        string $cutoffTime,
        string $tanggalPaket,
        ?\DateTimeInterface $now = null
    ): array {
        $tz = new \DateTimeZone('Asia/Jakarta');
        $current = $now
            ? \DateTimeImmutable::createFromInterface($now)->setTimezone($tz)
            : new \DateTimeImmutable('now', $tz);

        $cleanDate = substr(trim($tanggalPaket), 0, 10);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $cleanDate)) {
            $cleanDate = $current->format('Y-m-d');
        }

        $cleanTime = trim($cutoffTime);
        if (strlen($cleanTime) === 5) {
            $cleanTime .= ':00';
        }
        if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $cleanTime)) {
            $cleanTime = self::DEFAULT_CUTOFF;
        }

        $cutoffDt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', "{$cleanDate} {$cleanTime}", $tz);
        if ($cutoffDt === false) {
            $cutoffDt = new \DateTimeImmutable($cleanDate . ' ' . self::DEFAULT_CUTOFF, $tz);
        }

        $deltaMinutes = (int)floor(($cutoffDt->getTimestamp() - $current->getTimestamp()) / 60);

        if ($deltaMinutes <= 30) {
            return ['score' => 100, 'remaining_minutes' => $deltaMinutes, 'zone' => 'KRITIS'];
        }
        if ($deltaMinutes <= 60) {
            return ['score' => 80, 'remaining_minutes' => $deltaMinutes, 'zone' => 'MENDESAK'];
        }
        if ($deltaMinutes <= 120) {
            return ['score' => 50, 'remaining_minutes' => $deltaMinutes, 'zone' => 'SIAGA'];
        }

        return ['score' => 20, 'remaining_minutes' => $deltaMinutes, 'zone' => 'AMAN'];
    }

    /**
     * Komponen 2: Skor SLA Layanan Ekspedisi (T_i)
     */
    public function calculateServiceScore(string $layananEkspedisi, ?string $namaEkspedisi = null): int
    {
        $layanan = strtoupper(trim($layananEkspedisi));
        $profiles = $this->getMasterProfiles();

        if (isset($profiles[$layanan]['bobot_layanan'])) {
            return (int)$profiles[$layanan]['bobot_layanan'];
        }

        return match ($layanan) {
            'INSTANT', 'INSTAN', 'SAMEDAY' => 100,
            'EXPRESS'            => 75,
            'REGULER'            => (int)($profiles[strtoupper((string)$namaEkspedisi)]['bobot_layanan'] ?? 50),
            'CARGO'              => 25,
            default              => 50,
        };
    }

    /**
     * Komponen 3: Skor Efisiensi Skala Volume Batch Harian (V_i)
     *
     *   Volume >= 500 paket        -> V_i = 80 (HEAVY_BATCH)
     *   100 <= Volume < 500 paket  -> V_i = 60 (MEDIUM_BATCH)
     *   Volume < 100 paket         -> V_i = 40 (SMALL_BATCH)
     */
    public function calculateVolumeScore(int $batchVolume): int
    {
        if ($batchVolume >= 500) {
            return 80;
        }
        if ($batchVolume >= 100) {
            return 60;
        }
        return 40;
    }

    /**
     * Komponen 4: Skor Dynamic Aging / Pencegahan Starvation (A_i)
     *
     *   A_i = min(100, max(0, (menit_tunggu / 60) * 10))
     *
     * @return array{score: float, waiting_minutes: int}
     */
    public function calculateAgingScore(
        string $tanggalMasuk,
        ?\DateTimeInterface $now = null
    ): array {
        $tz = new \DateTimeZone('Asia/Jakarta');
        $current = $now
            ? \DateTimeImmutable::createFromInterface($now)->setTimezone($tz)
            : new \DateTimeImmutable('now', $tz);

        try {
            $masukDt = new \DateTimeImmutable($tanggalMasuk, $tz);
        } catch (\Throwable $e) {
            $masukDt = $current;
        }

        $waitingMinutes = max(0, (int)floor(($current->getTimestamp() - $masukDt->getTimestamp()) / 60));
        $agingScore = min(100.0, round(($waitingMinutes / 60.0) * 10.0, 2));

        return [
            'score'           => $agingScore,
            'waiting_minutes' => $waitingMinutes
        ];
    }

    /**
     * Kalkulasi Skor Akhir Prioritas (S_i) beserta rincian komponen
     *
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function calculatePriority(array $input, ?\DateTimeInterface $now = null): array
    {
        $tanggalPaket = (string)($input['tanggal'] ?? date('Y-m-d'));
        $tanggalMasuk = (string)($input['tanggal_masuk'] ?? date('Y-m-d H:i:s'));
        $batchVolume  = max(1, (int)($input['batch_volume'] ?? 1));

        $resolved = $this->resolveParameters(
            $input['nama_ekspedisi'] ?? null,
            $input['layanan_ekspedisi'] ?? null,
            $input['cutoff_time'] ?? null,
            $input['no_pesanan'] ?? '',
            $input['kode'] ?? '',
            $input['sesi'] ?? ''
        );

        $cutoffInfo  = $this->calculateCutoffScore($resolved['cutoff_time'], $tanggalPaket, $now);
        $ci          = $cutoffInfo['score'];
        $ti          = $resolved['service_score'];
        $vi          = $this->calculateVolumeScore($batchVolume);
        $agingInfo   = $this->calculateAgingScore($tanggalMasuk, $now);
        $ai          = $agingInfo['score'];

        $wC = round(self::WEIGHT_CUTOFF * $ci, 2);
        $wT = round(self::WEIGHT_SERVICE * $ti, 2);
        $wV = round(self::WEIGHT_VOLUME * $vi, 2);
        $wA = round(self::WEIGHT_AGING * $ai, 2);

        $totalScore = min(100.00, max(0.00, round($wC + $wT + $wV + $wA, 2)));
        $level      = self::classifyPriorityLevel($totalScore);

        $breakdownCompact = json_encode([
            'C'   => $ci,
            'T'   => $ti,
            'V'   => $vi,
            'A'   => $ai,
            'vol' => $batchVolume,
            'rem' => $cutoffInfo['remaining_minutes'],
            'wt'  => $agingInfo['waiting_minutes'],
            'lvl' => $level
        ], JSON_UNESCAPED_SLASHES);

        return [
            'priority_score'       => $totalScore,
            'priority_level'       => $level,
            'nama_ekspedisi'       => $resolved['nama_ekspedisi'],
            'layanan_ekspedisi'    => $resolved['layanan_ekspedisi'],
            'cutoff_time'          => $resolved['cutoff_time'],
            'components'           => [
                'C' => $ci,
                'T' => $ti,
                'V' => $vi,
                'A' => $ai,
            ],
            'weighted'             => [
                'wC' => $wC,
                'wT' => $wT,
                'wV' => $wV,
                'wA' => $wA,
            ],
            'meta'                 => [
                'cutoff_zone'       => $cutoffInfo['zone'],
                'remaining_minutes' => $cutoffInfo['remaining_minutes'],
                'waiting_minutes'   => $agingInfo['waiting_minutes'],
                'batch_volume'      => $batchVolume,
            ],
            'score_breakdown_json' => $breakdownCompact ?: null,
        ];
    }

    /**
     * Klasifikasi level prioritas visual dari skor S_i
     */
    public static function classifyPriorityLevel(float $score): string
    {
        if ($score >= 75.0) {
            return 'KRITIS';
        }
        if ($score >= 60.0) {
            return 'TINGGI';
        }
        if ($score >= 45.0) {
            return 'SEDANG';
        }
        return 'RENDAH';
    }

    /**
     * Menghitung volume paket aktif per ekspedisi pada tanggal tertentu
     *
     * @return array<string, int>
     */
    public function getDailyVolumeMap(string $tanggal): array
    {
        $rows = $this->db->fetchAll(
            "SELECT COALESCE(nama_ekspedisi, 'SPX') AS ekspedisi,
                    SUM(GREATEST(COALESCE(jumlah_item, 1), 1)) AS total_vol
             FROM paket
             WHERE is_deleted = 0 AND tanggal = :tanggal AND status != 'Batal'
             GROUP BY COALESCE(nama_ekspedisi, 'SPX')",
            ['tanggal' => $tanggal]
        );

        $map = [];
        foreach ($rows as $row) {
            $map[strtoupper((string)$row['ekspedisi'])] = (int)$row['total_vol'];
        }
        return $map;
    }

    /**
     * Menghitung ulang skor prioritas seluruh antrean paket pada tanggal tertentu
     * secara transaksional (Hybrid Architecture: Pre-computed Indexed Score).
     *
     * @return array{updated: int, tanggal: string, duration_ms: float}
     */
    public function recalculateDailyQueue(
        ?string $tanggal = null,
        bool $onlyPending = true,
        ?\DateTimeInterface $now = null
    ): array {
        $t0 = microtime(true);
        $targetDate = $tanggal ?: date('Y-m-d');
        $scheduledTimestamp = ($now ? $now->format('Y-m-d H:i:s') : date('Y-m-d H:i:s'));

        $statusClause = $onlyPending ? "AND status = 'Pending'" : "";
        $rows = $this->db->fetchAll(
            "SELECT id, no_pesanan, kode, sesi, tanggal, tanggal_masuk,
                    nama_ekspedisi, layanan_ekspedisi, jumlah_item, cutoff_time
             FROM paket
             WHERE is_deleted = 0 AND tanggal = :tanggal {$statusClause}",
            ['tanggal' => $targetDate]
        );

        if (empty($rows)) {
            return [
                'updated'     => 0,
                'tanggal'     => $targetDate,
                'duration_ms' => round((microtime(true) - $t0) * 1000, 2)
            ];
        }

        $volumeMap = $this->getDailyVolumeMap($targetDate);
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare(
            "UPDATE paket
             SET nama_ekspedisi    = :nama_ekspedisi,
                 layanan_ekspedisi = :layanan_ekspedisi,
                 cutoff_time       = :cutoff_time,
                 priority_score    = :priority_score,
                 score_breakdown   = :score_breakdown,
                 scheduled_at      = :scheduled_at
             WHERE id = :id"
        );

        $updated = 0;
        $this->db->beginTransaction();
        try {
            foreach ($rows as $row) {
                $resolved = $this->resolveParameters(
                    $row['nama_ekspedisi'] ?? null,
                    $row['layanan_ekspedisi'] ?? null,
                    $row['cutoff_time'] ?? null,
                    $row['no_pesanan'] ?? '',
                    $row['kode'] ?? '',
                    $row['sesi'] ?? ''
                );

                $ekspKey = $resolved['nama_ekspedisi'];
                $batchVol = $volumeMap[$ekspKey] ?? max(1, (int)($row['jumlah_item'] ?? 1));

                $calc = $this->calculatePriority([
                    'tanggal'           => $row['tanggal'],
                    'tanggal_masuk'     => $row['tanggal_masuk'],
                    'nama_ekspedisi'    => $resolved['nama_ekspedisi'],
                    'layanan_ekspedisi' => $resolved['layanan_ekspedisi'],
                    'cutoff_time'       => $resolved['cutoff_time'],
                    'batch_volume'      => $batchVol,
                ], $now);

                $stmt->execute([
                    'nama_ekspedisi'    => $calc['nama_ekspedisi'],
                    'layanan_ekspedisi' => $calc['layanan_ekspedisi'],
                    'cutoff_time'       => $calc['cutoff_time'],
                    'priority_score'    => $calc['priority_score'],
                    'score_breakdown'   => $calc['score_breakdown_json'],
                    'scheduled_at'      => $scheduledTimestamp,
                    'id'                => $row['id'],
                ]);
                $updated++;
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        return [
            'updated'     => $updated,
            'tanggal'     => $targetDate,
            'duration_ms' => round((microtime(true) - $t0) * 1000, 2)
        ];
    }

    /**
     * Auto-refresh ringan (tanpa memerlukan cron daemon eksternal):
     * Memperbarui komponen waktu dinamis (Cut-Off & Aging) pada antrean Pending
     * apabila pembaruan terakhir lebih lama dari $maxAgeSeconds (default 300 detik / 5 menit).
     */
    public function refreshIfStale(string $tanggal, int $maxAgeSeconds = 300): bool
    {
        $check = $this->db->fetch(
            "SELECT COUNT(*) AS pending_count,
                    SUM(CASE WHEN scheduled_at IS NULL THEN 1 ELSE 0 END) AS unscheduled_count,
                    MIN(scheduled_at) AS oldest_scheduled
             FROM paket
             WHERE is_deleted = 0 AND status = 'Pending' AND tanggal = :tanggal",
            ['tanggal' => $tanggal]
        );

        $pendingCount = (int)($check['pending_count'] ?? 0);
        if ($pendingCount === 0) {
            return false;
        }

        $unscheduledCount = (int)($check['unscheduled_count'] ?? 0);
        if ($unscheduledCount > 0) {
            $this->recalculateDailyQueue($tanggal, true);
            return true;
        }

        $oldest = $check['oldest_scheduled'] ?? null;
        if ($oldest === null || (time() - strtotime((string)$oldest)) >= $maxAgeSeconds) {
            $this->recalculateDailyQueue($tanggal, true);
            return true;
        }

        return false;
    }
}

