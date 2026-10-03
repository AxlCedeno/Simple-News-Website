<?php
session_start();
session_destroy();
header("Location: news.php");
exit;
?>