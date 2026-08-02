<?php

/**
 * Roles must exactly match the `users.role` ENUM in the database.
 */
const ROLE_SUPER_ADMIN    = 'super_admin';
const ROLE_PRINCIPAL      = 'principal';
const ROLE_VICE_PRINCIPAL = 'vice_principal';
const ROLE_ACCOUNTANT     = 'accountant';
const ROLE_TEACHER        = 'teacher';
const ROLE_CLASS_TEACHER  = 'class_teacher';
const ROLE_LIBRARIAN      = 'librarian';
const ROLE_RECEPTIONIST   = 'receptionist';
const ROLE_PARENT         = 'parent';
const ROLE_STUDENT        = 'student';

const ALL_ROLES = [
    ROLE_SUPER_ADMIN, ROLE_PRINCIPAL, ROLE_VICE_PRINCIPAL, ROLE_ACCOUNTANT,
    ROLE_TEACHER, ROLE_CLASS_TEACHER, ROLE_LIBRARIAN, ROLE_RECEPTIONIST,
    ROLE_PARENT, ROLE_STUDENT,
];

/**
 * Roles considered "staff" — used for admin-area sidebar / staff-only screens.
 */
const STAFF_ROLES = [
    ROLE_SUPER_ADMIN, ROLE_PRINCIPAL, ROLE_VICE_PRINCIPAL, ROLE_ACCOUNTANT,
    ROLE_TEACHER, ROLE_CLASS_TEACHER, ROLE_LIBRARIAN, ROLE_RECEPTIONIST,
];

/**
 * Module => roles allowed to access it at all (fine-grained action checks
 * happen in each Controller via Auth::authorize()).
 */
const MODULE_PERMISSIONS = [
    'dashboard'          => ALL_ROLES,
    'users'              => [ROLE_SUPER_ADMIN],
    'students'           => [ROLE_SUPER_ADMIN, ROLE_PRINCIPAL, ROLE_VICE_PRINCIPAL, ROLE_TEACHER, ROLE_CLASS_TEACHER, ROLE_RECEPTIONIST],
    'parents'            => [ROLE_SUPER_ADMIN, ROLE_PRINCIPAL, ROLE_RECEPTIONIST],
    'teachers'           => [ROLE_SUPER_ADMIN, ROLE_PRINCIPAL, ROLE_VICE_PRINCIPAL],
    'classes'            => [ROLE_SUPER_ADMIN, ROLE_PRINCIPAL, ROLE_VICE_PRINCIPAL],
    'sections'           => [ROLE_SUPER_ADMIN, ROLE_PRINCIPAL, ROLE_VICE_PRINCIPAL],
    'subjects'           => [ROLE_SUPER_ADMIN, ROLE_PRINCIPAL, ROLE_VICE_PRINCIPAL],
    'attendance'         => [ROLE_SUPER_ADMIN, ROLE_PRINCIPAL, ROLE_TEACHER, ROLE_CLASS_TEACHER],
    'teacher_attendance' => [ROLE_SUPER_ADMIN, ROLE_PRINCIPAL, ROLE_VICE_PRINCIPAL],
    'exams'              => [ROLE_SUPER_ADMIN, ROLE_PRINCIPAL, ROLE_VICE_PRINCIPAL, ROLE_TEACHER],
    'exam_types'         => [ROLE_SUPER_ADMIN, ROLE_PRINCIPAL, ROLE_VICE_PRINCIPAL],
    'exam_schedule'      => [ROLE_SUPER_ADMIN, ROLE_PRINCIPAL, ROLE_VICE_PRINCIPAL, ROLE_TEACHER],
    'exam_grades'        => [ROLE_SUPER_ADMIN, ROLE_PRINCIPAL, ROLE_VICE_PRINCIPAL],
    'marks'              => [ROLE_SUPER_ADMIN, ROLE_PRINCIPAL, ROLE_TEACHER, ROLE_CLASS_TEACHER],
    'fees'               => [ROLE_SUPER_ADMIN, ROLE_PRINCIPAL, ROLE_ACCOUNTANT],
    'payments'           => [ROLE_SUPER_ADMIN, ROLE_PRINCIPAL, ROLE_ACCOUNTANT],
    'library'            => [ROLE_SUPER_ADMIN, ROLE_PRINCIPAL, ROLE_LIBRARIAN],
    'hostel'             => [ROLE_SUPER_ADMIN, ROLE_PRINCIPAL],
    'transport'          => [ROLE_SUPER_ADMIN, ROLE_PRINCIPAL],
    'homework'           => [ROLE_SUPER_ADMIN, ROLE_PRINCIPAL, ROLE_TEACHER, ROLE_CLASS_TEACHER],
    'inventory'          => [ROLE_SUPER_ADMIN, ROLE_PRINCIPAL, ROLE_RECEPTIONIST],
    'leaves'             => [ROLE_SUPER_ADMIN, ROLE_PRINCIPAL, ROLE_VICE_PRINCIPAL, ROLE_TEACHER, ROLE_CLASS_TEACHER],
    'notices'            => ALL_ROLES,
    'notifications'      => ALL_ROLES,
    'events'             => ALL_ROLES,
    'timetable'          => ALL_ROLES,
    'academic_years'     => [ROLE_SUPER_ADMIN, ROLE_PRINCIPAL],
    'settings'           => [ROLE_SUPER_ADMIN],
    'reports'            => [ROLE_SUPER_ADMIN, ROLE_PRINCIPAL, ROLE_VICE_PRINCIPAL, ROLE_ACCOUNTANT],
    'activity_logs'      => [ROLE_SUPER_ADMIN],
    'parent_portal'      => [ROLE_PARENT],
];

