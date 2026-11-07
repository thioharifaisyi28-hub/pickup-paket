<?php
/**
 * IntegritySha256Service.php
 *
 * Implementasi Algoritma Fungsi Hash Kriptografis SHA-256 (Secure Hash Algorithm 256-bit)
 * untuk Pengamanan Bukti Serah Terima Fisik, Integritas Manifest Resi, dan Audit Forensik
 * pada Sistem Informasi Pick-Up Paket PT Polo Sport Indonesia.
 *
 * Skema String Kanonikal (POLO-SHA256-V1):
 *   POLO-SHA256-V1|EKSP:{ekspedisi}|UID:{user_id}|PETUGAS:{nama_petugas}|KURIR:{nama_kurir}|
 *   NOPOL:{no_polisi}|QTY_ADM:{qty_admin}|QTY_STF:{qty_staff}|WAKTU:{timestamp}|
 *   IMG_SHA256:{photo_hash}|MANIFEST_SHA256:{manifest_hash}
 */

class IntegritySha256Service
{
    public const SCHEMA_VERSION = 'POLO-SHA256-V1';
    public const HASH_ALGO      = 'sha256';

    private Database $db;
    private string $publicHtmlRoot;

    public function __construct(?Database $db = null, ?string $publicHtmlRoot = null)
    {
        $this->db = $db ?? Database::getInstance();
        $this->publicHtmlRoot = $publicHtmlRoot
            ?? realpath(BASE_PATH . '/../public_html')
            ?: (BASE_PATH . '/../public_html');
    }

    /**
     * Menghitung SHA-256 dari string teks murni (64-char lowercase hex)
     */
    public function hashString(string $input): string
    {
        return hash(self::HASH_ALGO, $input);
    }

    /**
     * Menghitung SHA-256 langsung dari biner berkas fisik di disk
     */
    public function hashFile(string $absolutePath): string
    {
        if (!is_file($absolutePath) || !is_readable($absolutePath)) {
            throw new \RuntimeException("Berkas fisik tidak ditemukan atau tidak dapat dibaca: {$absolutePath}");
        }

        $digest = hash_file(self::HASH_ALGO, $absolutePath);
        if ($digest === false) {
            throw new \RuntimeException("Gagal menghitung digest SHA-256 pada berkas: {$absolutePath}");
        }

        return strtolower($digest);
    }

    /**
     * Sanitasi token kanonikal untuk mencegah Canonical Delimiter Injection
     */
    public static function sanitizeToken(?string $value, string $fallback = '-'): string
    {
        $clean = trim((string)($value ?? ''));
        if ($clean === '') {
            return $fallback;
        }
        // Ganti karakter delimiter '|' atau newline agar struktur kanonikal 100% deterministik
        $clean = str_replace(['|', "\r", "\n"], ['/', ' ', ' '], $clean);
        return preg_replace('/\s+/', ' ', $clean) ?? $fallback;
    }

    /**
     * Membentuk String Kanonikal Serah Terima Paket (Deterministic State Representation)
     *
     * @param array<string, mixed> $data
     */
    public function buildCanonicalPayload(array $data): string
    {
        $ekspedisi    = strtoupper(self::sanitizeToken((string)($data['nama_ekspedisi'] ?? ''), 'UNKNOWN'));
        $userId       = (int)($data['user_id'] ?? 0);
        $namaPetugas  = self::sanitizeToken((string)($data['nama_petugas'] ?? ''), 'UNKNOWN');
        $namaKurir    = self::sanitizeToken((string)($data['nama_kurir'] ?? ''), '-');
        $noPolisi     = strtoupper(self::sanitizeToken((string)($data['no_polisi_kurir'] ?? ''), '-'));
        $qtyAdmin     = (int)($data['qty_admin'] ?? 0);
        $qtyStaff     = (int)($data['qty_staff'] ?? 0);
        $timestamp    = self::normalizeTimestamp((string)($data['timestamp'] ?? date('Y-m-d H:i:s')));
        $photoHash    = strtolower(self::sanitizeToken((string)($data['photo_hash'] ?? ''), 'NONE'));
        $manifestHash = strtolower(self::sanitizeToken((string)($data['manifest_hash'] ?? ''), 'NONE'));

        $segments = [
            self::SCHEMA_VERSION,
            "EKSP:{$ekspedisi}",
            "UID:{$userId}",
            "PETUGAS:{$namaPetugas}",
            "KURIR:{$namaKurir}",
            "NOPOL:{$noPolisi}",
            "QTY_ADM:{$qtyAdmin}",
            "QTY_STF:{$qtyStaff}",
            "WAKTU:{$timestamp}",
            "IMG_SHA256:{$photoHash}",
            "MANIFEST_SHA256:{$manifestHash}",
        ];

        return implode('|', $segments);
    }

