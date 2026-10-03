<?php
// profile.php - public profile page: profile.php?user=USERNAME
session_start();
require 'database.php';

// Filter input: username must be a non-empty string up to 50 chars (matches users.username)
$username = $_GET['user'] ?? '';
if (!is_string($username) || $username === '' || strlen($username) > 50) {
    http_response_code(400);
    exit('Invalid user.');
}

// Look up the user (prepared statement)
$stmt = $mysqli->prepare("SELECT id, username, created_at FROM users WHERE username = ?");
$stmt->bind_param('s', $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    http_response_code(404);
    exit('User not found.');
}

// This user's stories with their scores
$stmt = $mysqli->prepare("
    SELECT stories.id, stories.title, stories.link, stories.created_at,
           COALESCE(SUM(votes.vote_value), 0) AS score
    FROM stories
    LEFT JOIN votes ON votes.story_id = stories.id
    WHERE stories.user_id = ?
    GROUP BY stories.id, stories.title, stories.link, stories.created_at
    ORDER BY stories.created_at DESC
");
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$stories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Karma = total score across all of their stories
$karma = 0;
foreach ($stories as $story) {
    $karma += (int)$story['score'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($user['username']); ?> - News Site</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="site-header-bar">
        <h1 class="site-header-title">News Site</h1>
    </header>

    <nav class="site-nav-bar">
        <a class="site-nav-link" href="news.php">Home</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a class="site-nav-link" href="addStory.php">Submit a Story</a>
            <a class="site-nav-link" href="logout.php">Log Out</a>
        <?php else: ?>
            <a class="site-nav-link" href="login.php">Log In</a>
            <a class="site-nav-link" href="register.php">Register</a>
        <?php endif; ?>
    </nav>

    <div class="page-content">
        <h2 class="profile-name"><?php echo htmlspecialchars($user['username']); ?></h2>
        <div class="profile-stats">
            <span class="profile-stat"><strong><?php echo (int)$karma; ?></strong> karma</span>
            <span class="profile-stat"><strong><?php echo count($stories); ?></strong> <?php echo count($stories) === 1 ? 'story' : 'stories'; ?></span>
            <span class="profile-stat">member since <?php echo htmlspecialchars(date('F j, Y', strtotime($user['created_at']))); ?></span>
        </div>

        <?php if (count($stories) === 0): ?>
            <p class="story-meta-text">No stories yet.</p>
        <?php else: ?>
            <ul class="story-list">
                <?php foreach ($stories as $story): ?>
                    <li class="story-list-item">
                        <span class="vote-form">
                            <span class="vote-score"><?php echo (int)$story['score']; ?></span>
                        </span>
                        <a class="story-title-link"
                           href="<?php echo $story['link'] ? htmlspecialchars($story['link']) : 'story.php?id=' . (int)$story['id']; ?>"
                           <?php echo $story['link'] ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
                            <?php echo htmlspecialchars($story['title']); ?>
                        </a>
                        <div class="story-meta-text">
                            <?php echo htmlspecialchars(date('M j, Y', strtotime($story['created_at']))); ?>
                            · <a class="story-meta-link" href="story.php?id=<?php echo (int)$story['id']; ?>">comments</a>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</body>
</html>