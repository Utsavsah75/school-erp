<?php

namespace App\Models;

use App\Core\Model;

class Author extends Model
{
    protected string $table = 'authors';
    protected string $primaryKey = 'id';

    protected array $fillable = ['name', 'photo', 'bio', 'country', 'is_active'];
    protected array $searchable = ['name', 'country'];

    public function paginateList(int $page, int $perPage, string $search = ''): array
    {
        $where = ['deleted_at IS NULL'];
        $params = [];
        if ($search !== '') {
            $where[] = '(name LIKE :search OR country LIKE :search)';
            $params['search'] = "%{$search}%";
        }
        $whereSql = implode(' AND ', $where);

        $total = (int) ($this->raw("SELECT COUNT(*) AS c FROM `authors` WHERE {$whereSql}", $params)[0]['c'] ?? 0);
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT a.*, (SELECT COUNT(*) FROM books WHERE author_id = a.id) AS book_count
                FROM `authors` a WHERE {$whereSql} ORDER BY `name` ASC LIMIT {$perPage} OFFSET {$offset}";

        return [
            'data' => $this->raw($sql, $params),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => max(1, (int) ceil($total / max(1, $perPage))),
        ];
    }

    /** All active authors, for Add/Edit Book dropdown. */
    public function forDropdown(): array
    {
        return $this->raw("SELECT id, name FROM `authors` WHERE deleted_at IS NULL AND is_active = 1 ORDER BY name ASC");
    }

    public function nameExists(string $name, ?int $ignoreId = null): bool
    {
        $params = ['name' => trim($name)];
        $sql = "SELECT COUNT(*) AS c FROM `authors` WHERE LOWER(TRIM(name)) = LOWER(TRIM(:name)) AND deleted_at IS NULL";
        if ($ignoreId !== null) {
            $sql .= " AND id != :id";
            $params['id'] = $ignoreId;
        }
        return (int) ($this->raw($sql, $params)[0]['c'] ?? 0) > 0;
    }

    /** How many books currently reference this author — blocks deletion if > 0. */
    public function bookCount(int $authorId): int
    {
        return (int) ($this->raw(
            "SELECT COUNT(*) AS c FROM books WHERE author_id = :id",
            ['id' => $authorId]
        )[0]['c'] ?? 0);
    }

    /** Most recently added authors, with a live book count — dashboard "Recently Added Authors". */
    public function recent(int $limit = 10): array
    {
        $sql = "SELECT a.*, (SELECT COUNT(*) FROM books WHERE author_id = a.id) AS book_count
                FROM `authors` a WHERE a.deleted_at IS NULL
                ORDER BY a.created_at DESC LIMIT " . (int) $limit;
        return $this->raw($sql);
    }
}
