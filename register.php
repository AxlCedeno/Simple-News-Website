<?php
session_start();
require 'database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if ($username === '' || $password === '') {
        $error = "Username and password are required.";
    } elseif (preg_match('/\s/', $username)) {
        $error = "Username cannot contain spaces.";
    } else {
        $stmt = $mysqli->prepare("SELECT COUNT(*) AS cnt FROM users WHERE username=?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if ($row['cnt'] > 0) {
            $error = "That username is already taken.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $mysqli->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
            $stmt->bind_param('ss', $username, $hash);
            $stmt->execute();
            $new_id = $stmt->insert_id;
            $stmt->close();

            $_SESSION['user_id'] = $new_id;
            $_SESSION['username'] = $username;
            header("Location: login.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Register</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="site-header-bar">
        <h1 class="site-header-title">News Site</h1>
    </header>

    <div class="auth-page-wrapper">
        <div class="auth-card">
            <h1>Create an Account</h1>
            <?php if (isset($error)): ?>
                <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <form method="post">
                <input type="text" class="form-text-input" name="username" placeholder="Username"><br>
                <input type="password" class="form-password-input" name="password" placeholder="Password"><br>
                <input type="submit" class="primary-submit-button" value="Register">
            </form>
            <p class="auth-switch-text">Already have an account? <a href="login.php">Log in</a></p>
        </div>
    </div>
</body>
</html>