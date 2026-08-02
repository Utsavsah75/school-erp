<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Models\ClassModel;
use App\Models\Notice;
use App\Models\Section;
use App\Models\Student;

/**
 * Notice Board module (MODULE_PERMISSIONS['notices'] = ALL_ROLES, same as
 * the existing sidebar link and the Dashboard "Latest Notices" widget).
 * Everyone logged in can view the board; only staff-type roles may
 * create/edit/delete — enforced by requireManager() below rather than a
 * new MODULE_PERMISSIONS entry, so config/constants.php stays untouched.
 */
class NoticeController extends Controller
{
    /** Roles allowed to create/edit/delete notices. Viewing stays open to everyone (MODULE_PERMISSIONS['notices']). */
    private const MANAGE_ROLES = [
        'super_admin', 'principal', 'vice_principal', 'accountant',
        'teacher', 'class_teacher', 'receptionist',
    ];

    public function __construct()
    {
        $this->authorizeModule('notices');
    }

    public function index(): void
    {
        $noticeModel = new Notice();

        $filters = [
            'category'     => $this->input('category', ''),
            'class_id'     => $this->input('class_id', ''),
            'section_id'   => $this->input('section_id', ''),
            'status'       => $this->input('status', ''),
            'priority'     => $this->input('priority', ''),
            'publish_date' => $this->input('publish_date', ''),
        ];
        $search = trim((string) $this->input('search', ''));
        $sort = $this->input('sort', 'publish_date');
        $direction = $this->input('direction', 'DESC');

        $result = $noticeModel->paginateWithJoins($this->currentPage(), 10, $filters, $search, $sort, $direction);

        // AJAX search/filter: return just the table+pagination partial.
        if ($this->input('ajax') === '1') {
            $this->viewRaw('notices/_table', [
                'result' => $result, 'filters' => $filters, 'search' => $search,
                'sort' => $sort, 'direction' => $direction, 'canManage' => $this->canManage(),
            ]);
            return;
        }

        $this->view('notices/index', [
            'pageTitle'  => 'Notice Board',
            'result'     => $result,
            'filters'    => $filters,
            'search'     => $search,
            'sort'       => $sort,
            'direction'  => $direction,
            'classes'    => (new ClassModel())->all('display_order'),
            'sections'   => !empty($filters['class_id']) ? (new Section())->forClass((int) $filters['class_id']) : (new Section())->all('name'),
            'categories' => Notice::categoryOptions(),
            'canManage'  => $this->canManage(),
            'errors'     => Session::getErrors(),
        ]);
    }

    /** Notice Details page — /notices/{id}. Used by the Dashboard marquee and "Latest Notices" widget links. */
    public function show(string $id): void
    {
        $notice = (new Notice())->findActive((int) $id);
        if (!$notice) {
            http_response_code(404);
            require dirname(__DIR__) . '/Views/errors/404.php';
            return;
        }

        $this->view('notices/show', [
            'pageTitle' => $notice['title'],
            'notice'    => $notice,
        ]);
    }

    public function create(): void
    {
        $this->requireManager();

        $this->view('notices/create', [
            'pageTitle'  => 'Add Notice',
            'classes'    => (new ClassModel())->all('display_order'),
            'categories' => Notice::categoryOptions(),
            'errors'     => Session::getErrors(),
        ]);
    }

    public function store(): void
    {
        $this->requireManager();
        $data = $this->validateNotice();

        $attachmentPath = null;
        $attachmentName = null;
        $file = $this->file('attachment');
        if ($file && $file['error'] !== UPLOAD_ERR_NO_FILE) {
            try {
                $attachmentPath = handle_upload($file, 'notices', false);
                $attachmentName = $file['name'];
            } catch (\RuntimeException $e) {
                Session::setErrors(['attachment' => [$e->getMessage()]]);
                Session::setOldInput($data);
                $this->redirect(url('notices/create'));
                return;
            }
        }

        $payload = $this->buildPayload($data, $attachmentPath, $attachmentName);
        $payload['published_by'] = Auth::id();
        $payload['created_by'] = Auth::id();
        $payload['updated_by'] = Auth::id();

        $id = (new Notice())->insert($payload);

        log_activity('created_record', "Created notice #{$id}: {$data['title']}");
        $this->flashSuccess('Notice created successfully.');
        $this->redirect(url('notices'));
    }

    public function edit(string $id): void
    {
        $this->requireManager();
        $notice = (new Notice())->findActive((int) $id);
        if (!$notice) {
            $this->flashError('Notice not found.');
            $this->redirect(url('notices'));
            return;
        }

        $this->view('notices/edit', [
            'pageTitle'  => 'Edit Notice',
            'notice'     => $notice,
            'classes'    => (new ClassModel())->all('display_order'),
            'sections'   => !empty($notice['class_id']) ? (new Section())->forClass((int) $notice['class_id']) : [],
            'categories' => Notice::categoryOptions(),
            'errors'     => Session::getErrors(),
        ]);
    }

