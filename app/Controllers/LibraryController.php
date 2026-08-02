<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Models\Author;
use App\Models\Book;
use App\Models\BookCategory;
use App\Models\BookCopy;
use App\Models\BookIssue;
use App\Models\LibraryActivity;
use App\Models\LibraryFinePayment;
use App\Models\LibraryReservation;
use App\Models\LibrarySetting;
use App\Models\Payment;
use App\Models\Publisher;
use App\Models\Student;
use App\Models\Teacher;

/**
 * Library module. Built against the library schema that already exists
 * in production (books, book_issues) — see
 * database/library_module_migration.sql for exactly what was added on
 * top of it. Reuses School ERP's own students/teachers/users tables and
 * Auth/session system throughout; never stores its own borrower or login
 * records.
 */
class LibraryController extends Controller
{
    /** Payment modes where a reference/transaction number is required to prove the payment (everything but cash-in-hand). */
    private const FINE_MODES_REQUIRING_REFERENCE = ['upi', 'bank_transfer', 'online', 'cheque', 'card'];

    public function __construct()
    {
        $this->authorizeModule('library');
    }

    // ------------------------------------------------------------------
    // Dashboard
    // ------------------------------------------------------------------

    public function index(): void
    {
        $this->view('library/index', array_merge(
            ['pageTitle' => 'Library Dashboard'],
            $this->dashboardPayload()
        ));
    }

    /**
     * Everything the redesigned Library Dashboard needs: stat cards, every
     * "Recent ..." table, quick-action targets, and the activity/notification
     * feed. Shared by index() (first page load) and refreshBody() (the
     * polling endpoint the page calls every 20s so librarians never have to
     * hit refresh manually).
     */
    private function dashboardPayload(): array
    {
        $bookModel = new Book();
        $issueModel = new BookIssue();
        $studentModel = new Student();
        $finePaymentModel = new LibraryFinePayment();
        $paymentModel = new Payment();

        $counts = $bookModel->dashboardCounts();
        $counts['returned_today'] = $issueModel->returnedTodayCount();
        $counts['active_members'] = $issueModel->activeMembersCount();
        $counts['total_students'] = $studentModel->countByStatus('active');
        $counts['total_publishers'] = (new Publisher())->totalCount();
        $counts['fine_collected_today'] = $finePaymentModel->totalCollectedToday();

        // Recent Payments: library fine receipts + the school's general fee
        // payments, merged and re-sorted so librarians see one combined feed
        // (each row still says which kind it is; "View full history" links
        // through to the student's existing payment record either way).
        $finePayments = array_map(static function (array $row): array {
            $row['payment_kind'] = 'library_fine';
            $row['paid_amount'] = $row['amount'];
            $row['student_display'] = $row['student_name'] ?? $row['teacher_name'] ?? '—';
            return $row;
        }, $finePaymentModel->recent(10));

        $feePayments = array_map(static function (array $row): array {
            $row['payment_kind'] = 'fee';
            $row['paid_amount'] = $row['amount'];
            $row['student_display'] = $row['student_name'] ?? '—';
            return $row;
        }, $paymentModel->recent(10));

        $recentPayments = array_merge($finePayments, $feePayments);
        usort($recentPayments, static fn($a, $b) => strtotime($b['paid_at']) <=> strtotime($a['paid_at']));
        $recentPayments = array_slice($recentPayments, 0, 10);

        return [
            'counts' => $counts,
            'overdue' => array_slice($issueModel->overdueList(), 0, 10),
            'lowStock' => $bookModel->lowStock(3, 10),
            'recentIssued' => $issueModel->recentIssued(10),
            'recentReturned' => $issueModel->recentReturned(10),
            'recentReservations' => (new LibraryReservation())->recent(10),
            'recentlyAdded' => $bookModel->recentlyAdded(10),
            'recentlyUpdated' => $bookModel->recentlyUpdated(10),
            'recentStudents' => $studentModel->recent(10),
            'recentCategories' => (new BookCategory())->recent(10),
            'recentAuthors' => (new Author())->recent(10),
            'recentPublishers' => (new Publisher())->recent(10),
            'recentPayments' => $recentPayments,
            // Single merged, newest-first feed — powers both "Recent Library
            // Activities" and "Recent Notifications" so the two widgets can
            // never fall out of sync with each other.
            'recentActivity' => $this->formatActivityFeed((new LibraryActivity())->recent(10)),
            'generatedAt' => date('c'),
        ];
    }

    /**
     * Enriches each row of LibraryActivity::recent() with everything the
     * dashboard views need to render it directly: a human label, a badge
     * class, an icon, a "View" target, and a ready-made notification
     * sentence — so _dashboard_body.php doesn't need its own switch
     * statement per activity type (and Activities/Notifications can't drift
     * apart from each other).
     */
    private function formatActivityFeed(array $rows): array
    {
        $meta = [
            'issued'                => ['label' => 'Issued',                'badge' => 'bg-info text-dark', 'icon' => 'bi-box-arrow-right text-warning'],
            'returned'              => ['label' => 'Returned',               'badge' => 'bg-success',        'icon' => 'bi-box-arrow-in-left text-success'],
            'lost'                  => ['label' => 'Lost',                   'badge' => 'bg-danger',         'icon' => 'bi-x-octagon text-danger'],
            'reserved'              => ['label' => 'Reserved',               'badge' => 'bg-info text-dark', 'icon' => 'bi-bookmark-plus text-info'],
            'reservation_cancelled' => ['label' => 'Reservation Cancelled',  'badge' => 'bg-secondary',      'icon' => 'bi-bookmark-x text-secondary'],
            'book_added'            => ['label' => 'Book Added',             'badge' => 'bg-primary',        'icon' => 'bi-journal-plus text-primary'],
            'book_updated'          => ['label' => 'Book Updated',           'badge' => 'bg-warning text-dark', 'icon' => 'bi-pencil-square text-warning'],
            'book_deleted'          => ['label' => 'Book Deleted',           'badge' => 'bg-dark',           'icon' => 'bi-trash text-danger'],
            'fine_collected'        => ['label' => 'Fine Collected',         'badge' => 'bg-success',        'icon' => 'bi-cash-coin text-success'],
            'student_added'         => ['label' => 'Student Added',          'badge' => 'bg-primary',        'icon' => 'bi-person-plus text-primary'],
            'student_updated'       => ['label' => 'Student Updated',        'badge' => 'bg-warning text-dark', 'icon' => 'bi-person-gear text-warning'],
        ];

        return array_map(function (array $row) use ($meta): array {
            $type = $row['activity_type'];
            $info = $meta[$type] ?? ['label' => ucfirst(str_replace('_', ' ', $type)), 'badge' => 'bg-secondary', 'icon' => 'bi-activity'];
            $book = $row['book_title'] ?? null;
            $borrower = $row['borrower_name'] ?? null;
            $actor = $row['actor_name'] ?? null;

            $who = match ($type) {
                'book_added', 'book_updated', 'book_deleted', 'fine_collected' => $actor ?: 'A librarian',
                'student_added', 'student_updated' => $actor ?: 'Someone',
                default => $borrower ?: 'Someone',
            };

            $message = match ($type) {
                'issued' => "{$who} was issued \"{$book}\"",
                'returned' => "{$who} returned \"{$book}\"",
                'lost' => "\"{$book}\" was reported lost by {$who}",
                'reserved' => "{$who} reserved \"{$book}\"",
                'reservation_cancelled' => "{$who}'s reservation for \"{$book}\" was cancelled",
                'book_added' => "{$who} added \"{$book}\" to the catalog",
                'book_updated' => "{$who} updated \"{$book}\"",
                'book_deleted' => "\"{$book}\" was removed from the catalog",
                'fine_collected' => "{$who} collected a fine for \"{$book}\"",
                'student_added' => "New student {$borrower} was added" . ($actor ? " by {$actor}" : ''),
                'student_updated' => "Student {$borrower} was updated" . ($actor ? " by {$actor}" : ''),
                default => "{$who} — {$info['label']}",
            };

            $viewUrl = match ($row['entity_type']) {
                'book_issue', 'fine_payment' => url('library/transactions'),
                'reservation' => url('library/reservations'),
                'book' => $type === 'book_deleted' ? url('library/books/trash') : url('library/books/' . $row['entity_id']),
                'student' => url('students/' . $row['entity_id']),
                default => url('library'),
            };

            $row['label'] = $info['label'];
            $row['badge_class'] = $info['badge'];
            $row['icon'] = $info['icon'];
            $row['view_url'] = $viewUrl;
            $row['message'] = $message;
            $row['display_user'] = $who;

            return $row;
        }, $rows);
    }

