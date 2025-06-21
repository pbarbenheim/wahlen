<?php
require_once "../includes/config.php";

$user = include "../includes/auth.php";
if (!$user) {
    header("Location: login.php?from=needto");
    exit;
}
$db = include "../includes/db.php";
$PAGE_TITLE = "Wahl Auswertung";
include "../includes/head.php";
include "../includes/header.php";

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    header("Location: index.php");
    exit;
}

$wahlgangId = $_POST['wahlgang'];
if (!$wahlgangId) {
    header("Location: index.php");
    exit;
}

$wahlgangStmt = $db->prepare("SELECT wg.id as id, wg.titel as titel, wg.start as start, wg.end as end, wg.anzahl_posten as posten, wg.wahl_id as wahl_id FROM wahlgang AS wg, wahl AS w WHERE wg.id = ? AND wg.wahl_id = w.id AND w.user_id = ?");
$wahlgangStmt->execute([$wahlgangId, $user]);
$wahlgangRes = $wahlgangStmt->get_result()->fetch_all(MYSQLI_ASSOC);
if (count($wahlgangRes) === 0) {
    echo "<p class='error'>Ungültiger oder abgelaufener Wahlgang!</p>";
    exit;
}
$wahlgang = $wahlgangRes[0];

// Prüfe, ob der Wahlgang auswertbar ist. Genau dann wenn, Zeit abgelaufen ist oder alle Stimmen abgegeben sind.
$wahlberechtigteStmt = $db->prepare("SELECT COUNT(*) as count FROM waehler WHERE wahl_id = (SELECT wahl_id FROM wahlgang WHERE id = ?)");
$stimmzettelStmt = $db->prepare("SELECT s.inhalt AS stimme FROM stimmzettel AS s WHERE wahlgang_id = ?");

$wahlberechtigteStmt->execute([$wahlgang["id"]]);
$wahlberechtigteCount = $wahlberechtigteStmt->get_result()->fetch_assoc()['count'];

$stimmzettelStmt->execute([$wahlgang["id"]]);
$stimmzettel = $stimmzettelStmt->get_result()->fetch_all(MYSQLI_ASSOC);

try {
    $currentTZ = new DateTimeZone(date_default_timezone_get());
    $end = (new DateTimeImmutable('@' . $wahlgang['end']))->setTimezone($currentTZ);
} catch (Exception $e) {
    echo "<p class='error'>Fehler bei der Zeitzonenkonvertierung: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

count($stimmzettel) == $wahlberechtigteCount || $end->diff(new DateTimeImmutable())->invert === 0 ? $auswertbar = true : $auswertbar = false;

if (!$auswertbar) {
    ?>
    <main>
    <h1>Wahl Auswertung</h1>
    <p>Der Wahlgang <strong><?php echo htmlspecialchars($wahlgang['titel']); ?></strong> ist noch nicht auswertbar. Entweder sind noch nicht alle Stimmen abgegeben oder die Zeit ist noch nicht abgelaufen.</p>
        <a href="wahl.php?id=<?php echo htmlspecialchars($wahlgang['wahl_id']); ?>" class="button">Zurück zur gesamten Wahl</a>
    </main>
<?php
    include "../includes/footer.php";
    exit;
}

// Wahl ist jetzt auswertbar
$kandidatenStmt = $db->prepare("SELECT v.id as id, v.name as name FROM vorschlag as v, wahlgang_vorschlag as wv WHERE wv.wahlgang_id = ? AND wv.vorschlag_id = v.id");
$kandidatenStmt->execute([$wahlgangId]);
$kandidaten = $kandidatenStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$kandidatenResult = [];
foreach ($kandidaten as $kandidat) {
    $kandidatenResult[$kandidat["id"]] = 0;
}
$posten = $wahlgang["posten"];

foreach ($stimmzettel as $stimme) {
    $stimme = json_decode($stimme["stimme"], true);
    if (is_array($stimme)) {
        if (count($stimme) <= $posten) {
            foreach ($stimme as $kandidatId) {
                if (isset($kandidatenResult[$kandidatId])) {
                    $kandidatenResult[$kandidatId]++;
                }
            }
        }
    }
}

// Auszählung in DB übertragen
$auswertungUpdate = $db->prepare("UPDATE wahlgang_vorschlag SET result = ? WHERE wahlgang_id = ? AND vorschlag_id = ?");

foreach ($kandidatenResult as $k => $r) {
    $auswertungUpdate->execute([$r, $wahlgangId, $k]);
}

?>

<main>
    <h1>Auswertung</h1>
    <p class="success">Der Wahlgang wurde erfolgreich ausgewertet</p>
    <a href="wahl.php?id=<?php echo htmlspecialchars($wahlgang['wahl_id']); ?>" class="button">Zurück zur gesamten Wahl</a>
</main>

<?php

include "../includes/footer.php";
