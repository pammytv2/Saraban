<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * ทะเบียนหนังสือรับ-ส่ง
 */
final class Document extends Model
{
    /** ฟิลด์ที่อนุญาตให้บันทึก/แก้ไขจากฟอร์ม */
    private const FILLABLE = [
        'doc_number', 'doc_date', 'from_org_id', 'from_text', 'to_org_id', 'to_text',
        'subject', 'detail', 'doc_type', 'speed', 'secrecy', 'owner_department_id', 'signer',
    ];

    /**
     * ลงทะเบียนหนังสือใหม่ — ออกเลขทะเบียนอัตโนมัติภายใน transaction
     * ล็อกแถวด้วย FOR UPDATE + UNIQUE KEY (direction, reg_year, reg_number) กันเลขซ้ำ
     *
     * @return array{id:int, reg_number:int, reg_year:int}
     */
    public function create(string $direction, int $regYear, array $input, ?int $userId): array
    {
        return $this->transaction(function (PDO $pdo) use ($direction, $regYear, $input, $userId): array {
            $stmt = $pdo->prepare(
                'SELECT COALESCE(MAX(reg_number), 0) + 1
                   FROM documents
                  WHERE direction = ? AND reg_year = ?
                  FOR UPDATE'
            );
            $stmt->execute([$direction, $regYear]);
            $regNumber = (int) $stmt->fetchColumn();

            $data = [
                'direction'    => $direction,
                'reg_year'     => $regYear,
                'reg_number'   => $regNumber,
                'reg_datetime' => $input['reg_datetime'] ?? date('Y-m-d H:i:s'),
                'status'       => 'pending',
                'created_by'   => $userId,
            ];
            foreach (self::FILLABLE as $field) {
                $data[$field] = $input[$field] ?? null;
            }

            $columns = array_keys($data);
            $sql = sprintf(
                'INSERT INTO documents (`%s`) VALUES (%s)',
                implode('`, `', $columns),
                implode(', ', array_fill(0, count($columns), '?'))
            );
            $insert = $pdo->prepare($sql);
            $insert->execute(array_values($data));
            $id = (int) $pdo->lastInsertId();

            $log = $pdo->prepare(
                'INSERT INTO document_logs (document_id, user_id, action, detail) VALUES (?, ?, ?, ?)'
            );
            $log->execute([
                $id,
                $userId,
                'created',
                sprintf('ลงทะเบียน%s เลขที่ %s', DOC_DIRECTIONS[$direction], reg_label($regNumber, $regYear)),
            ]);

            return ['id' => $id, 'reg_number' => $regNumber, 'reg_year' => $regYear];
        });
    }

    public function updateDocument(int $id, array $input): void
    {
        $data = [];
        foreach (self::FILLABLE as $field) {
            if (array_key_exists($field, $input)) {
                $data[$field] = $input[$field];
            }
        }
        $this->update('documents', $id, $data);
    }

    public function changeStatus(int $id, string $status): void
    {
        $this->update('documents', $id, ['status' => $status]);
    }

    /** หนังสือพร้อมชื่อหน่วยงาน/ฝ่าย/ผู้บันทึก */
    public function findFull(int $id): ?array
    {
        return $this->one(
            'SELECT d.*,
                    fo.name AS from_org_name,
                    to_.name AS to_org_name,
                    dept.name AS owner_department_name,
                    u.full_name AS created_by_name
               FROM documents d
               LEFT JOIN organizations fo  ON fo.id  = d.from_org_id
               LEFT JOIN organizations to_ ON to_.id = d.to_org_id
               LEFT JOIN departments  dept ON dept.id = d.owner_department_id
               LEFT JOIN users        u    ON u.id    = d.created_by
              WHERE d.id = ?',
            [$id]
        );
    }

    /** ชื่อผู้ส่ง/ผู้รับที่จะแสดง (เลือกจากรายการ หรือพิมพ์เอง) */
    public static function partyName(array $doc, string $side): string
    {
        $org  = $side === 'from' ? ($doc['from_org_name'] ?? null) : ($doc['to_org_name'] ?? null);
        $text = $side === 'from' ? ($doc['from_text'] ?? null)     : ($doc['to_text'] ?? null);
        $parts = array_filter([$org, $text]);
        return $parts === [] ? '-' : implode(' / ', $parts);
    }

