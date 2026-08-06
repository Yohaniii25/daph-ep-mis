<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect settings to profile page
header("Location: profile.php");
exit();