    /** HTML fragment the dashboard polls periodically so it can refresh without a manual page reload. */
    public function refreshBody(): void
    {
        $this->viewRaw('library/_dashboard_body', $this->dashboardPayload());
    }

    /**
     * Export to Excel / PDF / Print for any Recent-activity table on the
     * dashboard. GET /library/export/{type}?format=csv|pdf
     * "Export to Excel" opens the CSV in Excel/Sheets (a real .xlsx would
     * need a bundled writer library this project doesn't have installed —
     * CSV opens natively in Excel with a double-click, so it's the same
     * one-click experience for the librarian). "Print" is handled entirely
     * client-side (browser print of the on-screen table) — no server route.
     */
    public function exportTable(string $type): void
    {
        $format = (string) $this->input('format', 'csv');
        $dataset = $this->exportDataset($type);
        if ($dataset === null) {
            http_response_code(404);
            echo 'Unknown export type.';
            return;
        }

        if ($format === 'pdf') {
            $this->exportPdf($dataset['title'], $dataset['headers'], $dataset['rows']);
        } else {
            $this->exportCsv($dataset['title'], $dataset['headers'], $dataset['rows']);
        }
    }

    /** @return array{title:string,headers:string[],rows:array<int,array<int,string>>}|null */
    private function exportDataset(string $type): ?array
    {
        // The dashboard widgets only ever show the latest 10 rows of each
        // list, but an export should contain the *complete* dataset — e.g.
        // every overdue book (and the fine each borrower currently owes),
        // not just the 10 shown on-screen. Fetch the full overdue list
        // directly instead of reusing the sliced dashboard payload.
        if ($type === 'overdue') {
            $overdue = (new BookIssue())->overdueList();
            return [
                'title' => 'Overdue Books',
                'headers' => ['Book', 'ISBN', 'Borrower', 'Type', 'ID No.', 'Class/Section', 'Issue Date', 'Due Date', 'Days Overdue', 'Fine Due'],
                'rows' => array_map(static fn($r) => [
                    $r['book_title'],
                    (string) ($r['isbn'] ?? ''),
                    $r['student_name'] ?? $r['teacher_name'] ?? '—',
                    $r['student_id'] ? 'Student' : 'Teacher',
                    (string) ($r['admission_number'] ?? $r['employee_number'] ?? ''),
                    trim(($r['class_name'] ?? '') . ' ' . ($r['section_name'] ?? '')),
                    (string) $r['issue_date'],
                    (string) $r['due_date'],
                    (string) $r['days_overdue'],
                    format_currency($r['fine_amount']),
                ], $overdue),
            ];
        }

        $payload = $this->dashboardPayload();

        return match ($type) {
            'issued' => [
                'title' => 'Recent Issued Books',
                'headers' => ['Issue Date', 'Book', 'ISBN', 'Borrower', 'ID', 'Due Date', 'Issued By', 'Status'],
                'rows' => array_map(static fn($r) => [
                    format_datetime($r['created_at'] ?? $r['issue_date']), $r['book_title'], (string) ($r['isbn'] ?? ''),
                    $r['student_name'] ?? $r['teacher_name'] ?? '—', $r['admission_number'] ?? $r['employee_number'] ?? '',
                    (string) $r['due_date'], (string) ($r['issued_by_name'] ?? ''), ucfirst($r['status']),
                ], $payload['recentIssued']),
            ],
            'returned' => [
                'title' => 'Recent Returned Books',
                'headers' => ['Return Date', 'Book', 'ISBN', 'Borrower', 'Fine', 'Returned By'],
                'rows' => array_map(static fn($r) => [
                    format_datetime($r['returned_at'] ?? $r['return_date']), $r['book_title'], (string) ($r['isbn'] ?? ''),
                    $r['student_name'] ?? $r['teacher_name'] ?? '—', format_currency($r['fine_amount']), (string) ($r['returned_by_name'] ?? ''),
                ], $payload['recentReturned']),
            ],
            'added' => [
                'title' => 'Recently Added Books',
                'headers' => ['Added Date', 'Book', 'ISBN', 'Category', 'Author', 'Publisher', 'Copies', 'Available'],
                'rows' => array_map(static fn($r) => [
                    format_datetime($r['created_at']), $r['title'], (string) ($r['isbn'] ?? ''), (string) ($r['category_name'] ?? ''),
                    (string) ($r['author_name'] ?? ''), (string) ($r['publisher_name'] ?? ''), (string) $r['total_copies'], (string) $r['available_copies'],
                ], $payload['recentlyAdded']),
            ],
            'updated' => [
                'title' => 'Recently Updated Books',
                'headers' => ['Updated Date', 'Book', 'ISBN', 'Updated By'],
                'rows' => array_map(static fn($r) => [
                    format_datetime($r['updated_at']), $r['title'], (string) ($r['isbn'] ?? ''), (string) ($r['updated_by_name'] ?? ''),
                ], $payload['recentlyUpdated']),
            ],
            'students' => [
                'title' => 'Recently Added Students',
                'headers' => ['Admission Date', 'Student ID', 'Name', 'Class', 'Section', 'Phone', 'Status'],
                'rows' => array_map(static fn($r) => [
                    format_datetime($r['created_at'] ?? null, 'd M Y'), (string) ($r['admission_number'] ?? ''), (string) $r['full_name'],
                    (string) ($r['class_name'] ?? ''), (string) ($r['section_name'] ?? ''), (string) ($r['phone'] ?? ''), ucfirst((string) $r['status']),
                ], $payload['recentStudents']),
            ],
            'overdue' => [
                'title' => 'Overdue Books',
                'headers' => ['Book', 'Borrower', 'Due Date', 'Days Overdue', 'Fine'],
                'rows' => array_map(static fn($r) => [
                    $r['book_title'], $r['student_name'] ?? $r['teacher_name'] ?? '—', (string) $r['due_date'],
                    (string) $r['days_overdue'], format_currency($r['fine_amount']),
                ], $payload['overdue']),
            ],
            'lowstock' => [
                'title' => 'Low Stock Books',
                'headers' => ['Book', 'ISBN', 'Total Copies', 'Available', 'Shelf', 'Rack'],
                'rows' => array_map(static fn($r) => [
                    $r['title'], (string) ($r['isbn'] ?? ''), (string) $r['total_copies'], (string) $r['available_copies'],
                    (string) ($r['shelf_number'] ?? ''), (string) ($r['rack_number'] ?? ''),
                ], $payload['lowStock']),
            ],
            'categories' => [
                'title' => 'Recently Added Categories',
                'headers' => ['Category', 'Total Books', 'Added Date'],
                'rows' => array_map(static fn($r) => [$r['name'], (string) $r['book_count'], format_datetime($r['created_at'], 'd M Y')], $payload['recentCategories']),
            ],
            'authors' => [
                'title' => 'Recently Added Authors',
                'headers' => ['Author', 'Total Books', 'Added Date'],
                'rows' => array_map(static fn($r) => [$r['name'], (string) $r['book_count'], format_datetime($r['created_at'], 'd M Y')], $payload['recentAuthors']),
            ],
            'publishers' => [
                'title' => 'Recently Added Publishers',
                'headers' => ['Publisher', 'Total Books', 'Added Date'],
                'rows' => array_map(static fn($r) => [$r['name'], (string) $r['book_count'], format_datetime($r['created_at'], 'd M Y')], $payload['recentPublishers']),
            ],
            'reservations' => [
                'title' => 'Recent Reservations',
                'headers' => ['Reserved On', 'Book', 'ISBN', 'Borrower', 'Reserved For', 'Status'],
                'rows' => array_map(static fn($r) => [
                    format_datetime($r['created_at'] ?? $r['reserved_date']), $r['book_title'], (string) ($r['isbn'] ?? ''),
                    $r['student_name'] ?? $r['teacher_name'] ?? '—', (string) $r['reserved_date'], ucfirst((string) $r['status']),
                ], $payload['recentReservations']),
            ],
            'payments' => [
                'title' => 'Recent Payments',
                'headers' => ['Student', 'Receipt #', 'Amount', 'Mode', 'Type', 'Paid At'],
                'rows' => array_map(static fn($r) => [
                    $r['student_display'], (string) ($r['receipt_number'] ?? ''), format_currency($r['paid_amount']),
                    ucwords(str_replace('_', ' ', (string) $r['payment_mode'])),
                    $r['payment_kind'] === 'library_fine' ? 'Library Fine' : 'Fee Payment',
                    format_datetime($r['paid_at']),
                ], $payload['recentPayments']),
            ],
            default => null,
        };
    }

