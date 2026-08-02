<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Teacher;
use App\Models\TeacherAttendanceSetting;

/**
 * Teacher Attendance module (MODULE_PERMISSIONS['teacher_attendance'] —
 * separate from 'teachers' so attendance staff don't automatically get
 * full teacher-record edit rights, and vice versa).
 *
 * Device/biometric/shift settings used to live inside the Teacher
 * registration wizard's "7. Attendance" tab; they now live here instead,
 * configured per-teacher after the teacher record already exists. See
 * app/Views/teachers/_form.php for the note pointing admins here.
 */
class TeacherAttendanceController extends Controller
{
    public function __construct()
    {
        $this->authorizeModule('teacher_attendance');
    }

    /** Pick a teacher to configure attendance/device settings for. */
    public function index(): void
    {
        $teacherModel = new Teacher();
        $search = trim((string) $this->input('search', ''));
        $result = $teacherModel->paginate($this->currentPage(), 20, ['deleted_at' => null], $search, 'full_name', 'ASC');
        $result['data'] = array_values(array_filter($result['data'], fn($r) => empty($r['deleted_at'])));

        $this->view('teacher_attendance/settings_index', [
            'pageTitle' => 'Teacher Attendance Settings',
            'result'    => $result,
            'search'    => $search,
        ]);
    }

    public function edit(string $id): void
    {
        $teacherId = (int) $id;
        $teacher = (new Teacher())->find($teacherId);
        if (!$teacher || !empty($teacher['deleted_at'])) {
            http_response_code(404);
            require dirname(__DIR__) . '/Views/errors/404.php';
            return;
        }

        $this->view('teacher_attendance/settings_form', [
            'pageTitle'     => 'Attendance Settings — ' . $teacher['full_name'],
            'teacher'       => $teacher,
            'attendanceSet' => (new TeacherAttendanceSetting())->forTeacher($teacherId) ?: [],
            'managers'      => (new Teacher())->namesList($teacherId),
            'errors'        => Session::getErrors(),
        ]);
    }

    public function update(string $id): void
    {
        $teacherId = (int) $id;
        $teacher = (new Teacher())->find($teacherId);
        if (!$teacher || !empty($teacher['deleted_at'])) {
            http_response_code(404);
            require dirname(__DIR__) . '/Views/errors/404.php';
            return;
        }

        $data = $this->all();

        try {
            $this->saveAttendanceSettings($teacherId, $data);
        } catch (\Throwable $e) {
            error_log('[TEACHER ATTENDANCE SETTINGS ERROR] ' . $e->getMessage());
            Session::flash('error', 'Could not save attendance settings. Please try again.');
            $this->redirect(url('teacher-attendance/' . $teacherId . '/settings'));
            return;
        }

        $this->flashSuccess('Attendance settings updated successfully for ' . $teacher['full_name'] . '.');
        $this->redirect(url('teacher-attendance/' . $teacherId . '/settings'));
    }

    private function saveAttendanceSettings(int $teacherId, array $data): void
    {
        (new TeacherAttendanceSetting())->save($teacherId, [
            'biometric_id'         => $this->nullableText($data['biometric_id'] ?? null),
            'rfid_card_number'     => $this->nullableText($data['rfid_card_number'] ?? null),
            'face_recognition_id'  => $this->nullableText($data['face_recognition_id'] ?? null),
            'attendance_device_id' => $this->nullableText($data['attendance_device_id'] ?? null),
            'default_shift'        => $this->nullableText($data['default_shift'] ?? null),
            'working_hours'        => $this->nullableText($data['working_hours'] ?? null),
            'weekly_off'           => (!empty($data['weekly_off']) && is_array($data['weekly_off'])) ? implode(',', $data['weekly_off']) : null,

            // Device information
            'device_name'                => $this->nullableText($data['device_name'] ?? null),
            'device_location'            => $this->nullableText($data['device_location'] ?? null),
            'device_serial_number'       => $this->nullableText($data['device_serial_number'] ?? null),
            'device_type'                => $this->nullableText($data['device_type'] ?? null),

            // Attendance policy
            'attendance_method'                => $this->nullableText($data['attendance_method'] ?? null),
            'attendance_status'                => $this->nullableText($data['attendance_status'] ?? null) ?? 'active',
            'overtime_eligible'                 => !empty($data['overtime_eligible']) ? 1 : 0,
            'late_grace_period_minutes'        => $this->nullableInt($data['late_grace_period_minutes'] ?? null),
            'early_exit_grace_period_minutes'  => $this->nullableInt($data['early_exit_grace_period_minutes'] ?? null),
            'minimum_working_hours'            => $this->nullableFloat($data['minimum_working_hours'] ?? null),

            // Shift details
            'shift_type'             => $this->nullableText($data['shift_type'] ?? null),
            'shift_start_time'       => $this->nullableText($data['shift_start_time'] ?? null),
            'shift_end_time'         => $this->nullableText($data['shift_end_time'] ?? null),
            'break_duration_minutes' => $this->nullableInt($data['break_duration_minutes'] ?? null),

            // Registration details
            'registration_date'           => $this->nullableText($data['registration_date'] ?? null),
            'registered_by'               => $this->nullableInt($data['registered_by'] ?? null),
            'device_registration_status'  => $this->nullableText($data['device_registration_status'] ?? null),
            'last_device_sync_date'       => $this->nullableText($data['last_device_sync_date'] ?? null),

            // Attendance rules
            'auto_mark_absent'        => !empty($data['auto_mark_absent']) ? 1 : 0,
            'allow_manual_attendance' => array_key_exists('allow_manual_attendance', $data) ? (!empty($data['allow_manual_attendance']) ? 1 : 0) : 1,
            'require_gps'             => !empty($data['require_gps']) ? 1 : 0,
            'require_selfie'          => !empty($data['require_selfie']) ? 1 : 0,

            // Check-in / check-out
            'default_check_in_time'   => $this->nullableText($data['default_check_in_time'] ?? null),
            'default_check_out_time'  => $this->nullableText($data['default_check_out_time'] ?? null),
            'max_late_minutes'        => $this->nullableInt($data['max_late_minutes'] ?? null),
            'max_early_leave_minutes' => $this->nullableInt($data['max_early_leave_minutes'] ?? null),
        ]);
    }

    private function nullableFloat(mixed $value): ?float
    {
        return ($value === null || $value === '') ? null : (float) $value;
    }

    private function nullableInt(mixed $value): ?int
    {
        return ($value === null || $value === '') ? null : (int) $value;
    }

    private function nullableText(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : $value;
    }
}
