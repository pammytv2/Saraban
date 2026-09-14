# ระบบสารบรรณอิเล็กทรอนิกส์ (หนังสือรับ-ส่ง)

ระบบทะเบียนหนังสือรับ-ส่งสำหรับโรงเรียน/หน่วยงานราชการ เขียนด้วย **PHP + MySQL** ในรูปแบบ **MVC**
โดยไม่พึ่ง framework หรือ Composer — อัปไฟล์ขึ้นโฮสต์แล้วใช้งานได้ทันที

## ความสามารถ

- **ลงทะเบียนหนังสือรับ / หนังสือส่ง** — ออกเลขทะเบียนอัตโนมัติ นับแยกตามประเภทและปี พ.ศ.
  (ล็อกด้วย `SELECT ... FOR UPDATE` + UNIQUE key จึงไม่มีเลขซ้ำแม้เจ้าหน้าที่หลายคนกดบันทึกพร้อมกัน)
- **เกษียณ/มอบหมายหนังสือ** ถึงฝ่ายหรือรายบุคคล พร้อมคำสั่งการ แล้วติดตามสถานะจนปิดเรื่อง
- **แนบไฟล์สแกน** (PDF/JPG/PNG) — เก็บนอก docroot ดาวน์โหลดผ่านระบบที่ตรวจสิทธิ์ก่อนเสมอ
- **ค้นหา/กรอง** ตามคำค้น ปี ประเภท ชั้นความเร็ว สถานะ ฝ่ายเจ้าของเรื่อง และช่วงวันที่
- **หน้าพิมพ์ A4** — ใบปะหน้า/ใบเกษียณ (แนวตั้ง) และทะเบียนคุม (แนวนอน) สั่งพิมพ์หรือบันทึกเป็น PDF จากเบราว์เซอร์ได้เลย
- **แดชบอร์ด** — สถิติหนังสือรับ-ส่ง งานค้าง งานด่วน และงานที่มอบหมายถึงตนเอง
- **จัดการข้อมูลพื้นฐาน** — ผู้ใช้ ฝ่าย/กลุ่มงาน หน่วยงานภายนอก และข้อมูลหน่วยงานเจ้าของระบบ

## ความต้องการของระบบ

| รายการ | ขั้นต่ำ | หมายเหตุ |
|---|---|---|
| PHP | 7.4+ | ทดสอบบน 7.4.9 และเข้ากันได้กับ PHP 8.x |
| ส่วนขยาย PHP | `pdo_mysql`, `mbstring`, `json` | `fileinfo` ใช้ถ้ามี ถ้าไม่มีระบบจะตรวจชนิดไฟล์จาก magic bytes เอง |
| MySQL | 5.7+ / MariaDB 10.3+ | ทดสอบบน MySQL 8.4 |
| อื่นๆ | — | **ไม่ต้องใช้ Composer / ไม่ต้องใช้ ext-gd** |

## ติดตั้ง

### 1. เตรียมฐานข้อมูล

เลือกวิธีใดวิธีหนึ่ง

**ก. ใช้ Docker Compose (แนะนำ)** — มีไฟล์ `docker-compose.yml` ให้แล้ว
```bash
docker compose up -d
```
ครั้งแรกจะสร้างฐานข้อมูล `saraban` แล้ว import `database/schema.sql` + `database/seed.sql` ให้อัตโนมัติ (**ข้ามขั้นที่ 2 ได้เลย**)
รอสักครู่ให้ MySQL พร้อม ตรวจสถานะด้วย `docker compose ps`

| บริการ | เข้าถึงที่ | บัญชี |
|---|---|---|
| MySQL 8.4 | `127.0.0.1:3306` ฐานข้อมูล `saraban` | root / root |
| phpMyAdmin | <http://localhost:8081> | root / root |

คำสั่งที่ใช้บ่อย
```bash
docker compose down       # หยุด (ข้อมูลยังอยู่)
docker compose down -v    # ลบข้อมูลทั้งหมด ครั้งหน้าจะ import schema+seed ใหม่
docker compose logs -f mysql
```

