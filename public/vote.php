<?php
require_once "../includes/util.php";
$db = include "../includes/db.php";

if (!isset($_GET["code"]) && !isset($_POST["code"])) {
    header("Location: index.php");
    exit;
}
$code = $_GET["code"] ?? $_POST["code"];


$PAGE_TITLE = "Abstimmen";
include "../includes/head.php";
include("../includes/header.php");

$wahlgaengeStmt = $db->prepare("SELECT wg.id as id, wg.start as start, wg.end as end, wg.titel as titel, wg.anzahl_posten as posten, wg.serial as serial from wahlgang as wg, wahl as w, waehler as wa WHERE wa.code = ? AND wa.wahl_id = w.id AND wg.wahl_id = w.id ORDER BY wg.serial");
$stimmzettelCheckStmt = $db->prepare("SELECT COUNT(*) as count FROM stimmzettel WHERE wahlgang_id = ? AND waehler_id = (SELECT id FROM waehler WHERE code = ?)");

$wahlgaengeStmt->execute([$code]);
$wahlgaenge = $wahlgaengeStmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (count($wahlgaenge) === 0) {
    echo "<p class='error'>Ungültiger oder abgelaufener Code!</p>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['skip'] === 'reload') {
        $wahlgang = array_find($wahlgaenge, function ($wg) {
            return $wg['id'] === intval($_POST['from']);
        });
    } else {
        $oldWahlgang = array_find($wahlgaenge, function ($wg) {
            return $wg['id'] === intval($_POST['from']);
        });
        // Funktioniert nur, wenn die Wahlgänge in aufsteigender Reihenfolge sortiert sind (sind sie in durch die Abfrage)
        $nextWahlgang = array_find($wahlgaenge, function ($wg) use ($oldWahlgang) {
            return $wg['serial'] > $oldWahlgang['serial'];
        });
        if ($_POST['skip'] !== 'true') {
            // Stimmzettel verarbeiten und speichern
            $candidates = $_POST['candidates'] ?? [];
            if (count($candidates) > $oldWahlgang['posten']) {
                echo "<p class='error'>Du kannst nur bis zu " . htmlspecialchars($oldWahlgang['posten']) . " Kandidaten auswählen.</p>";
                $wahlgang = $oldWahlgang; // Zurück zum alten Wahlgang
            } else {
                $waehlerIdStmt = $db->prepare("SELECT id FROM waehler WHERE code = ?");
                $waehlerIdStmt->execute([$code]);
                $waehlerId = $waehlerIdStmt->get_result()->fetch_assoc()['id'];

                // Stimmzettel speichern
                $insertStmt = $db->prepare("INSERT INTO stimmzettel (wahlgang_id, waehler_id, inhalt) VALUES (?, ?, ?)");
                $insertStmt->execute([$oldWahlgang['id'], $waehlerId, json_encode($candidates)]);

                echo "<p class='success'>Deine Stimme wurde erfolgreich abgegeben!</p>";
            }
        }
        if (!$wahlgang && $nextWahlgang) {
            $wahlgang = $nextWahlgang;
        } else {
            // Kein nächster Wahlgang, Stimmabgabe abgeschlossen
            echo "<main>";
            echo "<h1>Abstimmung abgeschlossen</h1><p>Du hast alle Wahlgänge abgeschlossen. Vielen Dank für deine Teilnahme!</p>";
            echo "<a href='index.php'>Zurück zur Startseite</a>";
            echo "</main>";
            include "../includes/footer.php";
            exit;
        }
    }
} else {
    $wahlgang = $wahlgaenge[0];
}

$stimmzettelCheckStmt->execute([$wahlgang['id'], $code]);
$count = $stimmzettelCheckStmt->get_result()->fetch_all(MYSQLI_ASSOC)[0]["count"];
if ($count !== 0) {
    // Es wurde bereits abgestimmt
    echo "<main>";
    echo "<h1>Bereits abgestimmt</h1><p>Du hast in diesem Wahlgang bereits abgestimmt. Vielleicht gibt es einen nächsten Wahlgang, in dem du noch abstimmen kannst.</p>";
    echo "<form method='post' action=''>";
    echo "<input type='hidden' name='skip' value='true'>";
    echo "<input type='hidden' name='code' value='$code'>";
    echo "<input type='hidden' name='from' value='" . $wahlgang['id'] . "'>";
    echo "<button type='submit'>Weiter zum nächsten Wahlgang</button>";
    echo "</form>";
    echo "</main>";
    include "../includes/footer.php";
    exit;
}

