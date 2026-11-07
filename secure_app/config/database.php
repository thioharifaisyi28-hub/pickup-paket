<?php
/**
 * config/database.php - Database Configuration
 * 
 * Membaca konfigurasi dari file .env
 */

return [
    'host'     => env('DB_HOST', 'localhost'),
    'port'     => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'pickup_paket'),
    'username' => env('DB_USERNAME', 'root'),
    'password' => env('DB_PASSWORD', ''),
];
