<?php

use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\RegistrationController;
use App\Controllers\TwoFactorController;
use App\Controllers\StaffInviteController;
use App\Controllers\DashboardController;
use App\Controllers\StudentController;
use App\Controllers\ParentController;
use App\Controllers\ParentsController;
use App\Controllers\TeacherController;
use App\Controllers\TeacherAttendanceController;
use App\Controllers\SubjectController;
use App\Controllers\AttendanceController;
use App\Controllers\DocumentController;
use App\Controllers\ExamController;
use App\Controllers\ExamScheduleController;
use App\Controllers\ExamGradeController;
use App\Controllers\NoticeController;
use App\Controllers\ClassController;
use App\Controllers\SectionController;
use App\Controllers\LibraryController;
use App\Controllers\SettingsController;
use App\Controllers\FeeTypeController;
use App\Controllers\FeeController;
use App\Controllers\PaymentController;

/**
 * All routes are registered here. This file returns a callback that
 * App::run() invokes with the Router instance, so route classes are
 * only resolved (autoloaded) once actually needed.
 *
 * Route groups are added module-by-module as each module is built.
 */
return function (Router $router): void {

    // ------------------------------------------------------------
    // Public / Auth
    // ------------------------------------------------------------
    $router->get('/', [AuthController::class, 'showLogin']);

    $router->get('/login', [AuthController::class, 'showLogin'], ['guest']);
    $router->post('/login', [AuthController::class, 'login'], ['guest', 'csrf', 'throttle:login,8,60,15,email']);
    $router->post('/logout', [AuthController::class, 'logout'], ['auth', 'csrf']);

    // ------------------------------------------------------------
    // Phone + OTP login (passwordless tab on the login page)
    // ------------------------------------------------------------
    $router->post('/login/phone/start', [AuthController::class, 'phoneOtpStart'], ['guest', 'csrf', 'throttle:login-phone,5,60,15,phone']);
    $router->post('/login/phone/verify', [AuthController::class, 'phoneOtpVerify'], ['guest', 'csrf', 'throttle:login-phone-verify,8,60,15,phone']);

    // ------------------------------------------------------------
    // Two-Factor Authentication
    // ------------------------------------------------------------
    $router->get('/2fa/verify', [TwoFactorController::class, 'showVerify'], ['guest']);
    $router->post('/2fa/verify', [TwoFactorController::class, 'verify'], ['guest', 'csrf', 'throttle:2fa-verify,8,60,15']);
    $router->get('/2fa/setup', [TwoFactorController::class, 'showSetup'], ['auth']);
    $router->post('/2fa/setup', [TwoFactorController::class, 'enable'], ['auth', 'csrf']);
    $router->post('/2fa/disable', [TwoFactorController::class, 'disable'], ['auth', 'csrf']);

    // ------------------------------------------------------------
    // Public self-registration wizard — see RegistrationController for
    // the full role/method matrix (Parent, Student, Teacher, Staff).
    // ------------------------------------------------------------
    $router->get('/register', [RegistrationController::class, 'showRoleSelect'], ['guest']);
    $router->get('/register/wizard', [RegistrationController::class, 'showWizard'], ['guest']);
    $router->post('/register/start', [RegistrationController::class, 'start'], ['guest', 'csrf', 'throttle:register-start,10,300,15']);
    $router->post('/register/resend-otp', [RegistrationController::class, 'resendOtp'], ['guest', 'csrf', 'throttle:register-resend,10,300,15']);
    $router->post('/register/verify-otp', [RegistrationController::class, 'verifyOtp'], ['guest', 'csrf', 'throttle:register-verify,15,300,15']);
    $router->post('/register/complete', [RegistrationController::class, 'complete'], ['guest', 'csrf']);

    // ------------------------------------------------------------
    // Admin Registration — Super Admin only, direct account creation
    // (no self-service OTP; see RegistrationController::storeAdmin()).
    // ------------------------------------------------------------
    $router->get('/admin/register', [RegistrationController::class, 'showCreateAdmin'], ['auth', 'role:super_admin']);
    $router->post('/admin/register', [RegistrationController::class, 'storeAdmin'], ['auth', 'role:super_admin', 'csrf']);

    // ------------------------------------------------------------
    // Staff Registration Invites — Super Admin pre-provisions Employee
    // Codes that the public "Staff/Employee Registration" wizard step
    // requires (prevents self-granting a privileged role).
    // ------------------------------------------------------------
    $router->get('/staff-invites', [StaffInviteController::class, 'index'], ['auth', 'role:super_admin']);
    $router->post('/staff-invites', [StaffInviteController::class, 'store'], ['auth', 'role:super_admin', 'csrf']);
    $router->post('/staff-invites/{id}/delete', [StaffInviteController::class, 'destroy'], ['auth', 'role:super_admin', 'csrf']);

    $router->get('/forgot-password', [AuthController::class, 'showForgotPassword'], ['guest']);
    $router->post('/forgot-password', [AuthController::class, 'sendResetLink'], ['guest', 'csrf', 'throttle:forgot-password,5,300,15,email']);

    $router->get('/reset-password/{token}', [AuthController::class, 'showResetPassword'], ['guest']);
    $router->post('/reset-password', [AuthController::class, 'resetPassword'], ['guest', 'csrf']);

    $router->get('/change-password', [AuthController::class, 'showChangePassword'], ['auth']);
    $router->post('/change-password', [AuthController::class, 'changePassword'], ['auth', 'csrf']);

    // ------------------------------------------------------------
    // Email verification
    // ------------------------------------------------------------
    $router->get('/verify-email/{token}', [AuthController::class, 'verifyEmail'], ['guest']);
    $router->post('/resend-verification', [AuthController::class, 'resendVerification'], ['guest', 'csrf']);

    // ------------------------------------------------------------
    // Forgot password — email/SMS OTP channel
    // ------------------------------------------------------------
    $router->post('/forgot-password/otp', [AuthController::class, 'sendResetOtp'], ['guest', 'csrf', 'throttle:forgot-password-otp,5,300,15,email']);
    $router->get('/forgot-password/otp/verify', [AuthController::class, 'showVerifyResetOtp'], ['guest']);
    $router->post('/forgot-password/otp/verify', [AuthController::class, 'verifyResetOtp'], ['guest', 'csrf', 'throttle:forgot-password-otp-verify,10,300,15']);
    $router->get('/forgot-password/otp/reset', [AuthController::class, 'showResetPasswordAfterVerification'], ['guest']);
    $router->post('/forgot-password/otp/reset', [AuthController::class, 'resetPasswordAfterVerification'], ['guest', 'csrf']);

    // ------------------------------------------------------------
    // Admin-triggered password reset (any role — students, teachers,
    // parents, staff all live in the `users` table).
    // ------------------------------------------------------------
    $router->post('/admin/users/{id}/send-reset', [AuthController::class, 'adminSendReset'], ['auth', 'csrf']);

    // ------------------------------------------------------------
    // Security-question fallback — disabled unless
    // FEATURE_SECURITY_QUESTIONS=true (see config/app.php 'features').
    // ------------------------------------------------------------
    if (config('features.security_questions_enabled')) {
        $router->get('/security-questions/setup', [AuthController::class, 'showSetupSecurityQuestions'], ['auth']);
        $router->post('/security-questions/setup', [AuthController::class, 'saveSecurityQuestions'], ['auth', 'csrf']);
        $router->get('/forgot-password/security-questions', [AuthController::class, 'showSecurityQuestionFallback'], ['guest']);
        $router->post('/forgot-password/security-questions', [AuthController::class, 'verifySecurityAnswers'], ['guest', 'csrf']);
    }

    // ------------------------------------------------------------
    // Dashboard
    // ------------------------------------------------------------
    $router->get('/dashboard', [DashboardController::class, 'index'], ['auth']);

    // ------------------------------------------------------------
    // Students
    // ------------------------------------------------------------
    $router->get('/students', [StudentController::class, 'index'], ['auth']);
    $router->get('/students/create', [StudentController::class, 'create'], ['auth']);
    $router->post('/students', [StudentController::class, 'store'], ['auth', 'csrf']);
    $router->get('/students/sections-for-class/{classId}', [StudentController::class, 'sectionsForClass'], ['auth']);
    $router->get('/students/next-roll-number', [StudentController::class, 'nextRollNumber'], ['auth']);
    $router->get('/students/documents-audit', [StudentController::class, 'documentsAudit'], ['auth']);
    $router->get('/students/{id}', [StudentController::class, 'show'], ['auth']);
    $router->get('/students/{id}/edit', [StudentController::class, 'edit'], ['auth']);
    $router->post('/students/{id}', [StudentController::class, 'update'], ['auth', 'csrf']);
    $router->post('/students/{id}/delete', [StudentController::class, 'destroy'], ['auth', 'csrf']);

    // ------------------------------------------------------------
    // Parents (admin management) — list/view/edit parent records and
    // grant portal logins. Gated by MODULE_PERMISSIONS['parents']
    // (super_admin, principal, receptionist). Not to be confused with
    // the parent self-service routes below.
    // ------------------------------------------------------------
    $router->get('/parents', [ParentsController::class, 'index'], ['auth']);
    $router->get('/parents/{id}', [ParentsController::class, 'show'], ['auth']);
    $router->get('/parents/{id}/edit', [ParentsController::class, 'edit'], ['auth']);
    $router->post('/parents/{id}', [ParentsController::class, 'update'], ['auth', 'csrf']);
    $router->post('/parents/{id}/grant-portal-access', [ParentsController::class, 'grantPortalAccess'], ['auth', 'csrf']);

    // ------------------------------------------------------------
    // Teachers (admin management) — list/view teacher records.
    // Gated by MODULE_PERMISSIONS['teachers'] (super_admin, principal,
    // vice_principal).
    // ------------------------------------------------------------
    $router->get('/teachers', [TeacherController::class, 'index'], ['auth']);
    $router->get('/teachers/create', [TeacherController::class, 'create'], ['auth']);
    $router->post('/teachers', [TeacherController::class, 'store'], ['auth', 'csrf']);
    $router->get('/teachers/sections-for-class/{classId}', [TeacherController::class, 'sectionsForClass'], ['auth']);
    $router->get('/teachers/subjects-for-class/{classId}', [TeacherController::class, 'subjectsForClass'], ['auth']);
    $router->get('/teachers/{id}', [TeacherController::class, 'show'], ['auth']);
    $router->get('/teachers/{id}/edit', [TeacherController::class, 'edit'], ['auth']);
    $router->post('/teachers/{id}', [TeacherController::class, 'update'], ['auth', 'csrf']);
    $router->post('/teachers/{id}/delete', [TeacherController::class, 'destroy'], ['auth', 'csrf']);
    $router->post('/teachers/{id}/documents/{docId}/delete', [TeacherController::class, 'deleteDocument'], ['auth', 'csrf']);

    // ------------------------------------------------------------
    // Teacher Attendance — device/biometric/shift settings, configured
    // per-teacher after the teacher record exists (moved out of the
    // registration wizard). Gated by MODULE_PERMISSIONS['teacher_attendance']
    // (super_admin, principal, vice_principal) — see TeacherAttendanceController.
    // ------------------------------------------------------------
    $router->get('/teacher-attendance', [TeacherAttendanceController::class, 'index'], ['auth']);
    $router->get('/teacher-attendance/{id}/settings', [TeacherAttendanceController::class, 'edit'], ['auth']);
    $router->post('/teacher-attendance/{id}/settings', [TeacherAttendanceController::class, 'update'], ['auth', 'csrf']);

    // ------------------------------------------------------------
    // Settings — Mail Setup (SMTP status + test email). super_admin only.
    // ------------------------------------------------------------
    $router->get('/settings', [SettingsController::class, 'index'], ['auth']);
    $router->post('/settings/test-email', [SettingsController::class, 'sendTestEmail'], ['auth', 'csrf']);
    $router->get('/settings/registration-security', [SettingsController::class, 'registrationSecurity'], ['auth', 'role:super_admin']);
    $router->post('/settings/registration-security', [SettingsController::class, 'updateRegistrationSecurity'], ['auth', 'role:super_admin', 'csrf']);

    // ------------------------------------------------------------
    // Subjects — single-page create + searchable/filterable/sortable
    // list with inline edit and bulk delete. Gated by
    // MODULE_PERMISSIONS['subjects'].
    // ------------------------------------------------------------
    $router->get('/subjects', [SubjectController::class, 'index'], ['auth']);
    $router->post('/subjects', [SubjectController::class, 'store'], ['auth', 'csrf']);
    $router->post('/subjects/bulk-delete', [SubjectController::class, 'bulkDestroy'], ['auth', 'csrf']);
    $router->post('/subjects/{id}', [SubjectController::class, 'update'], ['auth', 'csrf']);
    $router->post('/subjects/{id}/delete', [SubjectController::class, 'destroy'], ['auth', 'csrf']);

    // ------------------------------------------------------------
    // Student Attendance (Individual Student Attendance module) —
    // pick a student, then their monthly attendance sheet. Gated by
    // MODULE_PERMISSIONS['attendance'].
    // ------------------------------------------------------------
    $router->get('/attendance', [AttendanceController::class, 'index'], ['auth']);
    $router->get('/attendance/mark', [AttendanceController::class, 'markForm'], ['auth']);
    $router->post('/attendance/mark', [AttendanceController::class, 'storeMark'], ['auth', 'csrf']);
    $router->get('/attendance/reports', [AttendanceController::class, 'reports'], ['auth']);
    $router->get('/attendance/reports/export/{format}', [AttendanceController::class, 'exportReport'], ['auth']);
    $router->get('/attendance/{id}', [AttendanceController::class, 'show'], ['auth']);

    // ------------------------------------------------------------
    // Documents — safe, friendly download endpoint for Student/Teacher
    // uploaded documents (see DocumentController for why this replaced
    // linking straight to the static /uploads/... path).
    // ------------------------------------------------------------
    $router->get('/documents/{type}/{id}/download', [DocumentController::class, 'download'], ['auth']);

    // ------------------------------------------------------------
    // Parent portal — self-service actions scoped to the logged-in
    // parent's own children (see ParentController for the ownership
    // checks; MODULE_PERMISSIONS['parent_portal'] = [ROLE_PARENT]).
    // ------------------------------------------------------------
    $router->get('/parent/children/{id}', [ParentController::class, 'child'], ['auth']);
    $router->get('/parent/children/{id}/report-card', [ParentController::class, 'reportCard'], ['auth']);
    $router->post('/parent/leave', [ParentController::class, 'storeLeave'], ['auth', 'csrf']);
    $router->post('/parent/messages', [ParentController::class, 'sendMessage'], ['auth', 'csrf']);
    $router->post('/parent/messages/{id}/read', [ParentController::class, 'markMessageRead'], ['auth', 'csrf']);

    // ------------------------------------------------------------
    // Exam Types — lookup list behind the Exam Schedule "Exam Type"
    // dropdown. Gated by MODULE_PERMISSIONS['exam_types'].
    // ------------------------------------------------------------
    $router->get('/exam-types', [ExamController::class, 'index'], ['auth']);
    $router->post('/exam-types', [ExamController::class, 'store'], ['auth', 'csrf']);
    $router->post('/exam-types/{id}', [ExamController::class, 'update'], ['auth', 'csrf']);
    $router->post('/exam-types/{id}/delete', [ExamController::class, 'destroy'], ['auth', 'csrf']);

    // ------------------------------------------------------------
    // Exam Schedule — create + searchable/filterable/sortable list,
    // AJAX dependent dropdowns, print, export, import, bulk delete,
    // status toggle. Gated by MODULE_PERMISSIONS['exam_schedule'].
    // ------------------------------------------------------------
    $router->get('/exam-schedule', [ExamScheduleController::class, 'index'], ['auth']);
    $router->post('/exam-schedule', [ExamScheduleController::class, 'store'], ['auth', 'csrf']);
    $router->get('/exam-schedule/sections-for-class/{classId}', [ExamScheduleController::class, 'sectionsForClass'], ['auth']);
    $router->get('/exam-schedule/subjects-for-class/{classId}', [ExamScheduleController::class, 'subjectsForClass'], ['auth']);
    $router->get('/exam-schedule/print', [ExamScheduleController::class, 'print'], ['auth']);
    $router->get('/exam-schedule/export-excel', [ExamScheduleController::class, 'exportExcel'], ['auth']);
    $router->get('/exam-schedule/export-pdf', [ExamScheduleController::class, 'exportPdf'], ['auth']);
    $router->get('/exam-schedule/import', [ExamScheduleController::class, 'importForm'], ['auth']);
    $router->post('/exam-schedule/import', [ExamScheduleController::class, 'import'], ['auth', 'csrf']);
    $router->post('/exam-schedule/bulk-delete', [ExamScheduleController::class, 'bulkDestroy'], ['auth', 'csrf']);
    $router->post('/exam-schedule/{id}', [ExamScheduleController::class, 'update'], ['auth', 'csrf']);
    $router->post('/exam-schedule/{id}/delete', [ExamScheduleController::class, 'destroy'], ['auth', 'csrf']);
    $router->post('/exam-schedule/{id}/toggle-status', [ExamScheduleController::class, 'toggleStatus'], ['auth', 'csrf']);

    // ------------------------------------------------------------
    // Exam Grades — grading scale CRUD with percentage-range overlap
    // validation. Gated by MODULE_PERMISSIONS['exam_grades'].
    // ------------------------------------------------------------
    $router->get('/exam-grades', [ExamGradeController::class, 'index'], ['auth']);
    $router->post('/exam-grades', [ExamGradeController::class, 'store'], ['auth', 'csrf']);
    $router->post('/exam-grades/bulk-delete', [ExamGradeController::class, 'bulkDestroy'], ['auth', 'csrf']);
    $router->post('/exam-grades/{id}', [ExamGradeController::class, 'update'], ['auth', 'csrf']);
    $router->post('/exam-grades/{id}/delete', [ExamGradeController::class, 'destroy'], ['auth', 'csrf']);
    $router->post('/exam-grades/{id}/toggle-status', [ExamGradeController::class, 'toggleStatus'], ['auth', 'csrf']);

    // ------------------------------------------------------------
    // Student Attendance — AJAX student search for the Student
    // Selector widget (class + section -> searchable autocomplete).
    // ------------------------------------------------------------
    $router->get('/attendance/search-students/{classId}/{sectionId}', [AttendanceController::class, 'searchStudents'], ['auth']);

    // ------------------------------------------------------------
    // Notice Board — list with search/filter/sort/pagination, AJAX
    // filtering, create/edit/view/delete (soft delete), print, Excel/PDF
    // export, plus the AJAX endpoints behind the create/edit form's
    // Class -> Section -> Student cascade and the Dashboard "Important
    // Notices" marquee. Gated by MODULE_PERMISSIONS['notices'] (ALL_ROLES
    // may view; create/edit/delete are further restricted to staff roles
    // inside NoticeController::requireManager()).
    // ------------------------------------------------------------
    $router->get('/notices', [NoticeController::class, 'index'], ['auth']);
    $router->get('/notices/create', [NoticeController::class, 'create'], ['auth']);
    $router->post('/notices', [NoticeController::class, 'store'], ['auth', 'csrf']);
    $router->get('/notices/print', [NoticeController::class, 'print'], ['auth']);
    $router->get('/notices/export-excel', [NoticeController::class, 'exportExcel'], ['auth']);
    $router->get('/notices/export-pdf', [NoticeController::class, 'exportPdf'], ['auth']);
    $router->get('/notices/marquee-data', [NoticeController::class, 'marqueeData'], ['auth']);
    $router->get('/notices/search-students', [NoticeController::class, 'searchStudents'], ['auth']);
    $router->get('/notices/sections-for-class/{classId}', [NoticeController::class, 'sectionsForClass'], ['auth']);
    $router->get('/notices/{id}/edit', [NoticeController::class, 'edit'], ['auth']);
    $router->post('/notices/{id}', [NoticeController::class, 'update'], ['auth', 'csrf']);
    $router->post('/notices/{id}/delete', [NoticeController::class, 'destroy'], ['auth', 'csrf']);
    $router->get('/notices/{id}', [NoticeController::class, 'show'], ['auth']);

    // ------------------------------------------------------------
    // Classes & Sections — "All Classes"/"Add New Class" and
    // "All Sections"/"Add New Section" menu items, each with
    // searchable/filterable/sortable list, bulk delete, print, Excel/PDF
    // export, and a Details page with Students/Subjects tabs. Gated by
    // MODULE_PERMISSIONS['classes'] / ['sections'].
    // ------------------------------------------------------------
    $router->get('/classes', [ClassController::class, 'index'], ['auth']);
    $router->get('/classes/create', [ClassController::class, 'create'], ['auth']);
    $router->post('/classes', [ClassController::class, 'store'], ['auth', 'csrf']);
    $router->get('/classes/print', [ClassController::class, 'print'], ['auth']);
    $router->get('/classes/export-excel', [ClassController::class, 'exportExcel'], ['auth']);
    $router->get('/classes/export-pdf', [ClassController::class, 'exportPdf'], ['auth']);
    $router->post('/classes/bulk-delete', [ClassController::class, 'bulkDestroy'], ['auth', 'csrf']);
    $router->get('/classes/{id}/edit', [ClassController::class, 'edit'], ['auth']);
    $router->post('/classes/{id}', [ClassController::class, 'update'], ['auth', 'csrf']);
    $router->post('/classes/{id}/delete', [ClassController::class, 'destroy'], ['auth', 'csrf']);
    $router->get('/classes/{id}', [ClassController::class, 'show'], ['auth']);

    $router->get('/sections', [SectionController::class, 'index'], ['auth']);
    $router->get('/sections/create', [SectionController::class, 'create'], ['auth']);
    $router->post('/sections', [SectionController::class, 'store'], ['auth', 'csrf']);
    $router->get('/sections/print', [SectionController::class, 'print'], ['auth']);
    $router->get('/sections/export-excel', [SectionController::class, 'exportExcel'], ['auth']);
    $router->get('/sections/export-pdf', [SectionController::class, 'exportPdf'], ['auth']);
    $router->post('/sections/bulk-delete', [SectionController::class, 'bulkDestroy'], ['auth', 'csrf']);
    $router->get('/sections/{id}/edit', [SectionController::class, 'edit'], ['auth']);
    $router->post('/sections/{id}', [SectionController::class, 'update'], ['auth', 'csrf']);
    $router->post('/sections/{id}/delete', [SectionController::class, 'destroy'], ['auth', 'csrf']);
    $router->get('/sections/{id}', [SectionController::class, 'show'], ['auth']);

    // ------------------------------------------------------------
    // Library
    // Access gated by MODULE_PERMISSIONS['library'] (super_admin,
    // principal, librarian) via LibraryController::authorizeModule(),
    // same pattern as every other module controller. This block was
    // previously missing entirely, which is why /library returned 404
    // even though the sidebar link and permission already existed.
    // ------------------------------------------------------------
    $router->get('/library', [LibraryController::class, 'index'], ['auth']);
    $router->get('/library/dashboard-data', [LibraryController::class, 'refreshBody'], ['auth']);
    $router->get('/library/fine-collected-today', [LibraryController::class, 'fineCollectedToday'], ['auth']);
    $router->get('/library/history/{type}', [LibraryController::class, 'history'], ['auth']);
    $router->get('/library/export/{type}', [LibraryController::class, 'exportTable'], ['auth']);

    $router->get('/library/books', [LibraryController::class, 'books'], ['auth']);
    $router->get('/library/books/create', [LibraryController::class, 'createBook'], ['auth']);
    $router->post('/library/books', [LibraryController::class, 'storeBook'], ['auth', 'csrf']);
    $router->get('/library/books/trash', [LibraryController::class, 'trashedBooks'], ['auth']);
    $router->post('/library/books/{id}/restore', [LibraryController::class, 'restoreBook'], ['auth', 'csrf']);
    $router->get('/library/books/check-isbn', [LibraryController::class, 'checkIsbn'], ['auth']);
    $router->get('/library/books/{id}/edit', [LibraryController::class, 'editBook'], ['auth']);
    $router->post('/library/books/{id}', [LibraryController::class, 'updateBook'], ['auth', 'csrf']);
    $router->post('/library/books/{id}/delete', [LibraryController::class, 'destroyBook'], ['auth', 'csrf']);
    $router->post('/library/books/{id}/damaged', [LibraryController::class, 'markDamaged'], ['auth', 'csrf']);
    $router->get('/library/books/{id}', [LibraryController::class, 'showBook'], ['auth']);

    $router->get('/library/categories', [LibraryController::class, 'categories'], ['auth']);
    $router->get('/library/categories/create', [LibraryController::class, 'createCategory'], ['auth']);
    $router->post('/library/categories', [LibraryController::class, 'storeCategory'], ['auth', 'csrf']);
    $router->get('/library/categories/{id}/edit', [LibraryController::class, 'editCategory'], ['auth']);
    $router->post('/library/categories/{id}', [LibraryController::class, 'updateCategory'], ['auth', 'csrf']);
    $router->post('/library/categories/{id}/toggle-status', [LibraryController::class, 'toggleCategoryStatus'], ['auth', 'csrf']);
    $router->post('/library/categories/{id}/delete', [LibraryController::class, 'destroyCategory'], ['auth', 'csrf']);

    $router->get('/library/authors', [LibraryController::class, 'authors'], ['auth']);
    $router->get('/library/authors/create', [LibraryController::class, 'createAuthor'], ['auth']);
    $router->post('/library/authors', [LibraryController::class, 'storeAuthor'], ['auth', 'csrf']);
    $router->get('/library/authors/{id}/edit', [LibraryController::class, 'editAuthor'], ['auth']);
    $router->post('/library/authors/{id}', [LibraryController::class, 'updateAuthor'], ['auth', 'csrf']);
    $router->post('/library/authors/{id}/delete', [LibraryController::class, 'destroyAuthor'], ['auth', 'csrf']);

    $router->get('/library/publishers', [LibraryController::class, 'publishers'], ['auth']);
    $router->get('/library/publishers/create', [LibraryController::class, 'createPublisher'], ['auth']);
    $router->post('/library/publishers', [LibraryController::class, 'storePublisher'], ['auth', 'csrf']);
    $router->get('/library/publishers/{id}/edit', [LibraryController::class, 'editPublisher'], ['auth']);
    $router->post('/library/publishers/{id}', [LibraryController::class, 'updatePublisher'], ['auth', 'csrf']);
    $router->post('/library/publishers/{id}/delete', [LibraryController::class, 'destroyPublisher'], ['auth', 'csrf']);

    $router->get('/library/issue', [LibraryController::class, 'issueForm'], ['auth']);
    $router->post('/library/issue', [LibraryController::class, 'issueBook'], ['auth', 'csrf']);
    $router->get('/library/search-borrower', [LibraryController::class, 'searchBorrower'], ['auth']);
    $router->get('/library/search-book', [LibraryController::class, 'searchBook'], ['auth']);
    $router->get('/library/books/{id}/available-copies', [LibraryController::class, 'availableCopies'], ['auth']);

    $router->get('/library/transactions', [LibraryController::class, 'transactions'], ['auth']);
    $router->get('/library/return', [LibraryController::class, 'returnForm'], ['auth']);
    $router->post('/library/transactions/{id}/return', [LibraryController::class, 'returnBook'], ['auth', 'csrf']);
    $router->post('/library/transactions/{id}/renew', [LibraryController::class, 'renewBook'], ['auth', 'csrf']);
    $router->post('/library/transactions/{id}/lost', [LibraryController::class, 'markLost'], ['auth', 'csrf']);
    $router->post('/library/transactions/{id}/pay-fine', [LibraryController::class, 'payFine'], ['auth', 'csrf']);
    $router->get('/library/fine-receipt/{receiptNumber}', [LibraryController::class, 'fineReceipt'], ['auth']);

    $router->get('/library/reservations', [LibraryController::class, 'reservations'], ['auth']);
    $router->post('/library/reservations', [LibraryController::class, 'reserveBook'], ['auth', 'csrf']);
    $router->post('/library/reservations/{id}/cancel', [LibraryController::class, 'cancelReservation'], ['auth', 'csrf']);

    // ------------------------------------------------------------
    // Fee Management — Fee Types, Bulk Fee Assignment
    // Module permission checked per-request in FeeTypeController /
    // FeeController via authorizeModule('fees').
    // ------------------------------------------------------------
    $router->get('/fee-types', [FeeTypeController::class, 'index'], ['auth']);
    $router->post('/fee-types', [FeeTypeController::class, 'store'], ['auth', 'csrf']);
    $router->post('/fee-types/{id}', [FeeTypeController::class, 'update'], ['auth', 'csrf']);
    $router->post('/fee-types/{id}/delete', [FeeTypeController::class, 'destroy'], ['auth', 'csrf']);

    $router->get('/fees/due', [FeeController::class, 'dueReport'], ['auth']);
    $router->get('/fees/bulk-assign', [FeeController::class, 'bulkAssignForm'], ['auth']);
    $router->post('/fees/bulk-assign', [FeeController::class, 'bulkAssignStore'], ['auth', 'csrf']);
    $router->get('/fees/sections-for-class', [FeeController::class, 'sectionsForClass'], ['auth']);
    $router->get('/fees/terms-for-year', [FeeController::class, 'termsForYear'], ['auth']);
    $router->get('/fees/fetch-students', [FeeController::class, 'fetchStudents'], ['auth']);

    // ------------------------------------------------------------
    // Payment Collection — the fee ledger + receipt-posting screen.
    // Module permission checked via authorizeModule('payments').
    // ------------------------------------------------------------
    $router->get('/payments', [PaymentController::class, 'index'], ['auth']);
    $router->get('/payments/daily-collection', [PaymentController::class, 'dailyCollection'], ['auth']);
    $router->get('/payments/class-wise', [PaymentController::class, 'classWiseReport'], ['auth']);
    $router->get('/payments/collect', [PaymentController::class, 'collectForm'], ['auth']);
    $router->post('/payments/collect', [PaymentController::class, 'store'], ['auth', 'csrf']);
    $router->get('/payments/search-student', [PaymentController::class, 'searchStudent'], ['auth']);
    $router->get('/payments/sections-for-class', [PaymentController::class, 'sectionsForClass'], ['auth']);
    $router->get('/payments/ledger', [PaymentController::class, 'ledgerJson'], ['auth']);
    $router->post('/payments/apply-discount', [PaymentController::class, 'applyDiscount'], ['auth', 'csrf']);
    $router->post('/payments/apply-fine', [PaymentController::class, 'applyFine'], ['auth', 'csrf']);
    $router->post('/payments/{id}/void', [PaymentController::class, 'void'], ['auth', 'csrf']);
    $router->get('/payments/receipt/{group}', [PaymentController::class, 'receipt'], ['auth']);
    $router->get('/payments/receipt/{group}/pdf', [PaymentController::class, 'receiptPdf'], ['auth']);
    $router->get('/payments/bill/{group}', [PaymentController::class, 'institutionalBill'], ['auth']);
    $router->get('/payments/bill/{group}/pdf', [PaymentController::class, 'institutionalBillPdf'], ['auth']);
    $router->get('/payments/print-ledger', [PaymentController::class, 'printLedger'], ['auth']);

    // ------------------------------------------------------------
    // Further modules (Teachers, ...) are registered here as
    // each module is built.
    // ------------------------------------------------------------
};