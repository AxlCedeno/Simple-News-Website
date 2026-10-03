<?php
session_start();
require 'database.php';

// CSRF: create a token for this session if one doesn't exist yet
if (empty($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}

$story_id = (int) $_GET['id'];

//get story
$stmt = $mysqli->prepare("
    SELECT stories.id, stories.title, stories.body, stories.link, stories.created_at, stories.user_id, users.username
    FROM stories
    JOIN users ON stories.user_id = users.id
    WHERE stories.id = ?
");
$stmt->bind_param('i', $story_id);
$stmt->execute();
$result = $stmt->get_result();
$story = $result->fetch_assoc();
$stmt->close();

if (!$story) {
    die("Story not found.");
}

// Handle new comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    // CSRF: reject the request if the token is missing or wrong
    if (!isset($_SESSION['token']) || !isset($_POST['token']) || !hash_equals($_SESSION['token'], $_POST['token'])) {
        die("Request forgery detected");
    }

    $body = $_POST['body'];
    if ($body !== '') {
        $stmt = $mysqli->prepare("INSERT INTO comments (story_id, user_id, body) VALUES (?, ?, ?)");
        $stmt->bind_param('iis', $story_id, $_SESSION['user_id'], $body);
        $stmt->execute();
        $stmt->close();
        header("Location: story.php?id=" . $story_id);
        exit;
    }
}

// Fetch comments for this story
$stmt = $mysqli->prepare("
    SELECT comments.id, comments.body, comments.created_at, comments.user_id, users.username
    FROM comments
    JOIN users ON comments.user_id = users.id
    WHERE comments.story_id = ?
    ORDER BY comments.created_at ASC
");
$stmt->bind_param('i', $story_id);
$stmt->execute();
$comments_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($story['title']); ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="site-header-bar">
        <h1 class="site-header-title">News Site</h1>
    </header>

    <div class="page-content">
        <p><a class="story-meta-link" href="news.php">&larr; Back to News</a></p>

        <h1><?php echo htmlspecialchars($story['title']); ?></h1>
        <p class="story-meta-text">posted by <?php echo htmlspecialchars($story['username']); ?></p>

        <?php if ($story['link']): ?>
        <p class="story-link-row">Link: <a class="story-link-url" href="<?php echo htmlspecialchars($story['link']); ?>" target="_blank">
            <?php echo htmlspecialchars($story['link']); ?>
        </a></p>
        <?php endif; ?>

        <p class="story-body-text"><?php echo nl2br(htmlspecialchars($story['body'])); ?></p>

        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $story['user_id']): ?>
            <p>
                <a class="edit-delete-link" href="edit_story.php?id=<?php echo $story_id; ?>">Edit</a>
                <form method="post" action="delete_story.php" style="display:inline;">
                    <input type="hidden" name="id" value="<?php echo $story_id; ?>">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($_SESSION['token']); ?>">
                    <input type="submit" class="edit-delete-link" value="Delete" onclick="return confirm('Delete this story?');">
                </form>
            </p>
        <?php endif; ?>

        <hr>
        <h2>Comments</h2>

        <?php while ($c = $comments_result->fetch_assoc()): ?>
            <div class="comment-row">
                <p><strong class="comment-author-name"><?php echo htmlspecialchars($c['username']); ?>:</strong>
                <?php echo nl2br(htmlspecialchars($c['body'])); ?></p>

                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $c['user_id']): ?>
                    <p>
                        <a class="edit-delete-link" href="edit_comment.php?id=<?php echo $c['id']; ?>">Edit</a>
                        <form method="post" action="delete_comment.php" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                            <input type="hidden" name="story_id" value="<?php echo $story_id; ?>">
                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($_SESSION['token']); ?>">
                            <input type="submit" class="edit-delete-link" value="Delete" onclick="return confirm('Delete this comment?');">
                        </form>
                    </p>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>

        <?php if (isset($_SESSION['user_id'])): ?>
            <form method="post">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($_SESSION['token']); ?>">
                <textarea class="form-textarea" name="body" rows="3" cols="50"></textarea><br>
                <input type="submit" class="primary-submit-button" value="Post Comment">
            </form>
        <?php else: ?>
            <p><a href="login.php">Log in</a> to comment.</p>
        <?php endif; ?>

        <?php $stmt->close(); ?>
    </div>
</body>
</html>