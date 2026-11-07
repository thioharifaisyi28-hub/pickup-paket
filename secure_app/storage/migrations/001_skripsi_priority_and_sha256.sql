-- ============================================================================
-- MIGRATION 001: IMPLEMENTASI PRIORITY SCHEDULING & INTEGRITAS DATA SHA-256
-- Target Database : pickup_skripsi_db
-- Sistem          : Sistem Informasi Pick-Up Paket PT Polo Sport Indonesia
-- Prinsip         : Backward-Compatible, 3NF Reference, Indexed Queue & Audit
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------------------------
-- 1. TABEL REFERENSI PARAMETER EKSPEDISI & SLA (master_ekspedisi)
--    Menyimpan jam cut-off operasional dan bobot dasar layanan (T_i)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `master_ekspedisi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode_ekspedisi` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `nama_lengkap` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `default_layanan` enum('INSTANT','SAMEDAY','EXPRESS','REGULER','CARGO') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'REGULER',
  `bobot_layanan` int NOT NULL DEFAULT 50 COMMENT 'Skor SLA T_i (0-100)',
  `cutoff_time` time NOT NULL DEFAULT '17:00:00' COMMENT 'Batas jam penjemputan kurir harian',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_kode_ekspedisi` (`kode_ekspedisi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `master_ekspedisi` (`kode_ekspedisi`, `nama_lengkap`, `default_layanan`, `bobot_layanan`, `cutoff_time`, `is_active`) VALUES
  ('INSTANT', 'Kurir Instant (GoSend / Grab / SPX Instant)', 'INSTANT', 100, '16:00:00', 1),
  ('SAMEDAY', 'Kurir Same-Day Delivery',                     'SAMEDAY', 100, '15:00:00', 1),
  ('EXPRESS', 'Layanan Next Day / Express',                  'EXPRESS',  75, '16:30:00', 1),
  ('SPX',     'Shopee Xpress (SPX)',                         'REGULER',  50, '17:00:00', 1),
  ('LEX',     'Lazada Express (LEX)',                        'REGULER',  50, '16:30:00', 1),
  ('JNT',     'J&T Express',                                 'REGULER',  50, '18:00:00', 1),
  ('JNE',     'JNE Express',                                 'REGULER',  50, '18:00:00', 1),
  ('SICEPAT', 'SiCepat Ekspres',                             'REGULER',  50, '17:30:00', 1),
  ('NINJA',   'Ninja Xpress',                                'REGULER',  50, '17:30:00', 1),
  ('ID',      'ID Express',                                  'REGULER',  50, '17:30:00', 1),
  ('CARGO',   'Layanan Kargo / Hemat / Bulky',               'CARGO',    25, '19:00:00', 1)
ON DUPLICATE KEY UPDATE
  `nama_lengkap`    = VALUES(`nama_lengkap`),
  `default_layanan` = VALUES(`default_layanan`),
  `bobot_layanan`   = VALUES(`bobot_layanan`),
  `cutoff_time`     = VALUES(`cutoff_time`);

-- ----------------------------------------------------------------------------
-- 2. TABEL TARGET KUANTITAS HARIAN EKSPEDISI (ekspedisi_target_qty)
--    Memastikan tabel acuan target QTY Admin tersedia secara eksplisit
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ekspedisi_target_qty` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_ekspedisi` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `tanggal` date NOT NULL,
  `total_qty` int NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ekspedisi_tanggal_unique` (`nama_ekspedisi`, `tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------------------------------------------------------
-- 3. PENYESUAIAN TABEL ANTREAN UTAMA (paket) — PRIORITY SCHEDULING
--    Rumus: S_i = (0.40 * C_i) + (0.30 * T_i) + (0.15 * V_i) + (0.15 * A_i)
-- ----------------------------------------------------------------------------
ALTER TABLE `paket`
  ADD COLUMN `nama_ekspedisi` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'SPX' AFTER `sesi`,
  ADD COLUMN `layanan_ekspedisi` enum('INSTANT','SAMEDAY','EXPRESS','REGULER','CARGO') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'REGULER' AFTER `nama_ekspedisi`,
  ADD COLUMN `jumlah_item` int NOT NULL DEFAULT 1 AFTER `layanan_ekspedisi`,
  ADD COLUMN `cutoff_time` time DEFAULT '17:00:00' AFTER `jam`,
  ADD COLUMN `priority_score` decimal(5,2) NOT NULL DEFAULT 0.00 AFTER `cutoff_time`,
  ADD COLUMN `score_breakdown` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL AFTER `priority_score`,
  ADD COLUMN `scheduled_at` datetime DEFAULT NULL AFTER `score_breakdown`,
  ADD INDEX `idx_priority_queue` (`is_deleted`, `status`, `tanggal`, `priority_score` DESC, `tanggal_masuk` ASC),
  ADD INDEX `idx_paket_ekspedisi` (`nama_ekspedisi`, `layanan_ekspedisi`);

