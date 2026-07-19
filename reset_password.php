<?php
session_start();
require_once 'includes/config.php';

$token = $_GET['token'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if ($password === '' || $password !== $confirm) {
        $_SESSION['error'] = 'Passwords must match and not be empty.';
        header('Location: reset_password.php?token=' . urlencode($token));
        exit;
    }

    // validate token
    $stmt = $conn->prepare('SELECT email, expires_at, used FROM password_resets WHERE token = ? LIMIT 1');
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        $_SESSION['error'] = 'Invalid or expired token.';
        header('Location: forgot_password.php');
        exit;
    }

    $row = $res->fetch_assoc();
    if ($row['used']) {
        $_SESSION['error'] = 'This reset token has already been used.';
        header('Location: forgot_password.php');
        exit;
    }

    if (strtotime($row['expires_at']) < time()) {
        $_SESSION['error'] = 'Reset token expired.';
        header('Location: forgot_password.php');
        exit;
    }

    $email = $row['email'];
    // Update user password
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $u = $conn->prepare('UPDATE users SET password = ? WHERE email = ?');
    $u->bind_param('ss', $hash, $email);
    $u->execute();

    // mark token used
    $m = $conn->prepare('UPDATE password_resets SET used = 1 WHERE token = ?');
    $m->bind_param('s', $token);
    $m->execute();

    $_SESSION['success'] = 'Your password has been reset. You can now login.';
    header('Location: login.php');
    exit;
}

require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h4 class="mb-3">Create New Password</h4>
                    <form method="post" action="reset_password.php">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        <div class="mb-3">
                            <label>New Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Confirm Password</label>
                            <input type="password" name="confirm" class="form-control" required>
                        </div>
                        <button class="btn btn-primary">Reset Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
