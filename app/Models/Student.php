<?php

namespace App\Models;

use App\Core\Model;

class Student extends Model
{
    protected string $table = 'students';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'user_id', 'admission_number', 'roll_number', 'full_name', 'gender', 'dob',
        'blood_group', 'religion', 'religion_other', 'nationality',
        'phone_country_code', 'phone', 'email', 'address',
        'address_country', 'address_province', 'address_district', 'address_municipality',
        'address_ward', 'address_street', 'address_postal_code',
        'photo_path', 'class_id', 'section_id', 'parent_id', 'academic_year_id',
        'admission_date', 'status',
        'id_card_number', 'previous_school', 'previous_class', 'birth_certificate_number',
        'citizenship_number', 'passport_number', 'scholarship_status',
        'medical_conditions', 'allergies',
        'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relationship',
        'remarks', 'created_by', 'updated_by', 'ip_address',
    ];

    protected array $searchable = ['full_name', 'admission_number', 'roll_number', 'phone', 'email'];

    public function findByAdmissionNumber(string $admissionNumber): array|false
    {
        return $this->findBy('admission_number', $admissionNumber);
    }

    /** One student with class/section/session/parent names already joined — Payment Collection's info card. */
    public function withDetails(int $id): array|false
    {
        $rows = $this->raw(
            "SELECT s.*, c.name AS class_name, sec.name AS section_name, ay.label AS academic_year_label,
                    p.father_name, p.mother_name, p.guardian_name, p.phone AS parent_phone
             FROM students s
             LEFT JOIN classes c ON c.id = s.class_id
             LEFT JOIN sections sec ON sec.id = s.section_id
             LEFT JOIN academic_years ay ON ay.id = s.academic_year_id
             LEFT JOIN parents p ON p.id = s.parent_id
             WHERE s.id = :id LIMIT 1",
            ['id' => $id]
        );
        return $rows[0] ?? false;
    }

    /** Most recently admitted students, with class/section names joined in. */
    public function recent(int $limit = 5): array
    {
        $sql = "SELECT s.*, c.name AS class_name, sec.name AS section_name
                FROM students s
                LEFT JOIN classes c ON c.id = s.class_id
                LEFT JOIN sections sec ON sec.id = s.section_id
                ORDER BY s.created_at DESC
                LIMIT " . (int) $limit;
        return $this->raw($sql);
    }

    public function countByStatus(string $status = 'active'): int
    {
        return $this->count(['status' => $status]);
    }

    /** Active students for the Library "Issue Book" dropdown — id/name/admission no./contact only. */
    public function forLibraryDropdown(): array
    {
        return $this->raw(
            "SELECT id, full_name, admission_number, phone, email, photo_path
             FROM students
             WHERE status = 'active'
             ORDER BY full_name ASC"
        );
    }

    /** @return array<int,array> Students in a given class + section, for attendance/marks entry. */
    public function byClassSection(int $classId, int $sectionId): array
    {
        return $this->raw(
            'SELECT * FROM students WHERE class_id = :cid AND section_id = :sid AND status = "active" ORDER BY roll_number ASC',
            ['cid' => $classId, 'sid' => $sectionId]
        );
    }

    /** Active student count for a whole class (all sections) — used by the Classes module. */
    public function countByClass(int $classId): int
    {
        return $this->count(['class_id' => $classId, 'status' => 'active']);
    }

    /** Active student count for a single section — used by the Sections module. */
    public function countBySection(int $sectionId): int
    {
        return $this->count(['section_id' => $sectionId, 'status' => 'active']);
    }

    /** @return array<int,array> Active students in a given section, for the Section Details "Students" tab. */
    public function bySection(int $sectionId): array
    {
        return $this->raw(
            'SELECT * FROM students WHERE section_id = :sid AND status = "active" ORDER BY roll_number ASC',
            ['sid' => $sectionId]
        );
    }

    /** The student record linked to a given login (users.id) — for student-portal "my own record" checks. */
    public function byUserId(int $userId): array|false
    {
        return $this->findBy('user_id', $userId);
    }
}
