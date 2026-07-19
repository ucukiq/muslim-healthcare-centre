<?php
session_start();
require_once 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');
    $phone = trim($_POST['phone'] ?? $_SESSION['otp_phone'] ?? '');

    if ($code === '' || $phone === '') {
        $_SESSION['error'] = 'Please enter the OTP code.';
        header('Location: verify_otp.php');
        exit;
    }

    $stmt = $conn->prepare('SELECT id, expires_at, used FROM phone_otps WHERE phone = ? AND code = ? ORDER BY created_at DESC LIMIT 1');
    $stmt->bind_param('ss', $phone, $code);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        $_SESSION['error'] = 'Invalid code.';
        header('Location: verify_otp.php');
        exit;
    }

    $row = $res->fetch_assoc();
    if ($row['used'] || strtotime($row['expires_at']) < time()) {
        $_SESSION['error'] = 'OTP expired or already used.';
        header('Location: verify_otp.php');
        exit;
    }

    // mark used
    $u = $conn->prepare('UPDATE phone_otps SET used = 1 WHERE id = ?');
    $u->bind_param('i', $row['id']);
    $u->execute();

    // Allow user to create new password: show a form
    if (isset($_POST['new_password'])) {
        $pw = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];
        if ($pw === '' || $pw !== $confirm) {
            $_SESSION['error'] = 'Passwords must match.';
            header('Location: verify_otp.php');
            exit;
        }

        // update user's password (assumes users.phone column)
        $hash = password_hash($pw, PASSWORD_DEFAULT);
        $up = $conn->prepare('UPDATE users SET password = ? WHERE phone = ?');
        $up->bind_param('ss', $hash, $phone);
        $up->execute();

        $_SESSION['success'] = 'Password reset successful. You can login now.';
        header('Location: login.php');
        exit;
    }

}

require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h4 class="mb-3">Verify OTP</h4>
                    <?php if(isset($_SESSION['info'])): ?>
                        <div class="alert alert-info"><?php echo $_SESSION['info']; unset($_SESSION['info']); ?></div>
                    <?php endif; ?>
                    <?php if(isset($_SESSION['otp_code'])): ?>
                        <div class="alert alert-warning">Test OTP (local): <strong><?php echo $_SESSION['otp_code']; ?></strong></div>
                    <?php endif; ?>

                    <form method="post" action="verify_otp.php">
                        <div class="mb-3">
                            <label>Phone (without country code)</label>
                            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($_SESSION['otp_phone'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label>OTP Code</label>
                            <input type="text" name="code" class="form-control" required>
                        </div>
                        <button class="btn btn-primary">Verify OTP</button>
                    </form>

                    <hr>

                    <h5>Create New Password</h5>
                    <form method="post" action="verify_otp.php">
                        <input type="hidden" name="code" value="">
                        <div class="mb-3">
                            <label>New Password</label>
                            <input type="password" name="new_password" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label>Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control">
                        </div>
                        <button class="btn btn-success">Reset Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
