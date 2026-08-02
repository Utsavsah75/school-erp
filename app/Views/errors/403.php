<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>403 - Access Denied | School ERP</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Roboto+Condensed:ital,wght@0,100..900;1,100..900&family=Share+Tech&display=swap" rel="stylesheet">
<link href="<?= e(function_exists('asset') ? asset('css/typography.css') : '/assets/css/typography.css') ?>" rel="stylesheet">
<style>
    body{background:#f4f6f9;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;color:#333}
    .box{text-align:center}
    h1{font-size:clamp(4rem,3rem+5vw,6rem);font-weight:var(--fw-bold);margin:0;/* intentional oversized error-code display, not the doc H1 scale */color:#e74a3b}
    p{font-size:var(--fs-h6);color:#666}
    a{display:inline-block;margin-top:20px;padding:10px 24px;background:#4e73df;color:#fff;text-decoration:none;border-radius:6px}
    a:hover{background:#3d5fc4}
</style>
</head>
<body>
    <div class="box">
        <h1>403</h1>
        <p>You don't have permission to access this page.</p>
        <a href="<?= e(function_exists('url') ? url('dashboard') : '/') ?>">Back to Dashboard</a>
    </div>
</body>
</html>
