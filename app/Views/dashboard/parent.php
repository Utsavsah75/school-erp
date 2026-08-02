<?php
/**
 * Enhanced Parent Dashboard. Expects: $parent, $children, $feesByChild, $paymentsByChild,
 * $resultsByChild, $attendancePctByChild, $attendanceHistoryByChild, $attendanceMonthlyByChild,
 * $homeworkByChild, $timetableByChild, $examScheduleByChild, $transportByChild, $stats,
 * $notices, $upcomingEvents, $notifications, $unreadCount, $myLeaveRequests, $myMessages,
 * $teachersByChild, $searchQuery
 */
?>
<?php if (!empty($searchQuery)): ?>
    <div class="alert alert-info">Showing results for "<?= e($searchQuery) ?>" — <a href="<?= e(url('dashboard')) ?>">clear search</a></div>
<?php endif; ?>

<!-- Quick Actions -->
<div class="card mb-3">
    <div class="card-body d-flex flex-wrap gap-2">
        <a href="#fees" data-bs-toggle="tab" class="btn btn-sm btn-outline-primary"><i class="bi bi-cash-coin"></i> Pay Fees</a>
        <?php if (!empty($children)): ?>
        <a href="<?= e(url('parent/children/' . $children[0]['id'] . '/report-card')) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i> Download Report Card</a>
        <?php endif; ?>
        <a href="#academics" data-bs-toggle="tab" class="btn btn-sm btn-outline-primary"><i class="bi bi-calendar-week"></i> View Timetable</a>
        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#applyLeaveModal"><i class="bi bi-envelope-paper"></i> Apply Leave</button>
        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#messageTeacherModal"><i class="bi bi-chat-dots"></i> Message Teacher</button>
        <a href="#homework" data-bs-toggle="tab" class="btn btn-sm btn-outline-primary"><i class="bi bi-journal-check"></i> View Homework</a>
        <?php if (array_filter($transportByChild ?? [])): ?>
        <a href="#calendar" data-bs-toggle="tab" class="btn btn-sm btn-outline-primary"><i class="bi bi-bus-front"></i> Track School Bus</a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3 mb-3">
    <!-- Children cards -->
    <div class="col-lg-6">
        <?php if (empty($children)): ?>
            <div class="card">
                <div class="card-body text-center text-muted py-5">
                    <i class="bi bi-emoji-neutral" style="font-size:32px;"></i>
                    <p class="mt-2 mb-0">No children are linked to your account yet.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($children as $i => $child): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <h6 class="fw-bold mb-3">My Children_<?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></h6>
                        <span class="text-muted small">AY <?= e($child['academic_year_id'] ?? '—') ?></span>
                    </div>
                    <div class="row">
                        <div class="col-4 text-center">
                            <?php if (!empty($child['photo_path'])): ?>
                                <img src="<?= e(upload_url($child['photo_path'])) ?>" class="rounded mb-2" style="width:100%;max-width:110px;aspect-ratio:1;object-fit:cover;" alt="Photo">
                            <?php else: ?>
                                <div class="rounded bg-light d-flex align-items-center justify-content-center mx-auto mb-2" style="width:100px;height:100px;">
                                    <i class="bi bi-person-fill" style="font-size:44px;color:#ccc;"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-8">
                            <table class="table table-sm table-borderless mb-0">
                                <tr><td class="text-muted">Name</td><td class="fw-semibold"><?= e($child['full_name']) ?></td></tr>
                                <tr><td class="text-muted">Gender</td><td><?= e(GENDER_OPTIONS[$child['gender']] ?? $child['gender']) ?></td></tr>
                                <tr><td class="text-muted">Roll</td><td>#<?= e($child['roll_number'] ?? '—') ?></td></tr>
                                <tr><td class="text-muted">Admission No.</td><td>#<?= e($child['admission_number']) ?></td></tr>
                                <tr><td class="text-muted">Admission Date</td><td><?= e(format_date($child['admission_date'])) ?></td></tr>
                                <tr><td class="text-muted">Class</td><td><?= e($child['class_name'] ?? '—') ?></td></tr>
                                <tr><td class="text-muted">Section</td><td><?= e($child['section_name'] ?? '—') ?></td></tr>
                            </table>
                        </div>
                    </div>
                    <div class="mt-2 d-flex gap-2">
                        <a href="<?= e(url('parent/children/' . $child['id'])) ?>" class="btn btn-sm btn-outline-primary" title="View full profile"><i class="bi bi-eye-fill"></i></a>
                        <a href="<?= e(url('parent/children/' . $child['id'])) ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Print (opens profile — use your browser's Print)"><i class="bi bi-printer-fill"></i></a>
                        <a href="<?= e(url('parent/children/' . $child['id'] . '/report-card')) ?>" class="btn btn-sm btn-outline-secondary" title="Download report card"><i class="bi bi-download"></i></a>
                        <button type="button" class="btn btn-sm btn-outline-secondary js-share-child" data-url="<?= e(url('parent/children/' . $child['id'])) ?>" title="Share"><i class="bi bi-share-fill"></i></button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Stat cards + Notice board -->
    <div class="col-lg-6">
        <div class="row g-3 mb-3">
            <div class="col-6 col-xl-4">
                <div class="card stat-card h-100" style="border-left-color:#e74a3b;">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon" style="background:#e74a3b;"><i class="bi bi-cash-coin"></i></div>
                        <div><h3><?= e(format_currency($stats['due_fees'])) ?></h3><div class="stat-label">Due Fees</div></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-4">
                <div class="card stat-card h-100" style="border-left-color:#1cc88a;">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon" style="background:#1cc88a;"><i class="bi bi-pencil-square"></i></div>
                        <div><h3><?= (int) $stats['upcoming_exams'] ?></h3><div class="stat-label">Upcoming Exams</div></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-4">
                <div class="card stat-card h-100" style="border-left-color:#4e73df;">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon" style="background:#4e73df;"><i class="bi bi-award-fill"></i></div>
                        <div><h3><?= (int) $stats['results_published'] ?></h3><div class="stat-label">Results Published</div></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-4">
                <div class="card stat-card h-100" style="border-left-color:#36b9cc;">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon" style="background:#36b9cc;"><i class="bi bi-cash-stack"></i></div>
                        <div><h3><?= e(format_currency($stats['total_paid'])) ?></h3><div class="stat-label">Total Fees Paid</div></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-4">
                <div class="card stat-card h-100" style="border-left-color:#f6c23e;">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon" style="background:#f6c23e;"><i class="bi bi-calendar-check-fill"></i></div>
                        <div><h3><?= e($stats['attendance_pct']) ?>%</h3><div class="stat-label">Attendance</div></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-4">
                <div class="card stat-card h-100" style="border-left-color:#858796;">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon" style="background:#858796;"><i class="bi bi-journal-check"></i></div>
                        <div><h3><?= (int) $stats['pending_homework'] ?></h3><div class="stat-label">Homework Due</div></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h5 class="fw-bold mb-3"><i class="bi bi-megaphone-fill me-2"></i>Notice Board</h5>
                <?php if (empty($notices)): ?>
                    <p class="text-muted mb-0">No notices published yet.</p>
                <?php else: ?>
                    <?php foreach ($notices as $notice): ?>
                        <div class="mb-3 pb-3 border-bottom">
                            <div class="d-flex justify-content-between">
                                <span class="fw-semibold"><?= e($notice['title']) ?></span>
                                <small class="text-muted"><?= e(format_datetime($notice['published_at'])) ?></small>
                            </div>
                            <p class="mb-0 small text-muted"><?= e(mb_strimwidth($notice['body'], 0, 140, '…')) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Tabs -->
