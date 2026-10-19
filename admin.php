<?php
// admin.php = para sa ADMIN lang: pamahalaan ang users (role/delete) at categories
include 'connection.php';

// Kailangan naka-login
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}
// ROLE-BASED ACCESS: kapag hindi admin, harang dito
if ($_SESSION['user']['role'] != 'admin') {
    http_response_code(403);
    die("403 Forbidden: Admins only. <a href='dashboard.php'>Back to dashboard</a>");
}
$me = (int)$_SESSION['user']['id'];

// ---------- Mga action (galing sa mga form sa baba) ----------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id   = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');

    if (isset($_POST['add_category'])) {
        if ($name == '' || strlen($name) > 80) {
            $_SESSION['flash'] = ['danger', 'Category name is required (max 80 characters).'];
        } else {
            $stmt = run($conn, "INSERT INTO categories (name) VALUES (?)", "s", [$name]);
            if (mysqli_stmt_errno($stmt) == 0) {
                $_SESSION['flash'] = ['success', 'Category added.'];
            } else {
                $_SESSION['flash'] = ['danger', 'Category name already exists.'];
            }
        }

    } elseif (isset($_POST['rename_category'])) {
        if ($name == '' || strlen($name) > 80) {
            $_SESSION['flash'] = ['danger', 'Category name is required (max 80 characters).'];
        } else {
            $stmt = run($conn, "UPDATE categories SET name = ? WHERE id = ?", "si", [$name, $id]);
            if (mysqli_stmt_errno($stmt) == 0) {
                $_SESSION['flash'] = ['success', 'Category updated.'];
            } else {
                $_SESSION['flash'] = ['danger', 'Category name already exists.'];
            }
        }

    } elseif (isset($_POST['delete_category'])) {
        run($conn, "DELETE FROM categories WHERE id = ?", "i", [$id]);
        $_SESSION['flash'] = ['success', 'Category deleted. Its items are kept as uncategorized.'];

    } elseif (isset($_POST['set_role'])) {
        $role = $_POST['role'] ?? '';
        if ($id == $me) {
            $_SESSION['flash'] = ['danger', 'You cannot change your own role.'];
        } elseif ($role == 'admin' || $role == 'user') {
            // Huwag hayaang mawalan ng admin access ang system.
            $target = run($conn, "SELECT role FROM users WHERE id = ?", "i", [$id]);
            $target_user = mysqli_fetch_assoc(mysqli_stmt_get_result($target));
            if (!$target_user) {
                $_SESSION['flash'] = ['danger', 'User not found.'];
            } elseif ($target_user['role'] == 'admin' && $role == 'user') {
                $count_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role = 'admin'");
                $admin_count = (int)mysqli_fetch_assoc($count_result)['total'];
                if ($admin_count <= 1) {
                    $_SESSION['flash'] = ['danger', 'The last admin account cannot be changed to a regular user.'];
                } else {
                    run($conn, "UPDATE users SET role = ? WHERE id = ?", "si", [$role, $id]);
                    $_SESSION['flash'] = ['success', 'Role updated.'];
                }
            } else {
                run($conn, "UPDATE users SET role = ? WHERE id = ?", "si", [$role, $id]);
                $_SESSION['flash'] = ['success', 'Role updated.'];
            }
        }

    } elseif (isset($_POST['delete_user'])) {
        if ($id == $me) {
            $_SESSION['flash'] = ['danger', 'You cannot delete your own account.'];
        } else {
            $target = run($conn, "SELECT role FROM users WHERE id = ?", "i", [$id]);
            $target_user = mysqli_fetch_assoc(mysqli_stmt_get_result($target));
            if (!$target_user) {
                $_SESSION['flash'] = ['danger', 'User not found.'];
            } elseif ($target_user['role'] == 'admin') {
                $count_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role = 'admin'");
                $admin_count = (int)mysqli_fetch_assoc($count_result)['total'];
                if ($admin_count <= 1) {
                    $_SESSION['flash'] = ['danger', 'The last admin account cannot be deleted.'];
                } else {
                    run($conn, "DELETE FROM users WHERE id = ?", "i", [$id]);
                    $_SESSION['flash'] = ['success', 'User deleted along with their items.'];
                }
            } else {
                run($conn, "DELETE FROM users WHERE id = ?", "i", [$id]);
                $_SESSION['flash'] = ['success', 'User deleted along with their items.'];
            }
        }
    }

    header("Location: admin.php");   // balik sa page para hindi ma-resubmit kapag nag-refresh
    exit;
}

