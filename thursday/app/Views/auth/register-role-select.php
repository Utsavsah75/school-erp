<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Create an Account | School ERP</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Roboto+Condensed:ital,wght@0,100..900;1,100..900&family=Share+Tech&display=swap" rel="stylesheet">
<link href="<?= e(asset('css/typography.css')) ?>" rel="stylesheet">
<style>
    body{background:linear-gradient(135deg,#4e73df 0%,#224abe 100%);min-height:100vh;padding:40px 0}
    .role-card{border:none;border-radius:16px;box-shadow:0 15px 35px rgba(0,0,0,.15);overflow:hidden;height:100%;transition:transform .15s ease}
    .role-card:hover{transform:translateY(-4px)}
    .role-card a{color:inherit;text-decoration:none;display:block;height:100%}
    .role-icon{width:56px;height:56px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:26px;color:#fff;margin-bottom:14px}
    .bg-parent{background:#4e73df}
    .bg-student{background:#1cc88a}
    .bg-teacher{background:#f6c23e}
    .bg-staff{background:#e74a3b}
</style>
</head>
<body>
<div class="container">
    <div class="text-center text-white mb-4">
        <i class="bi bi-mortarboard-fill" style="font-size:48px"></i>
        <h2 class="fw-bold mt-2 mb-1"><?= e(t('h_create_school_erp_account')) ?></h2>
        <p class="opacity-75 mb-0">Choose the option that matches you</p>
    </div>

    <div class="row g-4 justify-content-center">
        <div class="col-sm-6 col-lg-3">
            <div class="card role-card">
                <a href="<?= e(url('register/wizard?role=parent')) ?>">
                    <div class="card-body p-4">
                        <div class="role-icon bg-parent"><i class="bi bi-people-fill"></i></div>
                        <h5 class="fw-bold mb-1"><?= e(t('h_parent')) ?></h5>
                        <p class="text-muted small mb-0">Register using your child's Admission Number, then verify your phone and/or email.</p>
                    </div>
                </a>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card role-card">
                <a href="<?= e(url('register/wizard?role=student')) ?>">
                    <div class="card-body p-4">
                        <div class="role-icon bg-student"><i class="bi bi-backpack2-fill"></i></div>
                        <h5 class="fw-bold mb-1"><?= e(t('th_student')) ?></h5>
                        <p class="text-muted small mb-0">Register using your Admission Number — a verification code is sent to your parent on file.</p>
                    </div>
                </a>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card role-card">
                <a href="<?= e(url('register/wizard?role=teacher')) ?>">
                    <div class="card-body p-4">
                        <div class="role-icon bg-teacher"><i class="bi bi-person-workspace"></i></div>
                        <h5 class="fw-bold mb-1"><?= e(t('th_teacher')) ?></h5>
                        <p class="text-muted small mb-0">Register using the Employee Code assigned to you by the school.</p>
                    </div>
                </a>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card role-card">
                <a href="<?= e(url('register/wizard?role=staff')) ?>">
                    <div class="card-body p-4">
                        <div class="role-icon bg-staff"><i class="bi bi-briefcase-fill"></i></div>
                        <h5 class="fw-bold mb-1"><?= e(t('h_staff_employee')) ?></h5>
                        <p class="text-muted small mb-0">Librarian, Accountant, Receptionist, and other staff — requires an invite code from your administrator.</p>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <p class="text-center text-white mt-4 mb-0">
        Already have an account? <a href="<?= e(url('login')) ?>" class="text-white fw-bold text-decoration-underline">Sign in</a>
    </p>
</div>
</body>
</html>
