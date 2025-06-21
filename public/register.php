<?php

$PAGE_TITLE = "Registrieren";
include "../includes/head.php";
include "../includes/header.php";

$db = include "../includes/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $res = "";
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $res =  "<p class='error'>Bitte füllen Sie alle Felder aus!</p>";
        exit;
    }

    if ($password === $confirm_password) {
        $stmt = $db->prepare("INSERT INTO user (username, passhash) VALUES (?, ?)");
        $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
        header("Location:login.php?from=register", );
    } else {
        $res = "<p class='error'>Passwörter stimmen nicht überein!</p>";
    }
}
?>
<main>
    <h1>Registrieren</h1>
    <form method="POST" action="">
        <label for="username">Benutzername:</label>
        <input type="text" id="username" name="username" required>

        <label for="password">Passwort:</label>
        <input type="password" id="password" name="password" required>

        <label for="confirm_password">Passwort bestätigen:</label>
        <input type="password" id="confirm_password" name="confirm_password" required>

        <?php if (!empty($res)) echo $res . '<br>'; ?>

        <button type="submit">Registrieren</button>
    </form>
</main>
<?php

include "../includes/footer.php";