<?php
require_once "../includes/config.php";

use Random\RandomException;

include_once "../includes/util.php";
$PAGE_TITLE = "Wahl erstellen";
include "../includes/head.php";
include "../includes/header.php";

$user = include "../includes/auth.php";
if (!$user) {
    header("Location: login.php?from=needto");
    exit;
}

// Testen, ob das Formular ausgegeben oder verarbeitet werden soll
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = include "../includes/db.php";

    $name = $_POST['name'] ?? '';
    $stimmzettelCount = intval($_POST['stimmzettelCount']) ?? 0;

    // Wenn Daten nicht korrekt übermittelt wurden, dann Fehlermeldung
    if ($name == '' || $stimmzettelCount <= 0) {
        echo "<p class='error'>Bitte füllen Sie alle Felder aus!</p>";
        exit(1);
    }

    $wahlgangNamen = $_POST['wahlgang_name'] ?? [];
    $wahlgangAnzahl = $_POST['wahlgang_anzahl'] ?? [];
    $wahlgangBeginn = $_POST['wahlgang_beginn'] ?? [];
    $wahlgangEnde = $_POST['wahlgang_ende'] ?? [];
    $wahlgangKandidaten = $_POST['wahlgang_kandidaten'] ?? [];

    $wahlgangCount = count($wahlgangNamen);
    if ($wahlgangCount == 0) {
        echo "<p class='error'>Bitte fügen Sie mindestens einen Wahlgang hinzu!</p>";
        exit(1);
    }
    $wahlgangSql = "INSERT INTO wahlgang (titel, anzahl_posten, start, end, wahl_id, serial) VALUES (?, ?, ?, ?, ?, ?)";
    $wahlgangStmt = $db->prepare($wahlgangSql);

    $kandidatenSql = "INSERT INTO vorschlag (name) VALUES (?)";
    $kandidatenStmt = $db->prepare($kandidatenSql);

    $kandidatenVerbindungStmt = $db->prepare("INSERT INTO wahlgang_vorschlag (wahlgang_id, vorschlag_id) VALUES (?, ?)");

    // Erstellen der Wahl
    $wahlSql = "INSERT INTO wahl (name, user_id) VALUES (?, ?)";
    $stmt = $db->prepare($wahlSql);
    $stmt->execute([$name, $user]);
    $wahlId = $db->insert_id;

    // Erstellen der Wahlgänge und Kandidaten
    for ($i = 0; $i < $wahlgangCount; $i++) {
        $titel = $wahlgangNamen[$i] ?? '';
        $anzahl = intval($wahlgangAnzahl[$i] ?? 0);
        $start = $wahlgangBeginn[$i] ?? '';
        $end = $wahlgangEnde[$i] ?? '';
        if ($titel == '' || $anzahl <= 0 || $start == '' || $end == '') {
            echo "<p class='error'>Bitte füllen Sie alle Felder für jeden Wahlgang aus!</p>";
            exit(1);
        }

        try {
            $startDate = new DateTimeImmutable($start);
            $endDate = new DateTimeImmutable($end);
        } catch (Exception $e) {
            echo "<p class='error'>Ungültiges Datum/Zeit-Format für Wahlgang $i: " . htmlspecialchars($e->getMessage()) . "</p>";
            exit(1);
        }

        $wahlgangStmt->execute([$titel, $anzahl, $startDate->getTimestamp(), $endDate->getTimestamp(), $wahlId, $i]);
        $wahlgangId = $db->insert_id;

        // Kandidaten hinzufügen
        $kandidaten = explode("\n", $wahlgangKandidaten[$i] ?? '');
        if (count($kandidaten) == 0) {
            echo "<p class='error'>Bitte fügen Sie mindestens einen Kandidaten für jeden Wahlgang hinzu!</p>";
            exit(1);
        }
        foreach ($kandidaten as $kandidat) {
            $kandidat = trim($kandidat);
            if ($kandidat == '') continue; // Skip empty lines
            $kandidatenStmt->execute([$kandidat]);
            $vorschlagId = $db->insert_id;
            $kandidatenVerbindungStmt->execute([$wahlgangId, $vorschlagId]);
        }
    }

    // Wähler erstellen
    $waehlerSql = "INSERT INTO waehler (wahl_id, code) VALUES (?, ?)";
    $waehlerStmt = $db->prepare($waehlerSql);

    for ($i = 0; $i < $stimmzettelCount; $i++) {
        try {
            $code = uuidv4();
        } catch (RandomException $e) {
            echo "<p class='error'>Fehler beim Generieren des Wähler-Codes: " . htmlspecialchars($e->getMessage()) . "</p>";
            exit(1);
        }
        $waehlerStmt->execute([$wahlId, $code]);
    }

    echo "<p class='success'>Wahl erfolgreich erstellt!</p>";
    echo "<p>Jetzt ansehen: <a href='wahl.php?id=" . htmlspecialchars($wahlId) . "'>Wahl anzeigen</a></p>";

} else {?>

    <script type="text/javascript" src="/js/create_wahl.js"></script>

<main>
    <h1>Wahl erstellen</h1>
    <form method="POST" action="">
        <label for="name">Name der Wahl:</label>
        <input type="text" id="name" name="name" required>

        <label for="stimmzettelCount">Anzahl Wahlberechtigte:</label>
        <input type="number" id="stimmzettelCount" name="stimmzettelCount" min="1" value="10" required>

        <div id="wahlgaenge" style="grid-column: span 2;">

        </div>

        <button type="button" id="add-wahlgang">Wahlgang hinzufügen</button>

        <input type="submit" id="submit" value="Wahl erstellen">
    </form>


    <!-- Template für Wahlgang, außerhalb von Form, damit es nicht 'miterfasst' wird mit den required-Attributen -->
    <div id="wahlgang-template" class="d-none form-group">
        <span class="legend">Wahlgang</span>
        <label for="wahlgang-name">Name:</label>
        <input type="text" id="wahlgang-name" name="wahlgang_name[]" required>

        <label for="wahlgang-anzahl">Gremiengröße:</label>
        <input type="number" id="wahlgang-anzahl" name="wahlgang_anzahl[]" min="1" value="1" required>

        <label for="wahlgang-beginn">Beginn:</label>
        <input type="datetime-local" id="wahlgang-beginn" name="wahlgang_beginn[]" required>

        <label for="wahlgang-ende">Ende:</label>
        <input type="datetime-local" id="wahlgang-ende" name="wahlgang_ende[]" required>

        <label for="wahlgang-kandidaten">Kandidaten (ein Name pro Zeile)</label>
        <textarea id="wahlgang-kandidaten" name="wahlgang_kandidaten[]" rows="5" required></textarea>

        <button type="button" class="remove-wahlgang">Wahlgang entfernen</button>
    </div>
<?php
}

include  "../includes/footer.php";