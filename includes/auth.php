<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
    return $_SESSION['user_id'];
} else {
    return false;
}