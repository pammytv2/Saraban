<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;

/**
 * คลาสแม่ของ Model — ห่อคำสั่ง PDO ที่ใช้บ่อยให้สั้นลง
 * ทุกคำสั่งใช้ prepared statement เสมอ (กัน SQL injection)
 */
abstract class Model
{
    protected function db(): PDO
    {
        return Database::pdo();
    }

    protected function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /** คืนทุกแถว */
    protected function all(string $sql, array $params = []): array
    {
        return $this->run($sql, $params)->fetchAll();
    }

    /** คืนแถวเดียว หรือ null */
    protected function one(string $sql, array $params = []): ?array
    {
        $row = $this->run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /** คืนค่าคอลัมน์แรกของแถวแรก */
    protected function value(string $sql, array $params = [])
    {
        $value = $this->run($sql, $params)->fetchColumn();
        return $value === false ? null : $value;
    }

    /** INSERT จาก associative array คืน id ที่เพิ่ง insert */
    protected function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $sql = sprintf(
            'INSERT INTO `%s` (`%s`) VALUES (%s)',
            $table,
            implode('`, `', $columns),
            implode(', ', array_fill(0, count($columns), '?'))
        );
        $this->run($sql, array_values($data));
        return (int) $this->db()->lastInsertId();
    }

    /** UPDATE ตาม id */
    protected function update(string $table, int $id, array $data): void
    {
        if ($data === []) {
            return;
        }
        $sets = [];
        foreach (array_keys($data) as $column) {
            $sets[] = "`{$column}` = ?";
        }
        $sql = sprintf('UPDATE `%s` SET %s WHERE id = ?', $table, implode(', ', $sets));
        $params = array_values($data);
        $params[] = $id;
        $this->run($sql, $params);
    }

    protected function delete(string $table, int $id): void
    {
        $this->run("DELETE FROM `{$table}` WHERE id = ?", [$id]);
    }

    /** ทำงานหลายคำสั่งใน transaction เดียว */
    protected function transaction(callable $callback)
    {
        $pdo = $this->db();
        $pdo->beginTransaction();
        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
