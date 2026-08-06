<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ActivityLog;
use App\Models\User;

/**
 * Activity Log (Audit Trail) page — MODULE_PERMISSIONS['activity_logs'],
 * super_admin-only by default (config/constants.php). Read-only: nothing
 * here ever edits or deletes a log row (see spec section 26 — logs are
 * immutable once written by log_activity()).
 */
class ActivityLogController extends Controller
{
    public function __construct()
    {
        $this->authorizeModule('activity_logs');
    }

    public function index(): void
    {
        $filters = $this->currentFilters();
        $model = new ActivityLog();

        $result = $model->paginatedReport($this->currentPage(), (int) $this->input('per_page', 20), $filters);

        $this->view('activity-logs/index', [
            'pageTitle'     => 'Activity Log',
            'result'        => $result,
            'filters'       => $filters,
            'activeFilters' => array_filter($filters, static fn ($v) => $v !== '' && $v !== 0),
            'stats'         => $model->todayStats(),
            'users'         => $model->usersWithActivity(),
            'modules'       => $model->distinctModules(),
            'roles'         => ALL_ROLES,
            'categories'    => ['Create', 'Update', 'Delete', 'Login', 'Logout', 'Failed Login', 'Security', 'Payment', 'Receipt', 'Export', 'Settings', 'Other'],
        ]);
    }

    /** Full detail for one entry, rendered as a modal body fragment (AJAX). */
    public function show(int $id): void
    {
        $row = (new ActivityLog())->findWithUser($id);
        if (!$row) {
            $this->json(['ok' => false, 'message' => 'Log entry not found.'], 404);
            return;
        }
        $this->viewRaw('activity-logs/_detail', ['row' => $row]);
    }

    public function exportCsv(): void
    {
        $rows = (new ActivityLog())->allFiltered($this->currentFilters());

        $headers = ['Log ID', 'Date & Time', 'User', 'Role', 'Module', 'Action', 'Description', 'IP Address'];
        $csvRows = array_map(static fn ($r) => [
            $r['id'],
            format_datetime($r['created_at']),
            $r['user_name'] ?? 'System',
            role_label($r['user_role'] ?? null),
            $r['module'] ?? '',
            ActivityLog::actionLabel($r['action']),
            $r['description'],
            $r['ip_address'] ?? '—',
        ], $rows);

        log_activity('exported_record', 'Exported Activity Log to CSV.');
        csv_download('activity-log-' . date('Ymd-His') . '.csv', $headers, $csvRows);
    }

    public function exportPdf(): void
    {
        $rows = (new ActivityLog())->allFiltered($this->currentFilters(), 1000);

        ob_start();
        $this->viewRaw('activity-logs/print', ['rows' => $rows, 'printedAt' => date('d M Y, h:i A')]);
        $html = ob_get_clean();

        if (class_exists(\Dompdf\Dompdf::class)) {
            $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => false]);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            log_activity('exported_record', 'Exported Activity Log to PDF.');
            $dompdf->stream('activity-log-' . date('Ymd-His') . '.pdf', ['Attachment' => true]);
            exit;
        }

        // Dependency not installed yet (composer install pending) — fall back to the printable HTML.
        echo $html;
    }

    public function print(): void
    {
        $rows = (new ActivityLog())->allFiltered($this->currentFilters(), 1000);
        $this->viewRaw('activity-logs/print', ['rows' => $rows, 'printedAt' => date('d M Y, h:i A')]);
    }

    // ------------------------------------------------------------------

    private function currentFilters(): array
    {
        $dateFrom = trim((string) $this->input('date_from', ''));
        $dateTo = trim((string) $this->input('date_to', ''));

        // Named quick-filters (spec section 21) resolve to an explicit
        // date_from/date_to range so the rest of the pipeline only ever
        // deals with one representation of "when".
        $range = trim((string) $this->input('range', ''));
        if ($range !== '' && $range !== 'custom') {
            [$dateFrom, $dateTo] = $this->resolveRange($range);
        }

        return [
            'q'         => trim((string) $this->input('q', '')),
            'user_id'   => (int) $this->input('user_id', 0),
            'role'      => trim((string) $this->input('role', '')),
            'module'    => trim((string) $this->input('module', '')),
            'category'  => trim((string) $this->input('category', '')),
            'status'    => trim((string) $this->input('status', '')),
            'ip'        => trim((string) $this->input('ip', '')),
            'range'     => $range,
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
        ];
    }

    /** @return array{0:string,1:string} [date_from, date_to] as Y-m-d */
    private function resolveRange(string $range): array
    {
        $today = date('Y-m-d');
        return match ($range) {
            'today'      => [$today, $today],
            'yesterday'  => [date('Y-m-d', strtotime('-1 day')), date('Y-m-d', strtotime('-1 day'))],
            'last_7'     => [date('Y-m-d', strtotime('-6 days')), $today],
            'last_30'    => [date('Y-m-d', strtotime('-29 days')), $today],
            'this_month' => [date('Y-m-01'), $today],
            default      => ['', ''],
        };
    }
}
