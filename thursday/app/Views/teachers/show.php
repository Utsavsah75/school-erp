<?php
/**
 * Expects: $teacherRow, $contact, $addresses, $employment, $bank,
 * $qualifications, $experience, $assignments, $documents, $payroll,
 * $leave, $attendanceSet, $loginUser
 */
$permanent = $addresses['permanent'] ?? [];
$temporary = $addresses['temporary'] ?? [];
$addressLine = function (array $addr): string {
    $parts = array_filter([
        $addr['address_line'] ?? '', $addr['city'] ?? '', $addr['state_province'] ?? '',
        $addr['country'] ?? '', $addr['postal_code'] ?? '',
    ]);
    return trim(implode(', ', $parts));
};
$permanentDisplay = $addressLine($permanent) ?: '—';
$temporaryDisplay = $addressLine($temporary) ?: '—';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><?= e(t('h_teacher_profile')) ?></h5>
    <div>
        <a href="<?= e(url('teachers/' . $teacherRow['id'] . '/edit')) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-pencil-fill me-1"></i>Edit</a>
        <a href="<?= e(url('teachers')) ?>" class="btn btn-light btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-3">
        <div class="card mb-3">
            <div class="card-body text-center">
                <?php if (!empty($teacherRow['photo_path'])): ?>
                    <img src="<?= e(upload_url($teacherRow['photo_path'])) ?>" class="rounded-circle mb-3" style="width:140px;height:140px;object-fit:cover;" alt="Photo">
                <?php else: ?>
                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-3" style="width:140px;height:140px;">
                        <i class="bi bi-person-fill" style="font-size:60px;color:#ccc;"></i>
                    </div>
                <?php endif; ?>
                <h5 class="fw-bold mb-0"><?= e($teacherRow['full_name']) ?></h5>
                <p class="text-muted mb-2">Employee #<?= e($teacherRow['employee_number']) ?></p>
                <span class="badge <?= status_badge_class($teacherRow['status']) ?>"><?= e(TEACHER_STATUSES[$teacherRow['status']] ?? ucwords(str_replace('_', ' ', $teacherRow['status']))) ?></span>
                <?php if (!empty($employment['designation'])): ?>
                    <p class="text-muted small mt-2 mb-0"><?= e($employment['designation']) ?><?= !empty($employment['department']) ? ' · ' . e($employment['department']) : '' ?></p>
                <?php endif; ?>
                <?php if (!empty($teacherRow['signature_path'])): ?>
                    <hr>
                    <p class="small text-muted mb-1">Signature</p>
                    <img src="<?= e(upload_url($teacherRow['signature_path'])) ?>" style="max-height:50px;" alt="Signature">
                <?php endif; ?>
            </div>
        </div>
        <?php if ($loginUser): ?>
        <div class="card">
            <div class="card-body">
                <h6 class="fw-bold mb-2"><i class="bi bi-key-fill me-1"></i>Login</h6>
                <p class="small mb-1"><span class="text-muted">Username:</span> <?= e($loginUser['username'] ?? '—') ?></p>
                <p class="small mb-1"><span class="text-muted">Role:</span> <?= e(role_label($loginUser['role'])) ?></p>
                <p class="small mb-1"><span class="text-muted">2FA:</span> <?= !empty($loginUser['two_factor_enabled']) ? 'Enabled' : 'Disabled' ?></p>
                <p class="small mb-0"><span class="text-muted">Last Login:</span> <?= e(format_datetime($loginUser['last_login_at'] ?? null)) ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-9">
        <ul class="nav nav-tabs mb-3" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabPersonal" type="button">Personal &amp; Contact</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabEmployment" type="button"><?= e(t('th_employment')) ?></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabQual" type="button"><?= e(t('th_qualifications')) ?></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabAssign" type="button"><?= e(t('th_assignments')) ?></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabPayroll" type="button">Payroll &amp; Leave</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabAttendance" type="button"><?= e(t('th_attendance')) ?></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabDocs" type="button"><?= e(t('th_documents')) ?></button></li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="tabPersonal">
                <div class="card mb-3"><div class="card-body">
                    <h6 class="fw-bold mb-3"><?= e(t('h_personal_information')) ?></h6>
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td class="text-muted" style="width:200px;">Gender</td><td><?= e($teacherRow['gender'] ? st($teacherRow['gender']) : '—') ?></td></tr>
                        <tr><td class="text-muted">Date of Birth</td><td><?= e($teacherRow['dob'] ? format_date($teacherRow['dob']) : '—') ?></td></tr>
                        <tr><td class="text-muted">Blood Group</td><td><?= e($teacherRow['blood_group'] ?? '—') ?></td></tr>
                        <tr><td class="text-muted">Religion</td><td><?= e($teacherRow['religion'] ?? '—') ?></td></tr>
                        <tr><td class="text-muted">Nationality</td><td><?= e($teacherRow['nationality'] ?? '—') ?></td></tr>
                        <tr><td class="text-muted">Marital Status</td><td><?= e($teacherRow['marital_status'] ? (MARITAL_STATUS_OPTIONS[$teacherRow['marital_status']] ?? $teacherRow['marital_status']) : '—') ?></td></tr>
                        <tr><td class="text-muted">Citizenship No.</td><td><?= e($teacherRow['citizenship_number'] ?? '—') ?></td></tr>
                        <tr><td class="text-muted">Passport No.</td><td><?= e($teacherRow['passport_number'] ?? '—') ?></td></tr>
                    </table>
                </div></div>
                <div class="card mb-3"><div class="card-body">
                    <h6 class="fw-bold mb-3"><?= e(t('h_contact')) ?></h6>
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td class="text-muted" style="width:200px;">Mobile</td><td><?= e($contact['mobile_number'] ?? '—') ?></td></tr>
                        <tr><td class="text-muted">Alternate Mobile</td><td><?= e($contact['alternate_mobile_number'] ?? '—') ?></td></tr>
                        <tr><td class="text-muted">Personal Email</td><td><?= e($contact['personal_email'] ?? '—') ?></td></tr>
                        <tr><td class="text-muted">Official Email</td><td><?= e($contact['official_email'] ?? '—') ?></td></tr>
                        <tr><td class="text-muted">Emergency Contact</td><td><?= e($contact['emergency_contact_name'] ?? '—') ?><?= !empty($contact['emergency_contact_relationship']) ? ' (' . e($contact['emergency_contact_relationship']) . ')' : '' ?> <?= !empty($contact['emergency_contact_number']) ? '— ' . e($contact['emergency_contact_number']) : '' ?></td></tr>
                    </table>
                </div></div>
                <div class="card"><div class="card-body row">
                    <div class="col-md-6">
                        <h6 class="fw-bold mb-2"><?= e(t('h_permanent_address')) ?></h6>
                        <p class="small mb-0"><?= e($permanentDisplay) ?></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold mb-2"><?= e(t('h_temporary_address')) ?></h6>
                        <p class="small mb-0"><?= e($temporaryDisplay) ?></p>
                    </div>
                </div></div>
            </div>

            <div class="tab-pane fade" id="tabEmployment">
                <div class="card mb-3"><div class="card-body">
                    <h6 class="fw-bold mb-3"><?= e(t('th_employment')) ?></h6>
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td class="text-muted" style="width:200px;">Employment Type</td><td><?= e(!empty($employment['employment_type']) ? (EMPLOYMENT_TYPES[$employment['employment_type']] ?? $employment['employment_type']) : '—') ?></td></tr>
                        <tr><td class="text-muted">Designation</td><td><?= e($employment['designation'] ?? '—') ?></td></tr>
                        <tr><td class="text-muted">Department</td><td><?= e($employment['department'] ?? '—') ?></td></tr>
                        <tr><td class="text-muted">Joining Date</td><td><?= e($teacherRow['joining_date'] ? format_date($teacherRow['joining_date']) : '—') ?></td></tr>
                        <tr><td class="text-muted">Salary Grade</td><td><?= e($employment['salary_grade'] ?? '—') ?></td></tr>
                        <tr><td class="text-muted">Basic Salary</td><td><?= $employment['basic_salary'] ?? null ? e(format_currency($employment['basic_salary'])) : '—' ?></td></tr>
                        <tr><td class="text-muted">Employment Status</td><td><span class="badge <?= status_badge_class($employment['employment_status'] ?? 'active') ?>"><?= e(TEACHER_STATUSES[$employment['employment_status'] ?? 'active'] ?? '—') ?></span></td></tr>
                    </table>
                </div></div>
                <div class="card"><div class="card-body">
                    <h6 class="fw-bold mb-3">Bank &amp; Tax Details</h6>
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td class="text-muted" style="width:200px;">Bank</td><td><?= e($bank['bank_name'] ?? '—') ?><?= !empty($bank['branch']) ? ' — ' . e($bank['branch']) : '' ?></td></tr>
                        <tr><td class="text-muted">Account Number</td><td><?= e($bank['bank_account_number'] ?? '—') ?></td></tr>
                        <tr><td class="text-muted">PAN No.</td><td><?= e($bank['pan_number'] ?? '—') ?></td></tr>
                        <tr><td class="text-muted">PF No.</td><td><?= e($bank['pf_number'] ?? '—') ?></td></tr>
                        <tr><td class="text-muted">Insurance No.</td><td><?= e($bank['insurance_number'] ?? '—') ?></td></tr>
                    </table>
                </div></div>
            </div>

            <div class="tab-pane fade" id="tabQual">
                <div class="card mb-3"><div class="card-body">
                    <h6 class="fw-bold mb-3"><?= e(t('th_qualifications')) ?></h6>
                    <?php if (empty($qualifications)): ?>
                        <p class="text-muted mb-0">No qualifications on file.</p>
                    <?php else: ?>
                        <div class="table-responsive"><table class="table table-sm table-hover">
                            <thead><tr><th>Degree</th><th>University/Board</th><th><?= e(t('th_year')) ?></th><th>%/GPA</th><th>Highest</th></tr></thead>
                            <tbody><?php foreach ($qualifications as $q): ?>
                                <tr>
                                    <td><?= e($q['degree']) ?></td>
                                    <td><?= e($q['university'] ?: $q['board'] ?? '—') ?></td>
                                    <td><?= e($q['passing_year'] ?? '—') ?></td>
                                    <td><?= e($q['percentage_gpa'] ?? '—') ?></td>
                                    <td><?= $q['is_highest'] ? '<span class="badge bg-success">Highest</span>' : '' ?></td>
                                </tr>
                            <?php endforeach; ?></tbody>
                        </table></div>
                    <?php endif; ?>
                </div></div>
                <div class="card"><div class="card-body">
                    <h6 class="fw-bold mb-3"><?= e(t('h_prior_experience')) ?></h6>
                    <?php if (empty($experience)): ?>
                        <p class="text-muted mb-0">No prior experience on file.</p>
                    <?php else: ?>
                        <div class="table-responsive"><table class="table table-sm table-hover">
                            <thead><tr><th>Institution</th><th>Designation</th><th>From</th><th>To</th></tr></thead>
                            <tbody><?php foreach ($experience as $ex): ?>
                                <tr>
                                    <td><?= e($ex['institution_name']) ?></td>
                                    <td><?= e($ex['designation'] ?? '—') ?></td>
                                    <td><?= e(format_date($ex['from_date'])) ?></td>
                                    <td><?= e($ex['to_date'] ? format_date($ex['to_date']) : 'Present') ?></td>
                                </tr>
                            <?php endforeach; ?></tbody>
                        </table></div>
                    <?php endif; ?>
                </div></div>
            </div>

            <div class="tab-pane fade" id="tabAssign">
                <div class="card"><div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="bi bi-mortarboard-fill me-1"></i>Teaching Assignments</h6>
                    <?php if (empty($assignments)): ?>
                        <p class="text-muted mb-0">No class or subject assignments yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead><tr><th><?= e(t('th_year')) ?></th><th><?= e(t('th_class')) ?></th><th><?= e(t('th_section')) ?></th><th><?= e(t('th_subject')) ?></th><th>Weekly</th><th><?= e(t('th_class_teacher')) ?></th><th>Coordinator</th></tr></thead>
                                <tbody>
                                    <?php foreach ($assignments as $a): ?>
                                        <tr>
                                            <td><?= e($a['year_label']) ?></td>
                                            <td><?= e($a['class_name']) ?></td>
                                            <td><?= e($a['section_name']) ?></td>
                                            <td><?= e($a['subject_name']) ?></td>
                                            <td><?= e($a['weekly_classes'] ?? '—') ?></td>
                                            <td><?= $a['is_class_teacher'] ? '<i class="bi bi-check-circle-fill text-success"></i>' : '—' ?></td>
                                            <td><?= $a['is_subject_coordinator'] ? '<i class="bi bi-check-circle-fill text-success"></i>' : '—' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div></div>
            </div>

            <div class="tab-pane fade" id="tabPayroll">
                <div class="card mb-3"><div class="card-body">
                    <h6 class="fw-bold mb-3"><?= e(t('h_payroll')) ?></h6>
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td class="text-muted" style="width:200px;">Payment Type</td><td><?= e(!empty($payroll['payment_type']) ? (PAYMENT_TYPES[$payroll['payment_type']] ?? $payroll['payment_type']) : '—') ?></td></tr>
                        <tr><td class="text-muted">Net Salary</td><td><?= isset($payroll['net_salary']) && $payroll['net_salary'] !== null ? e(format_currency($payroll['net_salary'])) : '—' ?></td></tr>
                        <tr><td class="text-muted">Bonus</td><td><?= isset($payroll['bonus']) && $payroll['bonus'] !== null ? e(format_currency($payroll['bonus'])) : '—' ?></td></tr>
                        <tr><td class="text-muted">Provident Fund</td><td><?= isset($payroll['provident_fund']) && $payroll['provident_fund'] !== null ? e(format_currency($payroll['provident_fund'])) : '—' ?></td></tr>
                    </table>
                </div></div>
                <div class="card"><div class="card-body">
                    <h6 class="fw-bold mb-3"><?= e(t('h_leave_balances')) ?></h6>
                    <div class="row text-center g-2">
                        <div class="col"><div class="border rounded p-2"><div class="fs-5 fw-bold"><?= e($leave['casual_leave_balance'] ?? 0) ?></div><div class="small text-muted">Casual</div></div></div>
                        <div class="col"><div class="border rounded p-2"><div class="fs-5 fw-bold"><?= e($leave['sick_leave_balance'] ?? 0) ?></div><div class="small text-muted">Sick</div></div></div>
                        <div class="col"><div class="border rounded p-2"><div class="fs-5 fw-bold"><?= e($leave['annual_leave_balance'] ?? 0) ?></div><div class="small text-muted">Annual</div></div></div>
                        <div class="col"><div class="border rounded p-2"><div class="fs-5 fw-bold"><?= e($leave['maternity_paternity_leave'] ?? 0) ?></div><div class="small text-muted">Maternity/Paternity</div></div></div>
                    </div>
                </div></div>
            </div>

            <div class="tab-pane fade" id="tabAttendance">
                <div class="d-flex justify-content-end mb-2">
                    <a href="<?= e(url('teacher-attendance/' . $teacherRow['id'] . '/settings')) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-gear-fill me-1"></i>Edit Attendance Settings</a>
                </div>
                <div class="card mb-3"><div class="card-body">
                    <h6 class="fw-bold mb-3"><?= e(t('h_registration_details')) ?></h6>
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td class="text-muted" style="width:220px;">Biometric ID</td><td><?= e($attendanceSet['biometric_id'] ?? '—') ?></td></tr>
                        <tr><td class="text-muted">RFID Card Number</td><td><?= e($attendanceSet['rfid_card_number'] ?? '—') ?></td></tr>
                        <tr><td class="text-muted">Face Recognition ID</td><td><?= e($attendanceSet['face_recognition_id'] ?? '—') ?></td></tr>
                        <tr><td class="text-muted">Attendance Device ID</td><td><?= e($attendanceSet['attendance_device_id'] ?? '—') ?></td></tr>
                        <tr><td class="text-muted">Registration Date</td><td><?= e(!empty($attendanceSet['registration_date']) ? format_date($attendanceSet['registration_date']) : '—') ?></td></tr>
                        <tr><td class="text-muted">Last Device Sync</td><td><?= e(!empty($attendanceSet['last_device_sync_date']) ? format_datetime($attendanceSet['last_device_sync_date']) : '—') ?></td></tr>
                    </table>
                </div></div>
                <div class="card mb-3"><div class="card-body">
                    <h6 class="fw-bold mb-3"><?= e(t('h_device_information')) ?></h6>
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td class="text-muted" style="width:220px;">Device Name</td><td><?= e($attendanceSet['device_name'] ?? '—') ?></td></tr>
                        <tr><td class="text-muted">Device Location</td><td><?= e($attendanceSet['device_location'] ?? '—') ?></td></tr>
                        <tr><td class="text-muted">Device Serial Number</td><td><?= e($attendanceSet['device_serial_number'] ?? '—') ?></td></tr>
                        <tr><td class="text-muted">Device Type</td><td><?= e(!empty($attendanceSet['device_type']) ? (DEVICE_TYPES[$attendanceSet['device_type']] ?? $attendanceSet['device_type']) : '—') ?></td></tr>
                    </table>
                </div></div>
                <div class="card mb-3"><div class="card-body">
                    <h6 class="fw-bold mb-3">Shift &amp; Policy</h6>
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td class="text-muted" style="width:220px;">Attendance Method</td><td><?= e(!empty($attendanceSet['attendance_method']) ? (ATTENDANCE_METHODS[$attendanceSet['attendance_method']] ?? $attendanceSet['attendance_method']) : '—') ?></td></tr>
                        <tr><td class="text-muted">Attendance Status</td><td><span class="badge <?= status_badge_class($attendanceSet['attendance_status'] ?? 'active') ?>"><?= e(ATTENDANCE_REGISTRATION_STATUSES[$attendanceSet['attendance_status'] ?? 'active'] ?? '—') ?></span></td></tr>
                        <tr><td class="text-muted">Shift Type</td><td><?= e(!empty($attendanceSet['shift_type']) ? (SHIFT_TYPES[$attendanceSet['shift_type']] ?? $attendanceSet['shift_type']) : '—') ?></td></tr>
                        <tr><td class="text-muted">Default Shift</td><td><?= e($attendanceSet['default_shift'] ?? '—') ?></td></tr>
                        <tr><td class="text-muted">Shift Start / End</td><td><?= e($attendanceSet['shift_start_time'] ?? '—') ?> — <?= e($attendanceSet['shift_end_time'] ?? '—') ?></td></tr>
                        <tr><td class="text-muted">Working Hours</td><td><?= e($attendanceSet['working_hours'] ?? '—') ?></td></tr>
                        <tr><td class="text-muted">Break Duration</td><td><?= e($attendanceSet['break_duration_minutes'] ?? null) !== '' ? e($attendanceSet['break_duration_minutes']) . ' min' : '—' ?></td></tr>
                        <tr><td class="text-muted">Minimum Working Hours/Day</td><td><?= e($attendanceSet['minimum_working_hours'] ?? '—') ?></td></tr>
                        <tr><td class="text-muted">Grace Period (Late)</td><td><?= e($attendanceSet['late_grace_period_minutes'] ?? '—') ?></td></tr>
                        <tr><td class="text-muted">Grace Period (Early Exit)</td><td><?= e($attendanceSet['early_exit_grace_period_minutes'] ?? '—') ?></td></tr>
                        <tr><td class="text-muted">Overtime Eligible</td><td><?= !empty($attendanceSet['overtime_eligible']) ? 'Yes' : 'No' ?></td></tr>
                        <tr><td class="text-muted">Weekly Off</td><td><?= e(!empty($attendanceSet['weekly_off']) ? strtoupper($attendanceSet['weekly_off']) : '—') ?></td></tr>
                    </table>
                </div></div>
                <div class="card"><div class="card-body">
                    <h6 class="fw-bold mb-3">Rules &amp; Check-In / Check-Out</h6>
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td class="text-muted" style="width:220px;">Auto Mark Absent</td><td><?= !empty($attendanceSet['auto_mark_absent']) ? 'Yes' : 'No' ?></td></tr>
                        <tr><td class="text-muted">Allow Manual Attendance</td><td><?= !empty($attendanceSet['allow_manual_attendance']) ? 'Yes' : 'No' ?></td></tr>
                        <tr><td class="text-muted">Require GPS</td><td><?= !empty($attendanceSet['require_gps']) ? 'Yes' : 'No' ?></td></tr>
                        <tr><td class="text-muted">Require Selfie</td><td><?= !empty($attendanceSet['require_selfie']) ? 'Yes' : 'No' ?></td></tr>
                        <tr><td class="text-muted">Default Check-In</td><td><?= e($attendanceSet['default_check_in_time'] ?? '—') ?></td></tr>
                        <tr><td class="text-muted">Default Check-Out</td><td><?= e($attendanceSet['default_check_out_time'] ?? '—') ?></td></tr>
                        <tr><td class="text-muted">Maximum Late</td><td><?= e($attendanceSet['max_late_minutes'] ?? '—') ?></td></tr>
                        <tr><td class="text-muted">Maximum Early Leave</td><td><?= e($attendanceSet['max_early_leave_minutes'] ?? '—') ?></td></tr>
                    </table>
                </div></div>
            </div>

            <div class="tab-pane fade" id="tabDocs">
                <div class="card"><div class="card-body">
                    <h6 class="fw-bold mb-3"><?= e(t('th_documents')) ?></h6>
                    <?php if (empty($documents)): ?>
                        <p class="text-muted mb-0">No documents uploaded.</p>
                    <?php else: ?>
                        <div class="table-responsive"><table class="table table-sm table-hover">
                            <thead><tr><th><?= e(t('th_name')) ?></th><th><?= e(t('th_remarks')) ?></th><th><?= e(t('th_uploaded')) ?></th><th></th></tr></thead>
                            <tbody><?php foreach ($documents as $d): ?>
                                <tr>
                                    <td><?= e($d['doc_name']) ?></td>
                                    <td><?= e($d['remarks'] ?? '—') ?></td>
                                    <td><?= e(format_date($d['uploaded_at'])) ?></td>
                                    <td><a href="<?= e(url('documents/teacher/' . $d['id'] . '/download')) ?>" class="btn btn-sm btn-outline-primary" onclick="if(window.SA){window.SA.toast('success','Downloading file...');}"><i class="bi bi-download"></i></a></td>
                                </tr>
                            <?php endforeach; ?></tbody>
                        </table></div>
                    <?php endif; ?>
                </div></div>
            </div>
        </div>
    </div>
</div>
