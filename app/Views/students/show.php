<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Student Profile</h5>
    <div>
        <a href="<?= e(url('students/' . $student['id'] . '/edit')) ?>" class="btn btn-primary btn-sm"><i
                class="bi bi-pencil me-1"></i>Edit</a>
        <a href="<?= e(url('students')) ?>" class="btn btn-light btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                <?php if (!empty($student['photo_path'])): ?>
                <img src="<?= e(upload_url($student['photo_path'])) ?>" class="rounded-circle mb-3"
                    style="width:140px;height:140px;object-fit:cover;" alt="Photo">
                <?php else: ?>
                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-3"
                    style="width:140px;height:140px;">
                    <i class="bi bi-person-fill" style="font-size:60px;color:#ccc;"></i>
                </div>
                <?php endif; ?>
                <h5 class="fw-bold mb-0"><?= e($student['full_name']) ?></h5>
                <p class="text-muted mb-2">Admission #<?= e($student['admission_number']) ?></p>
                <span
                    class="badge <?= status_badge_class($student['status']) ?>"><?= e(ucfirst($student['status'])) ?></span>
            </div>
        </div>

        <?php if ($parent): ?>
        <div class="card mt-3">
            <div class="card-body">
                <h6 class="fw-bold mb-2"><i class="bi bi-person-hearts me-1"></i>Parent / Guardian</h6>
                <p class="mb-1"><strong>Father:</strong> <?= e($parent['father_name'] ?? '—') ?></p>
                <p class="mb-1"><strong>Mother:</strong> <?= e($parent['mother_name'] ?? '—') ?></p>
                <?php if (!empty($parent['guardian_name'])): ?>
                <p class="mb-1"><strong>Guardian:</strong>
                    <?= e($parent['guardian_name']) ?><?= !empty($parent['guardian_relationship']) ? ' (' . e($parent['guardian_relationship']) . ')' : '' ?>
                </p>
                <?php endif; ?>
                <p class="mb-1"><strong>Occupation:</strong> <?= e($parent['occupation'] ?? '—') ?></p>
                <p class="mb-1"><strong>Phone:</strong>
                    <?= e(trim(($parent['phone_country_code'] ?? '') . ' ' . ($parent['phone'] ?? '')) ?: '—') ?></p>
                <p class="mb-1"><strong>Email:</strong> <?= e($parent['email'] ?? '—') ?></p>
                <p class="mb-0"><strong>Address:</strong> <?= e($parent['address'] ?? '—') ?></p>
            </div>
        </div>
        <?php else: ?>
        <div class="card mt-3">
            <div class="card-body">
                <h6 class="fw-bold mb-2"><i class="bi bi-person-hearts me-1"></i>Parent / Guardian</h6>
                <p class="text-muted mb-0">No guardian record linked.</p>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($student['medical_conditions']) || !empty($student['allergies']) || !empty($student['emergency_contact_name'])): ?>
        <div class="card mt-3">
            <div class="card-body">
                <h6 class="fw-bold mb-2"><i class="bi bi-heart-pulse me-1"></i>Medical &amp; Emergency</h6>
                <p class="mb-1"><strong>Medical Conditions:</strong> <?= e($student['medical_conditions'] ?? '—') ?></p>
                <p class="mb-1"><strong>Allergies:</strong> <?= e($student['allergies'] ?? '—') ?></p>
                <p class="mb-1"><strong>Emergency Contact:</strong>
                    <?= e($student['emergency_contact_name'] ?? '—') ?><?= !empty($student['emergency_contact_relationship']) ? ' (' . e($student['emergency_contact_relationship']) . ')' : '' ?>
                </p>
                <p class="mb-0"><strong>Emergency Phone:</strong> <?= e($student['emergency_contact_phone'] ?? '—') ?>
                </p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Details</h6>
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-2 text-muted small">Gender</p>
                        <p><?= e(GENDER_OPTIONS[$student['gender']] ?? $student['gender']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-2 text-muted small">Date of Birth</p>
                        <p><?= e(format_date($student['dob'])) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-2 text-muted small">Blood Group</p>
                        <p><?= e($student['blood_group'] ?? '—') ?></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-2 text-muted small">Religion</p>
                        <p><?= e($student['religion'] ?? '—') ?></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-2 text-muted small">Class / Section</p>
                        <p><?= e($class['name'] ?? '—') ?> / <?= e($section['name'] ?? '—') ?></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-2 text-muted small">Roll Number</p>
                        <p><?= e($student['roll_number'] ?? '—') ?></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-2 text-muted small">Phone</p>
                        <p><?= e(trim(($student['phone_country_code'] ?? '') . ' ' . ($student['phone'] ?? '')) ?: '—') ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-2 text-muted small">Email</p>
                        <p><?= e($student['email'] ?? '—') ?></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-2 text-muted small">Admission Date</p>
                        <p><?= e(format_date($student['admission_date'])) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-2 text-muted small">Nationality</p>
                        <p><?= e($student['nationality'] ?? '—') ?></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-2 text-muted small">Previous School</p>
                        <p><?= e($student['previous_school'] ?? '—') ?></p>
                    </div>
                    <div class="col-12">
                        <p class="mb-2 text-muted small">Address</p>
                        <p><?= e($student['address'] ?? '—') ?></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-2 text-muted small">District / Province</p>
                        <p><?= e(trim(($student['address_district'] ?? '') . ' / ' . ($student['address_province'] ?? '')) ?: '—') ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-2 text-muted small">Country</p>
                        <p><?= e($student['address_country'] ?? '—') ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-cash-stack me-1"></i>Fees</h6>
                <?php if (empty($fees)): ?>
                <p class="text-muted mb-0">No fee records yet.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Due</th>
                                <th>Paid</th>
                                <th>Status</th>
                                <th>Due Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fees as $f): ?>
                            <tr>
                                <td><?= e($f['fee_type_name'] ?? '—') ?></td>
                                <td><?= e(format_currency($f['amount_due'])) ?></td>
                                <td><?= e(format_currency($f['amount_paid'])) ?></td>
                                <td><span
                                        class="badge <?= status_badge_class($f['status']) ?>"><?= e(ucfirst($f['status'])) ?></span>
                                </td>
                                <td><?= e(format_date($f['due_date'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-paperclip me-1"></i>Documents</h6>
                <?php if (empty($documents)): ?>
                <p class="text-muted mb-0">No documents uploaded.</p>
                <?php else: ?>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($documents as $doc):
                            $docLabel = $doc['original_name'] ?: ucwords(str_replace('_', ' ', $doc['doc_type']));
                            $available = uploaded_file_exists($doc['file_path'] ?? null);
                        ?>
                    <li class="mb-1">
                        <?php if ($available): ?>
                        <i class="bi bi-file-earmark-text me-1"></i>
                        <a href="<?= e(url('documents/student/' . $doc['id'] . '/download')) ?>"
                            onclick="if(window.SA){window.SA.toast('success','Downloading file...');}"><?= e($docLabel) ?></a>
                        <?php else: ?>
                        <i class="bi bi-file-earmark-excel text-muted me-1"></i>
                        <span class="text-muted" title="This document's file could not be found in storage.">
                            <?= e($docLabel) ?> — <em>Document not available</em>
                        </span>
                        <?php endif; ?>
                        <span
                            class="badge bg-secondary ms-1"><?= e(ucwords(str_replace('_', ' ', $doc['doc_type']))) ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-receipt me-1"></i>Payment History</h6>
                    <a href="<?= e(url('payments') . '?student_id=' . $student['id']) ?>"
                        class="btn btn-sm btn-outline-secondary">View full history</a>
                </div>
                <?php if (empty($payments)): ?>
                <p class="text-muted mb-0">No payment records found.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Receipt No</th>
                                <th>Date</th>
                                <th>Fee Type</th>
                                <th>Amount</th>
                                <th>Discount</th>
                                <th>Fine</th>
                                <th>Payment Method</th>
                                <th>Collected By</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $p): ?>
                            <tr class="<?= $p['status'] === 'cancelled' ? 'opacity-50' : '' ?>">
                                <td><?= e($p['receipt_number'] ?? '—') ?></td>
                                <td><?= e(format_date($p['paid_at'] ?? null)) ?></td>
                                <td><?= e($p['fee_type_name'] ?? '—') ?></td>
                                <td><?= e(format_currency($p['amount'])) ?></td>
                                <td><?= e(format_currency($p['discount_amount'] ?? 0)) ?></td>
                                <td><?= e(format_currency($p['fine_amount'] ?? 0)) ?></td>
                                <td class="text-capitalize"><?= e(str_replace('_', ' ', $p['payment_mode'] ?? '—')) ?>
                                </td>
                                <td><?= e($p['received_by_name'] ?? '—') ?></td>
                                <td>
                                    <?php if ($p['status'] === 'cancelled'): ?>
                                    <span class="badge bg-danger">Voided</span>
                                    <?php else: ?>
                                    <span
                                        class="badge <?= status_badge_class($p['fee_status'] ?? 'paid') ?>"><?= e(ucfirst($p['fee_status'] === 'unpaid' ? 'pending' : ($p['fee_status'] ?? 'paid'))) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($p['kind'] === 'library_fine' && !empty($p['receipt_number'])): ?>
                                    <a href="<?= e(url('library/fine-receipt/' . $p['receipt_number'])) ?>"
                                        target="_blank" class="btn btn-sm btn-outline-secondary" title="View Receipt"><i
                                            class="bi bi-eye-fill"></i></a>
                                    <?php elseif (!empty($p['receipt_group'])): ?>
                                    <a href="<?= e(url('payments/receipt/' . $p['receipt_group'])) ?>"
                                        class="btn btn-sm btn-outline-secondary" title="View Receipt"><i
                                            class="bi bi-eye-fill"></i></a>
                                    <?php else: ?>
                                    —
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-award-fill me-1"></i>Recent Exam Results</h6>
                <?php if (empty($marks)): ?>
                <p class="text-muted mb-0">No exam results yet.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Exam</th>
                                <th>Subject</th>
                                <th>Marks</th>
                                <th>Grade</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($marks as $m): ?>
                            <tr>
                                <td><?= e($m['exam_name']) ?></td>
                                <td><?= e($m['subject_name']) ?></td>
                                <td><?= e($m['marks_obtained']) ?> / <?= e($m['max_marks']) ?></td>
                                <td><span class="badge bg-primary"><?= e($m['grade']) ?></span></td>
                                <td><?= e(format_date($m['exam_date'])) ?></td>
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