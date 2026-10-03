<?php
session_start();
require 'database.php';

// CSRF: create a token for this session if one doesn't exist yet
if (empty($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}

// Must be logged in to submit a story
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF: reject the request if the token is missing or wrong
    if (!isset($_SESSION['token']) || !isset($_POST['token']) || !hash_equals($_SESSION['token'], $_POST['token'])) {
        die("Request forgery detected");
    }

    $title = $_POST['title'];
    $body = $_POST['body']; // optional
    $link = $_POST['link'];

    if ($title === '' || $link === '') {
        $error = "Title and link are required.";
    } else {
        $stmt = $mysqli->prepare("INSERT INTO stories (user_id, title, body, link) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('isss', $_SESSION['user_id'], $title, $body, $link);
        $stmt->execute();
        $stmt->close();

        header("Location: news.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Submit a Story</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="site-header-bar">
        <h1 class="site-header-title">News Site</h1>
    </header>

    <div class="page-content">
        <h1>Submit a Story</h1>
        <?php if (isset($error)): ?>
            <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <form method="post">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($_SESSION['token']); ?>">
            Title: <input type="text" class="form-text-input" name="title" required><br>
            Link: <input type="text" class="form-text-input" name="link" required><br>
            Body (optional):<br>
            <textarea class="form-textarea" name="body" rows="6" cols="50"></textarea><br>
            <input type="submit" class="primary-submit-button" value="Submit">
        </form>
        <p><a class="story-meta-link" href="news.php">Back to News</a></p>
    </div>
</body>
</html>
