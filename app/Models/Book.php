<?php

namespace App\Models;

use App\Core\Model;

/**
 * Books table, normalized (post library_normalization_migration.sql) to use
 * author_id/publisher_id/category_id foreign keys. The original free-text
 * author/publisher/category columns are kept as a read-only audit trail —
 * never write to them from here.
 */
class Book extends Model
{
    protected string $table = 'books';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'isbn', 'accession_number', 'title', 'author_id', 'publisher_id', 'category_id',
        'cover_image', 'edition', 'language', 'rack_number', 'shelf_number', 'description',
        'status', 'total_copies', 'available_copies', 'updated_by',
    ];

    protected array $searchable = ['title', 'isbn', 'accession_number'];

    private const JOIN_SELECT = "
        SELECT b.*, a.name AS author_name, p.name AS publisher_name, c.name AS category_name
        FROM `books` b
        LEFT JOIN `authors` a ON a.id = b.author_id
        LEFT JOIN `publishers` p ON p.id = b.publisher_id
        LEFT JOIN `book_categories` c ON c.id = b.category_id
    ";

    public function findWithNames(int $id): array|false
    {
        $rows = $this->raw(self::JOIN_SELECT . " WHERE b.id = :id LIMIT 1", ['id' => $id]);
        return $rows[0] ?? false;
    }

    public function paginateCatalog(int $page, int $perPage, string $search = '', string $categoryId = '', string $availability = ''): array
    {
        $where = ['b.deleted_at IS NULL'];
        $params = [];

        if ($search !== '') {
            // Each LIKE clause needs its own placeholder — PDO::ATTR_EMULATE_PREPARES
            // is off (see config/database.php), so the real MySQL driver does not
            // allow the same named parameter to be bound more than once per query.
            $where[] = '(b.title LIKE :search1 OR b.isbn LIKE :search2 OR b.accession_number LIKE :search3 OR a.name LIKE :search4)';
            $like = "%{$search}%";
            $params['search1'] = $like;
            $params['search2'] = $like;
            $params['search3'] = $like;
            $params['search4'] = $like;
        }
        if ($categoryId !== '') {
            $where[] = 'b.category_id = :category_id';
            $params['category_id'] = $categoryId;
        }
        if ($availability === 'available') {
            $where[] = 'b.available_copies > 0';
        } elseif ($availability === 'unavailable') {
            $where[] = 'b.available_copies = 0';
        }

        $whereSql = implode(' AND ', $where);
        $countSql = "SELECT COUNT(*) AS c FROM `books` b
                      LEFT JOIN `authors` a ON a.id = b.author_id
                      WHERE {$whereSql}";
        $total = (int) ($this->raw($countSql, $params)[0]['c'] ?? 0);

        $offset = ($page - 1) * $perPage;
        $sql = self::JOIN_SELECT . " WHERE {$whereSql} ORDER BY b.title ASC LIMIT {$perPage} OFFSET {$offset}";

        return [
            'data' => $this->raw($sql, $params),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => max(1, (int) ceil($total / max(1, $perPage))),
        ];
    }

    /**
     * ISBN must be unique catalog-wide. Backs the "ISBN already exists with
     * another book" validation message from the reference screenshots.
     */
    public function isbnExists(string $isbn, ?int $ignoreId = null): bool
    {
        if (trim($isbn) === '') {
            return false;
        }
        $params = ['isbn' => trim($isbn)];
        $sql = "SELECT COUNT(*) AS c FROM `books` WHERE isbn = :isbn AND deleted_at IS NULL";
        if ($ignoreId !== null) {
            $sql .= " AND id != :id";
            $params['id'] = $ignoreId;
        }
        return (int) ($this->raw($sql, $params)[0]['c'] ?? 0) > 0;
    }

    /**
     * Title uniqueness is intentionally scoped to (title + author), not
     * global — the real catalog has legitimate same-title/different-author
     * entries (e.g. multiple "Moral Stories" collections, verified against
     * the actual data during migration testing). This still gives the
     * "book title already exists" UX from the reference screenshots for
     * genuine accidental duplicates.
     */
    public function titleExistsForAuthor(string $title, ?int $authorId, ?int $ignoreId = null): bool
    {
        if (trim($title) === '') {
            return false;
        }
        $params = ['title' => trim($title)];
        $sql = "SELECT COUNT(*) AS c FROM `books` WHERE LOWER(TRIM(title)) = LOWER(TRIM(:title)) AND deleted_at IS NULL";
        if ($authorId) {
            $sql .= " AND author_id = :author_id";
            $params['author_id'] = $authorId;
        } else {
            $sql .= " AND author_id IS NULL";
        }
        if ($ignoreId !== null) {
            $sql .= " AND id != :id";
            $params['id'] = $ignoreId;
        }
        return (int) ($this->raw($sql, $params)[0]['c'] ?? 0) > 0;
    }

    /** Full list of books with a free copy, for the Issue Book dropdown. */
    public function forIssueDropdown(): array
    {
        return $this->raw(
            "SELECT b.id, b.title, b.isbn, b.accession_number, b.cover_image, b.available_copies, a.name AS author_name
             FROM `books` b LEFT JOIN `authors` a ON a.id = b.author_id
             WHERE b.deleted_at IS NULL AND b.available_copies > 0
             ORDER BY b.title ASC"
        );
    }

    /** Search-as-you-type for the Issue Book screen: only books with a free copy. */
    public function searchAvailable(string $term, int $limit = 15): array
    {
        return $this->raw(
            "SELECT b.id, b.title, b.isbn, b.accession_number, b.cover_image, b.available_copies, a.name AS author_name, c.name AS category_name
             FROM `books` b
             LEFT JOIN `authors` a ON a.id = b.author_id
             LEFT JOIN `book_categories` c ON c.id = b.category_id
             WHERE b.deleted_at IS NULL AND b.available_copies > 0
               AND (b.title LIKE :term1 OR b.isbn LIKE :term2 OR b.accession_number LIKE :term3)
             ORDER BY b.title ASC
             LIMIT {$limit}",
            ['term1' => "%{$term}%", 'term2' => "%{$term}%", 'term3' => "%{$term}%"]
        );
    }

    public function decrementAvailable(int $bookId): void
    {
        $this->raw(
            "UPDATE `books` SET `available_copies` = GREATEST(0, `available_copies` - 1) WHERE `id` = :id",
            ['id' => $bookId]
        );
    }

    public function incrementAvailable(int $bookId): void
    {
        $this->raw(
            "UPDATE `books` SET `available_copies` = LEAST(`total_copies`, `available_copies` + 1) WHERE `id` = :id",
            ['id' => $bookId]
        );
    }

    /** Book permanently lost: no longer counts toward total or available stock. */
    public function removeLostCopy(int $bookId): void
    {
        $this->raw(
            "UPDATE `books` SET `total_copies` = GREATEST(0, `total_copies` - 1) WHERE `id` = :id",
            ['id' => $bookId]
        );
    }

    /** A damaged copy taken permanently out of circulation (not currently issued). */
    public function removeDamagedCopy(int $bookId): void
    {
        $this->raw(
            "UPDATE `books` SET
                `total_copies` = GREATEST(0, `total_copies` - 1),
                `available_copies` = GREATEST(0, `available_copies` - 1)
             WHERE `id` = :id",
            ['id' => $bookId]
        );
    }

    /** Soft-delete: sets deleted_at, restorable from the Trash view. */
    public function softDelete(int $bookId): void
    {
        $this->raw("UPDATE `books` SET deleted_at = NOW() WHERE id = :id", ['id' => $bookId]);
    }

    public function restore(int $bookId): void
    {
        $this->raw("UPDATE `books` SET deleted_at = NULL WHERE id = :id", ['id' => $bookId]);
    }

    public function trashed(int $page, int $perPage): array
    {
        $total = (int) ($this->raw("SELECT COUNT(*) AS c FROM books WHERE deleted_at IS NOT NULL")[0]['c'] ?? 0);
        $offset = ($page - 1) * $perPage;
        $sql = self::JOIN_SELECT . " WHERE b.deleted_at IS NOT NULL ORDER BY b.deleted_at DESC LIMIT {$perPage} OFFSET {$offset}";
        return [
            'data' => $this->raw($sql),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => max(1, (int) ceil($total / max(1, $perPage))),
        ];
    }

    public function dashboardCounts(): array
    {
        $row = $this->raw(
            "SELECT
                (SELECT COUNT(*) FROM books WHERE deleted_at IS NULL) AS total_titles,
                (SELECT COALESCE(SUM(total_copies),0) FROM books WHERE deleted_at IS NULL) AS total_copies,
                (SELECT COALESCE(SUM(available_copies),0) FROM books WHERE deleted_at IS NULL) AS total_available,
                (SELECT COUNT(*) FROM book_issues WHERE status = 'issued') AS total_issued,
                (SELECT COUNT(*) FROM book_issues WHERE status = 'issued' AND due_date < CURDATE()) AS total_overdue,
                (SELECT COUNT(*) FROM book_issues WHERE status = 'lost') AS total_lost,
                (SELECT COUNT(*) FROM authors WHERE deleted_at IS NULL) AS total_authors,
                (SELECT COUNT(*) FROM book_categories WHERE deleted_at IS NULL) AS total_categories,
                (SELECT COUNT(*) FROM books WHERE deleted_at IS NULL) AS total_books,
                (SELECT COUNT(DISTINCT shelf_number) FROM books WHERE deleted_at IS NULL AND shelf_number IS NOT NULL AND shelf_number != '') AS total_shelves,
                (SELECT COUNT(DISTINCT rack_number) FROM books WHERE deleted_at IS NULL AND rack_number IS NOT NULL AND rack_number != '') AS total_racks"
        );
        return $row[0] ?? [];
    }

    /** Books added in the last N days, newest first — powers "Recently Added Books". */
    public function recentlyAdded(int $limit = 10): array
    {
        $sql = self::JOIN_SELECT . " WHERE b.deleted_at IS NULL ORDER BY b.created_at DESC LIMIT " . (int) $limit;
        return $this->raw($sql);
    }

    /**
     * Books whose updated_at moved past created_at — i.e. actually edited
     * since being added, not just migrated/backfilled. Newest edit first.
     */
    public function recentlyUpdated(int $limit = 10): array
    {
        return $this->raw(
            "SELECT b.*, a.name AS author_name, p.name AS publisher_name, c.name AS category_name, up.full_name AS updated_by_name
             FROM `books` b
             LEFT JOIN `authors` a ON a.id = b.author_id
             LEFT JOIN `publishers` p ON p.id = b.publisher_id
             LEFT JOIN `book_categories` c ON c.id = b.category_id
             LEFT JOIN `users` up ON up.id = b.updated_by
             WHERE b.deleted_at IS NULL AND b.updated_at IS NOT NULL AND b.updated_at > b.created_at
             ORDER BY b.updated_at DESC LIMIT " . (int) $limit
        );
    }

    /** Titles with fewer than 3 available copies — powers "Low Stock Books". */
    public function lowStock(int $threshold = 3, int $limit = 10): array
    {
        $sql = self::JOIN_SELECT . " WHERE b.deleted_at IS NULL AND b.available_copies < :threshold
                ORDER BY b.available_copies ASC LIMIT " . (int) $limit;
        return $this->raw($sql, ['threshold' => $threshold]);
    }
}