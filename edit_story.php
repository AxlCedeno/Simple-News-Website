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

$story_id = (int) $_GET['id'];

// Fetch the story, but ONLY if it belongs to the logged-in user
$stmt = $mysqli->prepare("SELECT title, body, link, user_id FROM stories WHERE id = ?");
$stmt->bind_param('i', $story_id);
$stmt->execute();
$result = $stmt->get_result();
$story = $result->fetch_assoc();
$stmt->close();

if (!$story || $story['user_id'] != $_SESSION['user_id']) {
    die("You do not have permission to edit this story.");
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
        $stmt = $mysqli->prepare("UPDATE stories SET title=?, body=?, link=? WHERE id=? AND user_id=?");
        $stmt->bind_param('sssii', $title, $body, $link, $story_id, $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();

        header("Location: story.php?id=" . $story_id);