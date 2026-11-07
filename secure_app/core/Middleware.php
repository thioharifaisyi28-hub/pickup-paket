<?php
/**
 * Middleware.php - Base Middleware
 * 
 * Interface dasar untuk semua middleware.
 */

abstract class Middleware
{
    /**
     * Handle middleware logic
     * Harus diimplementasikan oleh setiap middleware
     */
    abstract public function handle(): void;
}
