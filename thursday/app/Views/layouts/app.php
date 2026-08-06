<?php
use App\Core\Auth;
$currentUser = Auth::user();
$currentRole = Auth::role();

// Computed here (rather than in every controller) so the topbar's counts
// and dropdowns are correct on every page, not just the dashboard.
$topbarNotifications = [];
$topbarUnreadNotifications = 0;
$topbarUnreadMessages = 0;
if ($currentUser) {
    $notificationModel = new \App\Models\Notification();
    $topbarNotifications = $notificationModel->forUser((int) $currentUser['id'], 5);
    $topbarUnreadNotifications = $notificationModel->unreadCount((int) $currentUser['id']);
    try {
        // Guarded: the `messages` table only exists after migration 005 has
        // been run. Degrade to 0 rather than error out every page for
        // anyone who hasn't applied it yet.
        $topbarUnreadMessages = (new \App\Models\Message())->unreadCount((int) $currentUser['id']);
    } catch (\Throwable $e) {
        $topbarUnreadMessages = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="<?= e(\App\Core\Lang::current()) ?>">

<head>
    <meta charset="UTF-8">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' | School ERP' : 'School ERP' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script>
        // Applied synchronously, before any CSS or body content renders, so a saved
        // dark-mode preference takes effect immediately instead of flashing light
        // mode first and then flipping (the rest of the toggle logic — including
        // saving the preference on click — stays in app.js, loaded at the bottom).
        (function () {
            var saved = localStorage.getItem('school_erp_theme');
            var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.setAttribute('data-bs-theme', saved || (prefersDark ? 'dark' : 'light'));
        })();
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Roboto+Condensed:ital,wght@0,100..900;1,100..900&family=Share+Tech&display=swap" rel="stylesheet">
    <link href="<?= e(asset('css/typography.css')) ?>" rel="stylesheet">
    <link href="<?= e(asset('css/app.css')) ?>" rel="stylesheet">
</head>

<body>
    <div class="app-wrapper">

        <!-- Mobile sidebar backdrop: shown only while the mobile menu is open; tapping it closes the menu -->
        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <i class="bi bi-mortarboard-fill"></i>
                <span><?= e(t('app_name')) ?></span>
                <button type="button" class="sidebar-close-btn d-lg-none" id="sidebarClose"
                    aria-label="Close navigation menu"><i class="bi bi-x-lg"></i></button>
            </div>
            <nav class="sidebar-nav">
                <a href="<?= e(url('dashboard')) ?>" class="nav-link <?= active_route('dashboard') ?>"><i
                        class="bi bi-speedometer2"></i><span><?= e(t('dashboard')) ?></span></a>

                <?php if (Auth::can('students')): ?>
                <div class="nav-heading"><?= e(t('nav_heading_academics')) ?></div>
                <a href="<?= e(url('students')) ?>" class="nav-link <?= active_route('students') ?>"><i
                        class="bi bi-people-fill"></i><span><?= e(t('students')) ?></span></a>
                <?php endif; ?>
                <?php if (Auth::can('teachers')): ?>
                <a href="<?= e(url('teachers')) ?>" class="nav-link <?= active_route('teachers') ?>"><i
                        class="bi bi-person-workspace"></i><span><?= e(t('teachers')) ?></span></a>
                <?php endif; ?>
                <?php if (Auth::can('parents')): ?>
                <a href="<?= e(url('parents')) ?>" class="nav-link <?= active_route('parents') ?>"><i
                        class="bi bi-person-hearts"></i><span><?= e(t('parents')) ?></span></a>
                <?php endif; ?>
                <?php
                $classesMenuOpen = active_route('classes');
                $sectionsMenuOpen = active_route('sections');
            ?>
                <?php if (Auth::can('classes')): ?>
                <a href="#classesSubmenu" class="nav-link <?= $classesMenuOpen ? 'active' : '' ?>"
                    data-bs-toggle="collapse" role="button" aria-expanded="<?= $classesMenuOpen ? 'true' : 'false' ?>">
                    <i class="bi bi-diagram-3-fill"></i><span><?= e(t('classes')) ?></span><i
                        class="bi bi-chevron-down ms-auto small"></i>
                </a>
                <div class="collapse <?= $classesMenuOpen ? 'show' : '' ?>" id="classesSubmenu">
                    <div class="nav-submenu">
                        <a href="<?= e(url('classes')) ?>"
                            class="nav-link nav-sublink <?= active_route('classes') ?>"><i
                                class="bi bi-list-ul"></i><span><?= e(t('all_classes')) ?></span></a>
                        <a href="<?= e(url('classes/create')) ?>"
                            class="nav-link nav-sublink <?= active_route('classes/create') ?>"><i
                                class="bi bi-plus-circle"></i><span><?= e(t('add_new_class')) ?></span></a>
                    </div>
                </div>
                <?php endif; ?>
                <?php if (Auth::can('sections')): ?>
                <a href="#sectionsSubmenu" class="nav-link <?= $sectionsMenuOpen ? 'active' : '' ?>"
                    data-bs-toggle="collapse" role="button" aria-expanded="<?= $sectionsMenuOpen ? 'true' : 'false' ?>">
                    <i class="bi bi-columns-gap"></i><span><?= e(t('sections')) ?></span><i
                        class="bi bi-chevron-down ms-auto small"></i>
                </a>
                <div class="collapse <?= $sectionsMenuOpen ? 'show' : '' ?>" id="sectionsSubmenu">
                    <div class="nav-submenu">
                        <a href="<?= e(url('sections')) ?>"
                            class="nav-link nav-sublink <?= active_route('sections') ?>"><i
                                class="bi bi-list-ul"></i><span><?= e(t('all_sections')) ?></span></a>
                        <a href="<?= e(url('sections/create')) ?>"
                            class="nav-link nav-sublink <?= active_route('sections/create') ?>"><i
                                class="bi bi-plus-circle"></i><span><?= e(t('add_new_section')) ?></span></a>
                    </div>
                </div>
                <?php endif; ?>
                <?php if (Auth::can('subjects')): ?>
                <a href="<?= e(url('subjects')) ?>" class="nav-link <?= active_route('subjects') ?>"><i
                        class="bi bi-journal-bookmark-fill"></i><span><?= e(t('subjects')) ?></span></a>
                <?php endif; ?>
                <?php if (Auth::can('timetable')): ?>
                <a href="<?= e(url('timetable')) ?>" class="nav-link <?= active_route('timetable') ?>"><i
                        class="bi bi-calendar-week-fill"></i><span><?= e(t('timetable')) ?></span></a>
                <?php endif; ?>

                <?php if (Auth::can('attendance') || Auth::can('teacher_attendance')): ?>
                <div class="nav-heading"><?= e(t('nav_heading_attendance')) ?></div>
                <?php endif; ?>
                <?php $attendanceMenuOpen = active_route('attendance'); ?>
                <?php if (Auth::can('attendance')): ?>
                <a href="#attendanceSubmenu" class="nav-link <?= $attendanceMenuOpen ? 'active' : '' ?>"
                    data-bs-toggle="collapse" role="button"
                    aria-expanded="<?= $attendanceMenuOpen ? 'true' : 'false' ?>">
                    <i class="bi bi-calendar-check-fill"></i><span><?= e(t('student_attendance')) ?></span><i
                        class="bi bi-chevron-down ms-auto small"></i>
                </a>
                <div class="collapse <?= $attendanceMenuOpen ? 'show' : '' ?>" id="attendanceSubmenu">
                    <div class="nav-submenu">
                        <a href="<?= e(url('attendance')) ?>"
                            class="nav-link nav-sublink <?= active_route('attendance') && !active_route('attendance/mark') && !active_route('attendance/reports') ? 'active' : '' ?>"><i
                                class="bi bi-search"></i><span><?= e(t('find_student')) ?></span></a>
                        <a href="<?= e(url('attendance/mark')) ?>"
                            class="nav-link nav-sublink <?= active_route('attendance/mark') ?>"><i
                                class="bi bi-pencil-square"></i><span><?= e(t('mark_attendance')) ?></span></a>
                        <a href="<?= e(url('attendance/reports')) ?>"
                            class="nav-link nav-sublink <?= active_route('attendance/reports') ?>"><i
                                class="bi bi-bar-chart"></i><span><?= e(t('reports')) ?></span></a>
                    </div>
                </div>
                <?php endif; ?>
                <?php if (Auth::can('teacher_attendance')): ?>
                <a href="<?= e(url('teacher-attendance')) ?>"
                    class="nav-link <?= active_route('teacher-attendance') ?>"><i
                        class="bi bi-person-check-fill"></i><span><?= e(t('teacher_attendance')) ?></span></a>
                <?php endif; ?>

                <?php
                $canExamSchedule = Auth::can('exam_schedule');
                $canExamGrades = Auth::can('exam_grades');
                $canExamTypes = Auth::can('exam_types');
                $examMenuOpen = active_route('exam-schedule') || active_route('exam-grades') || active_route('exam-types');
            ?>
                <?php if ($canExamSchedule || $canExamGrades || $canExamTypes || Auth::can('marks')): ?>
                <div class="nav-heading"><?= e(t('nav_heading_examinations')) ?></div>
                <?php endif; ?>
                <?php if ($canExamSchedule || $canExamGrades || $canExamTypes): ?>
                <a href="#examSubmenu" class="nav-link <?= $examMenuOpen ? 'active' : '' ?>" data-bs-toggle="collapse"
                    role="button" aria-expanded="<?= $examMenuOpen ? 'true' : 'false' ?>">
                    <i class="bi bi-file-earmark-text-fill"></i><span><?= e(t('exam')) ?></span><i
                        class="bi bi-chevron-down ms-auto small"></i>
                </a>
                <div class="collapse <?= $examMenuOpen ? 'show' : '' ?>" id="examSubmenu">
                    <div class="nav-submenu">
                        <?php if ($canExamSchedule): ?>
                        <a href="<?= e(url('exam-schedule')) ?>"
                            class="nav-link nav-sublink <?= active_route('exam-schedule') ?>"><i
                                class="bi bi-calendar-week"></i><span><?= e(t('exam_schedule')) ?></span></a>
                        <?php endif; ?>
                        <?php if ($canExamGrades): ?>
                        <a href="<?= e(url('exam-grades')) ?>"
                            class="nav-link nav-sublink <?= active_route('exam-grades') ?>"><i
                                class="bi bi-award"></i><span><?= e(t('exam_grades')) ?></span></a>
                        <?php endif; ?>
                        <?php if ($canExamTypes): ?>
                        <a href="<?= e(url('exam-types')) ?>"
                            class="nav-link nav-sublink <?= active_route('exam-types') ?>"><i
                                class="bi bi-tags"></i><span><?= e(t('exam_types')) ?></span></a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php if (Auth::can('marks')): ?>
                <a href="<?= e(url('marks')) ?>" class="nav-link <?= active_route('marks') ?>"><i
                        class="bi bi-award-fill"></i><span><?= e(t('marks_and_grades')) ?></span></a>
                <?php endif; ?>

                <?php if (Auth::can('fees') || Auth::can('payments')): ?>
                <div class="nav-heading"><?= e(t('nav_heading_finance')) ?></div>
                <?php endif; ?>
                <?php $feesMenuOpen = active_route('fee-types') || active_route('fees'); ?>
                <?php if (Auth::can('fees')): ?>
                <a href="#feesSubmenu" class="nav-link <?= $feesMenuOpen ? 'active' : '' ?>" data-bs-toggle="collapse"
                    role="button" aria-expanded="<?= $feesMenuOpen ? 'true' : 'false' ?>">
                    <i class="bi bi-cash-stack"></i><span><?= e(t('fees')) ?></span><i class="bi bi-chevron-down ms-auto small"></i>
                </a>
                <div class="collapse <?= $feesMenuOpen ? 'show' : '' ?>" id="feesSubmenu">
                    <div class="nav-submenu">
                        <a href="<?= e(url('fees/bulk-assign')) ?>"
                            class="nav-link nav-sublink <?= active_route('fees') ?>"><i
                                class="bi bi-people"></i><span><?= e(t('bulk_fee_assignment')) ?></span></a>
                        <a href="<?= e(url('fee-types')) ?>"
                            class="nav-link nav-sublink <?= active_route('fee-types') ?>"><i
                                class="bi bi-tags"></i><span><?= e(t('fee_types')) ?></span></a>
                    </div>
                </div>
                <?php endif; ?>
                <?php $paymentsMenuOpen = active_route('payments'); ?>
                <?php if (Auth::can('payments')): ?>
                <a href="#paymentsSubmenu" class="nav-link <?= $paymentsMenuOpen ? 'active' : '' ?>"
                    data-bs-toggle="collapse" role="button" aria-expanded="<?= $paymentsMenuOpen ? 'true' : 'false' ?>">
                    <i class="bi bi-credit-card-fill"></i><span><?= e(t('payment_collection')) ?></span><i
                        class="bi bi-chevron-down ms-auto small"></i>
                </a>
                <div class="collapse <?= $paymentsMenuOpen ? 'show' : '' ?>" id="paymentsSubmenu">
                    <div class="nav-submenu">
                        <a href="<?= e(url('payments/collect')) ?>" class="nav-link nav-sublink"><i
                                class="bi bi-cash-coin"></i><span><?= e(t('collect_payment')) ?></span></a>
                        <a href="<?= e(url('payments')) ?>" class="nav-link nav-sublink"><i
                                class="bi bi-receipt"></i><span><?= e(t('payment_report')) ?></span></a>
                        <a href="<?= e(url('payments/daily-collection')) ?>" class="nav-link nav-sublink"><i
                                class="bi bi-calendar-check"></i><span><?= e(t('daily_collection_report')) ?></span></a>
                        <a href="<?= e(url('payments/class-wise')) ?>" class="nav-link nav-sublink"><i
                                class="bi bi-bar-chart"></i><span><?= e(t('class_wise_fee_report')) ?></span></a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (Auth::can('library') || Auth::can('hostel') || Auth::can('transport') || Auth::can('inventory')): ?>
                <div class="nav-heading"><?= e(t('nav_heading_facilities')) ?></div>
                <?php endif; ?>
                <?php $libraryMenuOpen = active_route('library'); ?>
                <?php if (Auth::can('library')): ?>
                <a href="#librarySubmenu" class="nav-link <?= $libraryMenuOpen ? 'active' : '' ?>"
                    data-bs-toggle="collapse" role="button" aria-expanded="<?= $libraryMenuOpen ? 'true' : 'false' ?>">
                    <i class="bi bi-book-fill"></i><span><?= e(t('library')) ?></span><i class="bi bi-chevron-down ms-auto small"></i>
                </a>
                <div class="collapse <?= $libraryMenuOpen ? 'show' : '' ?>" id="librarySubmenu">
                    <div class="nav-submenu">
                        <a href="<?= e(url('library')) ?>" class="nav-link nav-sublink"><i
                                class="bi bi-speedometer2"></i><span><?= e(t('dashboard')) ?></span></a>
                        <a href="<?= e(url('library/books')) ?>"
                            class="nav-link nav-sublink <?= active_route('library/books') ?>"><i
                                class="bi bi-journal-bookmark"></i><span><?= e(t('manage_books')) ?></span></a>
                        <a href="<?= e(url('library/books/create')) ?>"
                            class="nav-link nav-sublink <?= active_route('library/books/create') ?>"><i
                                class="bi bi-plus-circle"></i><span><?= e(t('add_book')) ?></span></a>
                        <a href="<?= e(url('library/categories')) ?>"
                            class="nav-link nav-sublink <?= active_route('library/categories') ?>"><i
                                class="bi bi-tags"></i><span><?= e(t('categories')) ?></span></a>
                        <a href="<?= e(url('library/authors')) ?>"
                            class="nav-link nav-sublink <?= active_route('library/authors') ?>"><i
                                class="bi bi-person-lines-fill"></i><span><?= e(t('authors')) ?></span></a>
                        <a href="<?= e(url('library/publishers')) ?>"
                            class="nav-link nav-sublink <?= active_route('library/publishers') ?>"><i
                                class="bi bi-building"></i><span><?= e(t('publishers')) ?></span></a>
                        <a href="<?= e(url('library/issue')) ?>"
                            class="nav-link nav-sublink <?= active_route('library/issue') ?>"><i
                                class="bi bi-box-arrow-right"></i><span><?= e(t('issue_book')) ?></span></a>
                        <a href="<?= e(url('library/transactions')) ?>"
                            class="nav-link nav-sublink <?= active_route('library/transactions') ?>"><i
                                class="bi bi-arrow-return-left"></i><span><?= e(t('issued_return')) ?></span></a>
                        <a href="<?= e(url('library/reservations')) ?>"
                            class="nav-link nav-sublink <?= active_route('library/reservations') ?>"><i
                                class="bi bi-bookmark-star"></i><span><?= e(t('reservations')) ?></span></a>
                    </div>
                </div>
                <?php endif; ?>
                <?php if (Auth::can('hostel')): ?>
                <a href="<?= e(url('hostel')) ?>" class="nav-link <?= active_route('hostel') ?>"><i
                        class="bi bi-building-fill"></i><span><?= e(t('hostel')) ?></span></a>
                <?php endif; ?>
                <?php if (Auth::can('transport')): ?>
                <a href="<?= e(url('transport')) ?>" class="nav-link <?= active_route('transport') ?>"><i
                        class="bi bi-bus-front-fill"></i><span><?= e(t('transport')) ?></span></a>
                <?php endif; ?>
                <?php if (Auth::can('inventory')): ?>
                <a href="<?= e(url('inventory')) ?>" class="nav-link <?= active_route('inventory') ?>"><i
                        class="bi bi-box-seam-fill"></i><span><?= e(t('inventory')) ?></span></a>
                <?php endif; ?>

                <div class="nav-heading"><?= e(t('nav_heading_communication')) ?></div>
                <?php if (Auth::can('homework')): ?>
                <a href="<?= e(url('homework')) ?>" class="nav-link <?= active_route('homework') ?>"><i
                        class="bi bi-journal-check"></i><span><?= e(t('homework')) ?></span></a>
                <?php endif; ?>
                <?php if (Auth::can('leaves')): ?>
                <a href="<?= e(url('leaves')) ?>" class="nav-link <?= active_route('leaves') ?>"><i
                        class="bi bi-envelope-paper-fill"></i><span><?= e(t('leave_requests')) ?></span></a>
                <?php endif; ?>
                <a href="<?= e(url('notices')) ?>" class="nav-link <?= active_route('notices') ?>"><i
                        class="bi bi-megaphone-fill"></i><span><?= e(t('notices')) ?></span></a>
                <a href="<?= e(url('events')) ?>" class="nav-link <?= active_route('events') ?>"><i
                        class="bi bi-calendar-event-fill"></i><span><?= e(t('events')) ?></span></a>

                <?php if (Auth::can('reports') || Auth::can('settings') || Auth::can('academic_years') || Auth::can('activity_logs')): ?>
                <div class="nav-heading"><?= e(t('nav_heading_administration')) ?></div>
                <?php endif; ?>
                <?php if (Auth::can('reports')): ?>
                <a href="<?= e(url('reports')) ?>" class="nav-link <?= active_route('reports') ?>"><i
                        class="bi bi-graph-up"></i><span><?= e(t('reports')) ?></span></a>
                <?php endif; ?>
                <?php if (Auth::can('academic_years')): ?>
                <a href="<?= e(url('academic-years')) ?>" class="nav-link <?= active_route('academic-years') ?>"><i
                        class="bi bi-calendar-range-fill"></i><span><?= e(t('academic_years')) ?></span></a>
                <?php endif; ?>
                <?php if (Auth::can('activity_logs')): ?>
                <a href="<?= e(url('activity-logs')) ?>" class="nav-link <?= active_route('activity-logs') ?>"><i
                        class="bi bi-clock-history"></i><span><?= e(t('activity_logs')) ?></span></a>
                <?php endif; ?>
                <?php if (Auth::can('settings')): ?>
                <a href="<?= e(url('settings')) ?>" class="nav-link <?= active_route('settings') ?>"><i
                        class="bi bi-gear-fill"></i><span><?= e(t('settings')) ?></span></a>
                <a href="<?= e(url('settings/registration-security')) ?>" class="nav-link <?= active_route('settings/registration-security') ?>"><i
                        class="bi bi-shield-lock-fill"></i><span><?= e(t('registration_security')) ?></span></a>
                <a href="<?= e(url('staff-invites')) ?>" class="nav-link <?= active_route('staff-invites') ?>"><i
                        class="bi bi-ticket-perforated-fill"></i><span><?= e(t('staff_invites')) ?></span></a>
                <a href="<?= e(url('admin/register')) ?>" class="nav-link <?= active_route('admin/register') ?>"><i
                        class="bi bi-person-plus-fill"></i><span><?= e(t('register_new_account')) ?></span></a>
                <?php endif; ?>
            </nav>
        </aside>

        <!-- Main content -->
        <div class="main-content">
            <!-- Topbar -->
            <header class="topbar">
                <button class="btn btn-sm btn-light d-lg-none" id="sidebarToggle" aria-label="<?= e(t('toggle_navigation_menu')) ?>" aria-expanded="false" aria-controls="sidebar"><i class="bi bi-list" id="sidebarToggleIcon"></i></button>

                <nav aria-label="breadcrumb" class="d-none d-md-block">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?= e(url('dashboard')) ?>"><?= e(t('home')) ?></a></li>
                        <?php if (isset($pageTitle)): ?>
                        <li class="breadcrumb-item active" aria-current="page"><?= e($pageTitle) ?></li>
                        <?php endif; ?>
                    </ol>
                </nav>

                <div class="topbar-right ms-auto d-flex align-items-center gap-3">
                    <?php if ($currentUser): ?>
                    <form method="GET" action="<?= e(url('search')) ?>" class="d-none d-md-block position-relative" role="search" id="globalSearchForm" autocomplete="off">
                        <input type="search" name="q" id="globalSearchInput" class="form-control form-control-sm" placeholder="<?= e(t('search_here')) ?>"
                            value="<?= e($_GET['q'] ?? '') ?>" style="width:220px;"
                            role="combobox" aria-expanded="false" aria-controls="globalSearchResults" aria-autocomplete="list">
                        <div id="globalSearchResults" class="global-search-dropdown shadow-sm" role="listbox" hidden></div>
                    </form>
                    <?php endif; ?>

                    <div class="dropdown">
                        <button class="btn btn-sm btn-light" data-bs-toggle="dropdown" title="<?= e(t('language')) ?>">
                            <i class="bi bi-globe2"></i>
                            <span class="d-none d-lg-inline"><?= e(\App\Core\Lang::label()) ?></span></button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php foreach (\App\Core\Lang::SUPPORTED as $langCode): ?>
                            <li>
                                <a class="dropdown-item d-flex align-items-center justify-content-between <?= $langCode === \App\Core\Lang::current() ? 'active' : '' ?>"
                                    href="<?= e(url('set-language/' . $langCode)) ?>">
                                    <?= e(\App\Core\Lang::label($langCode)) ?>
                                    <?php if ($langCode === \App\Core\Lang::current()): ?>
                                    <i class="bi bi-check-lg"></i>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <button class="btn btn-sm btn-light" id="themeToggle" title="<?= e(t('toggle_dark_mode')) ?>">
                        <i class="bi bi-moon-stars-fill"></i>
                    </button>

                    <?php if ($currentUser): ?>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light position-relative" data-bs-toggle="dropdown">
                            <i class="bi bi-envelope-fill"></i>
                            <?php if ($topbarUnreadMessages > 0): ?>
                            <span
                                class="badge rounded-pill bg-danger position-absolute top-0 start-100 translate-middle"><?= (int) $topbarUnreadMessages ?></span>
                            <?php endif; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end p-2" style="min-width:260px;">
                            <li class="px-2 py-1 fw-bold small text-muted"><?= e(t('messages')) ?></li>
                            <?php if ($topbarUnreadMessages > 0): ?>
                            <li class="px-2 small"><?= e(t($topbarUnreadMessages === 1 ? 'unread_message' : 'unread_messages', ['count' => (int) $topbarUnreadMessages])) ?></li>
                            <?php else: ?>
                            <li class="px-2 small text-muted"><?= e(t('no_new_messages')) ?></li>
                            <?php endif; ?>
                            <?php if ($currentRole === ROLE_PARENT): ?>
                            <li><a class="dropdown-item small" href="<?= e(url('dashboard')) ?>#communication"><?= e(t('open_communication_tab')) ?></a></li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <div class="dropdown">
                        <button class="btn btn-sm btn-light position-relative" data-bs-toggle="dropdown">
                            <i class="bi bi-bell-fill"></i>
                            <?php if ($topbarUnreadNotifications > 0): ?>
                            <span
                                class="badge rounded-pill bg-danger position-absolute top-0 start-100 translate-middle"><?= (int) $topbarUnreadNotifications ?></span>
                            <?php endif; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end p-2" style="min-width:300px;">
                            <li class="px-2 py-1 fw-bold small text-muted"><?= e(t('notifications')) ?></li>
                            <?php if (empty($topbarNotifications)): ?>
                            <li class="px-2 small text-muted"><?= e(t('no_notifications_yet')) ?></li>
                            <?php else: ?>
                            <?php foreach ($topbarNotifications as $n): ?>
                            <li
                                class="px-2 py-1 small border-bottom <?= empty($n['is_read']) ? 'fw-semibold' : 'text-muted' ?>">
                                <?= e(mb_strimwidth($n['message'], 0, 70, '…')) ?></li>
                            <?php endforeach; ?>
                            <?php endif; ?>
                            <li><a class="dropdown-item small" href="<?= e(url('notifications')) ?>"><?= e(t('view_all_notifications')) ?></a></li>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <div class="dropdown">
                        <button class="btn btn-light d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                            <span
                                class="avatar-circle"><?= e(strtoupper(substr($currentUser['full_name'] ?? '?', 0, 1))) ?></span>
                            <span class="d-none d-md-inline">
                                <?= e($currentUser['full_name'] ?? 'User') ?>
                                <small class="d-block text-muted"><?= e(role_label($currentRole)) ?></small>
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= e(url('change-password')) ?>"><i
                                        class="bi bi-key-fill me-2"></i><?= e(t('change_password')) ?></a></li>
                            <li><a class="dropdown-item" href="<?= e(url('2fa/setup')) ?>"><i
                                        class="bi bi-shield-lock-fill me-2"></i><?= e(t('two_factor_auth')) ?></a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <form method="POST" action="<?= e(url('logout')) ?>" data-confirm
                                    data-confirm-icon="question" data-confirm-title="<?= e(t('log_out_confirm_title')) ?>"
                                    data-confirm-message="<?= e(t('log_out_confirm_message')) ?>"
                                    data-confirm-button="<?= e(t('log_out_confirm_button')) ?>" data-confirm-variant="danger">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="dropdown-item text-danger"><i
                                            class="bi bi-box-arrow-right me-2"></i><?= e(t('logout')) ?></button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>

            <!-- Page content -->
            <main class="page-content">
                <?= flash_alerts() ?>
                <?= $content ?>
            </main>

            <footer class="page-footer">
                &copy; <?= date('Y') ?> <?= e(t('app_name')) ?>. <?= e(t('all_rights_reserved')) ?>
            </footer>
        </div>
    </div>

    <script>
    window.LIBRARY_PAYMENT_MODES = <?= json_encode(PAYMENT_MODES) ?>;
    window.LIBRARY_CURRENCY_PREFIX = <?= json_encode('Rs. ') ?>;
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?= e(asset('js/sweetalert-helpers.js')) ?>"></script>
    <script src="<?= e(asset('js/library-fine-gate.js')) ?>"></script>
    <script src="<?= e(asset('js/global-search.js')) ?>"></script>
    <script src="<?= e(asset('js/app.js')) ?>"></script>
</body>

</html>