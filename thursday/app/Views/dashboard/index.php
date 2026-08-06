<?php use App\Core\Auth; ?>
<style>
.marquee-viewport {
    white-space: nowrap;
}

.marquee-track {
    will-change: transform;
    animation: notices-marquee linear infinite;
    animation-duration: 20s; /* placeholder; JS sets the real value once content is measured */
}

.marquee-viewport:hover .marquee-track {
    animation-play-state: paused;
}

.marquee-track a {
    color: inherit;
    text-decoration: none;
}

.marquee-track a:hover {
    text-decoration: underline;
}

@keyframes notices-marquee {
    from {
        transform: translateX(var(--marquee-start, 100%));
    }

    to {
        transform: translateX(var(--marquee-end, -100%));
    }
}

.stat-card-clickable {
    cursor: pointer;
    transition: transform .15s ease, box-shadow .15s ease;
}

.stat-card-clickable:hover {
    transform: translateY(-2px) scale(1.015);
    box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .1);
}

.recent-row-clickable {
    cursor: pointer;
}

.notice-card-clickable {
    transition: box-shadow .15s ease;
    cursor: pointer;
}

.notice-card-clickable:hover {
    box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .08);
}
</style>

<!-- Important Notices marquee: loaded live via AJAX from NoticeController::marqueeData(),
     shows published + currently-in-window notices, high priority first. -->
<div class="card mb-4" id="noticesMarqueeCard" style="display:none;">
    <div class="card-body py-2 d-flex align-items-center gap-3">
        <span class="text-danger fw-bold text-nowrap"><i class="bi bi-megaphone-fill me-1"></i></span>
        <div class="marquee-viewport flex-grow-1 overflow-hidden position-relative">
            <div class="marquee-track d-flex align-items-center gap-5" id="noticesMarqueeTrack"></div>
        </div>
        <a href="<?= e(url('notices')) ?>" class="small text-nowrap">All Notices</a>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Welcome back, <?= e(Auth::name()) ?> 👋</h4>
        <p class="text-muted mb-0">Here's what's happening at your school today.</p>
    </div>
</div>

