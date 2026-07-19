<?php
session_start();
require_once 'includes/config.php';

// Ensure tables exist
$createPR = "CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) DEFAULT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($createPR);

$createOTP = "CREATE TABLE IF NOT EXISTS phone_otps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(32) NOT NULL,
    code VARCHAR(8) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)",
$conn->query($createOTP);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: forgot_password.php');
    exit;
}

$method = $_POST['method'] ?? 'email';

if ($method === 'email') {
    $email = trim($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Please enter a valid email address.';
        header('Location: forgot_password.php');
        exit;
    }

    // check user exists - prefer users.email, fall back to patients.email or doctors.email
    $res = null;
    $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $res = $stmt->get_result();
    } else {
        $stmt2 = $conn->prepare('SELECT id FROM patients WHERE email = ? LIMIT 1');
        if ($stmt2) {
            $stmt2->bind_param('s', $email);
            $stmt2->execute();
            $res = $stmt2->get_result();
        } else {
            $stmt3 = $conn->prepare('SELECT id FROM doctors WHERE email = ? LIMIT 1');
            if ($stmt3) {
                $stmt3->bind_param('s', $email);
                $stmt3->execute();
                $res = $stmt3->get_result();
            }
        }
    }

    if (!$res || $res->num_rows === 0) {
        // avoid leaking existence
        $_SESSION['success'] = 'If that email is registered, you will receive a reset link.';
        header('Location: forgot_password.php');
        exit;
    }

    // generate token
    $token = bin2hex(random_bytes(16));
    $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

    $ins = $conn->prepare('INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)');
    $ins->bind_param('sss', $email, $token, $expires);
    $ins->execute();

    // reset link
    $link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/reset_password.php?token=' . $token;

    // Try to send email (may not work locally) -- fallback: show link on screen
    $subject = 'Password reset for Muslim Healthcare Centre';
    $message = "Click this link to reset your password:\n\n" . $link . "\n\nThis link expires in 1 hour.";
    $headers = 'From: noreply@localhost' . "\r\n" . 'Reply-To: noreply@localhost';

    $sent = false;
    if (function_exists('mail')) {
        $sent = @mail($email, $subject, $message, $headers);
    }

    if ($sent) {
        $_SESSION['success'] = 'A reset link has been sent to your email (may take a minute).';
        header('Location: forgot_password.php');
        exit;
    }

    // Local fallback: show link to user (for testing on localhost)
    $_SESSION['info'] = 'Mail could not be sent from this server. Use the link below to reset your password (testing only).';
    $_SESSION['reset_link'] = $link;
    header('Location: forgot_password.php');
    exit;

} else {
    // Phone OTP flow
    $phone = trim($_POST['phone'] ?? '');
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if ($phone === '') {
        $_SESSION['error'] = 'Please enter your phone number.';
        header('Location: forgot_password.php');
        exit;
    }

    // check user exists - prefer users.phone, fall back to patients.phone or doctors.phone
    $res = null;
    $stmt = $conn->prepare('SELECT id FROM users WHERE phone = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('s', $phone);
        $stmt->execute();
        $res = $stmt->get_result();
    } else {
        // try patients table
        $stmt2 = $conn->prepare('SELECT id FROM patients WHERE phone = ? LIMIT 1');
        if ($stmt2) {
            $stmt2->bind_param('s', $phone);
            $stmt2->execute();
            $res = $stmt2->get_result();
        } else {
            $stmt3 = $conn->prepare('SELECT id FROM doctors WHERE phone = ? LIMIT 1');
            if ($stmt3) {
                $stmt3->bind_param('s', $phone);
                $stmt3->execute();
                $res = $stmt3->get_result();
            }
        }
    }

    if (!$res || $res->num_rows === 0) {
        $_SESSION['success'] = 'If that phone number is registered, you will receive an OTP.';
        header('Location: forgot_password.php');
        exit;
    }

    $code = random_int(100000, 999999);
    $expires = date('Y-m-d H:i:s', time() + 600); // 10 minutes

    $ins = $conn->prepare('INSERT INTO phone_otps (phone, code, expires_at) VALUES (?, ?, ?)');
    $ins->bind_param('sss', $phone, $code, $expires);
    $ins->execute();
    // Try MessageBird first if configured
    $sentSms = false;
    if (defined('MESSAGEBIRD_API_KEY') && MESSAGEBIRD_API_KEY !== '' && defined('MESSAGEBIRD_ORIGINATOR') && MESSAGEBIRD_ORIGINATOR !== '') {
        $mbKey = MESSAGEBIRD_API_KEY;
        $originator = MESSAGEBIRD_ORIGINATOR;
        $recipients = $phone; // MessageBird expects numbers without + for some regions, but we will use full
        $body = "Your verification code is: {$code}";

        $url = 'https://rest.messagebird.com/messages';
        $post = json_encode([
            'recipients' => [$phone],
            'originator' => $originator,
            'body' => $body,
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: AccessKey ' . $mbKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            $sentSms = true;
        }
    }

    // Fallback: try Twilio if MessageBird not configured or failed
    if (!$sentSms && defined('TWILIO_SID') && TWILIO_SID !== '' && defined('TWILIO_TOKEN') && TWILIO_TOKEN !== '' && defined('TWILIO_FROM') && TWILIO_FROM !== '') {
        $twilioSid = TWILIO_SID;
        $twilioToken = TWILIO_TOKEN;
        $twilioFrom = TWILIO_FROM;
        $to = '+' . $phone;
        $body = "Your verification code is: {$code}";

        $url = "https://api.twilio.com/2010-04-01/Accounts/{$twilioSid}/Messages.json";
        $data = http_build_query([
            'To' => $to,
            'From' => $twilioFrom,
            'Body' => $body,
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_USERPWD, $twilioSid . ':' . $twilioToken);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            $sentSms = true;
        }
    }

    $_SESSION['info'] = $sentSms ? 'OTP sent via SMS.' : 'OTP generated (testing). SMS not configured or failed; OTP shown for testing.';
    $_SESSION['otp_code'] = $code; // keep for testing fallback
    $_SESSION['otp_phone'] = $phone;
    header('Location: verify_otp.php');
    exit;
}
