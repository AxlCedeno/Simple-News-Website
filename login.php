<?php
session_start();
require 'database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $mysqli->prepare("SELECT COUNT(*) AS cnt, id, password_hash FROM users WHERE username=?");

    $user = $_POST['username'];
    $stmt->bind_param('s', $user);
    $stmt->execute();

    $stmt->bind_result($cnt, $user_id, $pwd_hash);
    $stmt->fetch();
    $stmt->close();

    $pwd_guess = $_POST['password'];

    if ($cnt == 1 && password_verify($pwd_guess, $pwd_hash)) {
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $user;
        header("Location: news.php");
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Log In</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="site-header-bar">
        <h1 class="site-header-title">News Site</h1>
    </header>

    <div class="auth-page-wrapper">
        <div class="auth-card">
            <h1>Log In</h1>
            <?php if (isset($error)): ?>
                <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <form method="post">
                <input type="text" class="form-text-input" name="username" placeholder="Username"><br>
                <input type="password" class="form-password-input" name="password" placeholder="Password"><br>
                <input type="submit" class="primary-submit-button" value="Log In">
            </form>
            <p class="auth-switch-text">Don't have an account? <a href="register.php">Create an account here!</a></p>
        </div>
    </div>
</body>
</html>