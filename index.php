<?php
// index.php = LOGIN page
include 'connection.php';

// Kapag naka-login na, diretso sa dashboard
if (isset($_SESSION['user'])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";
$email = "";

// Kapag pinindot ang Login button (name="login")
if (isset($_POST['login'])) {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass  = $_POST['password'] ?? '';

    // Hanapin ang user gamit ang email (prepared statement)
    $stmt   = run($conn, "SELECT id, name, email, password_hash, role FROM users WHERE email = ?", "s", [$email]);
    $result = mysqli_stmt_get_result($stmt);
    $user   = mysqli_fetch_assoc($result);

    // password_verify() = ikinukumpara ang tinype na password sa naka-hash sa database
    if ($user && password_verify($pass, $user['password_hash'])) {
        session_regenerate_id(true);   // bagong session ID para mas ligtas
        $_SESSION['user'] = [
            'id'    => (int)$user['id'],
            'name'  => $user['name'],
            'email' => $user['email'],
            'role'  => $user['role']
        ];
        header("Location: dashboard.php");
        exit;
    }
    $error = "Invalid email or password.";
}

$page_title = "Login";
include 'header.php';
?>

<div class="row justify-content-center">
  <div class="col-md-6 col-lg-5">
    <div class="card">
      <div class="card-body p-4">
        <h3 class="mb-3">Login</h3>
        <p class="text-muted">Welcome back to StockWise. Please log in to continue.</p>

        <?php if ($error != ""): ?>
          <div class="alert alert-danger py-2"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="needs-validation" novalidate>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required value="<?= e($email) ?>">
            <div class="invalid-feedback">Enter a valid email.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
            <div class="invalid-feedback">Password is required.</div>
          </div>
          <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
        </form>

        <p class="mt-3 mb-0 text-center">No account yet? <a href="register.php">Register</a></p>
      </div>
    </div>
  </div>
</div>

</main>
</body>
</html>
