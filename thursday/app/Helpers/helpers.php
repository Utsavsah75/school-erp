<?php

use App\Core\Session;
use App\Core\Database;
use App\Core\Lang;

if (!function_exists('t')) {
    /**
     * Translate a string for the current UI language (English / Nepali).
     * Falls back to English, then to the raw key, so a missing
     * translation never breaks a page — it just shows in English.
     *
     * Example: t('unread_messages', ['count' => 3]) => "3 unread messages"
     */
    function t(string $key, array $replace = []): string
    {
        return Lang::get($key, $replace);
    }
}

if (!function_exists('tr_const')) {
    /**
     * Translate a value normally looked up in one of config/constants.php's
     * label maps (STUDENT_STATUSES, PAYMENT_MODES, CLASS_SECTION_SHIFTS...).
     * Tries the Nepali/English dictionary first (as 'status_<value>'); if
     * no such entry exists, falls back to the constant's own English label
     * map (unchanged behavior), then to st().
     */
    function tr_const(array $map, ?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        $key = 'status_' . strtolower(str_replace(' ', '_', $value));
        $translated = Lang::get($key);
        return $translated !== $key ? $translated : ($map[$value] ?? st($value));
    }
}

if (!function_exists('st')) {
    /**
     * Translate a dynamic status/enum value coming from the database
     * (e.g. $row['status'] === 'active', 'present', 'paid'...).
     * Looks it up as 'status_<value>' in the language files; if no
     * translation exists for that word, falls back to the previous
     * behavior (ucfirst) so nothing regresses for values we haven't
     * catalogued (shifts, categories, payment modes, etc.).
     */
    function st(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        $key = 'status_' . strtolower(str_replace(' ', '_', $value));
        $translated = Lang::get($key);
        return $translated !== $key ? $translated : ucfirst($value);
    }
}

/**
 * Returns the app's base path (the URL path segment before routes start),
 * e.g. "/school-erp/public" when running at http://localhost/school-erp/public.
 * Derived from SCRIPT_NAME so it works regardless of how deep the project
 * folder is nested under the web root.
 */
if (!function_exists('resolve_upload_path')) {
    /**
     * Resolves a DB-stored relative upload path (e.g. "uploads/documents/xyz.pdf")
     * to an absolute filesystem path, or null if the path is empty or tries to
     * escape the uploads directory (e.g. via "../"). Single source of truth for
     * this so DocumentController and any view checking "does this file really
     * exist" (before rendering a download link) can never disagree.
     */
    function resolve_upload_path(?string $relativePath): ?string
    {
        if (!$relativePath) {
            return null;
        }
        $relativePath = ltrim($relativePath, '/');
        if ($relativePath === '' || str_contains($relativePath, '..')) {
            return null;
        }
        // NOTE: base_path() (below) builds URL paths from $_SERVER['SCRIPT_NAME'],
        // not filesystem paths — using it here would silently resolve to the
        // wrong location. dirname(__DIR__, 2) from app/Helpers/helpers.php is
        // the actual project root on disk, matching how every other upload
        // path in this codebase is resolved (see handle_upload() below).
        return dirname(__DIR__, 2) . '/public/' . $relativePath;
    }
}

if (!function_exists('uploaded_file_exists')) {
    /** True if a DB-stored upload path resolves to a real, readable file on disk. */
    function uploaded_file_exists(?string $relativePath): bool
    {
        $full = resolve_upload_path($relativePath);
        if (!$full) {
            return false;
        }
        $uploadsRoot = dirname(__DIR__, 2) . '/public/uploads';
        if (strpos(realpath(dirname($full)) ?: '', realpath($uploadsRoot) ?: "\0") !== 0) {
            return false;
        }
        return is_file($full);
    }
}

if (!function_exists('base_path')) {
    function base_path(): string
    {
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $base = str_replace('\\', '/', dirname($script));
        return $base === '/' ? '' : rtrim($base, '/');
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $path = '/' . ltrim($path, '/');
        return base_path() . ($path === '/' ? '/' : $path);
    }
}