const GENDER_OPTIONS = ['male' => 'Male', 'female' => 'Female', 'other' => 'Other'];

const ATTENDANCE_STATUSES = ['present' => 'Present', 'absent' => 'Absent', 'late' => 'Late', 'leave' => 'Leave'];

const FEE_STATUSES = ['unpaid' => 'Unpaid', 'partial' => 'Partial', 'paid' => 'Paid'];

const PAYMENT_MODES = ['cash' => 'Cash', 'upi' => 'UPI', 'bank_transfer' => 'Bank Transfer', 'online' => 'Online', 'cheque' => 'Cheque', 'card' => 'Card'];

const EXAM_TYPES = ['unit_test' => 'Unit Test', 'mid_term' => 'Mid Term', 'final' => 'Final'];

const STUDENT_STATUSES = [
    'active' => 'Active', 'inactive' => 'Inactive', 'graduated' => 'Graduated',
    'transferred' => 'Transferred', 'suspended' => 'Suspended', 'promoted' => 'Promoted',
];

/** Spec section 7 — searchable Religion dropdown options. */
const RELIGION_OPTIONS = [
    'Hindu', 'Buddhist', 'Muslim', 'Christian', 'Sikh', 'Jain',
    'Kirat', 'Jewish', 'Bahai', 'Shinto', 'Taoist', 'Other',
];

/**
 * Spec section 8/9 — Nationality options with calling codes, used for the
 * searchable nationality field and auto-detecting the phone country code.
 * NOTE: covers ~90 common nationalities (South Asia + major expat/diaspora
 * countries) rather than the full ISO-3166 list of 195+ — extend this array
 * if you need full global coverage.
 */
