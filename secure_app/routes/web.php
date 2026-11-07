<?php
/**
 * routes/web.php - Routing Configuration
 * Sistem Informasi Pick-Up Paket PT Polo Sport Indonesia (Priority Scheduling & SHA-256)
 */

// Route Auth
App::get('/login', 'AuthController@index');
App::post('/login', 'AuthController@login');
App::get('/logout', 'AuthController@logout', ['AuthMiddleware']);

// Route Dashboard
App::get('/', 'DashboardController@index', ['AuthMiddleware']);
App::get('/dashboard', 'DashboardController@index', ['AuthMiddleware']);

// Route Profil Mitra
App::get('/profil-mitra', 'ProfilMitraController@index', ['AuthMiddleware']);

// Route Paket & Priority Scheduling - Accessible by both Admin & Staff
// Catatan: Route statis ditempatkan SEBELUM route berparameter /paket/{id}
App::get('/paket', 'PaketController@index', ['AuthMiddleware']);
App::get('/paket/selectable-ids', 'PaketController@getSelectableIds', ['AuthMiddleware']);
App::post('/paket/recalculate-priority', 'PaketController@recalculatePriority', ['AuthMiddleware']);
App::get('/paket/create', 'PaketController@create', ['AuthMiddleware']);
App::post('/paket/store', 'PaketController@store', ['AuthMiddleware']);
App::get('/paket/template', 'PaketController@downloadTemplate', ['AuthMiddleware']);
App::post('/paket/import', 'PaketController@import', ['AuthMiddleware']);
App::post('/paket/delete-sudah', 'PaketController@deleteSudah', ['AuthMiddleware', 'RoleMiddleware']);
App::post('/paket/delete-all', 'PaketController@deleteAll', ['AuthMiddleware', 'RoleMiddleware']);
App::get('/paket/export', 'PaketController@export', ['AuthMiddleware']);
App::get('/paket/{id}/edit', 'PaketController@edit', ['AuthMiddleware']);
App::post('/paket/{id}/update', 'PaketController@update', ['AuthMiddleware']);
App::post('/paket/{id}/status', 'PaketController@updateStatus', ['AuthMiddleware']);
App::get('/paket/{id}', 'PaketController@show', ['AuthMiddleware']);
App::post('/paket/{id}/delete', 'PaketController@delete', ['AuthMiddleware', 'RoleMiddleware']);

// Route Foto Paket
App::get('/foto-instan/capture/{id}', 'FotoInstanController@capture', ['AuthMiddleware']);
App::match(['GET', 'POST'], '/foto-instan/capture', 'FotoInstanController@capture', ['AuthMiddleware']);
App::get('/foto-instan/{id}', 'FotoInstanController@showByPaket', ['AuthMiddleware']);
App::post('/foto-instan/store', 'FotoInstanController@store', ['AuthMiddleware']);

// Route Numbering Paket
App::get('/numbering', 'NumberingController@index', ['AuthMiddleware']);
App::get('/numbering/data', 'NumberingController@getData', ['AuthMiddleware']);
App::post('/numbering/{id}/toggle', 'NumberingController@toggleSelect', ['AuthMiddleware']);
App::post('/numbering/reset', 'NumberingController@resetAll', ['AuthMiddleware', 'RoleMiddleware']);
App::post('/numbering/delete-selected', 'NumberingController@deleteSelected', ['AuthMiddleware', 'RoleMiddleware']);
App::get('/numbering/export', 'NumberingController@export', ['AuthMiddleware']);

// Route Ekspedisi & Audit Verifikasi SHA-256
// Catatan: Route statis ditempatkan SEBELUM route berparameter /ekspedisi/{ekspedisi}
App::match(['GET', 'POST'], '/ekspedisi/verify-batch', 'EkspedisiController@verifyBatch', ['AuthMiddleware']);
App::match(['GET', 'POST'], '/ekspedisi/verify/{id}', 'EkspedisiController@verify', ['AuthMiddleware']);
App::post('/ekspedisi/store', 'EkspedisiController@store', ['AuthMiddleware']);
App::post('/ekspedisi/update-qty', 'EkspedisiController@updateQty', ['AuthMiddleware', 'RoleMiddleware']);
App::post('/ekspedisi/delete/{id}', 'EkspedisiController@delete', ['AuthMiddleware', 'RoleMiddleware']);
App::get('/ekspedisi/{ekspedisi}', 'EkspedisiController@index', ['AuthMiddleware']);
App::get('/ekspedisi/{ekspedisi}/capture', 'EkspedisiController@capture', ['AuthMiddleware']);

// Route Hasil Ekspedisi & Audit Manifest SHA-256
// Catatan: Route statis ditempatkan SEBELUM route berparameter /hasil-ekspedisi/{id}
App::get('/hasil-ekspedisi', 'HasilEkspedisiController@index', ['AuthMiddleware']);
App::match(['GET', 'POST'], '/hasil-ekspedisi/export', 'HasilEkspedisiController@export', ['AuthMiddleware']);
App::get('/hasil-ekspedisi/template', 'HasilEkspedisiController@downloadTemplate', ['AuthMiddleware']);
App::post('/hasil-ekspedisi/store', 'HasilEkspedisiController@store', ['AuthMiddleware']);
App::match(['GET', 'POST'], '/hasil-ekspedisi/{id}/verify', 'HasilEkspedisiController@verify', ['AuthMiddleware']);
App::get('/hasil-ekspedisi/{id}/show', 'HasilEkspedisiController@show', ['AuthMiddleware']);
App::get('/hasil-ekspedisi/{id}/download', 'HasilEkspedisiController@download', ['AuthMiddleware']);
App::post('/hasil-ekspedisi/{id}/delete', 'HasilEkspedisiController@delete', ['AuthMiddleware', 'RoleMiddleware']);

// Route User Management (Admin Only)
App::get('/user', 'UserController@index', ['AuthMiddleware', 'RoleMiddleware']);
App::get('/user/create', 'UserController@create', ['AuthMiddleware', 'RoleMiddleware']);
App::post('/user/store', 'UserController@store', ['AuthMiddleware', 'RoleMiddleware']);
App::get('/user/{id}/edit', 'UserController@edit', ['AuthMiddleware', 'RoleMiddleware']);
App::post('/user/{id}/update', 'UserController@update', ['AuthMiddleware', 'RoleMiddleware']);
App::post('/user/{id}/delete', 'UserController@delete', ['AuthMiddleware', 'RoleMiddleware']);