    private function exportCsv(string $title, array $headers, array $rows): void
    {
        $filename = strtolower(str_replace(' ', '_', $title)) . '_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fputcsv($out, $headers);
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        fclose($out);
    }

    private function exportPdf(string $title, array $headers, array $rows): void
    {
        ob_start();
        echo '<h4>' . e($title) . '</h4><table border="1" cellspacing="0" cellpadding="4" style="border-collapse:collapse;width:100%;font-size:12px">';
        echo '<thead><tr>';
        foreach ($headers as $h) {
            echo '<th>' . e($h) . '</th>';
        }
        echo '</tr></thead><tbody>';
        foreach ($rows as $row) {
            echo '<tr>';
            foreach ($row as $cell) {
                echo '<td>' . e((string) $cell) . '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table>';
        $html = ob_get_clean();

        if (class_exists(\Dompdf\Dompdf::class)) {
            $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => false]);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            $dompdf->stream(strtolower(str_replace(' ', '_', $title)) . '.pdf', ['Attachment' => true]);
            return;
        }

        // No PDF library available — fall back to a printable HTML page.
        echo $html;
    }

    // ------------------------------------------------------------------
    // Book catalog
    // ------------------------------------------------------------------

    public function books(): void
    {
        $bookModel = new Book();
        $search = trim((string) $this->input('search', ''));
        $categoryId = trim((string) $this->input('category', ''));
        $availability = (string) $this->input('availability', '');
        $perPage = $this->perPageChoice();

        $result = $bookModel->paginateCatalog($this->currentPage(), $perPage, $search, $categoryId, $availability);

        $this->view('library/books/index', [
            'pageTitle' => 'Library — Books',
            'result' => $result,
            'categories' => (new BookCategory())->forDropdown(),
            'search' => $search,
            'category' => $categoryId,
            'availability' => $availability,
            'perPage' => $perPage,
        ]);
    }

    /** Allowed "entries per page" choices for Library list screens; defaults to 10 (matches the reference UI). */
    private function perPageChoice(): int
    {
        $allowed = [10, 25, 50, 100];
        $requested = (int) $this->input('per_page', 10);
        return in_array($requested, $allowed, true) ? $requested : 10;
    }

    public function createBook(): void
    {
        $this->view('library/books/form', [
            'pageTitle' => 'Add Book',
            'book' => [],
            'authors' => (new Author())->forDropdown(),
            'publishers' => (new Publisher())->forDropdown(),
            'categories' => (new BookCategory())->forDropdown(),
        ]);
    }

    public function storeBook(): void
    {
        $data = $this->validate([
            'title' => 'required|max:255',
            'accession_number' => 'required|max:30',
            'isbn' => 'nullable|max:20',
            'author_id' => 'nullable|numeric',
            'publisher_id' => 'nullable|numeric',
            'category_id' => 'nullable|numeric',
            'edition' => 'nullable|max:50',
            'language' => 'nullable|max:50',
            'rack_number' => 'nullable|max:30',
            'shelf_number' => 'nullable|max:30',
            'description' => 'nullable|max:1000',
            'total_copies' => 'required|numeric',
        ], [], null);

        $authorId = $data['author_id'] !== '' ? (int) $data['author_id'] : null;
        $bookModel = new Book();

        if ($bookModel->isbnExists((string) $data['isbn'])) {
            Session::setErrors(['isbn' => ['ISBN already exists with another book.']]);
            Session::setOldInput($data);
            $this->back();
            return;
        }
        if ($bookModel->titleExistsForAuthor($data['title'], $authorId)) {
            Session::setErrors(['title' => ['The book title already exists.']]);
            Session::setOldInput($data);
            $this->back();
            return;
        }

        $coverPath = null;
        try {
            $coverPath = handle_upload($this->file('cover_image') ?? [], 'library_books', true);
        } catch (\RuntimeException $e) {
            Session::setErrors(['cover_image' => [$e->getMessage()]]);
            Session::setOldInput($data);
            $this->back();
            return;
        }

        $qty = max(1, (int) $data['total_copies']);

        $bookId = (new Book())->insert([
            'isbn' => $data['isbn'] ?: null,
            'accession_number' => $data['accession_number'],
            'title' => $data['title'],
            'author_id' => $authorId,
            'publisher_id' => $data['publisher_id'] !== '' ? (int) $data['publisher_id'] : null,
            'category_id' => $data['category_id'] !== '' ? (int) $data['category_id'] : null,
            'cover_image' => $coverPath,
            'edition' => $data['edition'] ?: null,
            'language' => $data['language'] ?: null,
            'rack_number' => $data['rack_number'] ?: null,
            'shelf_number' => $data['shelf_number'] ?: null,
            'description' => $data['description'] ?: null,
            'status' => 'active',
            'total_copies' => $qty,
            'available_copies' => $qty,
        ]);

        // One row per physical copy, each with its own auto-generated,
        // sequential, never-reused 5-digit Copy Code — see BookCopy model.
        $copies = (new BookCopy())->generateCopies($bookId, $qty, [
            'rack_number' => $data['rack_number'] ?: null,
            'shelf_number' => $data['shelf_number'] ?: null,
        ]);

        $codes = implode(', ', array_column($copies, 'copy_code'));
        $this->flashSuccess("Book added to the catalog. Copy codes generated: {$codes}");
        $this->redirect(url('library/books'));
    }

