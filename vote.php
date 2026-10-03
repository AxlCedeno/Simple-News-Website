<?php
// vote.php - handles an upvote/downvote submitted from news.php
session_start();
require 'database.php';

// 1. POST only (voting through GET links would be CSRF-prone)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}

// 2. Must be logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = (int)$_SESSION['user_id'];

// 3. CSRF token check (constant-time comparison)
$token = $_POST['token'] ?? '';
if (empty($_SESSION['token']) || !is_string($token) || !hash_equals($_SESSION['token'], $token)) {
    http_response_code(403);
    exit('Invalid request token.');
}

// 4. Filter input: story id must be a positive int, vote must be exactly 1 or -1
$story_id = filter_input(INPUT_POST, 'story_id', FILTER_VALIDATE_INT);
$vote     = filter_input(INPUT_POST, 'vote', FILTER_VALIDATE_INT);
if ($story_id === false || $story_id === null || $story_id < 1 || ($vote !== 1 && $vote !== -1)) {
    http_response_code(400);
    exit('Bad request.');
}

// Sort mode is only used to send the user back to the same view; whitelist it
$allowed_sorts = ['new', 'top', 'hot'];
$sort = $_POST['sort'] ?? 'new';
if (!is_string($sort) || !in_array($sort, $allowed_sorts, true)) {
    $sort = 'new';
}

// 5. Server-side preconditions: story must exist, and users can't vote on their own story
$stmt = $mysqli->prepare("SELECT user_id FROM stories WHERE id = ?");
$stmt->bind_param('i', $story_id);
$stmt->execute();
$story = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$story) {
    http_response_code(404);
    exit('Story not found.');
}
if ((int)$story['user_id'] === $user_id) {
    http_response_code(403);
    exit('You cannot vote on your own story.');
}

// 6. Look up any existing vote by this user on this story
$stmt = $mysqli->prepare("SELECT vote_value FROM votes WHERE user_id = ? AND story_id = ?");
$stmt->bind_param('ii', $user_id, $story_id);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existing && (int)$existing['vote_value'] === $vote) {
    // Same vote again = remove the vote (toggle off)
    $stmt = $mysqli->prepare("DELETE FROM votes WHERE user_id = ? AND story_id = ?");
    $stmt->bind_param('ii', $user_id, $story_id);
} else {
    // New vote, or switching direction. The primary key guarantees one row per user/story.
    $stmt = $mysqli->prepare(
        "INSERT INTO votes (user_id, story_id, vote_value) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE vote_value = ?"
    );
    $stmt->bind_param('iiii', $user_id, $story_id, $vote, $vote);
}
$stmt->execute();
$stmt->close();

// Post/Redirect/Get so refreshing the page doesn't resubmit the vote
header('Location: news.php?sort=' . urlencode($sort));
exit;