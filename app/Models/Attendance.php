<?php

namespace App\Models;

use App\Core\Model;

class Attendance extends Model
{
    protected string $table = 'attendance';
    protected string $primaryKey = 'id';

    protected array $fillable = ['student_id', 'class_id', 'section_id', 'date', 'status', 'marked_by', 'remarks'];

    /** Today's attendance status breakdown, e.g. ['present'=>320,'absent'=>12,'late'=>4,'leave'=>2]. */
    public function summaryForDate(?string $date = null): array
    {
        $date ??= date('Y-m-d');
        $rows = $this->raw(
            'SELECT status, COUNT(*) AS c FROM attendance WHERE date = :d GROUP BY status',
            ['d' => $date]
        );
        $summary = ['present' => 0, 'absent' => 0, 'late' => 0, 'leave' => 0];
        foreach ($rows as $row) {
            $summary[$row['status']] = (int) $row['c'];
        }
        return $summary;
    }

    public function forStudentAndDate(int $studentId, string $date): array|false
    {
        return $this->raw(
            'SELECT * FROM attendance WHERE student_id = :sid AND date = :d LIMIT 1',
            ['sid' => $studentId, 'd' => $date]
        )[0] ?? false;
    }

    public function forClassSectionDate(int $classId, int $sectionId, string $date): array
    {
        return $this->raw(
            'SELECT * FROM attendance WHERE class_id = :c AND section_id = :s AND date = :d',
            ['c' => $classId, 's' => $sectionId, 'd' => $date]
        );
    }

    /** Upsert: insert or update the day's attendance for one student. */
    public function markOne(int $studentId, int $classId, int $sectionId, string $date, string $status, ?int $markedBy, ?string $remarks = null): void
    {
        $existing = $this->forStudentAndDate($studentId, $date);
        $data = [
            'student_id' => $studentId, 'class_id' => $classId, 'section_id' => $sectionId,
            'date' => $date, 'status' => $status, 'marked_by' => $markedBy, 'remarks' => $remarks,
        ];
        if ($existing) {
            $this->update($existing['id'], $data);
        } else {
            $this->insert($data);
        }
    }

    /** Day-by-day attendance records for a student between two dates, most recent first. */
    public function history(int $studentId, string $from, string $to, int $limit = 60): array
    {
        $sql = 'SELECT * FROM attendance WHERE student_id = :sid AND date BETWEEN :f AND :t ORDER BY date DESC LIMIT ' . (int) $limit;
        return $this->raw($sql, ['sid' => $studentId, 'f' => $from, 't' => $to]);
    }

    /** Present/absent/late/leave/holiday counts for a student in one calendar month (Y-m). */
    public function monthlySummary(int $studentId, string $yearMonth): array
    {
        $from = $yearMonth . '-01';
        $to = date('Y-m-t', strtotime($from));
        $rows = $this->raw(
            'SELECT status, COUNT(*) AS c FROM attendance WHERE student_id = :sid AND date BETWEEN :f AND :t GROUP BY status',
            ['sid' => $studentId, 'f' => $from, 't' => $to]
        );
        $summary = ['present' => 0, 'absent' => 0, 'late' => 0, 'leave' => 0, 'holiday' => 0];
        foreach ($rows as $row) {
            $summary[$row['status']] = (int) $row['c'];
        }
        return $summary;
    }

    /** Day-of-month (1-31) => status map for one student/month, for the attendance calendar grid. */
    public function dayStatusesForMonth(int $studentId, string $yearMonth): array
    {
        $from = $yearMonth . '-01';
        $to = date('Y-m-t', strtotime($from));
        $rows = $this->raw(
            'SELECT date, status FROM attendance WHERE student_id = :sid AND date BETWEEN :f AND :t',
            ['sid' => $studentId, 'f' => $from, 't' => $to]
        );
        $map = [];
        foreach ($rows as $row) {
            $day = (int) date('j', strtotime($row['date']));
            $map[$day] = $row['status'];
        }
        return $map;
    }

    /** Percentage present for a student between two dates. */
    public function studentPercentage(int $studentId, string $from, string $to): float
    {
        $rows = $this->raw(
            'SELECT status, COUNT(*) AS c FROM attendance WHERE student_id = :sid AND date BETWEEN :f AND :t GROUP BY status',
            ['sid' => $studentId, 'f' => $from, 't' => $to]
        );
        $total = 0;
        $present = 0;
        foreach ($rows as $row) {
            $total += (int) $row['c'];
            if ($row['status'] === 'present') {
                $present = (int) $row['c'];
            }
        }
        return $total > 0 ? round(($present / $total) * 100, 1) : 0.0;
    }

