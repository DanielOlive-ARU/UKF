<?php
/**
 * user_edit.php - Edit office user (admin only)
 */
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';
include 'includes/header.php';
require_once dirname(__DIR__) . '/includes/password.php';

/* Admin-only access */
if ($_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php?msg=denied');
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
$errors = array();

/* Handle POST */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '', 'stock_user_edit')) {
        header('Location: users.php?msg=csrf');
        exit();
    }

    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $role = $_POST['role'];

    /* Validation */
    if ($username === '') {
        $errors[] = 'Username is required.';
    } else {
        /* Check for duplicate username (excluding current user) */
        $existing = Database::fetchOne(
            "SELECT id FROM users WHERE username = :username AND id != :id LIMIT 1",
            array(':username' => $username, ':id' => $id)
        );
        if ($existing) {
            $errors[] = 'Username already exists.';
        }
    }

    if ($password !== '' && $password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (!in_array($role, array('admin', 'clerk'))) {
        $errors[] = 'Invalid role selected.';
    }

    if (empty($errors)) {
        if ($password !== '') {
            /* Update with new password */
            $hashedPassword = hashPassword($password);
            Database::query(
                "UPDATE users SET username = :username, password = :password, role = :role WHERE id = :id",
                array(
                    ':username' => $username,
                    ':password' => $hashedPassword,
                    ':role'     => $role,
                    ':id'       => $id
                )
            );
        } else {
            /* Update without changing password */
            Database::query(
                "UPDATE users SET username = :username, role = :role WHERE id = :id",
                array(
                    ':username' => $username,
                    ':role'     => $role,
                    ':id'       => $id
                )
            );
        }
        header('Location: users.php?msg=updated');
        exit();
    }
}

/* Load existing user */
$user = Database::fetchOne(
    "SELECT id, username, role FROM users WHERE id = :id LIMIT 1",
    array(':id' => $id)
);

if (!$user) {
    echo '<p class="notice">User not found.</p>';
    include 'includes/footer.php';
    exit();
}

/* Use POST values if validation failed, otherwise use DB values */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $username = $user['username'];
    $role = $user['role'];
}
?>
<h2>Edit Office User</h2>

<?php if (!empty($errors)): ?>
    <?php foreach ($errors as $err): ?>
        <p class="notice"><?php echo htmlspecialchars($err); ?></p>
    <?php endforeach; ?>
<?php endif; ?>

<form action="user_edit.php" method="post">
    <?php echo Csrf::field('stock_user_edit'); ?>
    <input type="hidden" name="id" value="<?php echo $id; ?>">

    <label>Username:
        <input type="text" name="username" required maxlength="50"
               value="<?php echo htmlspecialchars($username); ?>">
    </label>

    <label>Password: <small>(leave blank to keep current)</small>
        <input type="password" name="password">
    </label>

    <label>Confirm Password:
        <input type="password" name="confirm_password">
    </label>

    <label>Role:
        <select name="role" required>
            <option value="clerk" <?php echo ($role === 'clerk') ? 'selected' : ''; ?>>Clerk</option>
            <option value="admin" <?php echo ($role === 'admin') ? 'selected' : ''; ?>>Admin</option>
        </select>
    </label>

    <p>
        <input type="submit" value="Save Changes">
        <a href="users.php">Cancel</a>
    </p>
</form>

<?php include 'includes/footer.php'; ?>