    /**
     * Menghasilkan Segel Digital SHA-256 Lengkap saat Serah Terima Fisik Paket
     *
     * @param array<string, mixed> $metadata
     * @return array{photo_hash: string, payload_string: string, verification_hash: string, timestamp: string}
     */
    public function createHandoverSeal(array $metadata, string $absolutePhotoPath): array
    {
        $photoHash = $this->hashFile($absolutePhotoPath);
        $timestamp = self::normalizeTimestamp((string)($metadata['timestamp'] ?? date('Y-m-d H:i:s')));

        $payloadData = array_merge($metadata, [
            'timestamp'  => $timestamp,
            'photo_hash' => $photoHash,
        ]);

        $payloadString    = $this->buildCanonicalPayload($payloadData);
        $verificationHash = $this->hashString($payloadString);

        return [
            'photo_hash'        => $photoHash,
            'payload_string'    => $payloadString,
            'verification_hash' => $verificationHash,
            'timestamp'         => $timestamp,
        ];
    }

    /**
     * Menghitung SHA-256 individual untuk satu nomor resi pada detail manifest
     */
    public function hashResiRecord(string $resi, string $ekspedisi, string $tanggal): string
    {
        $canonical = implode('|', [
            'POLO-RESI-V1',
            'RESI:' . strtoupper(self::sanitizeToken($resi, 'EMPTY')),
            'EKSP:' . strtoupper(self::sanitizeToken($ekspedisi, 'UMUM')),
            'TGL:' . self::normalizeTimestamp($tanggal),
        ]);

        return $this->hashString($canonical);
    }

    /**
     * Menghitung Aggregate SHA-256 untuk seluruh kumpulan nomor resi pada suatu Manifest.
     * Daftar resi diurutkan secara leksikografis (set-canonical) agar deterministik.
     *
     * @param array<int, string> $resiList
     */
    public function computeManifestAggregateHash(array $resiList, string $ekspedisi = ''): string
    {
        $normalized = [];
        foreach ($resiList as $resi) {
            $clean = strtoupper(trim((string)$resi));
            if ($clean !== '') {
                $normalized[] = self::sanitizeToken($clean);
            }
        }

        sort($normalized, SORT_STRING);

        $header = 'POLO-MANIFEST-V1|EKSP:' . strtoupper(self::sanitizeToken($ekspedisi, 'UMUM'))
            . '|COUNT:' . count($normalized)
            . '|ITEMS:' . implode(',', $normalized);

        return $this->hashString($header);
    }

    /**
     * Audit & Verifikasi Kriptografis Rekaman Bukti Serah Terima (foto_ekspedisi)
     * Memeriksa keutuhan biner foto fisik DAN keutuhan kolom metadata di database.
     *
     * @return array<string, mixed>
     */
    public function verifyFotoEkspedisi(
        int $fotoId,
        ?int $auditedBy = null,
        bool $recordAuditLog = true
    ): array {
        $record = $this->db->fetch(
            "SELECT * FROM foto_ekspedisi WHERE id = :id LIMIT 1",
            ['id' => $fotoId]
        );

        if (!$record) {
            return [
                'success' => false,
                'status'  => 'NOT_FOUND',
                'message' => "Data serah terima #{$fotoId} tidak ditemukan.",
            ];
        }

        $relativeFoto = ltrim((string)($record['foto'] ?? ''), '/\\');
        $absoluteFoto = rtrim($this->publicHtmlRoot, '/\\') . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativeFoto);
        $recordedHash = strtolower((string)($record['hash_blockchain'] ?? ''));
        $storedPayload = $record['payload_string'] ?? null;