    /**
     * Save a whole class/section/date's worth of attendance in one pass.
     * $entries is [student_id => status]. Uses INSERT ... ON DUPLICATE KEY
     * UPDATE against the uq_attendance_student_date unique key, so calling
     * this twice for the same date is always safe (edit == re-mark) and two
     * simultaneous submissions can never create duplicate rows for the same
     * student/date.
     *
     * @param array<int,string> $entries student_id => status
     * @param array<int,string> $remarks student_id => remark (optional)
     */
    public function markBulk(int $classId, int $sectionId, string $date, array $entries, ?int $markedBy, array $remarks = []): int
    {
        if (empty($entries)) {
            return 0;
        }

        $sql = "INSERT INTO `attendance`
                    (`student_id`, `class_id`, `section_id`, `date`, `status`, `marked_by`, `remarks`)
                VALUES
                    (:student_id, :class_id, :section_id, :date, :status, :marked_by, :remarks)
                ON DUPLICATE KEY UPDATE
                    `status` = VALUES(`status`),
                    `remarks` = VALUES(`remarks`),
                    `marked_by` = VALUES(`marked_by`)";

        $count = 0;
        foreach ($entries as $studentId => $status) {
            if (!in_array($status, ['present', 'absent', 'late', 'leave', 'holiday'], true)) {
                continue;
            }
            $this->db->query($sql, [
                'student_id' => (int) $studentId,
                'class_id' => $classId,
                'section_id' => $sectionId,
                'date' => $date,
                'status' => $status,
                'marked_by' => $markedBy,
                'remarks' => $remarks[$studentId] ?? null,
            ]);
            $count++;
        }
        return $count;
    }

    /**
     * Latest attendance actions across every class/section, newest first —
     * powers the "Recent Activity" panel at the top of the Attendance pages.
     * Falls back to created_at when a row has never been edited.
     */
    public function recentActivity(int $limit = 10): array
    {
        $sql = "SELECT a.id, a.date, a.status, a.created_at, a.updated_at,
                        s.full_name AS student_name, s.roll_number,
                        c.name AS class_name, sec.name AS section_name,
                        u.full_name AS marked_by_name
                 FROM attendance a
                 LEFT JOIN students s ON s.id = a.student_id
                 LEFT JOIN classes c ON c.id = a.class_id
                 LEFT JOIN sections sec ON sec.id = a.section_id
                 LEFT JOIN users u ON u.id = a.marked_by
                 ORDER BY COALESCE(a.updated_at, a.created_at) DESC, a.id DESC
                 LIMIT " . (int) $limit;
        return $this->raw($sql);
    }

    /**
     * Month-by-month present/absent/late/leave counts for one student across
     * a calendar year, plus a yearly percentage — powers the Yearly Report.
     */
    public function yearlySummary(int $studentId, string $year): array
    {
        $rows = $this->raw(
            "SELECT MONTH(date) AS m, status, COUNT(*) AS c
             FROM attendance
             WHERE student_id = :sid AND YEAR(date) = :y
             GROUP BY MONTH(date), status",
            ['sid' => $studentId, 'y' => $year]
        );

        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[$m] = ['present' => 0, 'absent' => 0, 'late' => 0, 'leave' => 0, 'holiday' => 0];
        }
        foreach ($rows as $row) {
            $months[(int) $row['m']][$row['status']] = (int) $row['c'];
        }

        $totalPresent = 0;
        $totalMarked = 0;
        foreach ($months as $m) {
            $working = $m['present'] + $m['absent'] + $m['late'] + $m['leave'];
            $totalPresent += $m['present'];
            $totalMarked += $working;
        }

        return [
            'months' => $months,
            'percentage' => $totalMarked > 0 ? round(($totalPresent / $totalMarked) * 100, 1) : 0.0,
        ];
    }

    /**
     * Flat, filterable rows for the attendance report screen and its
     * PDF/Excel export — one row per attendance record with student/class/
     * section names already joined in.
     */
    public function reportRows(string $from, string $to, int $classId = 0, int $sectionId = 0, int $studentId = 0): array
    {
        $where = ['a.date BETWEEN :f AND :t'];
        $params = ['f' => $from, 't' => $to];

        if ($classId > 0) {
            $where[] = 'a.class_id = :cid';
            $params['cid'] = $classId;
        }
        if ($sectionId > 0) {
            $where[] = 'a.section_id = :sid';
            $params['sid'] = $sectionId;
        }
        if ($studentId > 0) {
            $where[] = 'a.student_id = :stid';
            $params['stid'] = $studentId;
        }

        $sql = "SELECT a.date, a.status, a.remarks,
                        s.full_name AS student_name, s.roll_number,
                        c.name AS class_name, sec.name AS section_name
                 FROM attendance a
                 LEFT JOIN students s ON s.id = a.student_id
                 LEFT JOIN classes c ON c.id = a.class_id
                 LEFT JOIN sections sec ON sec.id = a.section_id
                 WHERE " . implode(' AND ', $where) . "
                 ORDER BY a.date DESC, s.roll_number ASC";

        return $this->raw($sql, $params);
    }
}
