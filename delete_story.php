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

    $story_id = (int) $_POST['id'];

    // Verify ownership BEFORE deleting anything
    $stmt = $mysqli->prepare("SELECT user_id FROM stories WHERE id = ?");
    $stmt->bind_param('i', $story_id);
    $stmt->execute();
    $story = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$story || (int)$story['user_id'] !== (int)$_SESSION['user_id']) {
        die("You do not have permission to delete this story.");
    }

    // Delete rows that depend on this story first (foreign keys)
    $stmt = $mysqli->prepare("DELETE FROM votes WHERE story_id = ?");
    $stmt->bind_param('i', $story_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $mysqli->prepare("DELETE FROM comments WHERE story_id = ?");
    $stmt->bind_param('i', $story_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $mysqli->prepare("DELETE FROM stories WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $story_id, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();

    header("Location: news.php");
    exit;
}
?>