-- ----------------------------------------------------------------------------
-- 4. PENYESUAIAN TABEL SERAH TERIMA FISIK (foto_ekspedisi) — SHA-256 SEAL
--    Mengikat identitas kurir, QTY fisik, hash biner foto, dan canonical string
-- ----------------------------------------------------------------------------
ALTER TABLE `foto_ekspedisi`
  ADD COLUMN `nama_kurir` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL AFTER `nama_petugas`,
  ADD COLUMN `no_polisi_kurir` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL AFTER `nama_kurir`,
  ADD COLUMN `qty_admin` int NOT NULL DEFAULT 0 AFTER `nama_ekspedisi`,
  ADD COLUMN `qty_staff` int NOT NULL DEFAULT 0 AFTER `qty_admin`,
  ADD COLUMN `photo_hash` char(64) COLLATE utf8mb4_general_ci DEFAULT NULL AFTER `foto`,
  ADD COLUMN `payload_string` text COLLATE utf8mb4_general_ci DEFAULT NULL AFTER `hash_blockchain`,
  ADD COLUMN `status_verifikasi` enum('VALID','TAMPERED','LEGACY') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'VALID' AFTER `payload_string`,
  ADD COLUMN `verified_at` datetime DEFAULT NULL AFTER `status_verifikasi`,
  ADD INDEX `idx_foto_ekspedisi_tanggal` (`nama_ekspedisi`, `created_at`),
  ADD INDEX `idx_foto_status_verifikasi` (`status_verifikasi`);

-- Sinkronisasi data historis pra-skripsi agar photo_hash terisi dan berstatus LEGACY
UPDATE `foto_ekspedisi`
SET `photo_hash` = `hash_blockchain`,
    `status_verifikasi` = 'LEGACY'
WHERE `payload_string` IS NULL AND `hash_blockchain` IS NOT NULL;

-- ----------------------------------------------------------------------------
-- 5. PENYESUAIAN TABEL MANIFEST RESI (hasil_ekspedisi & hasil_ekspedisi_detail)
--    Menyimpan SHA-256 berkas manifest, agregat resi, dan relasi foto fisik
-- ----------------------------------------------------------------------------
ALTER TABLE `hasil_ekspedisi`
  ADD COLUMN `file_hash` char(64) COLLATE utf8mb4_general_ci DEFAULT NULL AFTER `path_file`,
  ADD COLUMN `manifest_hash` char(64) COLLATE utf8mb4_general_ci DEFAULT NULL AFTER `file_hash`,
  ADD COLUMN `foto_ekspedisi_id` int DEFAULT NULL AFTER `manifest_hash`,
  ADD INDEX `idx_manifest_hash` (`manifest_hash`),
  ADD INDEX `idx_hasil_foto_ekspedisi` (`foto_ekspedisi_id`),
  ADD CONSTRAINT `fk_hasil_foto_ekspedisi`
    FOREIGN KEY (`foto_ekspedisi_id`) REFERENCES `foto_ekspedisi` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `hasil_ekspedisi_detail`
  ADD COLUMN `resi_hash` char(64) COLLATE utf8mb4_general_ci DEFAULT NULL AFTER `status_scan`,
  ADD INDEX `idx_resi_hash` (`resi_hash`);

-- ----------------------------------------------------------------------------
-- 6. TABEL LOG AUDIT FORENSIK INTEGRITAS DATA (integrity_audit_logs)
--    Mencatat setiap pengujian keaslian hash SHA-256 untuk pembuktian audit
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `integrity_audit_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `target_type` enum('FOTO_EKSPEDISI','HASIL_EKSPEDISI') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'FOTO_EKSPEDISI',
  `target_id` int NOT NULL,
  `audited_by` int DEFAULT NULL,
  `recorded_hash` char(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `computed_hash` char(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `photo_file_valid` tinyint(1) NOT NULL DEFAULT 1,
  `payload_valid` tinyint(1) NOT NULL DEFAULT 1,
  `result_status` enum('VALID','TAMPERED','FILE_MISSING') COLLATE utf8mb4_general_ci NOT NULL,
  `notes` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `audited_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_target` (`target_type`, `target_id`),
  KEY `idx_audit_time` (`audited_at`),
  KEY `fk_audit_user` (`audited_by`),
  CONSTRAINT `fk_audit_user`
    FOREIGN KEY (`audited_by`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