    /**
     * AJAX: does this ISBN already belong to another book? Backs the
     * auto-generated ISBN on the Add/Edit Book form — the client calls this
     * for each generated candidate and regenerates on a hit.
     */
    public function checkIsbn(): void
    {
        $isbn = trim((string) $this->input('isbn', ''));
        $excludeId = $this->input('exclude_id', '');
        $excludeId = $excludeId !== '' ? (int) $excludeId : null;

        $this->json([
            'exists' => $isbn !== '' && (new Book())->isbnExists($isbn, $excludeId),
        ]);
    }

    public function editBook(int $id): void
    {
        $book = (new Book())->findWithNames($id);
        if (!$book) {
            $this->flashError('Book not found.');
            $this->redirect(url('library/books'));
            return;
        }

        $this->view('library/books/form', [
            'pageTitle' => 'Edit Book',
            'book' => $book,
            'authors' => (new Author())->forDropdown(),
            'publishers' => (new Publisher())->forDropdown(),
            'categories' => (new BookCategory())->forDropdown(),
        ]);
    }

    public function updateBook(int $id): void
    {
        $data = $this->validate([
            'title' => 'required|max:255',
            'accession_number' => 'required|max:30',
            'isbn' => 'nullable|max:20',
            'author_id' => 'nullable|numeric',
            'publisher_id' => 'nullable|numeric',
            'category_id' => 'nullable|numeric',
            'edition' => 'nullable|max:50',
            'language' => 'nullable|max:50',
            'rack_number' => 'nullable|max:30',
            'shelf_number' => 'nullable|max:30',
            'description' => 'nullable|max:1000',
            'total_copies' => 'required|numeric',
            'available_copies' => 'required|numeric',
        ], [], null);

        $authorId = $data['author_id'] !== '' ? (int) $data['author_id'] : null;
        $bookModel = new Book();

        if ($bookModel->isbnExists((string) $data['isbn'], $id)) {
            Session::setErrors(['isbn' => ['ISBN already exists with another book.']]);
            Session::setOldInput($data);
            $this->back();
            return;
        }
        if ($bookModel->titleExistsForAuthor($data['title'], $authorId, $id)) {
            Session::setErrors(['title' => ['The book title already exists.']]);
            Session::setOldInput($data);
            $this->back();
            return;
        }

        $existing = $bookModel->find($id);
        $coverPath = $existing['cover_image'] ?? null;
        $newFile = $this->file('cover_image');
        if ($newFile && ($newFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $uploaded = handle_upload($newFile, 'library_books', true);
                if ($uploaded) {
                    delete_upload($coverPath);
                    $coverPath = $uploaded;
                }
            } catch (\RuntimeException $e) {
                Session::setErrors(['cover_image' => [$e->getMessage()]]);
                Session::setOldInput($data);
                $this->back();
                return;
            }
        }

        $copyModel = new BookCopy();
        $existingCopyCount = $copyModel->countsForBook($id)['total'];
        // total_copies can never go below the number of physical copies that
        // actually exist (that's not a "total copies" edit, that's a
        // withdraw-a-copy action — handled separately via markLost/markDamaged).
        $requestedTotal = max((int) $data['total_copies'], $existingCopyCount);
        $newCopiesNeeded = $requestedTotal - $existingCopyCount;

        $bookModel->update($id, [
            'isbn' => $data['isbn'] ?: null,
            'accession_number' => $data['accession_number'],
            'title' => $data['title'],
            'author_id' => $authorId,
            'publisher_id' => $data['publisher_id'] !== '' ? (int) $data['publisher_id'] : null,
            'category_id' => $data['category_id'] !== '' ? (int) $data['category_id'] : null,
            'cover_image' => $coverPath,
            'edition' => $data['edition'] ?: null,
            'language' => $data['language'] ?: null,
            'rack_number' => $data['rack_number'] ?: null,
            'shelf_number' => $data['shelf_number'] ?: null,
            'description' => $data['description'] ?: null,
            'total_copies' => $requestedTotal,
            'available_copies' => min((int) $data['available_copies'] + $newCopiesNeeded, $requestedTotal),
            'updated_by' => Auth::id(),
        ]);

        $addedCodes = [];
        if ($newCopiesNeeded > 0) {
            $addedCodes = array_column($copyModel->generateCopies($id, $newCopiesNeeded, [
                'rack_number' => $data['rack_number'] ?: null,
                'shelf_number' => $data['shelf_number'] ?: null,
            ]), 'copy_code');
        }

        $this->flashSuccess($addedCodes
            ? 'Book updated. New copy codes generated: ' . implode(', ', $addedCodes)
            : 'Book updated.');
        $this->redirect(url('library/books'));
    }

    public function showBook(int $id): void
    {
        $book = (new Book())->findWithNames($id);
        if (!$book) {
            $this->flashError('Book not found.');
            $this->redirect(url('library/books'));
            return;
        }
        $this->view('library/books/show', [
            'pageTitle' => $book['title'],
            'book' => $book,
            'copies' => (new BookCopy())->forBook($id),
            'copyCounts' => (new BookCopy())->countsForBook($id),
            'history' => (new BookIssue())->historyForBook($id),
        ]);
    }

    /** Soft delete — reversible from the Trash screen (matches the reference UI's "Delete + Restore option" requirement). */
    public function destroyBook(int $id): void
    {
        $bookModel = new Book();
        $book = $bookModel->find($id);
        if ($book && (int) $book['total_copies'] !== (int) $book['available_copies']) {
            $this->flashError('Cannot remove a book that currently has copies issued.');
            $this->back();
            return;
        }
        $bookModel->softDelete($id);
        $this->flashSuccess('Book moved to Trash.');
        $this->redirect(url('library/books'));
    }

    public function trashedBooks(): void
    {
        $perPage = $this->perPageChoice();
        $this->view('library/books/trash', [
            'pageTitle' => 'Deleted Books',
            'result' => (new Book())->trashed($this->currentPage(), $perPage),
        ]);
    }

    public function restoreBook(int $id): void
    {
        (new Book())->restore($id);
        $this->flashSuccess('Book restored.');
        $this->redirect(url('library/books'));
    }

    public function markDamaged(int $id): void
    {
        // Requirement #6 "Damaged Books" — takes one copy permanently out
        // of circulation. Picks the oldest (lowest copy code) on-shelf copy.
        $copyModel = new BookCopy();
        $available = $copyModel->availableForBook($id);
        if (empty($available)) {
            $this->flashError('No on-shelf copy available to mark damaged.');
            $this->back();
            return;
        }
        $copy = $available[0];
        $copyModel->markStatus((int) $copy['id'], 'damaged');
        (new Book())->removeDamagedCopy($id);
        $this->flashSuccess("Copy {$copy['copy_code']} marked damaged and removed from circulation.");
        $this->back();
    }

