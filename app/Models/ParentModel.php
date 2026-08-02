<?php

namespace App\Models;

use App\Core\Model;

/**
 * Maps to the `parents` table. Named ParentModel (not Parent) because
 * "parent" is a reserved word and cannot be used as a PHP class name
 * (same reason the `classes` table's model is ClassModel, not Class).
 */
class ParentModel extends Model
{
    protected string $table = 'parents';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'user_id', 'father_name', 'mother_name', 'guardian_name', 'guardian_relationship',
        'occupation', 'phone_country_code', 'phone', 'email', 'address',
    ];

    protected array $searchable = ['father_name', 'mother_name', 'guardian_name', 'phone', 'email'];

    /** The parent record linked to a given login (users.id). */
    public function byUserId(int $userId): array|false
    {
        return $this->findBy('user_id', $userId);
    }

    public function findByPhone(string $phone): array|false
    {
        return $this->findBy('phone', $phone);
    }

    public function findByEmail(string $email): array|false
    {
        return $this->findBy('email', $email);
    }

    /** All children (students) belonging to this parent. */
    public function children(int $parentId): array
    {
        return $this->raw(
            "SELECT s.*, c.name AS class_name, sec.name AS section_name
             FROM students s
             LEFT JOIN classes c ON c.id = s.class_id
             LEFT JOIN sections sec ON sec.id = s.section_id
             WHERE s.parent_id = :pid
             ORDER BY s.full_name ASC",
            ['pid' => $parentId]
        );
    }

    /** Count of students still linked to this parent — used to decide whether a parent record can be deleted. */
    public function childrenCount(int $parentId): int
    {
        $row = $this->raw('SELECT COUNT(*) AS c FROM students WHERE parent_id = :pid', ['pid' => $parentId]);
        return (int) ($row[0]['c'] ?? 0);
    }

    public function displayName(array $parent): string
    {
        return $parent['father_name'] ?? $parent['guardian_name'] ?? $parent['mother_name'] ?? 'Guardian';
    }

    /**
     * Paginated parent list for the admin "Parents" page, with each row
     * annotated with its linked-children count and portal-login status
     * (has_login / is_active / email_verified) via a LEFT JOIN to users.
     *
     * @return array{data: array, total: int, page: int, per_page: int, last_page: int}
     */
    public function paginateWithStats(int $page, int $perPage, string $search = ''): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        $whereSql = '';
        $params = [];
        if (trim($search) !== '') {
            $clauses = [];
            foreach ($this->searchable as $i => $col) {
                $clauses[] = "p.`{$col}` LIKE :search{$i}";
                $params["search{$i}"] = '%' . $search . '%';
            }
            $whereSql = ' WHERE (' . implode(' OR ', $clauses) . ')';
        }

        $countSql = "SELECT COUNT(*) AS c FROM `parents` p" . $whereSql;
        $total = (int) ($this->db->query($countSql, $params)->fetch()['c'] ?? 0);

        $sql = "SELECT p.*,
                       (SELECT COUNT(*) FROM students s WHERE s.parent_id = p.id) AS children_count,
                       u.id AS login_user_id, u.is_active AS login_is_active, u.email_verified_at AS login_verified_at
                FROM `parents` p
                LEFT JOIN `users` u ON u.id = p.user_id"
                . $whereSql .
                " ORDER BY p.id DESC LIMIT {$perPage} OFFSET {$offset}";
        $data = $this->db->query($sql, $params)->fetchAll();

        return [
            'data'      => $data,
            'total'     => $total,
            'page'      => $page,
            'per_page'  => $perPage,
            'last_page' => (int) max(1, ceil($total / $perPage)),
        ];
    }
}
