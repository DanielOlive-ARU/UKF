<?php
/**
 * wh_user_add.php - Add new warehouse user (admin only, managed from office)
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

$errors = array();

/* Handle POST */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '', 'stock_wh_user_add')) {
        header('Location: wh_users.php?msg=csrf');
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
        /* Check for duplicate username */
        $existing = Database::fetchOne(
            "SELECT id FROM wh_users WHERE username = :username LIMIT 1",
            array(':username' => $username)
        );
        if ($existing) {
            $errors[] = 'Username already exists.';
        }
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    } elseif ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (!in_array($role, array('manager', 'clerk', 'qa'))) {
        $errors[] = 'Invalid role selected.';
    }

    if (empty($errors)) {
        $hashedPassword = hashPassword($password);
        Database::query(
            "INSERT INTO wh_users (username, password, role) VALUES (:username, :password, :role)",
            array(
                ':username' => $username,
                ':password' => $hashedPassword,
                ':role'     => $role
            )
        );
        header('Location: wh_users.php?msg=added');
        exit();
    }
}
?>
<h2>Add Warehouse User</h2>

<?php if (!empty($errors)): ?>
    <?php foreach ($errors as $err): ?>
        <p class="notice"><?php echo htmlspecialchars($err); ?></p>
    <?php endforeach; ?>
<?php endif; ?>

<form action="wh_user_add.php" method="post">
    <?php echo Csrf::field('stock_wh_user_add'); ?>

    <label>Username:
        <input type="text" name="username" required maxlength="50"
               value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">
    </label>

    <label>Password:
        <input type="password" name="password" required>
    </label>

    <label>Confirm Password:
        <input type="password" name="confirm_password" required>
    </label>

    <label>Role:
        <select name="role" required>
            <option value="clerk" <?php echo (isset($role) && $role === 'clerk') ? 'selected' : ''; ?>>Clerk</option>
            <option value="manager" <?php echo (isset($role) && $role === 'manager') ? 'selected' : ''; ?>>Manager</option>
            <option value="qa" <?php echo (isset($role) && $role === 'qa') ? 'selected' : ''; ?>>QA</option>
        </select>
    </label>

    <p>
        <input type="submit" value="Add User">
        <a href="wh_users.php">Cancel</a>
    </p>
</form>

<?php include 'includes/footer.php'; ?>