    // ------------------------------------------------------------------
    // Categories
    // ------------------------------------------------------------------

    public function categories(): void
    {
        $search = trim((string) $this->input('search', ''));
        $this->view('library/categories/index', [
            'pageTitle' => 'Book Categories',
            'result' => (new BookCategory())->paginateList($this->currentPage(), $this->perPageChoice(), $search),
            'search' => $search,
        ]);
    }

    public function createCategory(): void
    {
        $this->view('library/categories/form', ['pageTitle' => 'Add Category', 'category' => []]);
    }

    public function storeCategory(): void
    {
        $data = $this->validate(['name' => 'required|max:120', 'description' => 'nullable|max:255'], [], null);

        $model = new BookCategory();
        if ($model->nameExists($data['name'])) {
            Session::setErrors(['name' => ['Duplicate category names are not allowed.']]);
            Session::setOldInput($data);
            $this->back();
            return;
        }

        $model->insert([
            'name' => $data['name'],
            'description' => $data['description'] ?: null,
            'is_active' => 1,
            'created_by' => Auth::id(),
        ]);

        $this->flashSuccess('Category added.');
        $this->redirect(url('library/categories'));
    }

    public function editCategory(int $id): void
    {
        $category = (new BookCategory())->find($id);
        if (!$category) {
            $this->flashError('Category not found.');
            $this->redirect(url('library/categories'));
            return;
        }
        $this->view('library/categories/form', ['pageTitle' => 'Edit Category', 'category' => $category]);
    }

    public function updateCategory(int $id): void
    {
        $data = $this->validate(['name' => 'required|max:120', 'description' => 'nullable|max:255'], [], null);

        $model = new BookCategory();
        if ($model->nameExists($data['name'], $id)) {
            Session::setErrors(['name' => ['Duplicate category names are not allowed.']]);
            Session::setOldInput($data);
            $this->back();
            return;
        }

        $model->update($id, [
            'name' => $data['name'],
            'description' => $data['description'] ?: null,
            'updated_by' => Auth::id(),
        ]);

        $this->flashSuccess('Category updated.');
        $this->redirect(url('library/categories'));
    }

    public function toggleCategoryStatus(int $id): void
    {
        $model = new BookCategory();
        $category = $model->find($id);
        if ($category) {
            $model->update($id, ['is_active' => $category['is_active'] ? 0 : 1, 'updated_by' => Auth::id()]);
        }
        $this->back();
    }

    public function destroyCategory(int $id): void
    {
        $model = new BookCategory();
        if ($model->bookCount($id) > 0) {
            $this->flashError('Cannot delete a category that still has books assigned to it.');
            $this->back();
            return;
        }
        $model->raw("UPDATE `book_categories` SET deleted_at = NOW() WHERE id = :id", ['id' => $id]);
        $this->flashSuccess('Category removed.');
        $this->redirect(url('library/categories'));
    }

    // ------------------------------------------------------------------
    // Authors
    // ------------------------------------------------------------------

    public function authors(): void
    {
        $search = trim((string) $this->input('search', ''));
        $this->view('library/authors/index', [
            'pageTitle' => 'Authors',
            'result' => (new Author())->paginateList($this->currentPage(), $this->perPageChoice(), $search),
            'search' => $search,
        ]);
    }

    public function createAuthor(): void
    {
        $this->view('library/authors/form', ['pageTitle' => 'Add Author', 'author' => []]);
    }

    public function storeAuthor(): void
    {
        $data = $this->validate([
            'name' => 'required|max:150',
            'country' => 'nullable|max:100',
            'bio' => 'nullable|max:1000',
        ], [], null);

        $model = new Author();
        if ($model->nameExists($data['name'])) {
            Session::setErrors(['name' => ['Duplicate authors are not allowed.']]);
            Session::setOldInput($data);
            $this->back();
            return;
        }

        $photoPath = null;
        try {
            $photoPath = handle_upload($this->file('photo') ?? [], 'library_authors', true);
        } catch (\RuntimeException $e) {
            Session::setErrors(['photo' => [$e->getMessage()]]);
            Session::setOldInput($data);
            $this->back();
            return;
        }

        $model->insert([
            'name' => $data['name'],
            'country' => $data['country'] ?: null,
            'bio' => $data['bio'] ?: null,
            'photo' => $photoPath,
            'is_active' => 1,
        ]);

        $this->flashSuccess('Author added.');
        $this->redirect(url('library/authors'));
    }

    public function editAuthor(int $id): void
    {
        $author = (new Author())->find($id);
        if (!$author) {
            $this->flashError('Author not found.');
            $this->redirect(url('library/authors'));
            return;
        }
        $this->view('library/authors/form', ['pageTitle' => 'Edit Author', 'author' => $author]);
    }

    public function updateAuthor(int $id): void
    {
        $data = $this->validate([
            'name' => 'required|max:150',
            'country' => 'nullable|max:100',
            'bio' => 'nullable|max:1000',
        ], [], null);

        $model = new Author();
        if ($model->nameExists($data['name'], $id)) {
            Session::setErrors(['name' => ['Duplicate authors are not allowed.']]);
            Session::setOldInput($data);
            $this->back();
            return;
        }

        $existing = $model->find($id);
        $photoPath = $existing['photo'] ?? null;
        $newFile = $this->file('photo');
        if ($newFile && ($newFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $uploaded = handle_upload($newFile, 'library_authors', true);
                if ($uploaded) {
                    delete_upload($photoPath);
                    $photoPath = $uploaded;
                }
            } catch (\RuntimeException $e) {
                Session::setErrors(['photo' => [$e->getMessage()]]);
                Session::setOldInput($data);
                $this->back();
                return;
            }
        }

        $model->update($id, [
            'name' => $data['name'],
            'country' => $data['country'] ?: null,
            'bio' => $data['bio'] ?: null,
            'photo' => $photoPath,
        ]);

        $this->flashSuccess('Author updated.');
        $this->redirect(url('library/authors'));
    }

    public function destroyAuthor(int $id): void
    {
        $model = new Author();
        if ($model->bookCount($id) > 0) {
            $this->flashError('Cannot delete an author that still has books assigned to them.');
            $this->back();
            return;
        }
        $model->raw("UPDATE `authors` SET deleted_at = NOW() WHERE id = :id", ['id' => $id]);
        $this->flashSuccess('Author removed.');
        $this->redirect(url('library/authors'));
    }

    // ------------------------------------------------------------------
    // Publishers
    // ------------------------------------------------------------------

    public function publishers(): void
    {
        $search = trim((string) $this->input('search', ''));
        $this->view('library/publishers/index', [
            'pageTitle' => 'Publishers',
            'result' => (new Publisher())->paginateList($this->currentPage(), $this->perPageChoice(), $search),
            'search' => $search,
        ]);
    }

    public function createPublisher(): void
    {
        $this->view('library/publishers/form', ['pageTitle' => 'Add Publisher', 'publisher' => []]);
    }