> แก้ schema.sql/seed.sql แล้วอยาก import ใหม่ ให้ `docker compose down -v` แล้ว `up -d` อีกครั้ง

**ข. ใช้ XAMPP / Laragon** — เปิด MySQL แล้วสร้างฐานข้อมูล
```sql
CREATE DATABASE saraban CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2. นำเข้าโครงสร้างและข้อมูลตัวอย่าง

> ใช้ Docker Compose ตามข้อ ก. แล้ว ข้ามขั้นนี้ได้เลย

```bash
mysql -u root -p saraban < database/schema.sql
mysql -u root -p saraban < database/seed.sql
```

> ใช้ Docker ให้ใส่ `docker exec -i saraban-mysql` นำหน้าคำสั่ง `mysql`

### 3. ตั้งค่าการเชื่อมต่อ

แก้ `config/database.php` ให้ตรงกับเครื่อง (ค่าเริ่มต้นคือ `127.0.0.1:3306`, user `root`, รหัส `root`, ฐานข้อมูล `saraban`)
หรือกำหนดผ่าน environment variable: `DB_HOST` `DB_PORT` `DB_NAME` `DB_USER` `DB_PASS`

### 4. รัน

**ทดสอบด้วยเซิร์ฟเวอร์ในตัวของ PHP**
```bash
php -S localhost:8000 -t public
```
เปิด <http://localhost:8000>

**ขึ้นโฮสต์จริง (Apache/Nginx)** — ชี้ document root มาที่โฟลเดอร์ `public/` เท่านั้น
ระบบหา BASE_URL เองอัตโนมัติ ถ้าต้องการกำหนดเองให้ตั้ง env `APP_BASE_URL`

---

## การติดตั้งบน IIS (Windows)

ระบบนี้ติดตั้งอยู่ที่ `C:\inetpub\wwwroot\php\Saraban` เข้าใช้งานที่

```
http://localhost/php/Saraban/public/
```

**สิ่งที่ต่างจาก Apache**

- IIS ไม่อ่าน `.htaccess` — ใช้ `web.config` แทน (มีให้แล้ว 2 ไฟล์)
  - `web.config` ที่รากโปรเจกต์ — ปิดกั้น `app/ config/ database/ storage/` และไฟล์ `.sql .log .md`
  - `public/web.config` — ตั้ง `index.php` เป็น default document
- เครื่องนี้**ไม่มีโมดูล URL Rewrite** ระบบจึงใช้รูปแบบ `index.php/...` โดยอัตโนมัติ
  (เช่น `/php/Saraban/public/index.php/documents`) — ตรวจจับให้เองใน `detect_base_url()` ไม่ต้องตั้งค่าอะไร
  ถ้าภายหลังติดตั้ง URL Rewrite แล้วอยากได้ URL สั้นลง ให้ตั้ง env `APP_BASE_URL=/php/Saraban/public`
  แล้วเพิ่ม rewrite rule ชี้ทุก request ที่ไม่ใช่ไฟล์จริงไปที่ `index.php`

**ต้องตั้งค่า php.ini เพื่อให้แนบไฟล์ได้**

PHP บน IIS รันด้วยผู้ใช้ `IIS AppPool\DefaultAppPool` ซึ่งเขียน `C:\WINDOWS\TEMP` ไม่ได้
ทำให้อัปโหลดไฟล์ล้มเหลว (`UPLOAD_ERR_NO_TMP_DIR`) แก้ที่ `C:\php\php.ini`

```ini
upload_tmp_dir = "C:/php/tmp"
upload_max_filesize = 10M
post_max_size = 12M
```

และให้สิทธิ์เขียนโฟลเดอร์ temp

```powershell
icacls "C:\php\tmp" /grant "IIS AppPool\DefaultAppPool:(OI)(CI)M"
```

จากนั้น **รีสตาร์ต IIS ด้วยสิทธิ์ผู้ดูแลระบบ** เพื่อให้ PHP อ่านค่าใหม่

```powershell
iisreset
```

> โฟลเดอร์ `storage/uploads` และ `storage/logs` ต้องให้ผู้ใช้ของ IIS เขียนได้
> (บน wwwroot มักได้สิทธิ์มาอยู่แล้ว ถ้าไม่ได้ให้ใช้คำสั่ง `icacls` แบบเดียวกัน)

## บัญชีทดสอบ

| ชื่อผู้ใช้ | รหัสผ่าน | บทบาท | สิทธิ์ |
|---|---|---|---|
| `admin` | `admin123` | ผู้ดูแลระบบ | ทุกอย่าง + หน้าตั้งค่า |
| `clerk` | `clerk123` | เจ้าหน้าที่สารบรรณ | ลงทะเบียน แก้ไข เกษียณ แนบไฟล์ พิมพ์ |
| `staff1` | `staff123` | เจ้าหน้าที่ฝ่าย (บริหารงานบุคคล) | เห็นเฉพาะหนังสือที่มอบหมายถึงตน/ฝ่ายตน |
| `staff2` | `staff123` | เจ้าหน้าที่ฝ่าย (นโยบายและแผน) | เช่นเดียวกับ staff1 |

> ⚠️ **เปลี่ยนรหัสผ่านทั้งหมดก่อนใช้งานจริง**

## โครงสร้างโปรเจกต์

```
public/          docroot — front controller + ไฟล์ static
app/Core/        Router, Database(PDO), Controller/Model แม่, Auth, Csrf, Upload
app/Controllers/ Auth, Dashboard, Document, Attachment, Print, Admin
app/Models/      Document, Assignment, Attachment, DocLog, Reference, User
app/Views/       layouts / auth / dashboard / documents / admin / print
config/          config.php (ค่าคงที่+autoloader), database.php
database/        schema.sql, seed.sql
storage/         uploads (ไฟล์แนบ), logs (error.log) — อยู่นอก docroot
```

## โครงสร้างฐานข้อมูลโดยย่อ

- `documents` — ทะเบียนหนังสือ (รับ/ส่งอยู่ตารางเดียวกัน แยกด้วย `direction`)
  UNIQUE `(direction, reg_year, reg_number)` กันเลขทะเบียนซ้ำ
- `document_assignments` — การเกษียณ/มอบหมาย และผลการดำเนินการ
- `document_attachments` — ไฟล์แนบ (เก็บชื่อไฟล์สุ่ม ไฟล์จริงอยู่ใน `storage/uploads`)
- `document_logs` — ประวัติการดำเนินการทุกขั้นตอน (audit trail)
- `users` / `departments` / `organizations` / `provinces` / `settings` — ข้อมูลพื้นฐาน

## ข้อควรทราบด้านความปลอดภัย

- **รหัสผ่านเก็บเป็น plain text ตามที่กำหนดไว้ตอนออกแบบ** — ผู้ที่เข้าถึงฐานข้อมูลได้จะเห็นรหัสผ่านทุกคน
  หากต้องการเข้ารหัส แก้เพียง 2 จุด:
  1. `app/Models/User::verify()` → ใช้ `password_verify($password, $user['password'])`
  2. `app/Controllers/AdminController::saveUser()` → เก็บด้วย `password_hash($password, PASSWORD_DEFAULT)`
- ทุกฟอร์ม POST ตรวจ CSRF token, ทุกคำสั่ง SQL ใช้ prepared statement, ทุกข้อความที่แสดงผ่าน `e()` (escape)
- ไฟล์แนบตรวจชนิดจาก**เนื้อไฟล์จริง** ไม่เชื่อนามสกุล และเก็บนอก docroot
- ข้อมูลตัวอย่างทั้งหมดเป็นข้อมูล**สมมติ** ลบทิ้งได้ด้วย
  ```sql
  DELETE FROM documents; DELETE FROM organizations;
  DELETE FROM users WHERE username <> 'admin'; DELETE FROM departments;
  ```
