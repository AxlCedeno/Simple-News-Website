<?php
session_start();
require 'database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF: reject the request if the token is missing or wrong
    if (!isset($_SESSION['token']) || !isset($_POST['token']) || !hash_equals($_SESSION['token'], $_POST['token'])) {
        die("Request forgery detected");
    }

    $comment_id = (int) $_POST['id'];
    $story_id = (int) $_POST['story_id']; // to redirect back to the right story

    $stmt = $mysqli->prepare("DELETE FROM comments WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $comment_id, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();

    header("Location: story.php?id=" . $story_id);
    exit;
}
?>