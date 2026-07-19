<?php
session_start();
$page_title = 'Forgot Password';
require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h3 class="card-title mb-3">Forgot Password?</h3>
                    <p>How would you like to reset your password?</p>

                    <form id="resetChoiceForm" method="post" action="send_reset_link.php">
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="method" id="byEmail" value="email" checked>
                                <label class="form-check-label" for="byEmail">Email Address</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="method" id="byPhone" value="phone">
                                <label class="form-check-label" for="byPhone">Phone Number</label>
                            </div>
                        </div>

                        <div id="emailGroup" class="mb-3">
                            <label for="email" class="form-label">Enter your registered email:</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="example@gmail.com">
                        </div>

                        <div id="phoneGroup" class="mb-3" style="display:none;">
                            <label for="phone" class="form-label">Enter your registered phone number:</label>
                            <div class="input-group">
                                <span class="input-group-text">+60</span>
                                <input type="text" class="form-control" id="phone" name="phone" placeholder="123456789">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" id="sendButton">Send Reset</button>
                        <button type="button" class="btn btn-outline-success ms-2" id="notifyWhatsappBtn">Notify admin via WhatsApp</button>
                    </form>

                    <script>
                        const byEmail = document.getElementById('byEmail');
                        const byPhone = document.getElementById('byPhone');
                        const emailGroup = document.getElementById('emailGroup');
                        const phoneGroup = document.getElementById('phoneGroup');

                        byEmail.addEventListener('change', () => {
                            emailGroup.style.display = '';
                            phoneGroup.style.display = 'none';
                        });
                        byPhone.addEventListener('change', () => {
                            emailGroup.style.display = 'none';
                            phoneGroup.style.display = '';
                        });

                        // WhatsApp notify button
                        const notifyBtn = document.getElementById('notifyWhatsappBtn');
                        notifyBtn.addEventListener('click', (e) => {
                            const method = document.querySelector('input[name="method"]:checked').value;
                            const user = 'User';
                            let email = document.getElementById('email').value || '';
                            let phone = document.getElementById('phone').value || '';
                            const adminNumber = '60175885869'; // no + sign

                            let msg = '🔐 Password Reset Request%0A%0A';
                            if (method === 'email') {
                                msg += 'Method: Email%0A';
                            } else {
                                msg += 'Method: Phone%0A';
                            }
                            msg += 'Email: ' + encodeURIComponent(email) + '%0A';
                            msg += 'Phone: ' + encodeURIComponent(phone) + '%0A%0A';
                            msg += 'Please reset this user\'s password.';

                            const waUrl = 'https://wa.me/' + adminNumber + '?text=' + msg;
                            window.open(waUrl, '_blank');
                        });
                    </script>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