const NATIONALITY_OPTIONS = [
    'Nepalese' => '+977', 'Indian' => '+91', 'Chinese' => '+86', 'American' => '+1',
    'British' => '+44', 'Canadian' => '+1', 'Australian' => '+61', 'German' => '+49',
    'French' => '+33', 'Japanese' => '+81', 'South Korean' => '+82', 'Bangladeshi' => '+880',
    'Bhutanese' => '+975', 'Pakistani' => '+92', 'Sri Lankan' => '+94', 'Burmese' => '+95',
    'Thai' => '+66', 'Malaysian' => '+60', 'Singaporean' => '+65', 'Indonesian' => '+62',
    'Filipino' => '+63', 'Vietnamese' => '+84', 'Emirati' => '+971', 'Qatari' => '+974',
    'Saudi' => '+966', 'Kuwaiti' => '+965', 'Israeli' => '+972', 'Turkish' => '+90',
    'Russian' => '+7', 'Italian' => '+39', 'Spanish' => '+34', 'Portuguese' => '+351',
    'Dutch' => '+31', 'Belgian' => '+32', 'Swiss' => '+41', 'Swedish' => '+46',
    'Norwegian' => '+47', 'Danish' => '+45', 'Finnish' => '+358', 'Polish' => '+48',
    'Austrian' => '+43', 'Greek' => '+30', 'Irish' => '+353', 'New Zealander' => '+64',
    'South African' => '+27', 'Egyptian' => '+20', 'Nigerian' => '+234', 'Kenyan' => '+254',
    'Brazilian' => '+55', 'Mexican' => '+52', 'Afghan' => '+93', 'Maldivian' => '+960',
    'Other' => '',
];

/**
 * Spec section 12 — Nepal address cascade: Province -> Districts (all 77).
 * Municipality/City is left as free text on the form (there are 753
 * nationwide) — wire it to a full dataset later if enforced selection
 * is needed.
 */
const NEPAL_PROVINCES = [
    'Koshi Province' => ['Bhojpur', 'Dhankuta', 'Ilam', 'Jhapa', 'Khotang', 'Morang', 'Okhaldhunga', 'Panchthar', 'Sankhuwasabha', 'Solukhumbu', 'Sunsari', 'Taplejung', 'Terhathum', 'Udayapur'],
    'Madhesh Province' => ['Bara', 'Dhanusha', 'Mahottari', 'Parsa', 'Rautahat', 'Saptari', 'Sarlahi', 'Siraha'],
    'Bagmati Province' => ['Bhaktapur', 'Chitwan', 'Dhading', 'Dolakha', 'Kathmandu', 'Kavrepalanchok', 'Lalitpur', 'Makwanpur', 'Nuwakot', 'Ramechhap', 'Rasuwa', 'Sindhuli', 'Sindhupalchok'],
    'Gandaki Province' => ['Baglung', 'Gorkha', 'Kaski', 'Lamjung', 'Manang', 'Mustang', 'Myagdi', 'Nawalpur', 'Parbat', 'Syangja', 'Tanahun'],
    'Lumbini Province' => ['Arghakhanchi', 'Banke', 'Bardiya', 'Dang', 'Eastern Rukum', 'Gulmi', 'Kapilvastu', 'Palpa', 'Parasi', 'Pyuthan', 'Rolpa', 'Rupandehi'],
    'Karnali Province' => ['Dailekh', 'Dolpa', 'Humla', 'Jajarkot', 'Jumla', 'Kalikot', 'Mugu', 'Salyan', 'Surkhet', 'Western Rukum'],
    'Sudurpashchim Province' => ['Achham', 'Baitadi', 'Bajhang', 'Bajura', 'Dadeldhura', 'Darchula', 'Doti', 'Kailali', 'Kanchanpur'],
];

const TEACHER_STATUSES = [
    'active' => 'Active', 'on_leave' => 'On Leave', 'suspended' => 'Suspended',
    'resigned' => 'Resigned', 'retired' => 'Retired',
];

/** Teacher Management Module (spec section 3) — Employment tab. */
const EMPLOYMENT_TYPES = [
    'full_time' => 'Full Time', 'part_time' => 'Part Time',
    'contract' => 'Contract', 'visiting' => 'Visiting',
];

const MARITAL_STATUS_OPTIONS = [
    'single' => 'Single', 'married' => 'Married', 'divorced' => 'Divorced', 'widowed' => 'Widowed',
];

/** Spec section 9 — Payroll tab. */
const PAYMENT_TYPES = ['bank_transfer' => 'Bank Transfer', 'cash' => 'Cash'];