        // Layer 1: Cek Keberadaan & Hash Biner Berkas Foto Fisik
        if ($relativeFoto === '' || !is_file($absoluteFoto)) {
            $result = [
                'success'          => true,
                'id'               => $fotoId,
                'status'           => 'FILE_MISSING',
                'is_valid'         => false,
                'photo_file_valid' => false,
                'payload_valid'    => false,
                'recorded_hash'    => $recordedHash,
                'computed_hash'    => null,
                'discrepancies'    => ['Berkas fisik foto bukti serah terima tidak ditemukan di media penyimpanan server.'],
                'message'          => 'PERINGATAN INTEGRITAS: Berkas foto bukti fisik hilang dari server!',
            ];
            $this->persistVerificationOutcome($fotoId, 'TAMPERED', $auditedBy, $result, $recordAuditLog);
            return $result;
        }

        $currentPhotoHash = $this->hashFile($absoluteFoto);

        // Penanganan Rekaman Historis Pra-Skripsi (LEGACY: hash_blockchain = sha256(file))
        if ($storedPayload === null || trim((string)$storedPayload) === '') {
            $expectedPhotoHash = strtolower((string)($record['photo_hash'] ?: $recordedHash));
            $photoMatch = ($expectedPhotoHash !== '') && hash_equals($expectedPhotoHash, $currentPhotoHash);
            $status = $photoMatch ? 'LEGACY' : 'TAMPERED';

            $result = [
                'success'          => true,
                'id'               => $fotoId,
                'status'           => $status,
                'is_valid'         => $photoMatch,
                'photo_file_valid' => $photoMatch,
                'payload_valid'    => $photoMatch,
                'recorded_hash'    => $recordedHash,
                'computed_hash'    => $currentPhotoHash,
                'discrepancies'    => $photoMatch ? [] : ['Biner berkas foto fisik tidak cocok dengan hash SHA-256 awal.'],
                'message'          => $photoMatch
                    ? 'Data historis (Pra- upgrade kanonikal): Berkas foto fisik terverifikasi utuh.'
                    : 'PERINGATAN INTEGRITAS: Berkas foto fisik pada data historis telah dimodifikasi!',
            ];

            if ($recordAuditLog) {
                $this->logAudit(
                    'FOTO_EKSPEDISI',
                    $fotoId,
                    $auditedBy,
                    $recordedHash,
                    $currentPhotoHash,
                    $photoMatch,
                    $photoMatch,
                    $photoMatch ? 'VALID' : 'TAMPERED',
                    $result['message']
                );
            }
            if (!$photoMatch) {
                $this->db->query(
                    "UPDATE foto_ekspedisi SET status_verifikasi = 'TAMPERED', verified_at = :now WHERE id = :id",
                    ['now' => date('Y-m-d H:i:s'), 'id' => $fotoId]
                );
            }
            return $result;
        }

        // Layer 2: Rekonstruksi String Kanonikal dari Kolom Aktif Database + Hash Foto Fisik Saat Ini
        $parsedOriginal = self::parseCanonicalPayload((string)$storedPayload);
        $timestampToUse = $parsedOriginal['WAKTU'] ?? self::normalizeTimestamp((string)($record['created_at'] ?? ''));
        $manifestHashToUse = $parsedOriginal['MANIFEST_SHA256'] ?? 'NONE';

        // Cek apakah ada manifest yang tertaut ke foto_ekspedisi ini
        $linkedManifest = $this->db->fetch(
            "SELECT manifest_hash FROM hasil_ekspedisi WHERE foto_ekspedisi_id = :fid AND deleted_at IS NULL ORDER BY id DESC LIMIT 1",
            ['fid' => $fotoId]
        );
        if ($linkedManifest && !empty($linkedManifest['manifest_hash']) && $manifestHashToUse !== 'none') {
            $manifestHashToUse = (string)$linkedManifest['manifest_hash'];
        }

        $reconstructedPayload = $this->buildCanonicalPayload([
            'nama_ekspedisi'  => $record['nama_ekspedisi'] ?? '',
            'user_id'         => $record['user_id'] ?? 0,
            'nama_petugas'    => $record['nama_petugas'] ?? '',
            'nama_kurir'      => $record['nama_kurir'] ?? '-',
            'no_polisi_kurir' => $record['no_polisi_kurir'] ?? '-',
            'qty_admin'       => $record['qty_admin'] ?? 0,
            'qty_staff'       => $record['qty_staff'] ?? 0,
            'timestamp'       => $timestampToUse,
            'photo_hash'      => $currentPhotoHash,
            'manifest_hash'   => $manifestHashToUse,
        ]);

