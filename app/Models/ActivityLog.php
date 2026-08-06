<?php

namespace App\Models;

use App\Core\Model;

/**
 * Activity Log (Audit Trail) — reads/writes the `activity_logs` table that
 * `log_activity()` (app/Helpers/helpers.php) has already been writing to
 * from ~50 call sites across the app. This model adds the read side:
 * a searchable/filterable/paginated listing plus the dashboard stat cards,
 * for the Activity Log page (see ActivityLogController).
 *
 * Table columns (existing, unchanged): id, user_id, action, description,
 * ip_address, created_at.
 *
 * `action` is a snake_case slug (e.g. 'created_record', 'payment_recorded',
 * 'login_failed'). There's no dedicated category/module column, so category
 * grouping is derived from the slug via CASE expressions below — this keeps
 * every existing log_activity() call site working unchanged.
 */
class ActivityLog extends Model
{
    protected string $table = 'activity_logs';

    protected array $fillable = ['user_id', 'action', 'description', 'ip_address'];

    /**
     * Action-slug substrings mapped to a display category + Bootstrap badge
     * class. Checked in order, first match wins. Falls back to 'Other'.
     * Mirrors the spec's colored-badge categories (Create/Update/Delete/
     * Login/Logout/Failed Login/Payment/Receipt/Report/Settings).
     */
    private const CATEGORY_RULES = [
        ['needle' => 'login_failed',      'label' => 'Failed Login', 'badge' => 'bg-danger'],
        ['needle' => 'login',             'label' => 'Login',        'badge' => 'bg-success'],
        ['needle' => 'logout',            'label' => 'Logout',       'badge' => 'bg-secondary'],
        ['needle' => 'locked',            'label' => 'Security',     'badge' => 'bg-danger'],
        ['needle' => 'unlocked',          'label' => 'Security',     'badge' => 'bg-info text-dark'],
        ['needle' => 'password',          'label' => 'Security',     'badge' => 'bg-dark'],
        ['needle' => 'two_factor',        'label' => 'Security',     'badge' => 'bg-dark'],
        ['needle' => 'otp',               'label' => 'Security',     'badge' => 'bg-dark'],
        ['needle' => 'verif',             'label' => 'Security',     'badge' => 'bg-dark'],
        ['needle' => 'fine_paid',         'label' => 'Payment',      'badge' => 'bg-purple'],
        ['needle' => 'payment',           'label' => 'Payment',      'badge' => 'bg-purple'],
        ['needle' => 'voided',            'label' => 'Payment',      'badge' => 'bg-purple'],
        ['needle' => 'receipt',           'label' => 'Receipt',      'badge' => 'bg-info text-dark'],
        ['needle' => 'exported',          'label' => 'Export',       'badge' => 'bg-orange'],
        ['needle' => 'printed',           'label' => 'Export',       'badge' => 'bg-orange'],
        ['needle' => 'settings',          'label' => 'Settings',     'badge' => 'bg-dark'],
        ['needle' => 'backup',            'label' => 'Settings',     'badge' => 'bg-dark'],
        ['needle' => 'created',           'label' => 'Create',       'badge' => 'bg-success'],
        ['needle' => 'assigned',          'label' => 'Create',       'badge' => 'bg-success'],
        ['needle' => 'updated',           'label' => 'Update',       'badge' => 'bg-primary'],
        ['needle' => 'promoted',          'label' => 'Update',       'badge' => 'bg-primary'],
        ['needle' => 'transferred',       'label' => 'Update',       'badge' => 'bg-primary'],
        ['needle' => 'applied',           'label' => 'Update',       'badge' => 'bg-primary'],
        ['needle' => 'deleted',           'label' => 'Delete',       'badge' => 'bg-danger'],
    ];

    /** Human label + badge class for one action slug. */
    public static function categorize(string $action): array
    {
        $a = strtolower($action);
        foreach (self::CATEGORY_RULES as $rule) {
            if (str_contains($a, $rule['needle'])) {
                return ['label' => $rule['label'], 'badge' => $rule['badge']];
            }
        }
        return ['label' => 'Other', 'badge' => 'bg-secondary'];
    }

    /** Human-friendly fallback label for an action slug ("payment_recorded" -> "Payment Recorded"). */
    public static function actionLabel(string $action): string
    {
        return ucwords(str_replace('_', ' ', $action));
    }

    /**
     * Paginated, filtered listing, newest first, with the acting user's
     * name/role joined in.
     *
     * Supported filters: q (action/description/user name), user_id, role,
     * category (one of the CATEGORY_RULES labels, matched via the same
     * needles), status ('failed' => login_failed only, 'success' => not
     * login_failed), ip, date_from, date_to.
     */
    public function paginatedReport(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        [$whereSql, $params] = $this->buildFilterWhere($filters);

        $joinSql = "FROM activity_logs a
                     LEFT JOIN users u ON u.id = a.user_id
                     {$whereSql}";

        $total = (int) ($this->raw("SELECT COUNT(*) AS c {$joinSql}", $params)[0]['c'] ?? 0);

        $sql = "SELECT a.*, u.full_name AS user_name, u.role AS user_role
                {$joinSql}
                ORDER BY a.created_at DESC, a.id DESC
                LIMIT {$perPage} OFFSET {$offset}";
        $data = $this->raw($sql, $params);

        return [
            'data'      => $data,
            'total'     => $total,
            'page'      => $page,
            'per_page'  => $perPage,
            'last_page' => (int) max(1, ceil($total / $perPage)),
        ];
    }

