<?php

namespace App\Models;

use App\Core\Model;

/**
 * One row per physical copy of a book (per the per-copy tracking
 * requirement — see database/library_book_copies_migration.sql).
 *
 * `copy_code` is always the zero-padded `id` (5 digits, e.g. 10001) —
 * it's stamped right after insert since MariaDB won't let a generated
 * column or an AFTER INSERT trigger reference the row's own
 * AUTO_INCREMENT value for a bulk INSERT (see migration file for the
 * full explanation). From the application's point of view `copy_code`
 * is read-only: nothing here ever writes to it except generateCopies().
 */
class BookCopy extends Model
{
    protected string $table = 'book_copies';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'book_id', 'condition', 'status', 'purchase_date', 'price',
        'rack_number', 'shelf_number', 'remarks',
    ];

    public const STATUSES = ['available', 'issued', 'reserved', 'lost', 'damaged', 'maintenance'];

    /**
     * Create $qty new copies for a book and stamp their copy_code.
     * Returns the created rows (id + copy_code), in id order.
     *
     * @return array<int,array{id:int,copy_code:string}>
     */
    public function generateCopies(int $bookId, int $qty, array $defaults = []): array
    {
        $qty = max(1, $qty);
        $ids = [];
        for ($i = 0; $i < $qty; $i++) {
            $ids[] = $this->insert(array_merge([
                'book_id' => $bookId,
                'condition' => 'good',
                'status' => 'available',
            ], $defaults));
        }

        // Stamp copy_code for exactly the rows we just created (not a blind
        // "WHERE copy_code IS NULL", so a concurrent insert on another
        // book can never have its code stamped by this call).
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare(
            "UPDATE `book_copies` SET `copy_code` = LPAD(`id`, 5, '0') WHERE `id` IN ({$placeholders})"
        );
        $stmt->execute($ids);

        $stmt = $this->pdo->prepare(
            "SELECT `id`, `copy_code` FROM `book_copies` WHERE `id` IN ({$placeholders}) ORDER BY `id`"
        );
        $stmt->execute($ids);
        return $stmt->fetchAll();
    }

    /** Available copies for a book, oldest (lowest code) first — for the Issue screen's cascading dropdown. */
    public function availableForBook(int $bookId): array
    {
        return $this->raw(
            "SELECT `id`, `copy_code`, `condition`, `rack_number`, `shelf_number`
             FROM `book_copies`
             WHERE `book_id` = :book_id AND `status` = 'available'
             ORDER BY `id` ASC",
            ['book_id' => $bookId]
        );
    }

    /** All copies for a book with their status, for the Book Details screen's per-copy breakdown. */
    public function forBook(int $bookId): array
    {
        return $this->raw(
            "SELECT * FROM `book_copies` WHERE `book_id` = :book_id ORDER BY `id` ASC",
            ['book_id' => $bookId]
        );
    }

    /**
     * Lock a single copy row for update inside the caller's transaction, to
     * prevent two simultaneous "Issue Book" submissions from both grabbing
     * the same copy. Caller must already be inside a transaction
     * (Database::getInstance()->beginTransaction()).
     */
    public function lockForIssue(int $copyId): array|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM `book_copies` WHERE `id` = :id AND `status` = 'available' FOR UPDATE"
        );
        $stmt->execute(['id' => $copyId]);
        return $stmt->fetch();
    }

    public function markStatus(int $copyId, string $status): void
    {
        if (!in_array($status, self::STATUSES, true)) {
            throw new \InvalidArgumentException("Invalid book copy status: {$status}");
        }
        $this->update($copyId, ['status' => $status]);
    }

    /** Status breakdown for one book: total/available/issued/reserved/lost/damaged/maintenance. */
    public function countsForBook(int $bookId): array
    {
        $counts = array_fill_keys(self::STATUSES, 0);
        $counts['total'] = 0;
        foreach ($this->raw(
            "SELECT `status`, COUNT(*) AS c FROM `book_copies` WHERE `book_id` = :book_id GROUP BY `status`",
            ['book_id' => $bookId]
        ) as $row) {
            $counts[$row['status']] = (int) $row['c'];
            $counts['total'] += (int) $row['c'];
        }
        return $counts;
    }
}
