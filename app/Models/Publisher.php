<?php

namespace App\Models;

use App\Core\Model;

class Publisher extends Model
{
    protected string $table = 'publishers';
    protected string $primaryKey = 'id';

    protected array $fillable = ['name', 'logo', 'email', 'phone', 'website', 'address', 'contact', 'is_active'];
    protected array $searchable = ['name', 'email', 'phone'];

    public function paginateList(int $page, int $perPage, string $search = ''): array
    {
        $where = ['deleted_at IS NULL'];
        $params = [];
        if ($search !== '') {
            $where[] = '(name LIKE :search OR email LIKE :search OR phone LIKE :search)';
            $params['search'] = "%{$search}%";
        }
        $whereSql = implode(' AND ', $where);

        $total = (int) ($this->raw("SELECT COUNT(*) AS c FROM `publishers` WHERE {$whereSql}", $params)[0]['c'] ?? 0);
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT p.*, (SELECT COUNT(*) FROM books WHERE publisher_id = p.id) AS book_count
                FROM `publishers` p WHERE {$whereSql} ORDER BY `name` ASC LIMIT {$perPage} OFFSET {$offset}";

        return [
            'data' => $this->raw($sql, $params),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => max(1, (int) ceil($total / max(1, $perPage))),
        ];
    }

    public function forDropdown(): array
    {
        return $this->raw("SELECT id, name FROM `publishers` WHERE deleted_at IS NULL AND is_active = 1 ORDER BY name ASC");
    }

    public function nameExists(string $name, ?int $ignoreId = null): bool
    {
        $params = ['name' => trim($name)];
        $sql = "SELECT COUNT(*) AS c FROM `publishers` WHERE LOWER(TRIM(name)) = LOWER(TRIM(:name)) AND deleted_at IS NULL";
        if ($ignoreId !== null) {
            $sql .= " AND id != :id";
            $params['id'] = $ignoreId;
        }
        return (int) ($this->raw($sql, $params)[0]['c'] ?? 0) > 0;
    }

    public function bookCount(int $publisherId): int
    {
        return (int) ($this->raw(
            "SELECT COUNT(*) AS c FROM books WHERE publisher_id = :id",
            ['id' => $publisherId]
        )[0]['c'] ?? 0);
    }

    /** Most recently added publishers, with a live book count — dashboard "Recently Added Publishers". */
    public function recent(int $limit = 10): array
    {
        $sql = "SELECT p.*, (SELECT COUNT(*) FROM books WHERE publisher_id = p.id) AS book_count
                FROM `publishers` p WHERE p.deleted_at IS NULL
                ORDER BY p.created_at DESC LIMIT " . (int) $limit;
        return $this->raw($sql);
    }

    public function totalCount(): int
    {
        return (int) ($this->raw("SELECT COUNT(*) AS c FROM `publishers` WHERE deleted_at IS NULL")[0]['c'] ?? 0);
    }
}
