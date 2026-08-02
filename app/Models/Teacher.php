<?php

namespace App\Models;

use App\Core\Model;

class Teacher extends Model
{
    protected string $table = 'teachers';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'user_id', 'employee_number', 'first_name', 'middle_name', 'last_name', 'full_name',
        'gender', 'dob', 'blood_group', 'religion', 'nationality', 'marital_status',
        'citizenship_number', 'passport_number', 'phone', 'email', 'address',
        'qualification', 'experience_years', 'salary', 'joining_date',
        'photo_path', 'signature_path', 'status', 'is_active', 'created_by', 'updated_by',
    ];

    protected array $searchable = ['full_name', 'employee_number', 'phone', 'email'];

    /** Only non-soft-deleted teachers — the listing/search screens should use this, not all(). */
    public function activeQuery(): array
    {
        return $this->raw('SELECT * FROM teachers WHERE deleted_at IS NULL ORDER BY full_name ASC');
    }

    public function countByStatus(string $status = 'active'): int
    {
        return $this->count(['status' => $status]);
    }

    /**
     * For a batch of teacher ids, returns each teacher's class/section/subject
     * assignments (from teacher_assignments) grouped as
     * [teacher_id => [['subject_name','class_name','section_name'], ...]].
     * Used by the admin Teachers list so it doesn't run one query per row.
     */
    public function assignmentsFor(array $teacherIds): array
    {
        if (empty($teacherIds)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($teacherIds), '?'));
        $sql = "SELECT ta.teacher_id, sub.name AS subject_name, c.name AS class_name, sec.name AS section_name
                FROM teacher_assignments ta
                JOIN subjects sub ON sub.id = ta.subject_id
                JOIN classes c ON c.id = ta.class_id
                JOIN sections sec ON sec.id = ta.section_id
                WHERE ta.teacher_id IN ($placeholders) AND ta.is_active = 1
                ORDER BY c.display_order ASC, sub.name ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($teacherIds));

        $byTeacher = [];
        foreach ($stmt->fetchAll() as $row) {
            $byTeacher[$row['teacher_id']][] = $row;
        }
        return $byTeacher;
    }

    /** Active teachers for the Library "Issue Book" dropdown — id/name/employee no./contact only. */
    public function forLibraryDropdown(): array
    {
        return $this->raw(
            "SELECT id, full_name, employee_number, phone, email, photo_path
             FROM teachers
             WHERE deleted_at IS NULL AND status = 'active'
             ORDER BY full_name ASC"
        );
    }

    /** Simple id => full_name list, used for "Reporting Manager" / "Leave Approver" pickers. */
    public function namesList(?int $excludeId = null): array
    {
        $sql = 'SELECT id, full_name, employee_number FROM teachers WHERE deleted_at IS NULL';
        $params = [];
        if ($excludeId) {
            $sql .= ' AND id != :ex';
            $params['ex'] = $excludeId;
        }
        $sql .= ' ORDER BY full_name ASC';
        return $this->raw($sql, $params);
    }
}
