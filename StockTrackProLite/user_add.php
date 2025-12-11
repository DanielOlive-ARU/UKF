<?php
/**
 * user_add.php - Add new office user (admin only)
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
    if (!Csrf::validate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '', 'stock_user_add')) {
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
        /* Check for duplicate username */
        $existing = Database::fetchOne(
            "SELECT id FROM users WHERE username = :username LIMIT 1",
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

    if (!in_array($role, array('admin', 'clerk'))) {
        $errors[] = 'Invalid role selected.';
    }

    if (empty($errors)) {
        $hashedPassword = hashPassword($password);
        Database::query(
            "INSERT INTO users (username, password, role) VALUES (:username, :password, :role)",
            array(
                ':username' => $username,
                ':password' => $hashedPassword,
                ':role'     => $role
            )
        );
        header('Location: users.php?msg=added');
        exit();
    }
}
?>
<h2>Add Office User</h2>

<?php if (!empty($errors)): ?>
    <?php foreach ($errors as $err): ?>
        <p class="notice"><?php echo htmlspecialchars($err); ?></p>
    <?php endforeach; ?>
<?php endif; ?>

<form action="user_add.php" method="post">
    <?php echo Csrf::field('stock_user_add'); ?>

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
            <option value="admin" <?php echo (isset($role) && $role === 'admin') ? 'selected' : ''; ?>>Admin</option>
        </select>
    </label>

    <p>
        <input type="submit" value="Add User">
        <a href="users.php">Cancel</a>
    </p>
</form>

<?php include 'includes/footer.php'; ?>
