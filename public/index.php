<?php
session_start();
require_once 'db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR uid = ?");
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_uid'] = $user['uid'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];

        // Check if this is their first login (has not seen welcome popup)
        if (isset($user['has_seen_welcome']) && $user['has_seen_welcome'] == 0) {
            $_SESSION['show_welcome_modal'] = true;

            // Update database so it never triggers again for this user
            $update = $pdo->prepare("UPDATE users SET has_seen_welcome = 1 WHERE uid = ?");
            $update->execute([$user['uid']]);
        }

        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid login credentials or account not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>The premiere mystery shopping company. Welcome</title>
    <link rel="icon" type="image/x-icon" href="assets/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        html { font-family: sans-serif; height: 100%; box-sizing: border-box; }
        body { margin: 0; padding: 0; background-color: #f1f4f8; font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; height: 100%; }
        *, *:before, *:after { box-sizing: border-box; transition: all 0.2s ease-in-out; }
        .top-header { background-color: #002e5b; width: 100%; height: 65px; display: flex; align-items: center; padding: 0 30px; }
        .top-header img { height: 53px; width: auto; }
        .login-wrapper { display: flex; justify-content: center; align-items: center; min-height: calc(100vh - 65px); padding: 20px; }
        .login-card { background: #ffffff; width: 100%; max-width: 420px; padding: 35px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); border: 1px solid #e2e8f0; }
        .logo-center { text-align: center; margin-bottom: 20px; }
        .logo-center img { height: 50px; width: auto; }
        .instruction-text { font-size: 13px; color: #444; line-height: 1.5; margin-bottom: 20px; font-weight: 500; }
        label { display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 6px; }
        .input-box-wrapper { position: relative; margin-bottom: 15px; }
        input { width: 100%; height: 40px; padding: 5px 12px; border: 1px solid #ccc; border-left: 2px solid #a94442; border-radius: 6px; font-size: 14px; outline: none; background: #fff; }
        input:focus { border-color: #4484f1; }
        .error-message { color: #eb3d3d; font-size: 12px; margin-top: 5px; font-weight: 500; }
        .toggle-password { position: absolute; right: 12px; top: 32px; color: #999; cursor: pointer; font-size: 14px; }
        .btn { width: 100%; height: 40px; border-radius: 6px; font-weight: 500; cursor: pointer; border: none; display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 14px; text-decoration: none; }
        .btn-login { background-color: #4484f1; color: white; margin-top: 5px; }
        .btn-login:hover { background-color: #3367d6; }
        .btn-signup { background-color: #7ca8ff; color: white; margin-top: 15px; }
        .btn-signup:hover { background-color: #6193ff; }
        .forgot-link { display: inline-block; color: #4484f1; font-size: 13px; text-decoration: none; margin: 15px 0; }
        hr { border: 0; border-top: 1px solid #eee; margin: 15px 0; }
        .help-footer { text-align: right; margin-top: 15px; }
        .help-footer a { color: #4484f1; font-size: 13px; text-decoration: none; }
    </style>
</head>
<body>
    <div class="top-header">
        <img src="assets/logo1.png" alt="Secret Shopper">
    </div>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="logo-center">
                <img src="assets/logo2.png" alt="Secret Shopper">
            </div>
            <div class="instruction-text">
                Log in using your User ID or email to access your personalized shopper portal and assigned mystery shops.
            </div>
            <?php if (!empty($error)): ?>
                <div class="error-message" style="display:block; margin-bottom:15px;"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="input-box-wrapper">
                    <label>User ID or Email</label>
                    <input type="text" name="identifier" required>
                </div>
                <div class="input-box-wrapper">
                    <label>Password</label>
                    <input type="password" name="password" id="login-password" required>
                    <i class="fas fa-eye toggle-password" id="toggle-pwd"></i>
                </div>
                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt"></i> Log In
                </button>
            </form>
            <a href="#" class="forgot-link">Forgot Password?</a>
            <hr>
            <button type="button" class="btn btn-signup" onclick="window.location.href='signup.php'">
                <i class="fas fa-user-plus"></i> Sign up to Shop
            </button>
            <div class="help-footer"><a href="#">Help</a></div>
        </div>
    </div>
    <script>
        const togglePwd = document.getElementById('toggle-pwd');
        const passwordInput = document.getElementById('login-password');
        togglePwd.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>