/** Spec section 6 — Login & Permissions tab. Roles a Teacher record can be assigned. */
const TEACHER_LOGIN_ROLES = [
    ROLE_TEACHER => 'Teacher',
    ROLE_CLASS_TEACHER => 'Class Teacher / HOD',
    ROLE_VICE_PRINCIPAL => 'Vice Principal',
    ROLE_PRINCIPAL => 'Principal',
    ROLE_SUPER_ADMIN => 'Admin',
];

/** Modules a per-teacher permission override can be granted/revoked for (spec section 6). */
const TEACHER_OVERRIDABLE_MODULES = [
    'students' => 'Students', 'attendance' => 'Student Attendance', 'marks' => 'Marks & Grades',
    'exams' => 'Exams', 'homework' => 'Homework', 'timetable' => 'Timetable',
    'library' => 'Library', 'leaves' => 'Leave Requests', 'notices' => 'Notices',
];

/** Spec section 7 — Documents tab, common document names offered as quick-picks. */
const TEACHER_DOCUMENT_TYPES = [
    'Citizenship', 'Passport', 'Academic Certificate', 'Experience Letter', 'Resume/CV',
    'Appointment Letter', 'Contract', 'Police Clearance', 'Medical Certificate',
    'PAN Card', 'Bank Cheque Copy', 'Other',
];

const LEAVE_STATUSES = ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'];

/** Subject Management module. */
const SUBJECT_TYPES = [
    'theory' => 'Theory', 'practical' => 'Practical', 'mathematics' => 'Mathematics', 'optional' => 'Optional',
];

/** Classes & Sections Management module. */
const CLASS_SECTION_SHIFTS = [
    'morning' => 'Morning', 'day' => 'Day', 'evening' => 'Evening',
];

const CLASS_SECTION_STATUSES = [
    'active' => 'Active', 'inactive' => 'Inactive',
];

/** Individual Student Attendance module. Mirrors the `attendance`.`status` enum. */
const STUDENT_ATTENDANCE_STATUSES = [
    'present' => 'Present', 'absent' => 'Absent', 'late' => 'Late', 'leave' => 'Leave', 'holiday' => 'Holiday',
];

/** Teacher Attendance tab — device/registration/policy options. */
const ATTENDANCE_METHODS = [
    'biometric' => 'Biometric', 'rfid' => 'RFID', 'face' => 'Face Recognition', 'manual' => 'Manual',
];

const DEVICE_TYPES = [
    'biometric' => 'Biometric', 'rfid' => 'RFID', 'face' => 'Face Recognition', 'mobile_app' => 'Mobile App',
];

const ATTENDANCE_REGISTRATION_STATUSES = ['active' => 'Active', 'inactive' => 'Inactive'];

const SHIFT_TYPES = [
    'morning' => 'Morning', 'evening' => 'Evening', 'night' => 'Night', 'rotational' => 'Rotational',
];

const BOOK_ISSUE_STATUSES = ['issued' => 'Issued', 'returned' => 'Returned', 'lost' => 'Lost'];

const HOMEWORK_SUBMISSION_STATUSES = [
    'pending' => 'Pending', 'submitted' => 'Submitted', 'late' => 'Late', 'graded' => 'Graded',
];

const INVENTORY_CATEGORIES = ['asset' => 'Asset', 'equipment' => 'Equipment', 'stationery' => 'Stationery'];

const INVENTORY_TXN_TYPES = ['purchase' => 'Purchase', 'issue' => 'Issue', 'adjustment' => 'Adjustment'];

const EVENT_TYPES = ['event' => 'Event', 'holiday' => 'Holiday', 'function' => 'Function', 'sports' => 'Sports'];

const NOTICE_SCOPES = ['school' => 'Whole School', 'class' => 'Specific Class', 'exam' => 'Exam Related'];

/** Exam Schedule / Exam Grades modules. Mirrors the `exam_schedule`.`status` / `exam_grades`.`status` enums. */
const EXAM_RECORD_STATUSES = ['active' => 'Active', 'inactive' => 'Inactive'];

const DAYS_OF_WEEK = [
    'mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday',
    'thu' => 'Thursday', 'fri' => 'Friday', 'sat' => 'Saturday',
];
