<?php
// dashboard.php = dito lahat ng CRUD ng items (Create, Read, Update, Delete)
include 'connection.php';

// Kailangan naka-login
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$me    = $_SESSION['user'];
$uid   = (int)$me['id'];
$admin = ($me['role'] == 'admin') ? 1 : 0;   // 1 = admin, 0 = ordinaryong user
// Rule: ang user ay sariling items lang ang makikita/mababago. Ang admin ay lahat.
// Sa SQL: (? = 1 OR user_id = ?)  -> kung admin, pasado lahat; kung hindi, sariling items lang.

$errors = [];
$item = ['id' => 0, 'name' => '', 'category_id' => '', 'quantity' => 0, 'price' => '0.00', 'description' => ''];

// ---------- DELETE ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if (!$id || $id < 1) {
        $_SESSION['flash'] = ['danger', 'Invalid item ID.'];
        header("Location: dashboard.php");
        exit;
    }

    $stmt = run(
        $conn,
        "DELETE FROM items WHERE id = ? AND (? = 1 OR user_id = ?)",
        "iii",
        [$id, $admin, $uid]
    );

    if ($stmt && mysqli_stmt_affected_rows($stmt) > 0) {
        $_SESSION['flash'] = ['success', 'Item deleted successfully.'];
    } else {
        $_SESSION['flash'] = [
            'danger',
            'Item was not deleted. It may not exist, or you may not have permission to delete it.'
        ];
    }

    header("Location: dashboard.php");
    exit;
}

// ---------- CREATE at UPDATE ----------
if (isset($_POST['save'])) {
    $item['id']          = (int)($_POST['id'] ?? 0);
    $item['name']        = trim($_POST['name'] ?? '');
    $item['category_id'] = $_POST['category_id'] ?? '';
    $item['quantity']    = trim($_POST['quantity'] ?? '');
    $item['price']       = trim($_POST['price'] ?? '');
    $item['description'] = trim($_POST['description'] ?? '');

    // SERVER-SIDE VALIDATION
    if ($item['name'] == '' || strlen($item['name']) > 120) {
        $errors[] = "Item name is required (max 120 characters).";
    }
    if (!ctype_digit($item['quantity']) || $item['quantity'] > 1000000) {
        $errors[] = "Quantity must be a whole number from 0 to 1,000,000.";
    }
    if (!is_numeric($item['price']) || $item['price'] < 0 || $item['price'] > 99999999) {
        $errors[] = "Price must be a number from 0 to 99,999,999.";
    }

    // Walang napiling category = NULL
    $category = ($item['category_id'] == '') ? null : (int)$item['category_id'];

    if (count($errors) == 0) {
        if ($item['id'] > 0) {
            // UPDATE
            $stmt = run($conn,
                "UPDATE items SET category_id = ?, name = ?, quantity = ?, price = ?, description = ?
                 WHERE id = ? AND (? = 1 OR user_id = ?)",
                "isissiii",
                [$category, $item['name'], (int)$item['quantity'], $item['price'], $item['description'], $item['id'], $admin, $uid]);
            $message = "Item updated.";
        } else {
            // CREATE
            $stmt = run($conn,
                "INSERT INTO items (user_id, category_id, name, quantity, price, description) VALUES (?, ?, ?, ?, ?, ?)",
                "iisiss",
                [$uid, $category, $item['name'], (int)$item['quantity'], $item['price'], $item['description']]);
            $message = "Item added.";
        }

        if (mysqli_stmt_errno($stmt) == 0) {
            $_SESSION['flash'] = ['success', $message];
            header("Location: dashboard.php");
            exit;
        }
        $errors[] = "Could not save the item. Please check the category and values.";
    }
}

// ---------- EDIT: i-load ang item sa form (galing sa link na ?edit=ID) ----------
if (isset($_GET['edit']) && !isset($_POST['save'])) {
    $stmt = run($conn,
        "SELECT id, name, category_id, quantity, price, description FROM items WHERE id = ? AND (? = 1 OR user_id = ?)",
        "iii", [(int)$_GET['edit'], $admin, $uid]);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if ($row) {
        $item = $row;
    }
}

// ---------- READ: categories (para sa dropdown) ----------
$cats = [];
$res = mysqli_query($conn, "SELECT id, name FROM categories ORDER BY name");
while ($row = mysqli_fetch_assoc($res)) {
    $cats[] = $row;
}

// ---------- READ: items + kuwenta ng summary ----------
$stmt = run($conn,
    "SELECT i.id, i.name, i.category_id, i.quantity, i.price, c.name AS category, u.name AS owner
     FROM items i
     LEFT JOIN categories c ON c.id = i.category_id
     JOIN users u ON u.id = i.user_id
     WHERE (? = 1 OR i.user_id = ?)
     ORDER BY i.updated_at DESC",
    "ii", [$admin, $uid]);
$result = mysqli_stmt_get_result($stmt);

$items = [];
$total_units = 0;
$total_value = 0;
$low_stock   = 0;
while ($row = mysqli_fetch_assoc($result)) {
    $items[] = $row;
    $total_units += $row['quantity'];
    $total_value += $row['quantity'] * $row['price'];
    if ($row['quantity'] <= 5) {
        $low_stock++;
    }
}

$page_title = "Dashboard";
include 'header.php';
?>

<div class="dashboard-heading mb-3">
  <h2><?= $admin ? 'Admin Dashboard' : 'My Dashboard' ?></h2>
  <p class="text-muted mb-0"><?= $admin ? 'Manage and monitor the inventory for all users.' : 'Manage your own inventory and keep track of your stock.' ?></p>
</div>