    /**
     * ค้นหา/กรองทะเบียนหนังสือ
     * @return array{rows:array, total:int}
     */
    /** JOIN ชุดมาตรฐานที่ใช้ทั้งหน้ารายการและหน้าพิมพ์ */
    private const JOINS = '
        LEFT JOIN organizations fo  ON fo.id  = d.from_org_id
        LEFT JOIN organizations to_ ON to_.id = d.to_org_id
        LEFT JOIN departments  dept ON dept.id = d.owner_department_id ';

    private const SELECT_COLUMNS = '
        d.*,
        fo.name  AS from_org_name,
        to_.name AS to_org_name,
        dept.name AS owner_department_name ';

    public function search(array $f, int $page = 1, int $perPage = 20): array
    {
        [$where, $params] = $this->buildFilters($f);

        $total = (int) $this->value(
            'SELECT COUNT(*) FROM documents d ' . self::JOINS . $where,
            $params
        );

        $offset = max(0, ($page - 1) * $perPage);
        $rows = $this->all(
            'SELECT ' . self::SELECT_COLUMNS . ' FROM documents d ' . self::JOINS . $where
            . ' ORDER BY d.reg_year DESC, d.reg_number DESC
                LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset,
            $params
        );

        return ['rows' => $rows, 'total' => $total];
    }

    /** ใช้กับหน้าพิมพ์ทะเบียนคุม (ไม่แบ่งหน้า เรียงจากเลขน้อยไปมาก) */
    public function searchAll(array $f): array
    {
        [$where, $params] = $this->buildFilters($f);
        return $this->all(
            'SELECT ' . self::SELECT_COLUMNS . ' FROM documents d ' . self::JOINS . $where
            . ' ORDER BY d.reg_year, d.reg_number',
            $params
        );
    }

    /** @return array{0:string, 1:array} */
    private function buildFilters(array $f): array
    {
        $conds = [];
        $params = [];

        if (!empty($f['direction']) && isset(DOC_DIRECTIONS[$f['direction']])) {
            $conds[] = 'd.direction = ?';
            $params[] = $f['direction'];
        }
        if (!empty($f['year'])) {
            $conds[] = 'd.reg_year = ?';
            $params[] = (int) $f['year'];
        }
        if (!empty($f['status']) && isset(DOC_STATUSES[$f['status']])) {
            $conds[] = 'd.status = ?';
            $params[] = $f['status'];
        }
        if (!empty($f['speed']) && in_array($f['speed'], DOC_SPEEDS, true)) {
            $conds[] = 'd.speed = ?';
            $params[] = $f['speed'];
        }
        if (!empty($f['doc_type']) && in_array($f['doc_type'], DOC_TYPES, true)) {
            $conds[] = 'd.doc_type = ?';
            $params[] = $f['doc_type'];
        }
        if (!empty($f['department'])) {
            $conds[] = 'd.owner_department_id = ?';
            $params[] = (int) $f['department'];
        }
        if (!empty($f['date_from'])) {
            $conds[] = 'DATE(d.reg_datetime) >= ?';
            $params[] = $f['date_from'];
        }
        if (!empty($f['date_to'])) {
            $conds[] = 'DATE(d.reg_datetime) <= ?';
            $params[] = $f['date_to'];
        }
        if (!empty($f['q'])) {
            // ค้นแบบ LIKE เพราะ FULLTEXT ของ MySQL ตัดคำภาษาไทยไม่ได้
            $conds[] = '(d.subject LIKE ? OR d.doc_number LIKE ? OR d.from_text LIKE ? OR d.to_text LIKE ?
                         OR fo.name LIKE ? OR to_.name LIKE ?)';
            $like = '%' . $f['q'] . '%';
            array_push($params, $like, $like, $like, $like, $like, $like);
        }

        // มุมมองสำเร็จรูป — ย่อตัวกรองหลายช่องให้เหลือปุ่มเดียว
        switch ($f['view'] ?? '') {
            case 'mine':
                $conds[] = 'd.status IN ("pending","in_progress")';
                if (!empty($f['my_department_id'])) {
                    $conds[] = 'd.owner_department_id = ?';
                    $params[] = (int) $f['my_department_id'];
                }
                break;
            case 'urgent':
                $conds[] = 'd.speed <> "ปกติ" AND d.status IN ("pending","in_progress")';
                break;
            case 'month':
                $conds[] = 'd.reg_datetime >= ?';
                $params[] = date('Y-m-01 00:00:00');
                break;
            case 'nodept':
                $conds[] = 'd.owner_department_id IS NULL';
                break;
        }

