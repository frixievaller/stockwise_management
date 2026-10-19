<?php
// header.php
// Itaas na bahagi ng bawat page (head + navbar). Ini-include pagkatapos ng PHP logic ng page.
// Kailangan munang mag-set ng $page_title ang page bago i-include ito.

// Flash message = isang beses lang lalabas na mensahe (hal. "Item added.")
$flash = null;
if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}
$nav_user = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($page_title) ?> | StockWise</title>
  <?php if (file_exists(__DIR__ . '/bootstrap.min.css')): ?>
    <link href="bootstrap.min.css" rel="stylesheet">
  <?php else: ?>
    <link href="offline.css" rel="stylesheet">
  <?php endif; ?>
  <?php if (file_exists(__DIR__ . '/bootstrap.bundle.min.js')): ?>
    <script src="bootstrap.bundle.min.js" defer></script>
  <?php else: ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
  <?php endif; ?>
  <link href="style.css" rel="stylesheet">
  <script src="app.js" defer></script>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-md navbar-dark bg-primary">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= $nav_user ? 'dashboard.php' : 'index.php' ?>">📦 StockWise</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div id="nav" class="collapse navbar-collapse">
      <ul class="navbar-nav me-auto">
        <?php if ($nav_user): ?>
          <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
          <?php if ($nav_user['role'] == 'admin'): ?>
            <li class="nav-item"><a class="nav-link" href="admin.php">Admin</a></li>
          <?php endif; ?>
        <?php endif; ?>
      </ul>
      <ul class="navbar-nav align-items-md-center">
        <?php if ($nav_user): ?>
          <li class="nav-item"><span class="navbar-text me-3"><?= e($nav_user['name']) ?>
            <span class="badge bg-light text-primary"><?= e($nav_user['role']) ?></span></span></li>
          <li class="nav-item"><button type="button" class="btn btn-outline-light btn-sm" data-confirm-message="Do you want to log out?" data-confirm-detail="Are you sure you want to log out of your StockWise account?" data-confirm-url="logout.php">↪ Logout</button></li>
        <?php else: ?>
          <?php if (($page_title ?? '') === 'Login'): ?>
            <li class="nav-item"><a class="btn btn-outline-light btn-sm" href="register.php">Register</a></li>
          <?php elseif (($page_title ?? '') === 'Register'): ?>
            <li class="nav-item"><a class="btn btn-outline-light btn-sm" href="index.php">Login</a></li>
          <?php else: ?>
            <li class="nav-item"><a class="nav-link" href="index.php">Login</a></li>
            <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
          <?php endif; ?>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<main class="container py-4">
<?php if ($flash): ?>
  <div class="alert alert-<?= e($flash[0]) ?> alert-dismissible fade show">
    <?= e($flash[1]) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<?php if ($nav_user): ?>
<div class="confirm-backdrop" id="confirmDialog" aria-hidden="true">
  <div class="confirm-box" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
    <div class="confirm-icon" aria-hidden="true">?</div>
    <h2 id="confirmTitle">Do you want to log out?</h2>
    <p id="confirmDetail">Are you sure you want to log out of your StockWise account?</p>
    <div class="confirm-actions">
      <button type="button" class="btn btn-outline-secondary" id="cancelConfirm">Cancel</button>
      <button type="button" class="btn btn-danger" id="acceptConfirm">Yes, Log Out</button>
    </div>
  </div>
</div>
<?php endif; ?>