// ---------- Kunin ang listahan ng categories at users ----------
$cats = [];
$res = mysqli_query($conn,
    "SELECT c.id, c.name, COUNT(i.id) AS total
     FROM categories c LEFT JOIN items i ON i.category_id = c.id
     GROUP BY c.id, c.name ORDER BY c.name");
while ($row = mysqli_fetch_assoc($res)) {
    $cats[] = $row;
}

$users = [];
$res = mysqli_query($conn,
    "SELECT u.id, u.name, u.email, u.role, COUNT(i.id) AS total
     FROM users u LEFT JOIN items i ON i.user_id = u.id
     GROUP BY u.id, u.name, u.email, u.role ORDER BY u.id DESC");
while ($row = mysqli_fetch_assoc($res)) {
    $users[] = $row;
}

$page_title = "Admin";
include 'header.php';
?>

<div class="row g-4">

  <!-- CATEGORIES -->
  <section class="col-lg-5">
    <h4 class="mb-3">Categories</h4>
    <div class="card mb-3">
      <div class="card-body">
        <form method="POST" class="row g-2 needs-validation" novalidate>
          <div class="col-8">
            <input type="text" name="name" class="form-control" placeholder="New category name" required maxlength="80">
          </div>
          <div class="col-4">
            <button type="submit" name="add_category" class="btn btn-success w-100">Add</button>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <?php foreach ($cats as $c): ?>
          <div class="mb-3">
            <form method="POST" class="d-flex gap-2 mb-1">
              <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
              <input type="text" name="name" class="form-control form-control-sm" value="<?= e($c['name']) ?>" required maxlength="80">
              <button type="submit" name="rename_category" class="btn btn-sm btn-outline-primary">Save</button>
            </form>
            <form method="POST" class="d-flex justify-content-between align-items-center"
                  onsubmit="return confirm('Delete this category?');">
              <small class="text-muted"><?= (int)$c['total'] ?> item(s)</small>
              <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
              <button type="submit" name="delete_category" class="btn btn-sm btn-outline-danger">Delete</button>
            </form>
          </div>
        <?php endforeach; ?>
        <?php if (count($cats) == 0): ?>
          <p class="text-muted mb-0">No categories yet.</p>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- USERS -->
  <section class="col-lg-7">
    <h4 class="mb-3">Users</h4>
    <div class="card">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead>
              <tr><th>Name</th><th>Email</th><th>Items</th><th>Role</th><th></th></tr>
            </thead>
            <tbody>
              <?php foreach ($users as $u): ?>
                <tr>
                  <td><?= e($u['name']) ?></td>
                  <td><?= e($u['email']) ?></td>
                  <td><?= (int)$u['total'] ?></td>
                  <td>
                    <?php if ((int)$u['id'] == $me): ?>
                      <span class="badge bg-primary">admin (you)</span>
                    <?php else: ?>
                      <form method="POST" class="d-flex gap-1">
                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                        <select name="role" class="form-select form-select-sm">
                          <option value="user" <?= $u['role'] == 'user' ? 'selected' : '' ?>>user</option>
                          <option value="admin" <?= $u['role'] == 'admin' ? 'selected' : '' ?>>admin</option>
                        </select>
                        <button type="submit" name="set_role" class="btn btn-sm btn-outline-primary">Set</button>
                      </form>
                    <?php endif; ?>
                  </td>
                  <td class="text-end">
                    <?php if ((int)$u['id'] != $me): ?>
                      <form method="POST" onsubmit="return confirm('Delete this user and all their items?');">
                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                        <button type="submit" name="delete_user" class="btn btn-sm btn-outline-danger">Delete</button>
                      </form>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>

</div>

</main>
</body>
</html>
