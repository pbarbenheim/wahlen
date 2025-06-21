<?php
require_once "../includes/config.php";

$PAGE_TITLE = "Wahlen-Tool";
include "../includes/head.php";
include "../includes/header.php";

$user = include "../includes/auth.php";

// Überprüfen, ob der Benutzer angemeldet ist
if (!$user) {
    // Hier könnte noch eine Landing-Page für nicht angemeldete Benutzer sein
    header("Location: login.php");
    exit;
} else {
?>

<main>
    <h1>Willkommen im Wahlen-Tool</h1>
    <p>Hier können Sie Wahlen erstellen und verwalten.</p>
</main>

<?php
}

include "../includes/footer.php";