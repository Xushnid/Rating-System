
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Tizimga kirish - Rating System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
	
	
</head>
<body class="d-flex align-items-center justify-content-center">
    <main class="w-100" style="max-width: 420px;">
        <div class="card p-2">
            <div class="card-body">
                <div class="text-center mb-4">
                    <h1 class="h3 mb-3 fw-bold"><i class="bi bi-graph-up-arrow me-2 text-primary"></i>Rating System</h1>
                    <p class="text-muted">Tizimga kirish uchun ma'lumotlaringizni kiriting</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" action="/login" novalidate>
                    <div class="mb-3">
                        <label for="username" class="form-label fw-medium">Login</label>
                        <input type="text" id="username" name="username" class="form-control form-control-lg" value="<?= htmlspecialchars($username) ?>" required autofocus>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label fw-medium">Parol</label>
                        <input type="password" id="password" name="password" class="form-control form-control-lg" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">Kirish</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>