try {
    $currentTZ = new DateTimeZone(date_default_timezone_get());
    $startDate = (new DateTimeImmutable('@' . $wahlgang['start']))->setTimezone($currentTZ);
    $endDate = (new DateTimeImmutable('@' . $wahlgang['end']))->setTimezone($currentTZ);
} catch (Exception $e) {
    echo "<p class='error'>Fehler beim Verarbeiten des Wahlgangs: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Prüfen, ob der Wahlgang zeitlich aktiv ist
if ($endDate->diff(new DateTimeImmutable())->invert === 0) {
    echo "<main>";
    echo "<h1>Wahlgang nicht mehr aktiv</h1><p>Dieser Wahlgang ist nicht mehr aktiv. Vielleicht gibt es einen nächsten Wahlgang, in dem du noch abstimmen kannst.</p>";
    echo "<form method='post' action=''>";
    echo "<input type='hidden' name='skip' value='true'>";
    echo "<input type='hidden' name='code' value='$code'>";
    echo "<input type='hidden' name='from' value='" . $wahlgang['id'] . "'>";
    echo "<button type='submit'>Weiter zum nächsten Wahlgang</button>";
    echo "</form>";
    echo "</main>";
    include "../includes/footer.php";
    exit;
}
if ($startDate->diff(new DateTimeImmutable())->invert === 1) {
    echo "<main>";
    echo "<h1>Wahlgang noch nicht aktiv</h1><p>Dieser Wahlgang ist noch nicht aktiv. Bitte warte, bis der Wahlgang beginnt.</p>";
    echo "<p>Startzeit: " . date("d.m.Y H:i", $wahlgang['start']) . "</p>";
    echo "<form method='post' action=''>";
    echo "<input type='hidden' name='skip' value='reload'>";
    echo "<input type='hidden' name='code' value='$code'>";
    echo "<input type='hidden' name='from' value='" . htmlspecialchars($wahlgang['id']) . "'>";
    echo "<button type='submit'>Prüfe Wahlgang erneut</button>";
    echo "</form>";
    echo "</main>";
    include "../includes/footer.php";
    exit;
}

?>

<main>
    <h1>Abstimmen für <?php echo htmlspecialchars($wahlgang['titel']); ?></h1>
    <p>Du hast noch nicht abgestimmt. Bitte wähle deine Kandidaten aus.</p>

    <form method="post" action="">
        <input type="hidden" name="code" value="<?php echo htmlspecialchars($code); ?>">
        <input type="hidden" name="from" value="<?php echo htmlspecialchars($wahlgang['id']); ?>">

        <?php
        $kandidatenStmt = $db->prepare("SELECT v.id as id, v.name as name FROM vorschlag as v, wahlgang_vorschlag as wv WHERE wv.wahlgang_id = ? AND wv.vorschlag_id = v.id");
        $kandidatenStmt->execute([$wahlgang['id']]);
        $kandidaten = $kandidatenStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        if (count($kandidaten) === 0) {
            echo "<p class='error'>Keine Kandidaten für diesen Wahlgang gefunden!</p>";
        } elseif (count($kandidaten) === 1 && $wahlgang['posten'] === 1) {
            ?>
            <p>Es gibt nur einen Kandidaten für diesen Wahlgang und nur eine zu besetzende Position. Du kannst mit Ja oder Nein abstimmen.</p>
            <p>Der Kandidat heißt <?php echo htmlspecialchars($kandidaten[0]["name"]) ?></p>
            <label>
            <input type="radio" name="candidates[]" value="<?php echo htmlspecialchars($kandidaten[0]['id']); ?>">
                Ja
            </label>
            <label>
            <input type="radio" name="candidates[]" value="no">
                Nein
            </label>
            <?php
        } else {
            echo "<p>Bitte wähle die Kandidaten aus, für die du stimmen möchtest. Du kannst bis zu " . htmlspecialchars($wahlgang['posten']) . " Kandidaten auswählen.</p>";
            foreach ($kandidaten as $kandidat) {
                echo "<div class='candidate'>";
                echo "<label>";
                echo "<input type='checkbox' name='candidates[]' value='" . htmlspecialchars($kandidat['id']) . "'>";
                echo htmlspecialchars($kandidat['name']);
                echo "</label>";
                echo "</div>";
            }
        }
        ?>

        <button type="submit">Abstimmen</button>
    </form>
</main>

<?php


include "../includes/footer.php";