        $computedHash   = $this->hashString($reconstructedPayload);
        $recordedPhoto  = strtolower((string)($record['photo_hash'] ?? ($parsedOriginal['IMG_SHA256'] ?? '')));
        $photoFileValid = ($recordedPhoto !== '') && hash_equals($recordedPhoto, $currentPhotoHash);
        $sealValid      = ($recordedHash !== '') && hash_equals($recordedHash, $computedHash);
        $isValid        = $photoFileValid && $sealValid;

        // Forensik: Identifikasi atribut spesifik yang berubah jika segel rusak
        $discrepancies = [];
        if (!$photoFileValid) {
            $discrepancies[] = 'Biner berkas foto fisik telah berubah / tidak identik dengan foto saat serah terima.';
        }
        if (!$sealValid) {
            $parsedCurrent = self::parseCanonicalPayload($reconstructedPayload);
            $fieldLabels = [
                'EKSP'            => 'Nama Ekspedisi',
                'UID'             => 'ID User Petugas',
                'PETUGAS'         => 'Nama Petugas Gudang',
                'KURIR'           => 'Nama Kurir Penjemput',
                'NOPOL'           => 'No. Polisi Kendaraan',
                'QTY_ADM'         => 'Kuantitas Target Admin',
                'QTY_STF'         => 'Kuantitas Fisik Serah Terima',
                'WAKTU'           => 'Timestamp Serah Terima',
                'MANIFEST_SHA256' => 'Hash Manifest Resi',
            ];
            foreach ($fieldLabels as $key => $label) {
                $origVal = $parsedOriginal[$key] ?? null;
                $currVal = $parsedCurrent[$key] ?? null;
                if ($origVal !== null && $origVal !== $currVal) {
                    $discrepancies[] = "Perubahan tidak sah pada {$label}: tercatat di segel '{$origVal}', nilai DB saat ini '{$currVal}'.";
                }
            }
            if (empty($discrepancies)) {
                $discrepancies[] = 'Nilai hash_blockchain atau payload_string di database telah dimodifikasi secara langsung.';
            }
        }

        $finalStatus = $isValid ? 'VALID' : 'TAMPERED';
        $message = $isValid
            ? 'TERVERIFIKASI SAH (VALID): Bukti foto fisik dan seluruh metadata serah terima 100% utuh sejak transaksi disimpan.'
            : 'PERINGATAN MANIPULASI (TAMPERED): Segel kriptografi SHA-256 tidak cocok! Data atau berkas foto telah mengalami perubahan.';

        $result = [
            'success'               => true,
            'id'                    => $fotoId,
            'status'                => $finalStatus,
            'is_valid'              => $isValid,
            'photo_file_valid'      => $photoFileValid,
            'payload_valid'         => $sealValid,
            'recorded_hash'         => $recordedHash,
            'computed_hash'         => $computedHash,
            'current_photo_hash'    => $currentPhotoHash,
            'reconstructed_payload' => $reconstructedPayload,
            'discrepancies'         => $discrepancies,
            'message'               => $message,
        ];

        $this->persistVerificationOutcome($fotoId, $finalStatus, $auditedBy, $result, $recordAuditLog);

