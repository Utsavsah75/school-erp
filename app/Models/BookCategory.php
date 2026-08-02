<?php

namespace App\Models;

use App\Core\Model;

class BookCategory extends Model
{
    protected string $table = 'book_categories';
    protected string $primaryKey = 'id';

    protected array $fillable = ['name', 'description', 'is_active', 'created_by', 'updated_by'];
    protected array $searchable = ['name', 'description'];

    public function paginateList(int $page, int $perPage, string $search = ''): array
    {
        $where = ['deleted_at IS NULL'];
        $params = [];
        if ($search !== '') {
            $where[] = '(name LIKE :search OR description LIKE :search)';
            $params['search'] = "%{$search}%";
        }
        $whereSql = implode(' AND ', $where);

        $total = (int) ($this->raw("SELECT COUNT(*) AS c FROM `book_categories` WHERE {$whereSql}", $params)[0]['c'] ?? 0);
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT c.*, (SELECT COUNT(*) FROM books WHERE category_id = c.id) AS book_count
                FROM `book_categories` c WHERE {$whereSql} ORDER BY `name` ASC LIMIT {$perPage} OFFSET {$offset}";

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
        return $this->raw("SELECT id, name FROM `book_categories` WHERE deleted_at IS NULL AND is_active = 1 ORDER BY name ASC");
    }

    public function nameExists(string $name, ?int $ignoreId = null): bool
    {
        $params = ['name' => trim($name)];
        $sql = "SELECT COUNT(*) AS c FROM `book_categories` WHERE LOWER(TRIM(name)) = LOWER(TRIM(:name)) AND deleted_at IS NULL";
        if ($ignoreId !== null) {
            $sql .= " AND id != :id";
            $params['id'] = $ignoreId;
        }
        return (int) ($this->raw($sql, $params)[0]['c'] ?? 0) > 0;
    }

    public function bookCount(int $categoryId): int
    {
        return (int) ($this->raw(
            "SELECT COUNT(*) AS c FROM books WHERE category_id = :id",
            ['id' => $categoryId]
        )[0]['c'] ?? 0);
    }

    /** Most recently added categories, with a live book count — dashboard "Recently Added Categories". */
    public function recent(int $limit = 10): array
    {
        $sql = "SELECT c.*, (SELECT COUNT(*) FROM books WHERE category_id = c.id) AS book_count
                FROM `book_categories` c WHERE c.deleted_at IS NULL
                ORDER BY c.created_at DESC LIMIT " . (int) $limit;
        return $this->raw($sql);
    }
}