if (!function_exists('absolute_url')) {
    /**
     * Fully-qualified URL (scheme + host + path), for links that leave the
     * current request — email bodies, SMS, anywhere there's no browser
     * "current page" to resolve a relative link against.
     *
     * url() deliberately returns only a host-relative path (derived from
     * SCRIPT_NAME) so in-page links/forms/redirects keep working no matter
     * what domain the app is accessed under. That's exactly wrong for an
     * emailed link: a mail client has no "current host" to resolve
     * "/school-erp/public/reset-password/..." against, so it renders as a
     * broken/invalid URL (this is what caused the Google redirect-notice
     * page — the href had no scheme or host at all). absolute_url() instead
     * builds on APP_URL from .env, which is a full origin
     * (http://localhost/school-erp/public), so links always work when
     * clicked outside the app itself.
     */
    function absolute_url(string $path = ''): string
    {
        $path = '/' . ltrim($path, '/');
        $base = rtrim((string) config('app.url'), '/');
        return $base . ($path === '/' ? '' : $path);
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('upload_url')) {
    function upload_url(?string $path): string
    {
        if (empty($path)) {
            return asset('images/placeholder.png');
        }
        return url($path);
    }
}

if (!function_exists('config')) {
    /** Dot-notation reader for config/app.php, e.g. config('mail.host'), config('otp.length', 6). */
    function config(string $key, mixed $default = null): mixed
    {
        static $app = null;
        $app ??= require dirname(__DIR__, 2) . '/config/app.php';

        $value = $app;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }
}

if (!function_exists('e')) {
    /** HTML-escape for safe output. */
    function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        return Session::old($key, $default);
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (!Session::has('_csrf_token')) {
            Session::set('_csrf_token', bin2hex(random_bytes(32)));
        }
        return Session::get('_csrf_token');
    }
}