<script>
(function() {
    fetch(<?= json_encode(url('notices/marquee-data')) ?>)
        .then(function(r) {
            return r.json();
        })
        .then(function(notices) {
            if (!notices.length) {
                return;
            }
            var track = document.getElementById('noticesMarqueeTrack');
            var itemsHtml = notices.map(function(n) {
                var badge = n.priority === 'high' ? '<span class="badge bg-danger me-1">High</span>' :
                    '';
                return '<a href="' + n.url + '" class="text-nowrap">' + badge + n.title + '</a>';
            }).join('');
            track.innerHTML = itemsHtml;
            document.getElementById('noticesMarqueeCard').style.display = '';

            // Enter fully off-screen to the right and exit fully off-screen to the
            // left, so the ticker visibly glides in instead of appearing already
            // mid-scroll. Measured in real pixels (not %) so it lines up exactly
            // with this viewport's width regardless of how long the text is.
            var viewportWidth = track.parentElement.offsetWidth;
            var trackWidth = track.scrollWidth;
            track.style.setProperty('--marquee-start', viewportWidth + 'px');
            track.style.setProperty('--marquee-end', (-trackWidth) + 'px');

            // Speed is constant (px/sec) regardless of how much text there is —
            // previously the duration only scaled with notice *count*, so a
            // couple of long notices still finished in ~2s and flew by unreadably.
            var pixelsPerSecond = 70;
            var distance = viewportWidth + trackWidth;
            track.style.animationDuration = Math.max(8, distance / pixelsPerSecond) + 's';
        })
        .catch(function() {});
})();
</script>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <a href="<?= e(url('students')) ?>" class="text-decoration-none text-reset">
            <div class="card stat-card stat-card-clickable">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Active Students</div>
                        <h3><?= number_format($stats['total_students']) ?></h3>
                    </div>
                    <div class="stat-icon" style="background:#4e73df;"><i class="bi bi-people-fill"></i></div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-xl-3 col-md-6">
        <a href="<?= e(url('teachers')) ?>" class="text-decoration-none text-reset">
            <div class="card stat-card stat-card-clickable" style="border-left-color:#1cc88a;">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Active Teachers</div>
                        <h3><?= number_format($stats['total_teachers']) ?></h3>
                    </div>
                    <div class="stat-icon" style="background:#1cc88a;"><i class="bi bi-person-workspace"></i></div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-xl-3 col-md-6">
        <a href="<?= e(url('payments/daily-collection')) ?>" class="text-decoration-none text-reset">
            <div class="card stat-card stat-card-clickable" style="border-left-color:#36b9cc;">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Collected Today</div>
                        <h3><?= e(format_currency($stats['collected_today'])) ?></h3>
                        <small class="text-muted">Incl. library fines</small>
                    </div>
                    <div class="stat-icon" style="background:#36b9cc;"><i class="bi bi-cash-coin"></i></div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-xl-3 col-md-6">
        <a href="<?= e(url('fees/due')) ?>" class="text-decoration-none text-reset">
            <div class="card stat-card stat-card-clickable" style="border-left-color:#e74a3b;">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Outstanding Fees</div>
                        <h3><?= e(format_currency($stats['outstanding_fees'])) ?></h3>
                        <small class="text-muted">Incl. library fines</small>
                    </div>
                    <div class="stat-icon" style="background:#e74a3b;"><i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Attendance summary chart -->
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Today's Attendance</h6>
                <canvas id="attendanceChart" height="220"></canvas>
                <div class="d-flex justify-content-between mt-3 small text-muted">
                    <span><i class="bi bi-square-fill text-success"></i> Present
                        <?= (int) $attendanceToday['present'] ?></span>
                    <span><i class="bi bi-square-fill text-danger"></i> Absent
                        <?= (int) $attendanceToday['absent'] ?></span>
                    <span><i class="bi bi-square-fill text-warning"></i> Late
                        <?= (int) $attendanceToday['late'] ?></span>
                    <span><i class="bi bi-square-fill text-info"></i> Leave
                        <?= (int) $attendanceToday['leave'] ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Upcoming exams -->
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-file-earmark-text-fill me-1"></i> Upcoming Exams</h6>
                <?php if (empty($upcomingExams)): ?>
                <p class="text-muted small mb-0">No upcoming exams scheduled.</p>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($upcomingExams as $exam):
                            $daysLeft = (int) ceil((strtotime($exam['start_date']) - strtotime(date('Y-m-d'))) / 86400);
                            $isToday = $daysLeft === 0;
                        ?>
                    <li
                        class="list-group-item px-0 d-flex justify-content-between align-items-center <?= $isToday ? 'bg-warning-subtle rounded' : '' ?>">
                        <div>
                            <div class="fw-semibold">
                                <?= e($exam['name']) ?><?= $isToday ? ' <span class="badge bg-warning text-dark ms-1">Today</span>' : '' ?>
                            </div>
                            <small class="text-muted"><?= e($exam['class_name'] ?? 'All Classes') ?> &middot;
                                <?= e(format_date($exam['start_date'])) ?></small>
                        </div>
                        <span
                            class="badge bg-primary rounded-pill"><?= $isToday ? 'Today' : ($daysLeft . 'd left') ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Upcoming events -->
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-calendar-event-fill me-1"></i> Upcoming Events</h6>
                <?php if (empty($upcomingEvents)): ?>
                <p class="text-muted small mb-0">No upcoming events.</p>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php
                        $eventTypeColor = fn ($t) => match (strtolower((string) $t)) {
                            'holiday' => 'bg-danger-subtle text-danger', 'sports' => 'bg-success-subtle text-success',
                            'exam' => 'bg-warning-subtle text-warning', 'meeting' => 'bg-info-subtle text-info',
                            default => 'bg-primary-subtle text-primary',
                        };
                        foreach ($upcomingEvents as $event):
                            $daysLeft = (int) ceil((strtotime($event['start_date']) - strtotime(date('Y-m-d'))) / 86400);
                        ?>
                    <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold"><?= e($event['title']) ?></div>
                            <span
                                class="badge <?= $eventTypeColor($event['event_type']) ?> text-capitalize"><?= e($event['event_type']) ?></span>
                            <small class="text-muted ms-1"><?= $daysLeft <= 0 ? 'Today' : "in {$daysLeft}d" ?></small>
                        </div>
                        <span class="badge bg-secondary rounded-pill"><?= e(format_date($event['start_date'])) ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Recent students -->
    <div class="col-xl-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-people-fill me-1"></i> Recent Students</h6>
                    <a href="<?= e(url('students')) ?>" class="small">View all</a>
                </div>
                <?php if (empty($recentStudents)): ?>
                <p class="text-muted small mb-0">No students added yet.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle table-hover">
                        <thead>
                            <tr>
                                <th><?= e(t('th_sn')) ?></th>
                                <th><?= e(t('th_name')) ?></th>
                                <th><?= e(t('th_class')) ?></th>
                                <th><?= e(t('th_admission_no')) ?></th>
                                <th><?= e(t('th_status')) ?></th>
                                <th class="text-end"><?= e(t('th_action')) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentStudents as $sn => $s): ?>
                            <tr>
                                <td><?= $sn + 1 ?></td>
                                <td><a href="<?= e(url('students/' . $s['id'])) ?>"
                                        class="text-decoration-none fw-semibold"
                                        title="Student Database ID: <?= e($s['id']) ?>"><?= e($s['full_name']) ?></a>
                                </td>
                                <td><?= e(trim(($s['class_name'] ?? '-') . ' ' . ($s['section_name'] ?? ''))) ?></td>
                                <td><?= e($s['admission_number']) ?></td>
                                <td><span
                                        class="badge <?= status_badge_class($s['status']) ?>"><?= e(st($s['status'])) ?></span>
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="<?= e(url('students/' . $s['id'])) ?>"
                                        class="btn btn-sm btn-outline-secondary" title="View Profile"><i
                                            class="bi bi-eye-fill"></i></a>
                                    <a href="<?= e(url('students/' . $s['id'] . '/edit')) ?>"
                                        class="btn btn-sm btn-outline-primary" title="Edit"><i
                                            class="bi bi-pencil-fill"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent payments -->
    <div class="col-xl-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-cash-stack me-1"></i> Recent Payments</h6>
                    <a href="<?= e(url('payments')) ?>" class="small">View all</a>
                </div>
                <?php if (empty($recentPayments)): ?>
                <p class="text-muted small mb-0">No payments recorded yet.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th><?= e(t('th_student')) ?></th>
                                <th>Receipt #</th>
                                <th><?= e(t('th_amount')) ?></th>
                                <th><?= e(t('th_mode')) ?></th>
                                <th class="text-end"><?= e(t('th_receipt')) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentPayments as $p): ?>
                            <?php $profileUrl = !empty($p['student_id']) ? url('students/' . $p['student_id']) : null; ?>
                            <tr class="recent-row-clickable"
                                <?= $profileUrl ? 'onclick="window.location=\'' . e($profileUrl) . '\'" style="cursor:pointer;"' : '' ?>
                                title="<?= $profileUrl ? 'Open this student\'s profile' : '' ?>">
                                <td><?= e($p['student_name'] ?? '-') ?><?= !empty($p['student_id']) ? ' <span class="text-muted small" title="Student Database ID: ' . e($p['student_id']) . '">&#9432;</span>' : '' ?>
                                </td>
                                <td><?= e($p['receipt_number']) ?></td>
                                <td><?= e(format_currency($p['amount'])) ?></td>
                                <td class="text-capitalize"><?= e(str_replace('_', ' ', $p['payment_mode'])) ?></td>
                                <td class="text-end">
                                    <a href="<?= e(url('payments/receipt/' . $p['receipt_group'])) ?>"
                                        class="btn btn-sm btn-light" title="View Receipt"
                                        onclick="event.stopPropagation();"><i class="bi bi-eye"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($recentNotices)): ?>
