<?php
/** Expects: $student, $class, $section, $results. Rendered standalone (no app layout) and piped into Dompdf — keep CSS simple. */
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    /*
      Font intentionally NOT standardized to the print-safe Arial stack used
      elsewhere: DejaVu Sans is Dompdf's built-in Unicode-safe font and is
      required here specifically because report cards render Nepali/Devanagari
      student and parent names, which Arial's font metrics in Dompdf do not
      reliably cover. Switching this would risk silently breaking non-Latin
      names. This is the one deliberate exception to the single-font-family
      rule, kept for correctness rather than consistency.
    */
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; line-height: 1.5; color: #222; }
    h1 { font-size: 18px; font-weight: 700; margin-bottom: 2px; }
    .muted { color: #666; }
    table { width: 100%; border-collapse: collapse; margin-top: 14px; }
    th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
    th { background: #f2f2f2; font-weight: 700; }
    .header { border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 10px; }
    .meta td { border: none; padding: 2px 8px 2px 0; }
</style>
</head>
<body>
    <div class="header">
        <h1>School ERP — Report Card</h1>
        <div class="muted">Generated <?= e(date('d M Y')) ?></div>
    </div>

    <table class="meta">
        <tr><td><strong>Name</strong></td><td><?= e($student['full_name']) ?></td><td><strong>Admission No.</strong></td><td>#<?= e($student['admission_number']) ?></td></tr>
        <tr><td><strong>Class</strong></td><td><?= e($class['name'] ?? '—') ?></td><td><strong>Section</strong></td><td><?= e($section['name'] ?? '—') ?></td></tr>
        <tr><td><strong>Roll No.</strong></td><td>#<?= e($student['roll_number'] ?? '—') ?></td><td></td><td></td></tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Exam</th>
                <th>Subject</th>
                <th>Date</th>
                <th>Marks Obtained</th>
                <th>Max Marks</th>
                <th>Grade</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($results)): ?>
                <tr><td colspan="6">No exam results recorded yet.</td></tr>
            <?php else: ?>
                <?php foreach ($results as $r): ?>
                    <tr>
                        <td><?= e($r['exam_name']) ?></td>
                        <td><?= e($r['subject_name']) ?></td>
                        <td><?= e(format_date($r['exam_date'])) ?></td>
                        <td><?= e($r['marks_obtained']) ?></td>
                        <td><?= e($r['max_marks']) ?></td>
                        <td><?= e($r['grade'] ?? '—') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
