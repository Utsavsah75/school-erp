<?php

namespace App\Models;

use App\Core\Model;

/** 1:1 with teachers (spec section 8 — Attendance Settings/devices, not the daily log). */
class TeacherAttendanceSetting extends Model
{
    protected string $table = 'teacher_attendance_settings';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'teacher_id', 'biometric_id', 'rfid_card_number', 'face_recognition_id',
        'attendance_device_id', 'default_shift', 'working_hours', 'weekly_off',
        // Device information
        'device_name', 'device_location', 'device_serial_number', 'device_type',
        // Attendance policy
        'attendance_method', 'attendance_status', 'overtime_eligible',
        'late_grace_period_minutes', 'early_exit_grace_period_minutes', 'minimum_working_hours',
        // Shift details
        'shift_type', 'shift_start_time', 'shift_end_time', 'break_duration_minutes',
        // Registration details
        'registration_date', 'registered_by', 'device_registration_status', 'last_device_sync_date',
        // Attendance rules
        'auto_mark_absent', 'allow_manual_attendance', 'require_gps', 'require_selfie',
        // Check-in / check-out
        'default_check_in_time', 'default_check_out_time', 'max_late_minutes', 'max_early_leave_minutes',
    ];

    public function forTeacher(int $teacherId): array|false
    {
        return $this->firstWhere(['teacher_id' => $teacherId]);
    }

    public function save(int $teacherId, array $data): void
    {
        $existing = $this->forTeacher($teacherId);
        $data['teacher_id'] = $teacherId;
        if ($existing) {
            $this->update($existing['id'], $data);
        } else {
            $this->insert($data);
        }
    }
}