<div class="row g-3 mt-1">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-megaphone-fill me-1"></i> Latest Notices</h6>
                    <a href="<?= e(url('notices')) ?>" class="small">View all</a>
                </div>
                <div class="row g-3">
                    <?php foreach ($recentNotices as $n):
                        $isNew = strtotime($n['published_at']) >= strtotime('-3 days');
                    ?>
                    <div class="col-md-4">
                        <a href="<?= e(url('notices/' . $n['id'])) ?>" class="text-decoration-none text-reset">
                            <div class="border rounded p-3 h-100 notice-card-clickable">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="fw-semibold mb-1"><?= e($n['title']) ?></div>
                                    <?php if ($isNew): ?><span class="badge bg-danger ms-1">New</span><?php endif; ?>
                                </div>
                                <div class="small text-muted mb-2"><?= e(format_date($n['published_at'])) ?></div>
                                <div class="small text-body">
                                    <?= e(mb_strimwidth(strip_tags($n['body']), 0, 120, '...')) ?></div>
                                <div class="small mt-2">Read More <i class="bi bi-arrow-right"></i></div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
new Chart(document.getElementById('attendanceChart'), {
    type: 'doughnut',
    data: {
        labels: ['Present', 'Absent', 'Late', 'Leave'],
        datasets: [{
            data: [
                <?= (int) $attendanceToday['present'] ?>,
                <?= (int) $attendanceToday['absent'] ?>,
                <?= (int) $attendanceToday['late'] ?>,
                <?= (int) $attendanceToday['leave'] ?>
            ],
            backgroundColor: ['#1cc88a', '#e74a3b', '#f6c23e', '#36b9cc'],
            borderWidth: 0
        }]
    },
    options: {
        plugins: {
            legend: {
                display: false
            }
        },
        cutout: '65%'
    }
});
</script>