    public function storePublisher(): void
    {
        $data = $this->validate([
            'name' => 'required|max:150',
            'email' => 'nullable|max:150',
            'phone' => 'nullable|max:50',
            'website' => 'nullable|max:255',
            'address' => 'nullable|max:255',
        ], [], null);

        $model = new Publisher();
        if ($model->nameExists($data['name'])) {
            Session::setErrors(['name' => ['Duplicate publishers are not allowed.']]);
            Session::setOldInput($data);
            $this->back();
            return;
        }

        $logoPath = null;
        try {
            $logoPath = handle_upload($this->file('logo') ?? [], 'library_publishers', true);
        } catch (\RuntimeException $e) {
            Session::setErrors(['logo' => [$e->getMessage()]]);
            Session::setOldInput($data);
            $this->back();
            return;
        }

        $model->insert([
            'name' => $data['name'],
            'email' => $data['email'] ?: null,
            'phone' => $data['phone'] ?: null,
            'website' => $data['website'] ?: null,
            'address' => $data['address'] ?: null,
            'contact' => $data['phone'] ?: null,
            'logo' => $logoPath,
            'is_active' => 1,
        ]);

        $this->flashSuccess('Publisher added.');
        $this->redirect(url('library/publishers'));
    }

    public function editPublisher(int $id): void
    {
        $publisher = (new Publisher())->find($id);
        if (!$publisher) {
            $this->flashError('Publisher not found.');
            $this->redirect(url('library/publishers'));
            return;
        }
        $this->view('library/publishers/form', ['pageTitle' => 'Edit Publisher', 'publisher' => $publisher]);
    }

    public function updatePublisher(int $id): void
    {
        $data = $this->validate([
            'name' => 'required|max:150',
            'email' => 'nullable|max:150',
            'phone' => 'nullable|max:50',
            'website' => 'nullable|max:255',
            'address' => 'nullable|max:255',
        ], [], null);

        $model = new Publisher();
        if ($model->nameExists($data['name'], $id)) {
            Session::setErrors(['name' => ['Duplicate publishers are not allowed.']]);
            Session::setOldInput($data);
            $this->back();
            return;
        }

        $existing = $model->find($id);
        $logoPath = $existing['logo'] ?? null;
        $newFile = $this->file('logo');
        if ($newFile && ($newFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $uploaded = handle_upload($newFile, 'library_publishers', true);
                if ($uploaded) {
                    delete_upload($logoPath);
                    $logoPath = $uploaded;
                }
            } catch (\RuntimeException $e) {
                Session::setErrors(['logo' => [$e->getMessage()]]);
                Session::setOldInput($data);
                $this->back();
                return;
            }
        }

        $model->update($id, [
            'name' => $data['name'],
            'email' => $data['email'] ?: null,
            'phone' => $data['phone'] ?: null,
            'website' => $data['website'] ?: null,
            'address' => $data['address'] ?: null,
            'contact' => $data['phone'] ?: null,
            'logo' => $logoPath,
        ]);

        $this->flashSuccess('Publisher updated.');
        $this->redirect(url('library/publishers'));
    }

    public function destroyPublisher(int $id): void
    {
        $model = new Publisher();
        if ($model->bookCount($id) > 0) {
            $this->flashError('Cannot delete a publisher that still has books assigned to it.');
            $this->back();
            return;
        }
        $model->raw("UPDATE `publishers` SET deleted_at = NOW() WHERE id = :id", ['id' => $id]);
        $this->flashSuccess('Publisher removed.');
        $this->redirect(url('library/publishers'));
    }

    // ------------------------------------------------------------------
    // Issue / Return / Renew
    // ------------------------------------------------------------------

    public function issueForm(): void
    {
        $this->view('library/issue', [
            'pageTitle' => 'Issue Book',
            'students' => (new Student())->forLibraryDropdown(),
            'teachers' => (new Teacher())->forLibraryDropdown(),
            'books' => (new Book())->forIssueDropdown(),
        ]);
    }

    public function searchBorrower(): void
    {
        $type = (string) $this->input('type', 'student');
        $term = trim((string) $this->input('term', ''));
        if ($term === '') {
            $this->json([]);
            return;
        }

        if ($type === 'teacher') {
            $rows = (new Teacher())->raw(
                "SELECT id, full_name, employee_number, phone FROM teachers
                 WHERE deleted_at IS NULL AND (full_name LIKE :t OR employee_number LIKE :t) LIMIT 10",
                ['t' => "%{$term}%"]
            );
        } else {
            $rows = (new Student())->raw(
                "SELECT id, full_name, admission_number, phone, status FROM students
                 WHERE (full_name LIKE :t OR admission_number LIKE :t) LIMIT 10",
                ['t' => "%{$term}%"]
            );
        }

        $this->json($rows);
    }

    public function searchBook(): void
    {
        $term = trim((string) $this->input('term', ''));
        $this->json((new Book())->searchAvailable($term));
    }

    /** GET /library/books/{id}/available-copies — cascading dropdown for the Issue screen. */
    public function availableCopies(int $bookId): void
    {
        $this->json((new BookCopy())->availableForBook($bookId));
    }