if (!function_exists('csrf_verify')) {
    function csrf_verify(string $token): bool
    {
        $sessionToken = Session::get('_csrf_token', '');
        return $sessionToken !== '' && hash_equals($sessionToken, $token);
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('method_field')) {
    function method_field(string $method): string
    {
        return '<input type="hidden" name="_method" value="' . e(strtoupper($method)) . '">';
    }
}

if (!function_exists('format_date')) {
    function format_date(?string $date, string $format = 'd M Y'): string
    {
        if (empty($date) || $date === '0000-00-00') {
            return '—';
        }
        $ts = strtotime($date);
        return $ts ? date($format, $ts) : '—';
    }
}

if (!function_exists('format_datetime')) {
    function format_datetime(?string $datetime, string $format = 'd M Y, h:i A'): string
    {
        if (empty($datetime) || str_starts_with($datetime, '0000-00-00')) {
            return '—';
        }
        $ts = strtotime($datetime);
        return $ts ? date($format, $ts) : '—';
    }
}

if (!function_exists('time_ago')) {
    /** Relative timestamp, e.g. "5 minutes ago", "2 hours ago", "3 days ago". */
    function time_ago(?string $datetime): string
    {
        if (empty($datetime) || str_starts_with($datetime, '0000-00-00')) {
            return '—';
        }
        $ts = strtotime($datetime);
        if (!$ts) {
            return '—';
        }
        $diff = time() - $ts;
        if ($diff < 0) {
            $diff = 0;
        }
        if ($diff < 60) {
            return 'just now';
        }
        $units = [
            31536000 => 'year',
            2592000  => 'month',
            86400    => 'day',
            3600     => 'hour',
            60       => 'minute',
        ];
        foreach ($units as $seconds => $label) {
            $count = intdiv($diff, $seconds);
            if ($count >= 1) {
                return $count . ' ' . $label . ($count > 1 ? 's' : '') . ' ago';
            }
        }
        return 'just now';
    }
}

if (!function_exists('format_currency')) {
    function format_currency(float|string|null $amount, string $symbol = 'Rs. '): string
    {
        return $symbol . number_format((float) ($amount ?? 0), 2);
    }
}

if (!function_exists('role_label')) {
    function role_label(?string $role): string
    {
        if (!$role) {
            return '';
        }
        $key = 'role_' . strtolower($role);
        $translated = Lang::get($key);
        return $translated !== $key ? $translated : ucwords(str_replace('_', ' ', $role));
    }
}

if (!function_exists('status_badge_class')) {
    function status_badge_class(string $status): string
    {
        return match (strtolower($status)) {
            'active', 'present', 'paid', 'approved', 'returned', 'graded', 'submitted', 'available' => 'bg-success',
            'pending', 'partial', 'late', 'on_leave' => 'bg-warning text-dark',
            'inactive', 'absent', 'unpaid', 'rejected', 'lost', 'resigned', 'leave', 'overdue' => 'bg-danger',
            'promoted', 'transferred', 'graduated', 'issued', 'in_app' => 'bg-info text-dark',
            default => 'bg-secondary',
        };
    }
}

if (!function_exists('flash_alerts')) {
    /**
     * Renders any queued flash alerts as a small JSON payload that
     * sweetalert-helpers.js picks up on page load and turns into
     * SweetAlert2 toasts (success/info) or modals (error/warning).
     * Call once inside the layout (or a standalone auth page).
     */
    function flash_alerts(): string
    {
        $messages = [];
        foreach (['success', 'error', 'warning', 'info'] as $type) {
            if (Session::hasFlash($type)) {
                $msg = Session::flash($type);
                if ($msg !== null && $msg !== '') {
                    $messages[] = ['type' => $type, 'message' => (string) $msg];
                }
            }
        }
        if (empty($messages)) {
            return '';
        }
        return '<script type="application/json" id="sa-flash-data">'
            . json_encode($messages, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
            . '</script>';
    }
}

if (!function_exists('field_error')) {
    /** Returns the first validation error message for a field, or '' if none. */
    function field_error(array $errors, string $field): string
    {
        return $errors[$field][0] ?? '';
    }
}

if (!function_exists('form_errors')) {
    /**
     * Session::getErrors() clears the session copy on first read, so it can
     * only safely be called once per request. This wraps it in a static
     * cache so any view can call form_errors() as many times as it wants
     * (e.g. once per field via field_error(form_errors(), 'x')) without a
     * controller having to remember to fetch-and-pass 'errors' explicitly.
     */
    function form_errors(): array
    {
        static $cached = null;
        if ($cached === null) {
            $cached = Session::getErrors();
        }
        return $cached;
    }
}

if (!function_exists('active_route')) {
    /** Returns 'active' if the current request path starts with $path — for sidebar highlighting. */
    function active_route(string $path): string
    {
        $current = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $target = url($path);
        return str_starts_with(rtrim($current, '/'), rtrim($target, '/')) && $target !== url('/') ? 'active' : '';
    }
}

if (!function_exists('paginate_links')) {
    /**
     * Renders Bootstrap pagination links for a paginate() result array.
     * @param array{page:int,last_page:int} $pagination
     */
    function paginate_links(array $pagination, string $baseUrl, array $queryParams = []): string
    {
        $page = $pagination['page'];
        $lastPage = $pagination['last_page'];
        if ($lastPage <= 1) {
            return '';
        }

        $buildUrl = function (int $p) use ($baseUrl, $queryParams) {
            $params = array_merge($queryParams, ['page' => $p]);
            return $baseUrl . '?' . http_build_query($params);
        };

        $html = '<nav aria-label="Pagination"><ul class="pagination">';
        $html .= '<li class="page-item ' . ($page <= 1 ? 'disabled' : '') . '"><a class="page-link" href="' . e($buildUrl(max(1, $page - 1))) . '">&laquo;</a></li>';

        $start = max(1, $page - 2);
        $end = min($lastPage, $page + 2);
        for ($i = $start; $i <= $end; $i++) {
            $html .= '<li class="page-item ' . ($i === $page ? 'active' : '') . '"><a class="page-link" href="' . e($buildUrl($i)) . '">' . $i . '</a></li>';
        }

        $html .= '<li class="page-item ' . ($page >= $lastPage ? 'disabled' : '') . '"><a class="page-link" href="' . e($buildUrl(min($lastPage, $page + 1))) . '">&raquo;</a></li>';
        $html .= '</ul></nav>';

        return $html;
    }
}

if (!function_exists('generate_code')) {
    /** Generates a prefixed sequential-looking unique code, e.g. admission numbers, receipt numbers. */
    function generate_code(string $prefix): string
    {
        return strtoupper($prefix) . '-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
    }
}

if (!function_exists('handle_upload')) {
    /**
     * Validates and moves an uploaded file into public/uploads/{folder}/.
     * Returns the relative path (e.g. "uploads/students/xxxx.jpg") to store
     * in the DB, or null if no file was uploaded. Throws on invalid files.
     *
     * @param array $file One entry from $_FILES, e.g. $_FILES['photo']
     */
    function handle_upload(array $file, string $folderKey, bool $imageOnly = true): ?string
    {
        if (empty($file['name']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('File upload failed (error code ' . $file['error'] . ').');
        }

        $config = require dirname(__DIR__, 2) . '/config/app.php';
        $upload = $config['upload'];

        if ($file['size'] > $upload['max_size']) {
            $maxMb = round($upload['max_size'] / 1048576, 1);
            throw new \RuntimeException("File is too large. Maximum size is {$maxMb}MB.");
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = $imageOnly ? $upload['allowed_image_ext'] : $upload['allowed_document_ext'];
        if (!in_array($ext, $allowed, true)) {
            throw new \RuntimeException('File type not allowed. Allowed: ' . implode(', ', $allowed));
        }

        // Verify the actual MIME type too — don't trust the extension alone.
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        $allowedMimes = $imageOnly
            ? ['image/jpeg', 'image/png', 'image/webp']
            : ['image/jpeg', 'image/png', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        if (!in_array($mime, $allowedMimes, true)) {
            throw new \RuntimeException('File content does not match an allowed file type.');
        }

        $folder = $upload['paths'][$folderKey] ?? ('uploads/' . $folderKey . '/');
        $destDir = dirname(__DIR__, 2) . '/public/' . $folder;
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $destPath = $destDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            throw new \RuntimeException('Could not save the uploaded file.');
        }

        return $folder . $filename;
    }
}

if (!function_exists('delete_upload')) {
    function delete_upload(?string $relativePath): void
    {
        if (empty($relativePath)) {
            return;
        }
        $fullPath = dirname(__DIR__, 2) . '/public/' . ltrim($relativePath, '/');
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }
}

if (!function_exists('peek_next_admission_number')) {
    /**
     * Preview the next Admission Number WITHOUT reserving it — safe to call
     * on GET (e.g. to display it read-only on the Add Student page) since it
     * doesn't touch the counter row.
     */
    function peek_next_admission_number(): string
    {
        $db = Database::getInstance();
        $year = date('Y');
        $row = $db->query('SELECT last_number, year FROM admission_number_sequence WHERE id = 1')->fetch();
        $lastNumber = ($row && $row['year'] == $year) ? (int) $row['last_number'] : 0;
        return sprintf('STU-%s-%06d', $year, $lastNumber + 1);
    }
}

if (!function_exists('reserve_admission_number')) {
    /**
     * Atomically reserve and return the next Admission Number
     * (spec section 1: must always be unique, safe under concurrent
     * admissions). Locks the single counter row with SELECT ... FOR UPDATE
     * inside its own short transaction, so two simultaneous "Save Student"
     * clicks can never be handed the same number — the second request
     * simply waits for the first to commit.
     *
     * Runs its own transaction rather than assuming the caller has one
     * open, so it's safe to call standalone. If you need it inside a
     * larger transaction, call reserve_admission_number_locked() instead
     * and manage the transaction yourself.
     */
    function reserve_admission_number(): string
    {
        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            $number = reserve_admission_number_locked();
            $db->commit();
            return $number;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }
}

if (!function_exists('reserve_admission_number_locked')) {
    /** Same as reserve_admission_number() but assumes a transaction is already open (caller manages commit/rollback). */
    function reserve_admission_number_locked(): string
    {
        $db = Database::getInstance();
        if (!$db->inTransaction()) {
            throw new \LogicException('reserve_admission_number_locked() must be called inside an open transaction.');
        }
        $year = date('Y');
        $row = $db->query('SELECT last_number, year FROM admission_number_sequence WHERE id = 1 FOR UPDATE')->fetch();
        $lastNumber = ($row && $row['year'] == $year) ? (int) $row['last_number'] : 0;
        $next = $lastNumber + 1;
        $db->query('UPDATE admission_number_sequence SET last_number = :n, year = :y WHERE id = 1', ['n' => $next, 'y' => $year]);
        return sprintf('STU-%s-%06d', $year, $next);
    }
}

if (!function_exists('next_roll_number')) {
    /**
     * Spec section 2: next Roll Number, scoped to Class + Section +
     * Academic Year (so "Roll #1" can independently exist in every
     * section). Manual override is allowed by the form as long as it's
     * unique within that same scope — see roll_number_is_unique().
     */
    function next_roll_number(int $classId, int $sectionId, int $academicYearId): int
    {
        $db = Database::getInstance();
        $row = $db->query(
            'SELECT MAX(CAST(roll_number AS UNSIGNED)) AS max_roll FROM students
             WHERE class_id = :c AND section_id = :s AND academic_year_id = :y',
            ['c' => $classId, 's' => $sectionId, 'y' => $academicYearId]
        )->fetch();
        return (int) ($row['max_roll'] ?? 0) + 1;
    }
}

if (!function_exists('roll_number_is_unique')) {
    function roll_number_is_unique(string $rollNumber, int $classId, int $sectionId, int $academicYearId, ?int $excludeStudentId = null): bool
    {
        $db = Database::getInstance();
        $sql = 'SELECT COUNT(*) AS c FROM students
                WHERE roll_number = :r AND class_id = :c AND section_id = :s AND academic_year_id = :y';
        $params = ['r' => $rollNumber, 'c' => $classId, 's' => $sectionId, 'y' => $academicYearId];
        if ($excludeStudentId) {
            $sql .= ' AND id != :ex';
            $params['ex'] = $excludeStudentId;
        }
        return (int) ($db->query($sql, $params)->fetch()['c'] ?? 0) === 0;
    }
}

if (!function_exists('student_age_years')) {
    /** Whole years of age as of today, from a Y-m-d date of birth string. */
    function student_age_years(string $dob): ?int
    {
        $ts = strtotime($dob);
        if ($ts === false) {
            return null;
        }
        return (new \DateTime())->diff((new \DateTime())->setTimestamp($ts))->y;
    }
}

if (!function_exists('student_age_display')) {
    /** "Age: 12 Years 3 Months" display string (spec section 5). */
    function student_age_display(string $dob): string
    {
        $ts = strtotime($dob);
        if ($ts === false) {
            return '';
        }
        $diff = (new \DateTime())->diff((new \DateTime())->setTimestamp($ts));
        return "Age: {$diff->y} Years {$diff->m} Months";
    }
}

if (!function_exists('find_student_duplicates')) {
    /**
     * Spec section 16: pre-save duplicate checks beyond what the plain
     * Validator's `unique` rule can express (composite phone+country-code,
     * and name+DOB combos). Returns a field => message error map, empty if
     * none found.
     */
    function find_student_duplicates(array $data, ?int $excludeStudentId = null): array
    {
        $db = Database::getInstance();
        $errors = [];
        $exclude = $excludeStudentId ? 'AND id != ' . (int) $excludeStudentId : '';

        $phone = trim((string) ($data['phone'] ?? ''));
        if ($phone !== '') {
            $row = $db->query(
                "SELECT COUNT(*) AS c FROM students WHERE phone = :phone AND phone_country_code = :cc {$exclude}",
                ['phone' => $phone, 'cc' => $data['phone_country_code'] ?? '']
            )->fetch();
            if ((int) $row['c'] > 0) {
                $errors['phone'][] = 'This phone number is already registered to another student.';
            }
        }

        $email = trim((string) ($data['email'] ?? ''));
        if ($email !== '') {
            $row = $db->query("SELECT COUNT(*) AS c FROM students WHERE email = :email {$exclude}", ['email' => $email])->fetch();
            if ((int) $row['c'] > 0) {
                $errors['email'][] = 'This email is already registered to another student.';
            }
        }

        $fullName = trim((string) ($data['full_name'] ?? ''));
        $dob = $data['dob'] ?? '';
        if ($fullName !== '' && $dob !== '') {
            $row = $db->query(
                "SELECT COUNT(*) AS c FROM students WHERE full_name = :name AND dob = :dob {$exclude}",
                ['name' => $fullName, 'dob' => $dob]
            )->fetch();
            if ((int) $row['c'] > 0) {
                $errors['full_name'][] = 'A student with this exact name and date of birth already exists — please confirm this is not a duplicate entry.';
            }
        }

        return $errors;
    }
}

if (!function_exists('teacher_full_name')) {
    /** Builds the auto-generated Full Name from First/Middle/Last (spec section 1). */
    function teacher_full_name(string $first, ?string $middle, string $last): string
    {
        $parts = array_filter([trim($first), trim((string) $middle), trim($last)], fn($p) => $p !== '');
        return implode(' ', $parts);
    }
}

if (!function_exists('find_teacher_duplicates')) {
    /**
     * Duplicate checks beyond the plain Validator `unique` rule (spec's
     * "duplicate checks: Employee Code, Email, Phone"). Returns a field =>
     * message error map, empty if none found.
     */
    function find_teacher_duplicates(array $data, ?int $excludeTeacherId = null): array
    {
        $db = Database::getInstance();
        $errors = [];
        $exclude = $excludeTeacherId ? 'AND id != ' . (int) $excludeTeacherId : '';

        $employeeCode = trim((string) ($data['employee_number'] ?? ''));
        if ($employeeCode !== '') {
            $row = $db->query("SELECT COUNT(*) AS c FROM teachers WHERE employee_number = :v {$exclude}", ['v' => $employeeCode])->fetch();
            if ((int) $row['c'] > 0) {
                $errors['employee_number'][] = 'This Employee Code is already in use by another teacher.';
            }
        }

        $email = trim((string) ($data['email'] ?? ''));
        if ($email !== '') {
            $row = $db->query("SELECT COUNT(*) AS c FROM teachers WHERE email = :v {$exclude}", ['v' => $email])->fetch();
            if ((int) $row['c'] > 0) {
                $errors['email'][] = 'This email is already registered to another teacher.';
            }
        }

        $phone = trim((string) ($data['phone'] ?? ''));
        if ($phone !== '') {
            $row = $db->query("SELECT COUNT(*) AS c FROM teachers WHERE phone = :v {$exclude}", ['v' => $phone])->fetch();
            if ((int) $row['c'] > 0) {
                $errors['phone'][] = 'This phone number is already registered to another teacher.';
            }
        }

        return $errors;
    }
}

if (!function_exists('next_employee_code')) {
    /** Preview-only suggestion for the next Employee Code (spec section 1). Not reserved — just a starting point the admin can edit. */
    function next_employee_code(): string
    {
        $db = Database::getInstance();
        $row = $db->query("SELECT employee_number FROM teachers ORDER BY id DESC LIMIT 1")->fetch();
        $lastNumber = 0;
        if ($row && preg_match('/(\d+)$/', $row['employee_number'], $m)) {
            $lastNumber = (int) $m[1];
        }
        return sprintf('EMP%04d', $lastNumber + 1);
    }
}

if (!function_exists('next_subject_code')) {
    /** Preview-only suggestion for the next Subject Code (SUB0001, SUB0002, ...). Not reserved. */
    function next_subject_code(): string
    {
        $db = Database::getInstance();
        $row = $db->query("SELECT code FROM subjects ORDER BY id DESC LIMIT 1")->fetch();
        $lastNumber = 0;
        if ($row && preg_match('/(\d+)$/', $row['code'], $m)) {
            $lastNumber = (int) $m[1];
        }
        return sprintf('SUB%04d', $lastNumber + 1);
    }
}

if (!function_exists('next_class_code')) {
    /** Preview-only suggestion for the next Class Code (CLS0001, CLS0002, ...). Not reserved. */
    function next_class_code(): string
    {
        $db = Database::getInstance();
        $row = $db->query("SELECT code FROM classes WHERE code IS NOT NULL ORDER BY id DESC LIMIT 1")->fetch();
        $lastNumber = 0;
        if ($row && preg_match('/(\d+)$/', (string) $row['code'], $m)) {
            $lastNumber = (int) $m[1];
        }
        return sprintf('CLS%04d', $lastNumber + 1);
    }
}

if (!function_exists('next_section_code')) {
    /** Preview-only suggestion for the next Section Code (SEC0001, SEC0002, ...). Not reserved. */
    function next_section_code(): string
    {
        $db = Database::getInstance();
        $row = $db->query("SELECT code FROM sections WHERE code IS NOT NULL ORDER BY id DESC LIMIT 1")->fetch();
        $lastNumber = 0;
        if ($row && preg_match('/(\d+)$/', (string) $row['code'], $m)) {
            $lastNumber = (int) $m[1];
        }
        return sprintf('SEC%04d', $lastNumber + 1);
    }
}

if (!function_exists('resolve_module_permissions')) {
    /**
     * Merges a teacher's role-based default module access with their
     * per-teacher overrides (spec section 6). Overrides win either way —
     * true grants access even if the role wouldn't normally have it, false
     * revokes access even if the role would.
     * @return array<string,bool>
     */
    function resolve_module_permissions(string $role, array $overrides): array
    {
        $result = [];
        foreach (TEACHER_OVERRIDABLE_MODULES as $key => $label) {
            $default = in_array($role, MODULE_PERMISSIONS[$key] ?? [], true);
            $result[$key] = array_key_exists($key, $overrides) ? (bool) $overrides[$key] : $default;
        }
        return $result;
    }
}

if (!function_exists('log_activity')) {
    /**
     * Writes one row to `activity_logs` for the currently logged-in user.
     * Never throws — a logging failure must not break the request that
     * triggered it (mirrors the defensive try/catch already used around
     * the `messages` table in app/Views/layouts/app.php).
     */
    function log_activity(string $action, string $description = ''): void
    {
        try {
            Database::getInstance()->query(
                'INSERT INTO `activity_logs` (`user_id`, `action`, `description`, `ip_address`, `created_at`) VALUES (:uid, :action, :description, :ip, NOW())',
                [
                    'uid'         => \App\Core\Auth::id(),
                    'action'      => $action,
                    'description' => $description,
                    'ip'          => $_SERVER['REMOTE_ADDR'] ?? null,
                ]
            );
        } catch (\Throwable $e) {
            error_log('[ACTIVITY LOG ERROR] ' . $e->getMessage());
        }
    }
}

if (!function_exists('csv_download')) {
    /**
     * Streams a simple CSV to the browser and exits. Used as the
     * dependency-free fallback for "Export Excel" — Excel/Sheets/LibreOffice
     * all open CSV natively. If phpoffice/phpspreadsheet is installed
     * (composer.json already requires it) controllers may use it directly
     * for a true .xlsx instead; this helper keeps the feature working even
     * before `composer install` has been run.
     *
     * @param string[] $headers
     * @param array<int,array> $rows
     */
    function csv_download(string $filename, array $headers, array $rows): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel renders non-ASCII correctly
        fputcsv($out, $headers);
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }
}

if (!function_exists('dd')) {
    /** Debug helper: dump and die (only meaningful in APP_DEBUG). */
    function dd(mixed ...$vars): void
    {
        echo '<pre style="background:#1e1e1e;color:#0f0;padding:15px;">';
        foreach ($vars as $var) {
            var_dump($var);
        }
        echo '</pre>';
        exit;
    }
}

if (!function_exists('teacher_full_name')) {
    /** Spec section 1: Full Name is auto-generated from First/Middle/Last. */
    function teacher_full_name(string $first, ?string $middle, string $last): string
    {
        $parts = array_filter([trim($first), trim((string) $middle), trim($last)], fn($p) => $p !== '');
        return implode(' ', $parts);
    }
}

if (!function_exists('find_teacher_duplicates')) {
    /**
     * Spec UI requirement: duplicate checks for Employee Code, Email, Phone
     * before saving a Teacher record.
     */
    function find_teacher_duplicates(array $data, ?int $excludeTeacherId = null): array
    {
        $db = Database::getInstance();
        $errors = [];
        $exclude = $excludeTeacherId ? 'AND id != ' . (int) $excludeTeacherId : '';

        $code = trim((string) ($data['employee_number'] ?? ''));
        if ($code !== '') {
            $row = $db->query("SELECT COUNT(*) AS c FROM teachers WHERE employee_number = :v {$exclude}", ['v' => $code])->fetch();
            if ((int) $row['c'] > 0) {
                $errors['employee_number'][] = 'This Employee Code is already used by another teacher.';
            }
        }

        $email = trim((string) ($data['email'] ?? ''));
        if ($email !== '') {
            $row = $db->query("SELECT COUNT(*) AS c FROM teachers WHERE email = :v {$exclude}", ['v' => $email])->fetch();
            if ((int) $row['c'] > 0) {
                $errors['email'][] = 'This email is already registered to another teacher.';
            }
        }

        $phone = trim((string) ($data['phone'] ?? ''));
        if ($phone !== '') {
            $row = $db->query("SELECT COUNT(*) AS c FROM teachers WHERE phone = :v {$exclude}", ['v' => $phone])->fetch();
            if ((int) $row['c'] > 0) {
                $errors['phone'][] = 'This phone number is already registered to another teacher.';
            }
        }

        return $errors;
    }
}

if (!function_exists('amount_in_words')) {
    /** Converts a rupee amount to words for printed receipts, e.g. 4910.50 -> "Four Thousand Nine Hundred Ten Rupees and Fifty Paisa". */
    function amount_in_words(float $amount): string
    {
        $rupees = (int) floor($amount);
        $paisa = (int) round(($amount - $rupees) * 100);

        $words = trim(number_to_words_int($rupees)) . ' Rupees';
        if ($paisa > 0) {
            $words .= ' and ' . trim(number_to_words_int($paisa)) . ' Paisa';
        }
        return $words . ' Only';
    }
}

if (!function_exists('number_to_words_int')) {
    /** Small-number-to-English-words converter (0–999,999,999) used by amount_in_words(). */
    function number_to_words_int(int $number): string
    {
        if ($number === 0) {
            return 'Zero';
        }

        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
                 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
                 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        $toWords = function (int $n) use (&$toWords, $ones, $tens): string {
            if ($n < 20) {
                return $ones[$n];
            }
            if ($n < 100) {
                return trim($tens[intdiv($n, 10)] . ' ' . $ones[$n % 10]);
            }
            return trim($ones[intdiv($n, 100)] . ' Hundred ' . $toWords($n % 100));
        };

        $parts = [];
        $crore = intdiv($number, 10000000);
        $number %= 10000000;
        $lakh = intdiv($number, 100000);
        $number %= 100000;
        $thousand = intdiv($number, 1000);
        $number %= 1000;
        $hundred = $number;

        if ($crore) {
            $parts[] = $toWords($crore) . ' Crore';
        }
        if ($lakh) {
            $parts[] = $toWords($lakh) . ' Lakh';
        }
        if ($thousand) {
            $parts[] = $toWords($thousand) . ' Thousand';
        }
        if ($hundred) {
            $parts[] = $toWords($hundred);
        }

        return implode(' ', $parts);
    }
}
