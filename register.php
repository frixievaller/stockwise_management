<?php
// register.php = gumawa ng bagong account
include 'connection.php';

if (isset($_SESSION['user'])) {
    header("Location: dashboard.php");
    exit;
}

$errors = [];
$name   = "";
$email  = "";

// Kapag pinindot ang Register button (name="register")
if (isset($_POST['register'])) {
    $name  = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass  = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';

    // SERVER-SIDE VALIDATION (kahit may validation na sa browser, dito pa rin sinusuri)
    if (strlen($name) < 2 || strlen($name) > 100) {
        $errors[] = "Name must be 2 to 100 characters.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        $errors[] = "Enter a valid email address.";
    }
    if (strlen($pass) < 8) {
        $errors[] = "Password must be at least 8 characters.";
    }
    if ($pass != $pass2) {
        $errors[] = "Passwords do not match.";
    }

    // Kung walang mali, i-check kung may gumagamit na ng email
    if (count($errors) == 0) {
        $stmt   = run($conn, "SELECT id FROM users WHERE email = ?", "s", [$email]);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_fetch_assoc($result)) {
            $errors[] = "That email is already registered.";
        } else {
            // password_hash() = hindi sine-save ang mismong password, naka-hash lang
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            // Lahat ng bagong register ay "user" role lang. Admin lang ang makakapagbigay ng admin role.
            run($conn, "INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'user')", "sss", [$name, $email, $hash]);

            $_SESSION['flash'] = ['success', 'Account created! You can now log in.'];
            header("Location: index.php");
            exit;
        }
    }
}

$page_title = "Register";
include 'header.php';
?>

<div class="row justify-content-center">
  <div class="col-md-6 col-lg-5">
    <div class="card">
      <div class="card-body p-4">
        <h3 class="mb-3">Create account</h3>

        <?php foreach ($errors as $er): ?>
          <div class="alert alert-danger py-2"><?= e($er) ?></div>
        <?php endforeach; ?>

        <form method="POST" class="needs-validation" novalidate>
          <div class="mb-3">
            <label class="form-label">Full name</label>
            <input type="text" name="name" class="form-control" required minlength="2" maxlength="100" value="<?= e($name) ?>">
            <div class="invalid-feedback">Name must be 2 to 100 characters.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required maxlength="150" value="<?= e($email) ?>">
            <div class="invalid-feedback">Enter a valid email.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" id="password" class="form-control" required minlength="8">
            <div class="form-text">At least 8 characters.</div>
            <div class="invalid-feedback">Password must be at least 8 characters.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Confirm password</label>
            <input type="password" name="password2" id="password2" class="form-control" required>
            <div class="invalid-feedback">Passwords must match.</div>
          </div>
          <button type="submit" name="register" class="btn btn-primary w-100">Register</button>
        </form>

        <p class="mt-3 mb-0 text-center">Already have an account? <a href="index.php">Login</a></p>
      </div>
    </div>
  </div>
</div>

</main>
</body>
</html>