    public function issueBook(): void
    {
        $data = $this->validate([
            'book_id' => 'required|numeric',
            'book_copy_id' => 'required|numeric',
            'borrower_type' => 'required',
            'borrower_id' => 'required|numeric',
        ], [], null);

        $borrowerType = $data['borrower_type'] === 'teacher' ? 'teacher' : 'student';
        $bookId = (int) $data['book_id'];
        $copyId = (int) $data['book_copy_id'];
        $borrowerId = (int) $data['borrower_id'];

        $settings = (new LibrarySetting())->current();
        $issueModel = new BookIssue();

        $maxAllowed = $borrowerType === 'teacher' ? (int) $settings['max_books_teacher'] : (int) $settings['max_books_student'];
        if ($issueModel->activeCountForBorrower($borrowerType, $borrowerId) >= $maxAllowed) {
            $this->flashError("This {$borrowerType} already has the maximum number of books issued.");
            $this->back();
            return;
        }

        $db = \App\Core\Database::getInstance();
        $copyModel = new BookCopy();

        $db->beginTransaction();
        try {
            // Row-locked read: if two "Issue Book" submissions race for the
            // same copy, the second one blocks here until the first commits,
            // then finds status is no longer 'available' and cleanly fails
            // instead of double-issuing the same physical copy.
            $copy = $copyModel->lockForIssue($copyId);
            if (!$copy || (int) $copy['book_id'] !== $bookId) {
                $db->rollBack();
                $this->flashError('That copy is no longer available. Please pick another copy.');
                $this->back();
                return;
            }

            $issueDate = date('Y-m-d');
            $dueDate = date('Y-m-d', strtotime('+' . (int) $settings['loan_period_days'] . ' days'));

            $issueModel->insert([
                'book_id' => $bookId,
                'book_copy_id' => $copyId,
                'copy_code' => $copy['copy_code'],
                'student_id' => $borrowerType === 'student' ? $borrowerId : null,
                'teacher_id' => $borrowerType === 'teacher' ? $borrowerId : null,
                'issue_date' => $issueDate,
                'due_date' => $dueDate,
                'status' => 'issued',
                'issued_by' => Auth::id(),
            ]);

            $copyModel->markStatus($copyId, 'issued');
            (new Book())->decrementAvailable($bookId); // denormalized counter, kept in sync for the catalog/dashboard views

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            error_log('[library_issue_failed] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            $this->flashError('Could not issue this book right now. Nothing was changed — please try again, or contact an administrator if this keeps happening.');
            $this->back();
            return;
        }

        $this->flashSuccess("Copy {$copy['copy_code']} issued successfully. Due back {$dueDate}.");
        $this->redirect(url('library/transactions'));
    }

    public function transactions(): void
    {
        $search = trim((string) $this->input('search', ''));
        $fromDate = trim((string) $this->input('from_date', ''));
        $toDate = trim((string) $this->input('to_date', ''));
        $perPage = $this->perPageChoice();

        $result = (new BookIssue())->paginateActive($this->currentPage(), $perPage, $search, $fromDate, $toDate);

        $this->view('library/transactions/index', [
            'pageTitle' => 'Issued Books',
            'result' => $result,
            'transactions' => $result['data'],
            'search' => $search,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'perPage' => $perPage,
        ]);
    }

    public function returnForm(): void
    {
        $this->view('library/return', ['pageTitle' => 'Return Book']);
    }

    /**
     * Validates the payment info submitted for a fine *before* it's collected —
     * a valid, known payment mode, and (for anything but cash) a non-blank
     * reference/transaction number. Returns an error message, or null if the
     * input is good to go. Shared by returnBook()/renewBook()/payFine() so
     * every fine-collection entry point enforces exactly the same rule.
     */
    private function finePaymentInputError(string $mode, string $reference): ?string
    {
        if ($mode === '' || !array_key_exists($mode, LibraryFinePayment::PAYMENT_MODES)) {
            return 'Select a valid payment mode.';
        }
        if ($reference === '' && in_array($mode, self::FINE_MODES_REQUIRING_REFERENCE, true)) {
            return 'A reference/transaction number is required for ' . LibraryFinePayment::PAYMENT_MODES[$mode] . ' payments.';
        }
        return null;
    }

    public function returnBook(int $issueId): void
    {
        $issueModel = new BookIssue();
        $issue = $issueModel->find($issueId);
        if (!$issue || $issue['status'] !== 'issued') {
            $this->flashError('Loan not found or already closed.');
            $this->back();
            return;
        }

        $settings = (new LibrarySetting())->current();
        $today = date('Y-m-d');
        $daysLate = 0;
        $fine = 0.0;
        if ($today > $issue['due_date']) {
            $daysLate = (int) round((strtotime($today) - strtotime($issue['due_date'])) / 86400);
            $fine = round($daysLate * (float) $settings['fine_per_day'], 2);
        }

        // An overdue loan carries a fine that has to be collected before the
        // loan can be closed out. The Return button on an overdue row bundles
        // payment_mode/reference_number into the same request (see the
        // fine-collection prompt in transactions/index.php), so a normal,
        // on-time return still goes through in one click exactly as before.
        $finePaid = (int) $issue['fine_paid'] === 1;
        $receiptNumber = null;
        if ($fine > 0 && !$finePaid) {
            $mode = (string) $this->input('payment_mode', '');
            $reference = trim((string) $this->input('reference_number', ''));
            $error = $this->finePaymentInputError($mode, $reference);
            if ($error !== null) {
                $this->flashError(
                    "This book is {$daysLate} day(s) overdue with an outstanding fine of "
                    . format_currency($fine) . ". {$error}"
                );
                $this->back();
                return;
            }

            try {
                $receiptNumber = $this->collectFine($issueId, $issue, $fine, $mode, $reference);
            } catch (\Throwable $e) {
                error_log('[LIBRARY FINE COLLECTION FAILED] loan #' . $issueId . ': ' . $e->getMessage());
                $this->flashError('The fine could not be collected due to a server error. The book was not returned — please try again.');
                $this->back();
                return;
            }
            // Don't just trust that insert() didn't throw — confirm the
            // receipt actually landed before treating this loan as paid
            // off and closing it out.
            if (!$this->fineWasCollected($issueId)) {
                $this->flashError('The fine could not be confirmed as collected. The book was not returned — please try again.');
                $this->back();
                return;
            }
            $finePaid = true;
        }

        $issueModel->update($issueId, [
            'return_date' => $today,
            'returned_at' => date('Y-m-d H:i:s'),
            'returned_by' => Auth::id(),
            'fine_amount' => $fine,
            'fine_paid' => $finePaid ? 1 : 0,
            'status' => 'returned',
        ]);

        (new Book())->incrementAvailable((int) $issue['book_id']);
        if (!empty($issue['book_copy_id'])) {
            (new BookCopy())->markStatus((int) $issue['book_copy_id'], 'available');
        }

        $copyNote = $issue['copy_code'] ? "Copy {$issue['copy_code']} returned." : 'Book returned successfully.';
        $msg = $copyNote;
        if ($fine > 0) {
            $msg .= $finePaid
                ? ' Fine of ' . format_currency($fine) . ' collected' . ($receiptNumber ? " (Receipt: {$receiptNumber})" : '') . '.'
                : ' Fine due: ' . format_currency($fine) . '.';
        }
        $this->flashSuccess($msg);
        $this->redirect(url('library/transactions'));
    }

    public function markLost(int $issueId): void
    {
        $issueModel = new BookIssue();
        $issue = $issueModel->find($issueId);
        if (!$issue || $issue['status'] !== 'issued') {
            $this->flashError('Loan not found or already closed.');
            $this->back();
            return;
        }

        $issueModel->update($issueId, ['status' => 'lost', 'returned_by' => Auth::id()]);
        // The copy never comes back — remove it from the title's total, not just available.
        (new Book())->removeLostCopy((int) $issue['book_id']);
        if (!empty($issue['book_copy_id'])) {
            (new BookCopy())->markStatus((int) $issue['book_copy_id'], 'lost');
        }

        $this->flashSuccess('Book marked as lost.');
        $this->redirect(url('library/transactions'));
    }

    public function payFine(int $issueId): void
    {
        $issueModel = new BookIssue();
        $issue = $issueModel->find($issueId);
        if (!$issue) {
            $this->flashError('Loan not found.');
            $this->back();
            return;
        }
        if ((int) $issue['fine_paid'] === 1) {
            $this->flashError('This fine has already been marked paid.');
            $this->back();
            return;
        }

        $amount = (float) $issue['fine_amount'];
        // `fine_amount` on book_issues is only ever finalized once a book is
        // actually returned (see returnBook()) — for a loan that's still
        // out but overdue it's always 0.00 in the database. Project what's
        // owed right now, the same way the Overdue Books dashboard widget
        // and BookIssue::overdueList() do, so the fine can be collected
        // before the book comes back.
        if ($amount <= 0 && $issue['status'] === 'issued' && $issue['due_date'] < date('Y-m-d')) {
            $settings = (new LibrarySetting())->current();
            $daysLate = (int) round((strtotime(date('Y-m-d')) - strtotime($issue['due_date'])) / 86400);
            $amount = round($daysLate * (float) $settings['fine_per_day'], 2);
        }
        if ($amount <= 0) {
            $this->flashError('There is no fine on this loan.');
            $this->back();
            return;
        }

        $mode = (string) $this->input('payment_mode', '');
        $reference = trim((string) $this->input('reference_number', ''));
        $error = $this->finePaymentInputError($mode, $reference);
        if ($error !== null) {
            $this->flashError($error);
            $this->back();
            return;
        }

        try {
            $receiptNumber = $this->collectFine($issueId, $issue, $amount, $mode, $reference);
        } catch (\Throwable $e) {
            error_log('[LIBRARY FINE COLLECTION FAILED] loan #' . $issueId . ': ' . $e->getMessage());
            $this->flashError('The fine could not be collected due to a server error. Please try again.');
            $this->back();
            return;
        }
        if (!$this->fineWasCollected($issueId)) {
            $this->flashError('The fine could not be confirmed as collected. Please try again.');
            $this->back();
            return;
        }
        $issueModel->update($issueId, ['fine_amount' => $amount, 'fine_paid' => 1]);

        $this->flashSuccess("Fine of " . format_currency($amount) . " collected. Receipt: {$receiptNumber}.");
        $this->back();
    }

    /**
     * Records one library fine receipt (ledger row + activity log entry)
     * and returns the receipt number. Shared by payFine() and by
     * returnBook()/renewBook() when they collect a fine as part of closing
     * out or extending an overdue loan — so there is exactly one place that
     * writes a fine payment record.
     */
    private function collectFine(int $issueId, array $issue, float $amount, string $mode, string $reference): string
    {
        $receiptNumber = (new Payment())->nextReceiptNumber();

        (new LibraryFinePayment())->insert([
            'book_issue_id' => $issueId,
            'student_id' => $issue['student_id'] ?: null,
            'teacher_id' => $issue['teacher_id'] ?: null,
            'amount' => $amount,
            'payment_mode' => $mode,
            'reference_number' => $reference ?: null,
            'receipt_number' => $receiptNumber,
            'received_by' => Auth::id(),
            'paid_at' => date('Y-m-d H:i:s'),
        ]);

        log_activity('library_fine_paid', "Collected library fine of " . format_currency($amount) . " for loan #{$issueId}, receipt {$receiptNumber}.");

        return $receiptNumber;
    }

    /**
     * Confirms a fine payment actually exists for this loan, rather than
     * just trusting that collectFine()'s insert() didn't throw. Used
     * anywhere a fine is collected as a *prerequisite* for another action
     * (renewing/returning/marking paid) so that action is never finalized
     * on the assumption of a payment that didn't really land.
     */
    private function fineWasCollected(int $issueId): bool
    {
        $rows = (new LibraryFinePayment())->raw(
            'SELECT COUNT(*) AS c FROM `library_fine_payments` WHERE `book_issue_id` = :id',
            ['id' => $issueId]
        );
        return (int) ($rows[0]['c'] ?? 0) > 0;
    }

    /** Printable receipt for one library fine payment (browser print view). */
    public function fineReceipt(string $receiptNumber): void
    {
        $payment = (new LibraryFinePayment())->byReceiptNumber($receiptNumber);
        if (!$payment) {
            http_response_code(404);
            require dirname(__DIR__) . '/Views/errors/404.php';
            return;
        }

        $this->viewRaw('library/fine-receipt', ['payment' => $payment]);
    }

    public function renewBook(int $issueId): void
    {
        $issueModel = new BookIssue();
        $issue = $issueModel->find($issueId);
        $settings = (new LibrarySetting())->current();

        if (!$issue || $issue['status'] !== 'issued') {
            $this->flashError('Loan not found or already closed.');
            $this->back();
            return;
        }
        if ((int) $issue['renewed_count'] >= (int) $settings['max_renewals']) {
            $this->flashError('Renewal limit reached for this loan.');
            $this->back();
            return;
        }

        $today = date('Y-m-d');
        $daysLate = 0;
        $fine = 0.0;
        if ($today > $issue['due_date']) {
            $daysLate = (int) round((strtotime($today) - strtotime($issue['due_date'])) / 86400);
            $fine = round($daysLate * (float) $settings['fine_per_day'], 2);
        }

        // Same rule as returnBook(): an overdue loan can't be extended until
        // its outstanding fine is collected. The Renew button on an overdue
        // row bundles payment_mode/reference_number into the same request
        // (see the fine-collection prompt in transactions/index.php).
        $updates = [];
        $finePaid = (int) $issue['fine_paid'] === 1;
        $receiptNumber = null;
        if ($fine > 0 && !$finePaid) {
            $mode = (string) $this->input('payment_mode', '');
            $reference = trim((string) $this->input('reference_number', ''));
            $error = $this->finePaymentInputError($mode, $reference);
            if ($error !== null) {
                $this->flashError(
                    "This book is {$daysLate} day(s) overdue with an outstanding fine of "
                    . format_currency($fine) . ". {$error}"
                );
                $this->back();
                return;
            }

            try {
                $receiptNumber = $this->collectFine($issueId, $issue, $fine, $mode, $reference);
            } catch (\Throwable $e) {
                error_log('[LIBRARY FINE COLLECTION FAILED] loan #' . $issueId . ': ' . $e->getMessage());
                $this->flashError('The fine could not be collected due to a server error. The book was not renewed — please try again.');
                $this->back();
                return;
            }
            // Don't just trust that insert() didn't throw — confirm the
            // receipt actually landed before extending the due date.
            if (!$this->fineWasCollected($issueId)) {
                $this->flashError('The fine could not be confirmed as collected. The book was not renewed — please try again.');
                $this->back();
                return;
            }
            // The fine just paid covers this overdue stretch only. The loan
            // is about to get a fresh due date, so clear the balance rather
            // than leaving a paid-off amount sitting against the new period.
            $updates['fine_amount'] = 0.0;
            $updates['fine_paid'] = 0;
            $finePaid = true;
        }

        $newDue = date('Y-m-d', strtotime($issue['due_date'] . ' +' . (int) $settings['loan_period_days'] . ' days'));
        $updates['due_date'] = $newDue;
        $updates['renewed_count'] = (int) $issue['renewed_count'] + 1;
        $issueModel->update($issueId, $updates);

        $msg = "Book renewed. New due date: {$newDue}.";
        if ($fine > 0 && $finePaid) {
            $msg = "Fine of " . format_currency($fine) . " collected" . ($receiptNumber ? " (Receipt: {$receiptNumber})" : '') . ". " . $msg;
        }
        $this->flashSuccess($msg);
        $this->back();
    }

    // ------------------------------------------------------------------
    // Reservations
    // ------------------------------------------------------------------

    public function reservations(): void
    {
        $this->view('library/reservations/index', [
            'pageTitle' => 'Book Reservations',
            'reservations' => (new LibraryReservation())->pendingList(),
        ]);
    }

    public function reserveBook(): void
    {
        $data = $this->validate([
            'book_id' => 'required|numeric',
            'borrower_type' => 'required',
            'borrower_id' => 'required|numeric',
        ], [], null);

        $borrowerType = $data['borrower_type'] === 'teacher' ? 'teacher' : 'student';

        (new LibraryReservation())->insert([
            'book_id' => (int) $data['book_id'],
            'borrower_type' => $borrowerType,
            'student_id' => $borrowerType === 'student' ? (int) $data['borrower_id'] : null,
            'teacher_id' => $borrowerType === 'teacher' ? (int) $data['borrower_id'] : null,
            'reserved_date' => date('Y-m-d'),
            'status' => 'pending',
        ]);

        $this->flashSuccess('Reservation placed.');
        $this->redirect(url('library/reservations'));
    }

    public function cancelReservation(int $id): void
    {
        (new LibraryReservation())->update($id, ['status' => 'cancelled']);
        $this->flashSuccess('Reservation cancelled.');
        $this->back();
    }
}