<div class="card">
    <div class="card-header bg-white">
        <ul class="nav nav-tabs card-header-tabs" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#fees" type="button"><i class="bi bi-receipt me-1"></i>Fees</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#academics" type="button"><i class="bi bi-mortarboard me-1"></i>Academics</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#attendance-tab" type="button"><i class="bi bi-calendar-check me-1"></i>Attendance</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#homework" type="button"><i class="bi bi-journal-check me-1"></i>Homework</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#calendar" type="button"><i class="bi bi-calendar-event me-1"></i>Calendar</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#communication" type="button"><i class="bi bi-chat-dots me-1"></i>Communication</button></li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content">

            <!-- Fees & Payment History -->
            <div class="tab-pane fade show active" id="fees">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="fw-bold mb-0">Fee & Payment History</h5>
                    <input type="text" class="form-control form-control-sm w-auto js-table-filter" data-target="feesTable" placeholder="Search fee records...">
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="feesTable">
                        <thead><tr><th>Child</th><th>Fee ID</th><th>Fee Type</th><th>Amount Due</th><th>Amount Paid</th><th>Status</th><th>Due Date</th></tr></thead>
                        <tbody>
                            <?php foreach ($children as $child): ?>
                                <?php $fees = $feesByChild[$child['id']] ?? []; ?>
                                <?php foreach ($fees as $fee): ?>
                                    <tr>
                                        <td><?= e($child['full_name']) ?></td>
                                        <td>#<?= e($fee['id']) ?></td>
                                        <td><?= e($fee['fee_type_name'] ?? '—') ?></td>
                                        <td><?= e(format_currency($fee['amount_due'])) ?></td>
                                        <td><?= e(format_currency($fee['amount_paid'])) ?></td>
                                        <td><span class="badge <?= status_badge_class($fee['status']) ?>"><?= e(ucfirst($fee['status'])) ?></span></td>
                                        <td><?= e(format_date($fee['due_date'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                            <?php if (empty($children)): ?><tr><td colspan="7" class="text-muted">No data yet.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <h6 class="fw-bold mt-4 mb-2">Payment Receipts</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead><tr><th>Child</th><th>Receipt No.</th><th>Amount</th><th>Method</th><th>Paid On</th></tr></thead>
                        <tbody>
                            <?php foreach ($children as $child): ?>
                                <?php foreach (($paymentsByChild[$child['id']] ?? []) as $p): ?>
                                    <tr>
                                        <td><?= e($child['full_name']) ?></td>
                                        <td><?= e($p['receipt_number']) ?></td>
                                        <td><?= e(format_currency($p['amount'])) ?></td>
                                        <td><?= e(PAYMENT_MODES[$p['payment_mode']] ?? ucfirst($p['payment_mode'])) ?></td>
                                        <td><?= e(format_datetime($p['paid_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Academic Information -->
            <div class="tab-pane fade" id="academics">
                <?php foreach ($children as $child): ?>
                    <h6 class="fw-bold mt-2"><?= e($child['full_name']) ?></h6>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">Latest Exam Results</span>
                        <a href="<?= e(url('parent/children/' . $child['id'] . '/report-card')) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i> Download Report Card</a>
                    </div>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-hover">
                            <thead><tr><th>Exam</th><th>Subject</th><th>Date</th><th>Marks</th><th>Max</th><th>Grade</th></tr></thead>
                            <tbody>
                                <?php $results = $resultsByChild[$child['id']] ?? []; ?>
                                <?php if (empty($results)): ?><tr><td colspan="6" class="text-muted">No results published yet.</td></tr><?php endif; ?>
                                <?php foreach ($results as $r): ?>
                                    <tr>
                                        <td><?= e($r['exam_name']) ?></td>
                                        <td><?= e($r['subject_name']) ?></td>
                                        <td><?= e(format_date($r['exam_date'])) ?></td>
                                        <td><?= e($r['marks_obtained']) ?></td>
                                        <td><?= e($r['max_marks']) ?></td>
                                        <td><span class="badge bg-info text-dark"><?= e($r['grade'] ?? '—') ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <span class="text-muted small">Class Timetable</span>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm">
                            <thead><tr><th>Day</th><th>Time</th><th>Subject</th><th>Teacher</th><th>Room</th></tr></thead>
                            <tbody>
                                <?php $tt = $timetableByChild[$child['id']] ?? []; ?>
                                <?php if (empty($tt)): ?><tr><td colspan="5" class="text-muted">No timetable published yet.</td></tr><?php endif; ?>
                                <?php foreach ($tt as $slot): ?>
                                    <tr>
                                        <td><?= e(DAYS_OF_WEEK[$slot['day_of_week']] ?? $slot['day_of_week']) ?></td>
                                        <td><?= e(substr($slot['start_time'], 0, 5)) ?> – <?= e(substr($slot['end_time'], 0, 5)) ?></td>
                                        <td><?= e($slot['subject_name'] ?? '—') ?></td>
                                        <td><?= e($slot['teacher_name'] ?? '—') ?></td>
                                        <td><?= e($slot['room_number'] ?? '—') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <span class="text-muted small">Exam Schedule</span>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm">
                            <thead><tr><th>Exam</th><th>Subject</th><th>Date</th><th>Time</th><th>Room</th></tr></thead>
                            <tbody>
                                <?php $es = $examScheduleByChild[$child['id']] ?? []; ?>
                                <?php if (empty($es)): ?><tr><td colspan="5" class="text-muted">No exam schedule published yet.</td></tr><?php endif; ?>
                                <?php foreach ($es as $row): ?>
                                    <tr>
                                        <td><?= e($row['exam_name']) ?></td>
                                        <td><?= e($row['subject_name']) ?></td>
                                        <td><?= e(format_date($row['exam_date'])) ?></td>
                                        <td><?= !empty($row['start_time']) ? e(substr($row['start_time'], 0, 5)) : '—' ?></td>
                                        <td><?= e($row['room_number'] ?? '—') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($children)): ?><p class="text-muted">No data yet.</p><?php endif; ?>
            </div>

            <!-- Attendance -->
            <div class="tab-pane fade" id="attendance-tab">
                <?php foreach ($children as $child): ?>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <h6 class="fw-bold mb-0"><?= e($child['full_name']) ?></h6>
                        <span class="badge bg-primary fs-6"><?= e($attendancePctByChild[$child['id']] ?? 0) ?>% this month</span>
                    </div>
                    <?php $ms = $attendanceMonthlyByChild[$child['id']] ?? ['present' => 0, 'absent' => 0, 'late' => 0, 'leave' => 0]; ?>
                    <div class="row g-2 my-2">
                        <div class="col-3"><div class="border rounded p-2 text-center"><div class="fw-bold text-success"><?= (int) $ms['present'] ?></div><div class="small text-muted">Present</div></div></div>
                        <div class="col-3"><div class="border rounded p-2 text-center"><div class="fw-bold text-danger"><?= (int) $ms['absent'] ?></div><div class="small text-muted">Absent</div></div></div>
                        <div class="col-3"><div class="border rounded p-2 text-center"><div class="fw-bold text-warning"><?= (int) $ms['late'] ?></div><div class="small text-muted">Late</div></div></div>
                        <div class="col-3"><div class="border rounded p-2 text-center"><div class="fw-bold text-info"><?= (int) $ms['leave'] ?></div><div class="small text-muted">Leave</div></div></div>
                    </div>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm">
                            <thead><tr><th>Date</th><th>Status</th><th>Remarks</th></tr></thead>
                            <tbody>
                                <?php $hist = $attendanceHistoryByChild[$child['id']] ?? []; ?>
                                <?php if (empty($hist)): ?><tr><td colspan="3" class="text-muted">No attendance recorded yet.</td></tr><?php endif; ?>
                                <?php foreach ($hist as $rec): ?>
                                    <tr>
                                        <td><?= e(format_date($rec['date'])) ?></td>
                                        <td><span class="badge <?= status_badge_class($rec['status']) ?>"><?= e(ATTENDANCE_STATUSES[$rec['status']] ?? ucfirst($rec['status'])) ?></span></td>
                                        <td class="text-muted"><?= e($rec['remarks'] ?? '—') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($children)): ?><p class="text-muted">No data yet.</p><?php endif; ?>
            </div>

            <!-- Homework & Assignments -->
            <div class="tab-pane fade" id="homework">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="fw-bold mb-0">Homework & Assignments</h5>
                    <input type="text" class="form-control form-control-sm w-auto js-table-filter" data-target="homeworkTable" placeholder="Search homework...">
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="homeworkTable">
                        <thead><tr><th>Child</th><th>Subject</th><th>Title</th><th>Due Date</th><th>Status</th><th>Grade</th><th>Attachments</th></tr></thead>
                        <tbody>
                            <?php foreach ($children as $child): ?>
                                <?php foreach (($homeworkByChild[$child['id']] ?? []) as $hw): ?>
                                    <tr>
                                        <td><?= e($child['full_name']) ?></td>
                                        <td><?= e($hw['subject_name'] ?? '—') ?></td>
                                        <td><?= e($hw['title']) ?></td>
                                        <td><?= e(format_date($hw['due_date'])) ?></td>
                                        <td><span class="badge <?= status_badge_class($hw['submission_status'] ?? 'pending') ?>"><?= e(HOMEWORK_SUBMISSION_STATUSES[$hw['submission_status'] ?? 'pending'] ?? 'Pending') ?></span></td>
                                        <td><?= e($hw['grade'] ?? '—') ?></td>
                                        <td>
                                            <?php if (!empty($hw['file_path'])): ?><a href="<?= e(upload_url($hw['file_path'])) ?>" target="_blank" class="small">Homework file</a><?php endif; ?>
                                            <?php if (!empty($hw['submission_file_path'])): ?><br><a href="<?= e(upload_url($hw['submission_file_path'])) ?>" target="_blank" class="small">Submitted file</a><?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                            <?php if (empty($children)): ?><tr><td colspan="7" class="text-muted">No data yet.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- School Calendar & Events -->
            <div class="tab-pane fade" id="calendar">
                <h5 class="fw-bold mb-2">Upcoming Events & Holidays</h5>
                <div class="table-responsive mb-4">
                    <table class="table table-sm table-hover">
                        <thead><tr><th>Title</th><th>Type</th><th>Date</th></tr></thead>
                        <tbody>
                            <?php if (empty($upcomingEvents)): ?><tr><td colspan="3" class="text-muted">No upcoming events.</td></tr><?php endif; ?>
                            <?php foreach ($upcomingEvents as $ev): ?>
                                <tr>
                                    <td><?= e($ev['title']) ?></td>
                                    <td><span class="badge <?= status_badge_class($ev['type'] ?? '') ?>"><?= e(EVENT_TYPES[$ev['type']] ?? ucfirst($ev['type'] ?? '')) ?></span></td>
                                    <td><?= e(format_date($ev['start_date'] ?? $ev['event_date'] ?? null)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (array_filter($transportByChild ?? [])): ?>
                <h5 class="fw-bold mb-2"><i class="bi bi-bus-front-fill me-1"></i>Transport Status</h5>
                <div class="row g-3 mb-2">
                    <?php foreach ($children as $child): ?>
                        <?php $tr = $transportByChild[$child['id']] ?? false; ?>
                        <?php if ($tr): ?>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="fw-bold"><?= e($child['full_name']) ?></h6>
                                    <div class="small text-muted mb-1">Route</div>
                                    <div class="mb-2"><?= e($tr['route_name']) ?></div>
                                    <div class="small text-muted mb-1">Pickup Stop</div>
                                    <div class="mb-2"><?= e($tr['pickup_stop'] ?? '—') ?></div>
                                    <div class="small text-muted mb-1">Vehicle</div>
                                    <div><?= e($tr['vehicle_number'] ?? '—') ?><?= !empty($tr['driver_name']) ? ' — Driver: ' . e($tr['driver_name']) : '' ?></div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <p class="text-muted small">Live GPS tracking isn't connected yet — this shows the assigned route and vehicle only.</p>
                <?php endif; ?>
            </div>

            <!-- Communication -->
            <div class="tab-pane fade" id="communication">
                <div class="row g-3">
                    <div class="col-lg-7">
                        <h5 class="fw-bold mb-2">Messages</h5>
                        <div class="list-group mb-3" style="max-height:360px;overflow-y:auto;">
                            <?php if (empty($myMessages)): ?>
                                <p class="text-muted">No messages yet. Use "Message Teacher" above to start a conversation.</p>
                            <?php endif; ?>
                            <?php foreach ($myMessages as $m): ?>
                                <div class="list-group-item <?= (empty($m['is_read']) && $m['recipient_id'] == $parent['user_id']) ? 'fw-semibold' : '' ?>">
                                    <div class="d-flex justify-content-between">
                                        <span><?= $m['sender_id'] == $parent['user_id'] ? 'To: ' . e($m['recipient_name'] ?? '—') : 'From: ' . e($m['sender_name'] ?? '—') ?></span>
                                        <small class="text-muted"><?= e(format_datetime($m['created_at'])) ?></small>
                                    </div>
                                    <div class="small text-muted"><?= !empty($m['student_name']) ? 'Re: ' . e($m['student_name']) : '' ?></div>
                                    <div class="fw-semibold"><?= e($m['subject']) ?></div>
                                    <div class="small"><?= e($m['body']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <h6 class="fw-bold">Notices</h6>
                        <?php foreach ($notices as $notice): ?>
                            <div class="mb-2 pb-2 border-bottom">
                                <span class="fw-semibold small"><?= e($notice['title']) ?></span>
                                <span class="text-muted small"> — <?= e(format_datetime($notice['published_at'])) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="col-lg-5">
                        <h5 class="fw-bold mb-2">My Leave Requests</h5>
                        <div class="table-responsive mb-3">
                            <table class="table table-sm">
                                <thead><tr><th>Child</th><th>Dates</th><th>Status</th></tr></thead>
                                <tbody>
                                    <?php if (empty($myLeaveRequests)): ?><tr><td colspan="3" class="text-muted">No leave requests submitted.</td></tr><?php endif; ?>
                                    <?php foreach ($myLeaveRequests as $lr): ?>
                                        <tr>
                                            <td><?= e($lr['student_name']) ?></td>
                                            <td><?= e(format_date($lr['start_date'])) ?> – <?= e(format_date($lr['end_date'])) ?></td>
                                            <td><span class="badge <?= status_badge_class($lr['status']) ?>"><?= e(LEAVE_STATUSES[$lr['status']] ?? ucfirst($lr['status'])) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Apply Leave Modal -->
<div class="modal fade" id="applyLeaveModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= e(url('parent/leave')) ?>">
            <?= csrf_field() ?>
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Apply Leave</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Child</label>
                        <select name="student_id" class="form-select" required>
                            <?php foreach ($children as $child): ?>
                                <option value="<?= e($child['id']) ?>"><?= e($child['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3"><label class="form-label">Start Date</label><input type="date" name="start_date" class="form-control" required></div>
                        <div class="col-6 mb-3"><label class="form-label">End Date</label><input type="date" name="end_date" class="form-control" required></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Reason</label><textarea name="reason" class="form-control" rows="3" required></textarea></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">Submit Request</button></div>
            </div>
        </form>
    </div>
</div>

<!-- Message Teacher Modal -->
<div class="modal fade" id="messageTeacherModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= e(url('parent/messages')) ?>">
            <?= csrf_field() ?>
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Message Teacher</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Child</label>
                        <select name="student_id" class="form-select js-child-select" required>
                            <?php foreach ($children as $child): ?>
                                <option value="<?= e($child['id']) ?>"><?= e($child['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Teacher</label>
                        <select name="recipient_id" class="form-select" required>
                            <?php foreach ($children as $child): ?>
                                <?php foreach (($teachersByChild[$child['id']] ?? []) as $t): ?>
                                    <?php if (!empty($t['user_id'])): ?>
                                        <option value="<?= e($t['user_id']) ?>" data-child="<?= e($child['id']) ?>"><?= e($t['full_name']) ?> (<?= e($t['subject_name']) ?>) — <?= e($child['full_name']) ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Subject</label><input type="text" name="subject" class="form-control" maxlength="200" required></div>
                    <div class="mb-3"><label class="form-label">Message</label><textarea name="body" class="form-control" rows="4" required></textarea></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">Send</button></div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-table-filter').forEach(function (input) {
        input.addEventListener('keyup', function () {
            var term = input.value.toLowerCase();
            var table = document.getElementById(input.dataset.target);
            if (!table) return;
            table.querySelectorAll('tbody tr').forEach(function (row) {
                row.style.display = row.textContent.toLowerCase().indexOf(term) > -1 ? '' : 'none';
            });
        });
    });

    document.querySelectorAll('.js-share-child').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var url = window.location.origin + btn.dataset.url;
            if (navigator.share) {
                navigator.share({ url: url }).catch(function () {});
            } else {
                navigator.clipboard.writeText(url);
                if (window.SA) { window.SA.clipboard.copied(); }
                else { alert('Link copied to clipboard.'); }
            }
        });
    });
});
</script>
