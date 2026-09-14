<?php

declare(strict_types=1);

namespace App\Core;

/**
 * คลาสแม่ของ Controller — render view, ตอบ JSON, และตรวจสิทธิ์
 */
abstract class Controller
{
    /**
     * แสดงผล view โดยครอบด้วย layout
     * @param string $view  เช่น 'documents/index'
     * @param array  $data  ตัวแปรที่ส่งเข้า view
     * @param string $layout 'main' | 'print_portrait' | 'print_landscape' | '' (ไม่ใช้ layout)
     */
    protected function view(string $view, array $data = [], string $layout = 'main'): void
    {
        $file = APP_PATH . '/Views/' . $view . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("ไม่พบไฟล์ view: {$view}");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $file;
        $content = ob_get_clean();

        if ($layout === '') {
            echo $content;
            return;
        }

        $layoutFile = APP_PATH . '/Views/layouts/' . $layout . '.php';
        if (!is_file($layoutFile)) {
            throw new \RuntimeException("ไม่พบ layout: {$layout}");
        }
        require $layoutFile;
    }

    protected function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** ต้องล็อกอินก่อน ไม่งั้นเด้งไปหน้า login */
    protected function requireLogin(): void
    {
        if (!Auth::check()) {
            $_SESSION['_intended'] = $_SERVER['REQUEST_URI'] ?? url('/');
            redirect('/login');
        }
    }

    /** จำกัดสิทธิ์ตาม role */
    protected function requireRole(string ...$roles): void
    {
        $this->requireLogin();
        if (!in_array(Auth::role(), $roles, true)) {
            $this->abort(403, 'คุณไม่มีสิทธิ์เข้าถึงส่วนนี้');
        }
    }

    /** สิทธิ์ลงทะเบียน/แก้ไขหนังสือ (admin + clerk) */
    protected function requireClerk(): void
    {
        $this->requireRole('admin', 'clerk');
    }

    /** ตรวจ CSRF ของทุก POST */
    protected function requireCsrf(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            $this->abort(419, 'เซสชันหมดอายุหรือแบบฟอร์มไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง');
        }
    }

    protected function abort(int $status, string $message): void
    {
        http_response_code($status);
        // หน้า error เป็นเอกสารเต็มใบในตัวเอง จึงต้องไม่ครอบด้วย layout หลัก
        $this->view('errors/show', ['status' => $status, 'message' => $message], '');
        exit;
    }

    /** ค่าจากฟอร์มแบบตัดช่องว่างหัวท้าย */
    protected function input(string $key, string $default = ''): string
    {
        $value = $_POST[$key] ?? $default;
        return is_string($value) ? trim($value) : $default;
    }

    /** ค่าจากฟอร์มที่เป็นตัวเลข คืน null ถ้าว่าง */
    protected function intInput(string $key): ?int
    {
        $value = $_POST[$key] ?? '';
        return ($value === '' || $value === null) ? null : (int) $value;
    }
}
