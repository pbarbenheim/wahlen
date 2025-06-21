<?php
require_once "../includes/config.php";

$PAGE_TITLE = "Anmelden";
include "../includes/head.php";
include "../includes/header.php";

/** @var mysqli $db */
$db = include "../includes/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $res = "";
    if (empty($username) || empty($password)) {
        $res = "<p class='error'>Bitte füllen Sie alle Felder aus!</p>";
    } else {
        $stmt = $db->prepare("SELECT id, passhash FROM user WHERE username = ?");
        $stmt->execute([$username]);
        $stmt->bind_result($user_id, $passhash);
        $stmt->fetch();

        if ($user_id && password_verify($password, $passhash)) {
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['user_id'] = $user_id;
            $redirectName = $_GET['redirect'] ?? 'index';
            $redirect = match ($redirectName) {
                'create_wahl' => 'create_wahl.php',
                default => 'index.php',
            };
            header("Location: $redirect");
            exit;
        } else {
            $res = "<p class='error'>Ungültiger Benutzername oder Passwort!</p>";
        }
    }
}
?>

<main>
    <h1>Anmelden</h1>

    <?php if (isset($_GET['from']) && $_GET['from'] === 'register') {
        echo "<p class='success'>Registrierung erfolgreich! Bitte melden Sie sich an.</p>";
    }
    if (isset($_GET['from']) && $_GET['from'] === 'logout') {
        echo "<p class='success'>Sie wurden erfolgreich abgemeldet.</p>";
    }
    if (isset($_GET['from']) && $_GET['from'] === 'needto') {
        echo "<p class='info'>Sie müssen sich erst anmelden!</p>";
    } ?>

    <form method="POST" action="">
        <label for="username">Benutzername:</label>
        <input type="text" id="username" name="username" required>

        <label for="password">Passwort:</label>
        <input type="password" id="password" name="password" required>

        <?php if (!empty($res)) echo $res . '<br>'; ?>

        <button type="submit">Anmelden</button>
    </form>
    <p>Noch kein Konto? <a href="register.php">Registrieren</a></p>

</main>
<?php

include "../includes/footer.php";