<!-- Summary cards -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3"><div class="card stat-card"><div class="card-body">
    <small class="text-muted"><?= $admin ? 'Total Items' : 'My Total Items' ?></small><h2><?= count($items) ?></h2><small class="text-muted">Different items in inventory</small></div></div></div>
  <div class="col-6 col-md-3"><div class="card stat-card"><div class="card-body">
    <small class="text-muted"><?= $admin ? 'Total Units' : 'My Total Units' ?></small><h2><?= (int)$total_units ?></h2><small class="text-muted">Total quantity of all items</small></div></div></div>
  <div class="col-6 col-md-3"><div class="card stat-card"><div class="card-body">
    <small class="text-muted"><?= $admin ? 'Stock Value' : 'My Stock Value' ?></small><h2>₱<?= number_format($total_value, 2) ?></h2><small class="text-muted">Value of current stock</small></div></div></div>
  <div class="col-6 col-md-3"><div class="card stat-card"><div class="card-body">
    <small class="text-muted"><?= $admin ? 'Low Stock' : 'My Low Stock' ?></small><h2 class="text-warning"><?= (int)$low_stock ?></h2><small class="text-muted">Items with 5 or less units</small></div></div></div>
</div>

<div class="row g-4">

  <!-- Form: Add / Edit item -->
  <section class="col-lg-4">
    <div class="card">
      <div class="card-body">
        <h4 class="mb-1"><?= $item['id'] > 0 ? 'Edit Stock' : 'Add New Stock' ?></h4>
        <p class="text-muted mb-3"><?= $item['id'] > 0 ? 'Update the details of this item.' : 'Enter the details of the item you want to add.' ?></p>

        <?php foreach ($errors as $er): ?>
          <div class="alert alert-danger py-2"><?= e($er) ?></div>
        <?php endforeach; ?>

        <form method="POST" class="needs-validation" novalidate>
          <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">

          <div class="mb-3">
            <label class="form-label">Item Name <span class="required-mark">*</span></label>
            <input type="text" name="name" class="form-control" required maxlength="120" value="<?= e($item['name']) ?>">
            <div class="invalid-feedback">Item name is required.</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Category <span class="text-muted">(Optional)</span></label>
            <select name="category_id" class="form-select">
              <option value="">Select category</option>
              <?php foreach ($cats as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= (string)$item['category_id'] === (string)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="row">
            <div class="col-6 mb-3">
              <label class="form-label">Quantity <span class="required-mark">*</span></label>
              <input type="number" name="quantity" class="form-control" required min="0" max="1000000" step="1" value="<?= e($item['quantity']) ?>">
              <div class="invalid-feedback">Whole number, 0 or more.</div>
            </div>
            <div class="col-6 mb-3">
              <label class="form-label">Price per unit (₱) <span class="required-mark">*</span></label>
              <input type="number" name="price" class="form-control" required min="0" max="99999999" step="0.01" value="<?= e($item['price']) ?>">
              <div class="invalid-feedback">Price must be 0 or more.</div>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Description <span class="text-muted">(Optional)</span></label>
            <textarea name="description" rows="3" class="form-control"><?= e($item['description']) ?></textarea>
          </div>

          <button type="submit" name="save" class="btn btn-primary"><?= $item['id'] > 0 ? 'Save Changes' : '+ Add Stock' ?></button>
          <?php if ($item['id'] > 0): ?>
            <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
          <?php endif; ?>
        </form>
      </div>
    </div>
  </section>

  <!-- Table: listahan ng items -->
  <section class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <div class="inventory-heading mb-3"><h4 class="mb-0">Inventory List</h4><p class="text-muted mb-0"><?= $admin ? 'All users’ items' : 'Your saved items' ?></p></div>
        <div class="row g-2 mb-3">
          <div class="col-12 col-md-7">
            <input type="text" id="search" class="form-control" placeholder="Search items...">
          </div>
          <div class="col-12 col-md-5">
            <select id="catFilter" class="form-select">
              <option value="">All categories</option>
              <?php foreach ($cats as $c): ?>
                <option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Item Name</th><th>Category</th>
                <th class="text-end">Quantity</th><th class="text-end">Price</th><th class="text-end">Stock Value</th>
                <?php if ($admin): ?><th>Owner</th><?php endif; ?>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody id="itemsBody">
              <?php foreach ($items as $row): ?>
                <tr class="item-row <?= $row['quantity'] <= 5 ? 'low-stock' : '' ?>" data-cat="<?= (int)$row['category_id'] ?>">
                  <td><?= e($row['name']) ?></td>
                  <td><?= e($row['category'] ?? '—') ?></td>
                  <td class="text-end"><?= (int)$row['quantity'] ?></td>
                  <td class="text-end">₱<?= number_format((float)$row['price'], 2) ?></td>
                  <td class="text-end">₱<?= number_format((float)$row['quantity'] * (float)$row['price'], 2) ?></td>
                  <?php if ($admin): ?><td><?= e($row['owner']) ?></td><?php endif; ?>
                  <td class="text-end text-nowrap">
                    <a href="dashboard.php?edit=<?= (int)$row['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                   
                  <form method="POST" action="dashboard.php" class="d-inline"
                        onsubmit="return confirm('Are you sure you want to delete this item?');">
                      <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                      <button type="submit" name="delete" value="1"
                              class="btn btn-sm btn-outline-danger">
                          Delete
                      </button>
                  </form>

                  </td>
                </tr>
              <?php endforeach; ?>
              <tr id="noRows" style="<?= count($items) > 0 ? 'display:none' : '' ?>">
                <td colspan="<?= $admin ? 7 : 6 ?>" class="text-center text-muted py-4">No items found. Add your first stock item using the form.</td>
              </tr>
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
