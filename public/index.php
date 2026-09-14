<?php
/**
 * Front controller — ทุก request ผ่านไฟล์นี้
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

use App\Core\Auth;
use App\Core\Router;

date_default_timezone_set('Asia/Bangkok');
mb_internal_encoding('UTF-8');

Auth::start();

$router = new Router();

// ---------- เข้าสู่ระบบ ----------
$router->get('/login', 'AuthController@showLogin');
$router->post('/login', 'AuthController@login');
$router->post('/logout', 'AuthController@logout');

// ---------- แดชบอร์ด ----------
$router->get('/', 'DashboardController@index');

// ---------- ทะเบียนหนังสือ ----------
$router->get('/documents', 'DocumentController@index');
$router->get('/documents/create', 'DocumentController@create');
$router->post('/documents', 'DocumentController@store');
$router->get('/documents/print/register', 'PrintController@register');
$router->get('/documents/{id}', 'DocumentController@show');
$router->get('/documents/{id}/edit', 'DocumentController@edit');
$router->post('/documents/{id}', 'DocumentController@update');
$router->post('/documents/{id}/assign', 'DocumentController@assign');
$router->post('/documents/{id}/status', 'DocumentController@changeStatus');
$router->get('/documents/{id}/print/cover', 'PrintController@cover');

// ---------- ไฟล์แนบ ----------
$router->post('/documents/{id}/attachments', 'AttachmentController@upload');
$router->get('/attachments/{id}', 'AttachmentController@download');
$router->post('/attachments/{id}/delete', 'AttachmentController@destroy');

// ---------- งานที่มอบหมาย ----------
$router->post('/assignments/{id}/respond', 'DocumentController@respondAssignment');

// ---------- ผู้ดูแลระบบ ----------
$router->get('/admin/users', 'AdminController@users');
$router->post('/admin/users', 'AdminController@saveUser');
$router->post('/admin/users/{id}/delete', 'AdminController@deleteUser');
$router->get('/admin/departments', 'AdminController@departments');
$router->post('/admin/departments', 'AdminController@saveDepartment');
$router->post('/admin/departments/{id}/delete', 'AdminController@deleteDepartment');
$router->get('/admin/organizations', 'AdminController@organizations');
$router->post('/admin/organizations', 'AdminController@saveOrganization');
$router->post('/admin/organizations/{id}/delete', 'AdminController@deleteOrganization');
$router->get('/admin/settings', 'AdminController@settings');
$router->post('/admin/settings', 'AdminController@saveSettings');

try {
    $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
} catch (\Throwable $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL, 3, LOG_PATH . '/error.log');
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><title>เกิดข้อผิดพลาด</title>';
    echo '<div style="font-family:Tahoma,sans-serif;max-width:720px;margin:60px auto;padding:24px;border:1px solid #e5e7eb;border-radius:12px">';
    echo '<h2 style="color:#b91c1c;margin-top:0">เกิดข้อผิดพลาดในระบบ</h2>';
    echo '<p style="color:#334155">' . e($e->getMessage()) . '</p>';
    echo '<p style="color:#94a3b8;font-size:13px">รายละเอียดถูกบันทึกไว้ที่ storage/logs/error.log</p>';
    echo '<a href="' . e(url('/')) . '" style="color:#1d4ed8">กลับหน้าแรก</a></div>';
}