    /** Same filters as paginatedReport(), unpaginated — for CSV/PDF export and printing. */
    public function allFiltered(array $filters = [], int $limit = 5000): array
    {
        [$whereSql, $params] = $this->buildFilterWhere($filters);
        $sql = "SELECT a.*, u.full_name AS user_name, u.role AS user_role
                FROM activity_logs a
                LEFT JOIN users u ON u.id = a.user_id
                {$whereSql}
                ORDER BY a.created_at DESC, a.id DESC
                LIMIT " . max(1, $limit);
        return $this->raw($sql, $params);
    }

    /** One log row with the acting user's name/role, or null. */
    public function findWithUser(int $id): ?array
    {
        $rows = $this->raw(
            'SELECT a.*, u.full_name AS user_name, u.role AS user_role
             FROM activity_logs a LEFT JOIN users u ON u.id = a.user_id
             WHERE a.id = :id LIMIT 1',
            ['id' => $id]
        );
        return $rows[0] ?? null;
    }

    /** Distinct, non-empty `module` values actually present, for the "Search by Module" filter dropdown. */
    public function distinctModules(): array
    {
        $rows = $this->raw(
            "SELECT DISTINCT module FROM activity_logs WHERE module IS NOT NULL AND module != '' ORDER BY module ASC"
        );
        return array_column($rows, 'module');
    }

    /**
     * Distinct users who actually have at least one log entry, for the
     * "Search by User" filter dropdown — deliberately not the full `users`
     * table, which could include thousands of student/parent accounts that
     * have never triggered a logged action.
     */
    public function usersWithActivity(): array
    {
        return $this->raw(
            "SELECT DISTINCT u.id, u.full_name, u.role
             FROM activity_logs a
             JOIN users u ON u.id = a.user_id
             ORDER BY u.full_name ASC
             LIMIT 500"
        );
    }

    /** Dashboard summary cards — today's counts, mirroring the spec's "Today" stat cards. */
    public function todayStats(): array
    {
        $row = $this->raw(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN action LIKE '%login_failed%' THEN 1 ELSE 0 END) AS failed_logins,
                SUM(CASE WHEN action LIKE '%login%' AND action NOT LIKE '%login_failed%' THEN 1 ELSE 0 END) AS logins,
                SUM(CASE WHEN action LIKE '%created%' OR action LIKE '%assigned%' THEN 1 ELSE 0 END) AS created,
                SUM(CASE WHEN action LIKE '%updated%' OR action LIKE '%applied%' THEN 1 ELSE 0 END) AS updated,
                SUM(CASE WHEN action LIKE '%deleted%' THEN 1 ELSE 0 END) AS deleted,
                SUM(CASE WHEN action LIKE '%payment%' OR action LIKE '%fine_paid%' THEN 1 ELSE 0 END) AS payments
             FROM activity_logs
             WHERE DATE(created_at) = CURDATE()"
        );
        $r = $row[0] ?? [];
        return [
            'total'         => (int) ($r['total'] ?? 0),
            'logins'        => (int) ($r['logins'] ?? 0),
            'failed_logins' => (int) ($r['failed_logins'] ?? 0),
            'created'       => (int) ($r['created'] ?? 0),
            'updated'       => (int) ($r['updated'] ?? 0),
            'deleted'       => (int) ($r['deleted'] ?? 0),
            'payments'      => (int) ($r['payments'] ?? 0),
        ];
    }

    /** Shared WHERE-builder for paginatedReport()/allFiltered(). */
    private function buildFilterWhere(array $filters): array
    {
        $where = [];
        $params = [];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(a.action LIKE :q1 OR a.description LIKE :q2 OR u.full_name LIKE :q3 OR a.ip_address LIKE :q4)';
            $params['q1'] = "%{$q}%";
            $params['q2'] = "%{$q}%";
            $params['q3'] = "%{$q}%";
            $params['q4'] = "%{$q}%";
        }

        $userId = (int) ($filters['user_id'] ?? 0);
        if ($userId > 0) {
            $where[] = 'a.user_id = :user_id';
            $params['user_id'] = $userId;
        }

        $module = trim((string) ($filters['module'] ?? ''));
        if ($module !== '') {
            $where[] = 'a.module = :module';
            $params['module'] = $module;
        }

        $role = trim((string) ($filters['role'] ?? ''));
        if ($role !== '') {
            $where[] = 'u.role = :role';
            $params['role'] = $role;
        }

        $ip = trim((string) ($filters['ip'] ?? ''));
        if ($ip !== '') {
            $where[] = 'a.ip_address LIKE :ip';
            $params['ip'] = "%{$ip}%";
        }

        $status = trim((string) ($filters['status'] ?? ''));
        if ($status === 'failed') {
            $where[] = "a.action LIKE '%login_failed%'";
        } elseif ($status === 'success') {
            $where[] = "a.action NOT LIKE '%login_failed%'";
        }

        $category = trim((string) ($filters['category'] ?? ''));
        if ($category !== '') {
            $needles = array_column(
                array_filter(self::CATEGORY_RULES, static fn ($r) => $r['label'] === $category),
                'needle'
            );
            if ($needles !== []) {
                $ors = [];
                foreach (array_values($needles) as $i => $needle) {
                    $key = "cat{$i}";
                    $ors[] = "a.action LIKE :{$key}";
                    $params[$key] = "%{$needle}%";
                }
                $where[] = '(' . implode(' OR ', $ors) . ')';
            }
        }

        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        if ($dateFrom !== '') {
            $where[] = 'a.created_at >= :date_from';
            $params['date_from'] = $dateFrom . ' 00:00:00';
        }
        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        if ($dateTo !== '') {
            $where[] = 'a.created_at <= :date_to';
            $params['date_to'] = $dateTo . ' 23:59:59';
        }

        $sql = $where !== [] ? ('WHERE ' . implode(' AND ', $where)) : '';
        return [$sql, $params];
    }
}
