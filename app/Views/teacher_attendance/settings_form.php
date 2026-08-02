<?php
/**
 * Attendance device/biometric/shift settings for one teacher.
 * Expects: $teacher, $attendanceSet (array), $managers, $errors
 */
$aval = fn(string $key, $default = '') => e((string) old($key, $attendanceSet[$key] ?? $default));
$weeklyOff = old('weekly_off', $attendanceSet['weekly_off'] ?? '');
$weeklyOffArr = is_array($weeklyOff) ? $weeklyOff : array_filter(explode(',', (string) $weeklyOff));
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Attendance Settings — <?= e($teacher['full_name']) ?></h5>
    <a href="<?= e(url('teacher-attendance')) ?>" class="btn btn-light"><i class="bi bi-arrow-left me-1"></i>Back to List</a>
</div>

<div class="card">
    <div class="card-body">
        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-danger"><?= e($errors['general'][0]) ?></div>
        <?php endif; ?>

        <form method="POST" action="<?= e(url('teacher-attendance/' . $teacher['id'] . '/settings')) ?>" novalidate>
            <?= csrf_field() ?>

            <h6 class="fw-bold mb-3">Attendance Registration Details</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-3"><label class="form-label">Biometric ID</label><input type="text" name="biometric_id" class="form-control" value="<?= $aval('biometric_id') ?>"></div>
                <div class="col-md-3"><label class="form-label">RFID Card Number</label><input type="text" name="rfid_card_number" class="form-control" value="<?= $aval('rfid_card_number') ?>"></div>
                <div class="col-md-3"><label class="form-label">Face Recognition ID</label><input type="text" name="face_recognition_id" class="form-control" value="<?= $aval('face_recognition_id') ?>"></div>
                <div class="col-md-3"><label class="form-label">Attendance Device ID</label><input type="text" name="attendance_device_id" class="form-control" value="<?= $aval('attendance_device_id') ?>"></div>

                <div class="col-md-3">
                    <label class="form-label">Device Name</label>
                    <input type="text" name="device_name" class="form-control" value="<?= $aval('device_name') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Attendance Method</label>
                    <select name="attendance_method" class="form-select">
                        <option value="">-- Select --</option>
                        <?php foreach (ATTENDANCE_METHODS as $mv => $ml): ?>
                            <option value="<?= $mv ?>" <?= $aval('attendance_method') === $mv ? 'selected' : '' ?>><?= $ml ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Shift Type</label>
                    <select name="shift_type" class="form-select">
                        <option value="">-- Select --</option>
                        <?php foreach (SHIFT_TYPES as $sv => $sl): ?>
                            <option value="<?= $sv ?>" <?= $aval('shift_type') === $sv ? 'selected' : '' ?>><?= $sl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Attendance Status</label>
                    <select name="attendance_status" class="form-select">
                        <?php foreach (ATTENDANCE_REGISTRATION_STATUSES as $sv => $sl): ?>
                            <option value="<?= $sv ?>" <?= ($aval('attendance_status', 'active') === $sv) ? 'selected' : '' ?>><?= $sl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3"><label class="form-label">Default Shift</label><input type="text" name="default_shift" class="form-control" value="<?= $aval('default_shift') ?>" placeholder="e.g. Morning (7:30 - 2:30)"></div>
                <div class="col-md-3"><label class="form-label">Working Hours</label><input type="text" name="working_hours" class="form-control" value="<?= $aval('working_hours') ?>" placeholder="e.g. 7:30 AM - 2:30 PM"></div>
                <div class="col-md-3">
                    <label class="form-label">Shift Start Time</label>
                    <input type="time" name="shift_start_time" class="form-control" value="<?= $aval('shift_start_time') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Shift End Time</label>
                    <input type="time" name="shift_end_time" class="form-control" value="<?= $aval('shift_end_time') ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Grace Period — Late (min)</label>
                    <input type="number" min="0" name="late_grace_period_minutes" class="form-control" value="<?= $aval('late_grace_period_minutes') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Grace Period — Early Exit (min)</label>
                    <input type="number" min="0" name="early_exit_grace_period_minutes" class="form-control" value="<?= $aval('early_exit_grace_period_minutes') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Break Duration (min)</label>
                    <input type="number" min="0" name="break_duration_minutes" class="form-control" value="<?= $aval('break_duration_minutes') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Minimum Working Hours / Day</label>
                    <input type="number" step="0.25" min="0" name="minimum_working_hours" class="form-control" value="<?= $aval('minimum_working_hours') ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Registration Date</label>
                    <input type="date" name="registration_date" class="form-control" value="<?= $aval('registration_date') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Registered By</label>
                    <select name="registered_by" class="form-select">
                        <option value="">-- None --</option>
                        <?php foreach ($managers as $m): ?>
                            <option value="<?= e($m['id']) ?>" <?= (string) old('registered_by', $attendanceSet['registered_by'] ?? '') === (string) $m['id'] ? 'selected' : '' ?>><?= e($m['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Last Device Sync</label>
                    <input type="datetime-local" name="last_device_sync_date" class="form-control" value="<?= $aval('last_device_sync_date') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label d-block">Weekly Off</label>
                    <?php foreach (['sun' => 'Sun', 'mon' => 'Mon', 'tue' => 'Tue', 'wed' => 'Wed', 'thu' => 'Thu', 'fri' => 'Fri', 'sat' => 'Sat'] as $dv => $dl): ?>
                        <div class="form-check form-check-inline">
                            <input type="checkbox" class="form-check-input" name="weekly_off[]" value="<?= $dv ?>" id="off_<?= $dv ?>" <?= in_array($dv, $weeklyOffArr, true) ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="off_<?= $dv ?>"><?= $dl ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <h6 class="fw-bold mb-3">Check-In / Check-Out</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Default Check-In Time</label>
                    <input type="time" name="default_check_in_time" class="form-control" value="<?= $aval('default_check_in_time') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Default Check-Out Time</label>
                    <input type="time" name="default_check_out_time" class="form-control" value="<?= $aval('default_check_out_time') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Maximum Late (min)</label>
                    <input type="number" min="0" name="max_late_minutes" class="form-control" value="<?= $aval('max_late_minutes') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Maximum Early Leave (min)</label>
                    <input type="number" min="0" name="max_early_leave_minutes" class="form-control" value="<?= $aval('max_early_leave_minutes') ?>">
                </div>
            </div>

            <h6 class="fw-bold mb-3">Attendance Rules</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="form-check form-switch pt-4">
                        <input type="checkbox" class="form-check-input" role="switch" name="auto_mark_absent" id="auto_mark_absent" value="1" <?= (old('auto_mark_absent', $attendanceSet['auto_mark_absent'] ?? 0)) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="auto_mark_absent">Auto Mark Absent</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check form-switch pt-4">
                        <input type="checkbox" class="form-check-input" role="switch" name="allow_manual_attendance" id="allow_manual_attendance" value="1" <?= (old('allow_manual_attendance', $attendanceSet['allow_manual_attendance'] ?? 1)) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="allow_manual_attendance">Allow Manual Attendance</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check form-switch pt-4">
                        <input type="checkbox" class="form-check-input" role="switch" name="require_gps" id="require_gps" value="1" <?= (old('require_gps', $attendanceSet['require_gps'] ?? 0)) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="require_gps">Require Location/GPS</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check form-switch pt-4">
                        <input type="checkbox" class="form-check-input" role="switch" name="require_selfie" id="require_selfie" value="1" <?= (old('require_selfie', $attendanceSet['require_selfie'] ?? 0)) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="require_selfie">Require Selfie on Check-in</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check form-switch pt-4">
                        <input type="checkbox" class="form-check-input" role="switch" name="overtime_eligible" id="overtime_eligible" value="1" <?= (old('overtime_eligible', $attendanceSet['overtime_eligible'] ?? 0)) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="overtime_eligible">Overtime Eligible</label>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-2 border-top pt-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle-fill me-1"></i>Save Attendance Settings</button>
                <a href="<?= e(url('teacher-attendance')) ?>" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>