    public function update(string $id): void
    {
        $this->requireManager();
        $noticeId = (int) $id;
        $noticeModel = new Notice();
        $existing = $noticeModel->findActive($noticeId);
        if (!$existing) {
            $this->flashError('Notice not found.');
            $this->redirect(url('notices'));
            return;
        }

        $data = $this->validateNotice();

        $attachmentPath = $existing['attachment_path'];
        $attachmentName = $existing['attachment_name'];
        if ($this->input('remove_attachment') === '1') {
            delete_upload($attachmentPath);
            $attachmentPath = null;
            $attachmentName = null;
        }
        $file = $this->file('attachment');
        if ($file && $file['error'] !== UPLOAD_ERR_NO_FILE) {
            try {
                $newPath = handle_upload($file, 'notices', false);
                delete_upload($existing['attachment_path']);
                $attachmentPath = $newPath;
                $attachmentName = $file['name'];
            } catch (\RuntimeException $e) {
                Session::setErrors(['attachment' => [$e->getMessage()]]);
                Session::setOldInput($data);
                $this->redirect(url('notices/' . $noticeId . '/edit'));
                return;
            }
        }

        $payload = $this->buildPayload($data, $attachmentPath, $attachmentName);
        $payload['updated_by'] = Auth::id();

        $noticeModel->update($noticeId, $payload);

        log_activity('updated_record', "Updated notice #{$noticeId}");
        $this->flashSuccess('Notice updated successfully.');
        $this->redirect(url('notices'));
    }

    public function destroy(string $id): void
    {
        $this->requireManager();
        $noticeId = (int) $id;
        $noticeModel = new Notice();
        if (!$noticeModel->findActive($noticeId)) {
            $this->flashError('Notice not found.');
            $this->redirect(url('notices'));
            return;
        }

        $noticeModel->delete($noticeId);
        log_activity('deleted_record', "Deleted notice #{$noticeId}");
        $this->flashSuccess('Notice deleted successfully.');
        $this->redirect(url('notices'));
    }

    // ------------------------------------------------------------------
    // AJAX helpers
    // ------------------------------------------------------------------

    /** AJAX: sections belonging to a class, for the create/edit form and the filter bar. */
    public function sectionsForClass(string $classId): void
    {
        $this->json((new Section())->forClass((int) $classId));
    }

    /** AJAX: searchable student dropdown, optionally scoped to a class/section. */
    public function searchStudents(): void
    {
        $q = trim((string) $this->input('q', ''));
        $classId = (int) $this->input('class_id', 0);
        $sectionId = (int) $this->input('section_id', 0);

        $conditions = ['status' => 'active'];
        if ($classId) {
            $conditions['class_id'] = $classId;
        }
        if ($sectionId) {
            $conditions['section_id'] = $sectionId;
        }
        $students = (new Student())->where($conditions, 'full_name', 'ASC');

        if ($q !== '') {
            $needle = mb_strtolower($q);
            $students = array_values(array_filter($students, fn ($s) => str_contains(mb_strtolower($s['full_name']), $needle)
                || str_contains(mb_strtolower((string) $s['admission_number']), $needle)));
        }

        $this->json(array_map(fn ($s) => [
            'id' => $s['id'], 'full_name' => $s['full_name'], 'admission_number' => $s['admission_number'],
        ], array_slice($students, 0, 20)));
    }

    /**
     * AJAX: latest live (published, in-window) notices for the Dashboard
     * "Important Notices" marquee — priority=high first, most recent first.
     */
    public function marqueeData(): void
    {
        $noticeModel = new Notice();
        $notices = $noticeModel->activeAndPublished(15);
        usort($notices, function ($a, $b) {
            $pOrder = ['high' => 0, 'medium' => 1, 'low' => 2];
            $cmp = ($pOrder[$a['priority']] ?? 1) <=> ($pOrder[$b['priority']] ?? 1);
            return $cmp !== 0 ? $cmp : strtotime($b['publish_date']) <=> strtotime($a['publish_date']);
        });

        $this->json(array_map(fn ($n) => [
            'id'       => $n['id'],
            'title'    => $n['title'],
            'priority' => $n['priority'],
            'url'      => url('notices/' . $n['id']),
        ], array_slice($notices, 0, 10)));
    }

    // ------------------------------------------------------------------
    // Print / Export
    // ------------------------------------------------------------------

    public function print(): void
    {
        $filters = $this->currentFilters();
        $rows = (new Notice())->allWithJoins($filters, trim((string) $this->input('search', '')));

        $this->viewRaw('notices/print', [
            'pageTitle' => 'Notice Board', 'rows' => $rows, 'printedAt' => date('d M Y, h:i A'),
        ]);
    }