        return $result;
    }

    /**
     * Audit & Verifikasi Kriptografis Manifest Resi (hasil_ekspedisi & hasil_ekspedisi_detail)
     *
     * @return array<string, mixed>
     */
    public function verifyHasilEkspedisi(
        int $hasilId,
        ?int $auditedBy = null,
        bool $recordAuditLog = true
    ): array {
        $manifest = $this->db->fetch(
            "SELECT * FROM hasil_ekspedisi WHERE id = :id AND deleted_at IS NULL LIMIT 1",
            ['id' => $hasilId]
        );

        if (!$manifest) {
            return [
                'success' => false,
                'status'  => 'NOT_FOUND',
                'message' => "Manifest #{$hasilId} tidak ditemukan.",
            ];
        }

        $recordedManifestHash = strtolower((string)($manifest['manifest_hash'] ?? ''));
        $recordedFileHash     = strtolower((string)($manifest['file_hash'] ?? ''));

        $details = $this->db->fetchAll(
            "SELECT resi FROM hasil_ekspedisi_detail WHERE hasil_ekspedisi_id = :hid",
            ['hid' => $hasilId]
        );
        $resiList = array_column($details, 'resi');
        $computedManifestHash = $this->computeManifestAggregateHash($resiList, (string)$manifest['ekspedisi']);

        // Cek file Excel asli di secure_app/storage/...
        $excelPath = BASE_PATH . '/storage/' . ltrim((string)($manifest['path_file'] ?? ''), '/\\');
        $fileExists = is_file($excelPath);
        $currentFileHash = $fileExists ? $this->hashFile($excelPath) : null;

        if ($recordedManifestHash === '') {
            return [
                'success'          => true,
                'id'               => $hasilId,
                'status'           => 'LEGACY',
                'is_valid'         => true,
                'total_resi_db'    => count($resiList),
                'computed_hash'    => $computedManifestHash,
                'recorded_hash'    => null,
                'discrepancies'    => [],
                'message'          => 'Data manifest historis (belum memiliki segel manifest_hash saat unggah awal).',
            ];
        }

        $manifestValid = hash_equals($recordedManifestHash, $computedManifestHash);
        $fileValid     = ($recordedFileHash === '') || ($currentFileHash !== null && hash_equals($recordedFileHash, $currentFileHash));
        $isValid       = $manifestValid && $fileValid;

        $discrepancies = [];
        if (!$manifestValid) {
            $discrepancies[] = sprintf(
                'Daftar resi di tabel hasil_ekspedisi_detail (%d resi) tidak cocok dengan segel manifest_hash asli (ada resi yang ditambah, dihapus, atau diubah).',
                count($resiList)
            );
        }
        if (!$fileValid) {
            $discrepancies[] = $fileExists
                ? 'Berkas Excel manifest di storage telah dimodifikasi.'
                : 'Berkas Excel manifest asli tidak ditemukan di storage.';
        }

        $status = $isValid ? 'VALID' : ($fileExists ? 'TAMPERED' : 'FILE_MISSING');

        if ($recordAuditLog) {
            $this->logAudit(
                'HASIL_EKSPEDISI',
                $hasilId,
                $auditedBy,
                $recordedManifestHash,
                $computedManifestHash,
                $fileValid,
                $manifestValid,
                $status,
                $isValid ? 'Manifest resi terverifikasi utuh.' : implode(' ', $discrepancies)
            );
        }

        return [
            'success'       => true,
            'id'            => $hasilId,
            'status'        => $status,
            'is_valid'      => $isValid,
            'total_resi_db' => count($resiList),
            'recorded_hash' => $recordedManifestHash,
            'computed_hash' => $computedManifestHash,
            'file_valid'    => $fileValid,
            'discrepancies' => $discrepancies,
            'message'       => $isValid
                ? 'TERVERIFIKASI SAH (VALID): Seluruh daftar nomor resi dan berkas manifest utuh.'
                : 'PERINGATAN INTEGRITAS: Terdeteksi ketidaksesuaian pada daftar resi atau berkas manifest!',
        ];
    }

    /**
     * Utilitas Pengujian Avalanche Effect SHA-256 (untuk Bab 4 Pengujian Skripsi)
     * Menghitung jarak Hamming level-bit (dari 256 bit) antara dua input payload.
     *
     * @return array{hash_original: string, hash_modified: string, bit_flips: int, total_bits: int, avalanche_percentage: float, hex_diff_count: int}
     */
    public function measureAvalancheEffect(string $originalInput, string $modifiedInput): array
    {
        $hexA = $this->hashString($originalInput);
        $hexB = $this->hashString($modifiedInput);

        $binA = hex2bin($hexA) ?: '';
        $binB = hex2bin($hexB) ?: '';

        $bitFlips = 0;
        $byteLen = min(strlen($binA), strlen($binB));
        for ($i = 0; $i < $byteLen; $i++) {
            $xor = ord($binA[$i]) ^ ord($binB[$i]);
            // Hitung jumlah bit '1' pada hasil XOR (Brian Kernighan's bit count)
            while ($xor > 0) {
                $xor &= ($xor - 1);
                $bitFlips++;
            }
        }

        $hexDiff = 0;
        for ($i = 0; $i < 64; $i++) {
            if (($hexA[$i] ?? '') !== ($hexB[$i] ?? '')) {
                $hexDiff++;
            }
        }

        return [
            'hash_original'        => $hexA,
            'hash_modified'        => $hexB,
            'bit_flips'            => $bitFlips,
            'total_bits'           => 256,
            'avalanche_percentage' => round(($bitFlips / 256) * 100, 2),
            'hex_diff_count'       => $hexDiff,
        ];
    }

    /**
     * Simpan status hasil verifikasi ke foto_ekspedisi & tabel integrity_audit_logs
     *
     * @param array<string, mixed> $result
     */
    private function persistVerificationOutcome(
        int $fotoId,
        string $dbStatus,
        ?int $auditedBy,
        array $result,
        bool $recordAuditLog
    ): void {
        $this->db->query(
            "UPDATE foto_ekspedisi
             SET status_verifikasi = :status, verified_at = :now
             WHERE id = :id",
            [
                'status' => $dbStatus,
                'now'    => date('Y-m-d H:i:s'),
                'id'     => $fotoId,
            ]
        );

        if ($recordAuditLog) {
            $auditResultStatus = ($result['status'] === 'FILE_MISSING')
                ? 'FILE_MISSING'
                : ($result['is_valid'] ? 'VALID' : 'TAMPERED');

            $notes = !empty($result['discrepancies'])
                ? mb_substr(implode(' | ', $result['discrepancies']), 0, 250)
                : mb_substr((string)($result['message'] ?? ''), 0, 250);

            $this->logAudit(
                'FOTO_EKSPEDISI',
                $fotoId,
                $auditedBy,
                $result['recorded_hash'] ?? null,
                $result['computed_hash'] ?? null,
                (bool)($result['photo_file_valid'] ?? false),
                (bool)($result['payload_valid'] ?? false),
                $auditResultStatus,
                $notes
            );
        }
    }

    /**
     * Mencatat log audit forensik ke tabel integrity_audit_logs
     */
    public function logAudit(
        string $targetType,
        int $targetId,
        ?int $auditedBy,
        ?string $recordedHash,
        ?string $computedHash,
        bool $photoFileValid,
        bool $payloadValid,
        string $resultStatus,
        string $notes = ''
    ): void {
        $this->db->query(
            "INSERT INTO integrity_audit_logs
             (target_type, target_id, audited_by, recorded_hash, computed_hash, photo_file_valid, payload_valid, result_status, notes)
             VALUES (:target_type, :target_id, :audited_by, :recorded_hash, :computed_hash, :photo_valid, :payload_valid, :result_status, :notes)",
            [
                'target_type'   => $targetType,
                'target_id'     => $targetId,
                'audited_by'    => $auditedBy ?: null,
                'recorded_hash' => $recordedHash,
                'computed_hash' => $computedHash,
                'photo_valid'   => $photoFileValid ? 1 : 0,
                'payload_valid' => $payloadValid ? 1 : 0,
                'result_status' => $resultStatus,
                'notes'         => mb_substr($notes, 0, 250),
            ]
        );
    }

    /**
     * Mem-parsing string kanonikal kembali ke associative array key => value
     *
     * @return array<string, string>
     */
    public static function parseCanonicalPayload(string $payload): array
    {
        $parts = explode('|', $payload);
        $parsed = [];
        foreach ($parts as $idx => $part) {
            if ($idx === 0 && str_starts_with($part, 'POLO-')) {
                $parsed['VERSION'] = $part;
                continue;
            }
            $pos = strpos($part, ':');
            if ($pos !== false) {
                $k = substr($part, 0, $pos);
                $v = substr($part, $pos + 1);
                $parsed[$k] = $v;
            }
        }
        return $parsed;
    }

    /**
     * Normalisasi format timestamp ke Y-m-d H:i:s agar deterministik
     */
    public static function normalizeTimestamp(string $raw): string
    {
        $trimmed = trim($raw);
        if ($trimmed === '') {
            return date('Y-m-d H:i:s');
        }
        try {
            $dt = new \DateTimeImmutable($trimmed, new \DateTimeZone('Asia/Jakarta'));
            return $dt->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return $trimmed;
        }
    }
}