        // จำกัดให้ผู้ใช้ role staff เห็นเฉพาะหนังสือที่เกษียณถึงตนเอง/ฝ่ายตน
        if (!empty($f['restrict_user_id'])) {
            $sub = 'a.assigned_to = ?';
            $params[] = (int) $f['restrict_user_id'];
            if (!empty($f['restrict_department_id'])) {
                $sub .= ' OR a.department_id = ?';
                $params[] = (int) $f['restrict_department_id'];
            }
            $conds[] = 'EXISTS (SELECT 1 FROM document_assignments a
                                 WHERE a.document_id = d.id AND (' . $sub . '))';
        }

        $where = $conds === [] ? '' : ' WHERE ' . implode(' AND ', $conds);

        return [$where, $params];
    }

    /**
     * จำนวนหนังสือของแต่ละมุมมองสำเร็จรูป (นับบนตัวกรองที่เปิดอยู่ เช่น ประเภททะเบียน)
     * ใช้โชว์ตัวเลขข้างปุ่มมุมมอง จะได้รู้ล่วงหน้าว่ากดแล้วเจออะไร
     * @return array<string,int>
     */
    public function viewCounts(array $base): array
    {
        $counts = ['' => 0];
        foreach (array_merge([''], array_keys(DOC_VIEWS)) as $view) {
            [$where, $params] = $this->buildFilters(array_merge($base, ['view' => $view]));
            $counts[$view] = (int) $this->value(
                'SELECT COUNT(*) FROM documents d ' . self::JOINS . $where,
                $params
            );
        }
        return $counts;
    }

    /** เลขทะเบียนที่กำลังจะได้ ใช้แสดงตัวอย่างในฟอร์ม (ของจริงออกตอนบันทึกใน create) */
    public function nextRegNumber(string $direction, int $regYear): int
    {
        return (int) $this->value(
            'SELECT COALESCE(MAX(reg_number), 0) + 1 FROM documents WHERE direction = ? AND reg_year = ?',
            [$direction, $regYear]
        );
    }

    /** ปีที่มีข้อมูล ใช้ทำ dropdown ตัวกรอง */
    public function years(): array
    {
        $rows = $this->all('SELECT DISTINCT reg_year FROM documents ORDER BY reg_year DESC');
        $years = array_map(static fn(array $r): int => (int) $r['reg_year'], $rows);
        $current = be_year();
        if (!in_array($current, $years, true)) {
            array_unshift($years, $current);
        }
        return $years;
    }

    /** สถิติสำหรับแดชบอร์ด */
    public function stats(int $year): array
    {
        $monthStart = date('Y-m-01 00:00:00');

        return [
            'incoming_month' => (int) $this->value(
                'SELECT COUNT(*) FROM documents WHERE direction = "incoming" AND reg_datetime >= ?',
                [$monthStart]
            ),
            'outgoing_month' => (int) $this->value(
                'SELECT COUNT(*) FROM documents WHERE direction = "outgoing" AND reg_datetime >= ?',
                [$monthStart]
            ),
            'pending' => (int) $this->value(
                'SELECT COUNT(*) FROM documents WHERE status IN ("pending","in_progress")'
            ),
            'urgent_open' => (int) $this->value(
                'SELECT COUNT(*) FROM documents
                  WHERE speed IN ("ด่วน","ด่วนมาก","ด่วนที่สุด")
                    AND status IN ("pending","in_progress")'
            ),
            'year_incoming' => (int) $this->value(
                'SELECT COUNT(*) FROM documents WHERE direction = "incoming" AND reg_year = ?',
                [$year]
            ),
            'year_outgoing' => (int) $this->value(
                'SELECT COUNT(*) FROM documents WHERE direction = "outgoing" AND reg_year = ?',
                [$year]
            ),
        ];
    }

    /** หนังสือล่าสุดสำหรับแดชบอร์ด */
    public function recent(int $limit = 8): array
    {
        return $this->all(
            'SELECT d.*, fo.name AS from_org_name, to_.name AS to_org_name
               FROM documents d
               LEFT JOIN organizations fo  ON fo.id  = d.from_org_id
               LEFT JOIN organizations to_ ON to_.id = d.to_org_id
              ORDER BY d.reg_datetime DESC
              LIMIT ' . (int) $limit
        );
    }
}