    public function exportExcel(): void
    {
        $filters = $this->currentFilters();
        $rows = (new Notice())->allWithJoins($filters, trim((string) $this->input('search', '')));

        $headers = ['Title', 'Category', 'Priority', 'Audience', 'Class', 'Section', 'Posted By', 'Publish Date', 'Expiry Date', 'Status'];
        $csvRows = array_map(fn ($r) => [
            $r['title'], $r['category'], ucfirst($r['priority']), Notice::audienceLabel($r['audience']),
            $r['class_name'] ?? '—', $r['section_name'] ?? '—', $r['posted_by_name'] ?? '—',
            format_date($r['publish_date']), $r['expiry_date'] ? format_date($r['expiry_date']) : '—', ucfirst($r['status']),
        ], $rows);

        log_activity('exported_record', 'Exported notice board to CSV/Excel.');
        csv_download('notices-' . date('Ymd-His') . '.csv', $headers, $csvRows);
    }

    public function exportPdf(): void
    {
        $filters = $this->currentFilters();
        $rows = (new Notice())->allWithJoins($filters, trim((string) $this->input('search', '')));

        ob_start();
        $this->viewRaw('notices/print', ['pageTitle' => 'Notice Board', 'rows' => $rows, 'printedAt' => date('d M Y, h:i A')]);
        $html = ob_get_clean();

        if (class_exists(\Dompdf\Dompdf::class)) {
            $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => false]);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            log_activity('exported_record', 'Exported notice board to PDF.');
            $dompdf->stream('notices-' . date('Ymd-His') . '.pdf', ['Attachment' => true]);
            exit;
        }

        echo $html;
    }

    // ------------------------------------------------------------------
    // Private helpers
    // ------------------------------------------------------------------

    private function canManage(): bool
    {
        return in_array(Auth::role(), self::MANAGE_ROLES, true);
    }

    private function requireManager(): void
    {
        if (!$this->canManage()) {
            http_response_code(403);
            require dirname(__DIR__) . '/Views/errors/403.php';
            exit;
        }
    }

    private function currentFilters(): array
    {
        return [
            'category'     => $this->input('category', ''),
            'class_id'     => $this->input('class_id', ''),
            'section_id'   => $this->input('section_id', ''),
            'status'       => $this->input('status', ''),
            'priority'     => $this->input('priority', ''),
            'publish_date' => $this->input('publish_date', ''),
        ];
    }

    private function validateNotice(): array
    {
        $rules = [
            'title'         => 'required|max:200',
            'body'          => 'required|max:5000',
            'category'      => 'required|max:60',
            'priority'      => 'required|in:high,medium,low',
            'audience'      => 'required|in:all,students,teachers,parents,staff,class,section,student',
            'publish_date'  => 'required|date',
            'expiry_date'   => 'nullable|date',
            'status'        => 'required|in:draft,published,archived',
            'class_id'      => 'nullable|exists:classes,id',
            'section_id'    => 'nullable|exists:sections,id',
            'student_id'    => 'nullable|exists:students,id',
        ];
        $data = $this->validate($rules, [], $this->all());

        $fieldErrors = [];
        if (in_array($data['audience'], ['class', 'section', 'student'], true) && empty($data['class_id'])) {
            $fieldErrors['class_id'] = ['Please select a Class for this audience.'];
        }
        if ($data['audience'] === 'section' && empty($data['section_id'])) {
            $fieldErrors['section_id'] = ['Please select a Section for this audience.'];
        }
        if ($data['audience'] === 'student' && empty($data['student_id'])) {
            $fieldErrors['student_id'] = ['Please select a Student for this audience.'];
        }
        if (!empty($data['expiry_date']) && strtotime($data['expiry_date']) < strtotime($data['publish_date'])) {
            $fieldErrors['expiry_date'] = ['Expiry Date must be on or after Publish Date.'];
        }
        if (!empty($fieldErrors)) {
            Session::setErrors($fieldErrors);
            Session::setOldInput($data);
            Session::flash('error', 'Please fix the errors below and try again.');
            $this->back();
            exit;
        }

        return $data;
    }

    private function buildPayload(array $data, ?string $attachmentPath, ?string $attachmentName): array
    {
        $audience = $data['audience'];
        $classId = in_array($audience, ['class', 'section', 'student'], true) ? ($data['class_id'] ?: null) : null;
        $sectionId = in_array($audience, ['section', 'student'], true) ? ($data['section_id'] ?: null) : null;
        $studentId = $audience === 'student' ? ($data['student_id'] ?: null) : null;

        // `scope` (legacy column) mirrors the granular audience, for anything still reading it.
        $scope = match ($audience) {
            'class', 'section', 'student' => 'class',
            default => 'school',
        };

        return [
            'title'           => trim($data['title']),
            'body'            => trim($data['body']),
            'category'        => trim($data['category']),
            'priority'        => $data['priority'],
            'scope'           => $scope,
            'audience'        => $audience,
            'class_id'        => $classId,
            'section_id'      => $sectionId,
            'student_id'      => $studentId,
            'visible_to'      => Notice::visibleToForAudience($audience),
            'publish_date'    => $data['publish_date'],
            'expiry_date'     => $data['expiry_date'] ?: null,
            'published_at'    => $data['publish_date'] . ' 00:00:00',
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'status'          => $data['status'],
        ];
    }
}
