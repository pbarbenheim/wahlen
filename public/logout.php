<?php
$PAGE_TITLE = "Abmelden";
include "../includes/head.php";
include "../includes/header.php";

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
// Zurücksetzen der Session
$_SESSION = [];

// Umleitung zur Login-Seite
header("Location: ../login.php?from=logout");

