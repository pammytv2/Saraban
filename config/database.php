<?php
/**
 * การเชื่อมต่อฐานข้อมูล MySQL
 * แก้ค่าให้ตรงกับเครื่องที่ติดตั้ง หรือกำหนดผ่าน environment variable ก็ได้
 */

declare(strict_types=1);

return [
    'host'    => getenv('DB_HOST') ?: '127.0.0.1',
    'port'    => getenv('DB_PORT') ?: '3306',
    'name'    => getenv('DB_NAME') ?: 'saraban',
    'user'    => getenv('DB_USER') ?: 'root',
    'pass'    => getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'root',
    'charset' => 'utf8mb4',
];
