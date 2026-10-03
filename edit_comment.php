<?php
session_start();
require 'database.php';

// CSRF: create a token for this session if one doesn't exist yet
if (empty($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$comment_id = (int) $_GET['id'];

$stmt = $mysqli->prepare("SELECT body, story_id, user_id FROM comments WHERE id = ?");
$stmt->bind_param('i', $comment_id);
$stmt->execute();
$result = $stmt->get_result();
$comment = $result->fetch_assoc();
$stmt->close();

if (!$comment || $comment['user_id'] != $_SESSION['user_id']) {
    die("You do not have permission to edit this comment.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF: reject the request if the token is missing or wrong
    if (!isset($_SESSION['token']) || !isset($_POST['token']) || !hash_equals($_SESSION['token'], $_POST['token'])) {
        die("Request forgery detected");
    }

    $body = $_POST['body'];

    if ($body === '') {
        $error = "Comment cannot be empty.";
    } else {
        $stmt = $mysqli->prepare("UPDATE comments SET body=? WHERE id=? AND user_id=?");
        $stmt->bind_param('sii', $body, $comment_id, $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();

        header("Location: story.php?id=" . $comment['story_id']);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Comment</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="site-header-bar">
        <h1 class="site-header-title">News Site</h1>
    </header>

    <div class="page-content">
        <h1>Edit Comment</h1>
        <?php if (isset($error)): ?>
            <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <form method="post">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($_SESSION['token']); ?>">
            <textarea class="form-textarea" name="body" rows="4" cols="50"><?php echo htmlspecialchars($comment['body']); ?></textarea><br>
            <input type="submit" class="primary-submit-button" value="Save Changes">
        </form>
        <p><a class="story-meta-link" href="story.php?id=<?php echo $comment['story_id']; ?>">Cancel</a></p>
    </div>
</body>
</html>