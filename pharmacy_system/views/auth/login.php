<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login — <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
<div class="login-wrapper">
    <div class="card login-card">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <i class="bi bi-prescription2 text-primary" style="font-size: 3rem;"></i>
                <h3 class="mt-2 mb-0"><?= APP_NAME ?></h3>
                <p class="text-muted small">Pharmacy Management System</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger small"><?= sanitize($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="<?= url('index.php?page=login') ?>" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" name="username" class="form-control" required autofocus>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-box-arrow-in-right"></i> Login</button>
            </form>

            <div class="mt-4 small text-muted">
                <strong>Demo accounts</strong> (password: <code>password123</code>):<br>
                <em>Platform:</em> superadmin<br>
                <em>PharmaCare Plus:</em> admin · pharmacist · cashier<br>
                <em>MediLink Pharmacy:</em> medilink_admin
            </div>
        </div>
    </div>
</div>
</body>
</html>
