<?php
session_start();
require 'database.php';

// ADDED: CSRF token for the vote forms
if (empty($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}

// ADDED: sort options. Only whitelisted keys are used, never raw user input.
// "new" is your original ordering and stays the default.
$order_clauses = [
    'new' => 'stories.created_at DESC',
    'top' => 'score DESC, stories.created_at DESC',
    'hot' => '(COALESCE(SUM(votes.vote_value), 0) / POW(TIMESTAMPDIFF(HOUR, stories.created_at, NOW()) + 2, 1.5)) DESC, stories.created_at DESC',
];
$sort = $_GET['sort'] ?? 'new';
if (!is_string($sort) || !array_key_exists($sort, $order_clauses)) {
    $sort = 'new';
}
$order_by = $order_clauses[$sort];

// ADDED: 0 when logged out
$current_user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

// CHANGED: added score and user_vote columns, a LEFT JOIN on votes, GROUP BY,
// and the whitelisted ORDER BY
$stmt = $mysqli->prepare("
    SELECT stories.id, stories.title, stories.link, stories.created_at, stories.user_id,
           users.username,
           COALESCE(SUM(votes.vote_value), 0) AS score,
           COALESCE(MAX(CASE WHEN votes.user_id = ? THEN votes.vote_value END), 0) AS user_vote
    FROM stories
    JOIN users ON stories.user_id = users.id
    LEFT JOIN votes ON votes.story_id = stories.id
    GROUP BY stories.id, stories.title, stories.link, stories.created_at, stories.user_id, users.username
    ORDER BY $order_by
");
$stmt->bind_param('i', $current_user_id); // ADDED
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>News Site</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="site-header-bar">
        <h1 class="site-header-title">News Site</h1>
    </header>

    <nav class="site-nav-bar">
        <?php if (isset($_SESSION['user_id'])): ?>
            Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!
            <a class="site-nav-link" href="addStory.php">Submit a Story</a>
            <a class="site-nav-link" href="profile.php?user=<?php echo urlencode($_SESSION['username']); ?>">My Profile</a>
            <a class="site-nav-link" href="logout.php">Log Out</a>
        <?php else: ?>
            <a class="site-nav-link" href="login.php">Log In</a>
            <a class="site-nav-link" href="register.php">Register</a>
        <?php endif; ?>
        <!-- ADDED: sort links -->
        | Sort:
        <a class="site-nav-link<?php echo $sort === 'new' ? ' sort-active' : ''; ?>" href="news.php?sort=new">New</a>
        <a class="site-nav-link<?php echo $sort === 'top' ? ' sort-active' : ''; ?>" href="news.php?sort=top">Top</a>
        <a class="site-nav-link<?php echo $sort === 'hot' ? ' sort-active' : ''; ?>" href="news.php?sort=hot">Hot</a>
    </nav>

    <div class="page-content">
        <ul class="story-list">
            <?php while ($row = $result->fetch_assoc()): ?>
                <?php
                    // ADDED: vote data for this story
                    $score     = (int)$row['score'];
                    $user_vote = (int)$row['user_vote'];
                    $is_owner  = $current_user_id === (int)$row['user_id'];
                ?>
                <li class="story-list-item">
                    <!-- ADDED: vote controls -->
                    <?php if ($current_user_id && !$is_owner): ?>
                        <form method="post" action="vote.php" class="vote-form">
                            <input type="hidden" name="story_id" value="<?php echo (int)$row['id']; ?>">
                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($_SESSION['token']); ?>">
                            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
                            <button type="submit" name="vote" value="1"
                                    class="vote-btn<?php echo $user_vote === 1 ? ' voted-up' : ''; ?>"
                                    title="Upvote" aria-label="Upvote">&#9650;</button>
                            <span class="vote-score"><?php echo $score; ?></span>
                            <button type="submit" name="vote" value="-1"
                                    class="vote-btn<?php echo $user_vote === -1 ? ' voted-down' : ''; ?>"
                                    title="Downvote" aria-label="Downvote">&#9660;</button>
                        </form>
                    <?php else: ?>
                        <span class="vote-form">
                            <span class="vote-btn vote-disabled">&#9650;</span>
                            <span class="vote-score"><?php echo $score; ?></span>
                            <span class="vote-btn vote-disabled">&#9660;</span>
                        </span>
                    <?php endif; ?>

                    <a class="story-title-link"
                       href="<?php echo $row['link'] ? htmlspecialchars($row['link']) : 'story.php?id=' . (int)$row['id']; ?>"
                       <?php echo $row['link'] ? 'target="_blank"' : ''; ?>>
                        <?php echo htmlspecialchars($row['title']); ?>
                    </a>
                    <div class="story-meta-text">
                        posted by <a class="story-meta-link" href="profile.php?user=<?php echo urlencode($row['username']); ?>"><?php echo htmlspecialchars($row['username']); ?></a>
                        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $row['user_id']): ?>
                            <a class="edit-delete-link" href="edit_story.php?id=<?php echo (int)$row['id']; ?>">Edit</a>
                            <form method="post" action="delete_story.php" style="display:inline;">
                                <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                                <input type="hidden" name="token" value="<?php echo htmlspecialchars($_SESSION['token']); ?>">
                                <input type="submit" class="edit-delete-link" value="Delete" onclick="return confirm('Delete this story?');">
                            </form>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endwhile; ?>
        </ul>
    </div>

    <?php $stmt->close(); ?>
</body>
</html>