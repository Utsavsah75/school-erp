<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\ParentModel;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\TeacherDocument;

/**
 * Centralised, safe file download for uploaded documents (Student and
 * Teacher modules). Fixes the "Download Document opens an error page"
 * bug: previously templates linked straight to the static /uploads/...
 * path via upload_url(), so a missing/moved file produced the web
 * server's raw 404 page and, for anyone poking at the URL, exposed the
 * on-disk folder layout. This controller instead:
 *   - looks the document up by DB id (never trusts a raw path from the client)
 *   - for a student/parent login, additionally confirms the document
 *     belongs to that student (or that student's own child) — a staff
 *     login with the module permission can still reach any student's file
 *   - confirms the file still exists on disk before doing anything
 *   - shows a friendly "Document not available." flash + redirect back if not
 *   - logs exactly what went wrong (missing DB row / ownership mismatch /
 *     missing file + resolved path) so a bad record is easy to trace
 *   - streams the file with a forced download + safe filename if it does
 */
class DocumentController extends Controller
{
    /** doc "type" (from the URL) => [Model class, module used for authorization]. */
    private const TYPES = [
        'student' => [StudentDocument::class, 'students'],
        'teacher' => [TeacherDocument::class, 'teachers'],
    ];

    public function download(string $type, string $id): void
    {
        $mapping = self::TYPES[$type] ?? null;
        if (!$mapping) {
            http_response_code(404);
            require dirname(__DIR__) . '/Views/errors/404.php';
            return;
        }

        [$modelClass, $module] = $mapping;

        // Staff with the module permission (teachers, receptionists, admins,
        // etc.) can access any document of that type — same as before.
        // A student/parent login instead needs an ownership check, since
        // they don't hold the 'students'/'teachers' module permission at
        // all today; this only matters if a future student/parent-facing
        // screen links here directly.
        if (!Auth::can($module)) {
            if ($type === 'student' && !$this->ownsStudentDocument((int) $id)) {
                http_response_code(403);
                require dirname(__DIR__) . '/Views/errors/403.php';
                return;
            }
            if ($type !== 'student') {
                Auth::authorize($module); // no self-service path for teacher docs — same as before
            }
        }

        $doc = (new $modelClass())->find((int) $id);
        if (!$doc || empty($doc['file_path'])) {
            error_log('[DOCUMENT DOWNLOAD] No ' . $type . ' document record for id #' . $id . ' (requested by user #' . Auth::id() . ').');
            $this->flashError('Document not available.');
            $this->back();
            return;
        }

        $fullPath = resolve_upload_path($doc['file_path']);

        // Guard against the stored path escaping the uploads directory
        // (e.g. via "../../") before we ever touch the filesystem with it.
        $uploadsRoot = dirname(__DIR__, 2) . '/public/uploads';
        if (!$fullPath || strpos(realpath(dirname($fullPath)) ?: '', realpath($uploadsRoot) ?: "\0") !== 0 || !is_file($fullPath)) {
            error_log(sprintf(
                '[DOCUMENT DOWNLOAD] Missing file for %s document #%s (student_id=%s): db path "%s" resolved to "%s".',
                $type,
                $id,
                $doc['student_id'] ?? $doc['teacher_id'] ?? 'n/a',
                $doc['file_path'],
                $fullPath ?? '(could not resolve)'
            ));
            $this->flashError('Document not available. It may not have been uploaded yet — please contact the office if this is unexpected.');
            $this->back();
            return;
        }

        $displayName = $doc['original_name'] ?? $doc['doc_name'] ?? basename($fullPath);
        $ext = pathinfo($fullPath, PATHINFO_EXTENSION);
        if ($ext && !str_ends_with(strtolower($displayName), '.' . strtolower($ext))) {
            $displayName .= '.' . $ext;
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $this->safeFilename($displayName) . '"');
        header('Content-Transfer-Encoding: binary');
        header('X-Content-Type-Options: nosniff');
        header('Content-Length: ' . filesize($fullPath));
        readfile($fullPath);
        exit;
    }

    /**
     * True if the current login is either the student the document
     * belongs to, or that student's linked parent. Staff logins never
     * reach this — Auth::can($module) already let them through above.
     */
    private function ownsStudentDocument(int $documentId): bool
    {
        $doc = (new StudentDocument())->find($documentId);
        if (!$doc) {
            return false;
        }

        $role = Auth::role();
        $userId = Auth::id();
        if (!$userId) {
            return false;
        }

        if ($role === ROLE_STUDENT) {
            $me = (new Student())->byUserId($userId);
            return $me && (int) $me['id'] === (int) $doc['student_id'];
        }

        if ($role === ROLE_PARENT) {
            $me = (new ParentModel())->byUserId($userId);
            if (!$me) {
                return false;
            }
            $student = (new Student())->find((int) $doc['student_id']);
            return $student && (int) $student['parent_id'] === (int) $me['id'];
        }

        return false;
    }

    private function safeFilename(string $name): string
    {
        $name = preg_replace('/[\/\\\\:*?"<>|]/', '_', $name);
        return $name ?: 'document';
    }
}
