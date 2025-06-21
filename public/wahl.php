<?php
require_once "../includes/config.php";

$user = include "../includes/auth.php";
if (!$user) {
    header("Location: login.php");
    exit;
}
$db = include "../includes/db.php";
if (!isset($_GET["id"])) {
    header("Location: my_wahlen.php");
    exit;
}
$wahlId = intval($_GET['id']);

$stmt = $db->prepare("SELECT id, name FROM wahl WHERE id = ? AND user_id = ?");
$stmt->execute([$wahlId, $user]);
$wahl = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
if (count($wahl) !== 1) {
    header("Location: my_wahlen.php");
    exit;
}
$wahlName = $wahl[0]['name'];
$wahlId = $wahl[0]['id'];

$PAGE_TITLE = htmlspecialchars($wahlName);
include "../includes/head.php";
include "../includes/header.php";

$wahlgangStmt = $db->prepare("SELECT id, titel, anzahl_posten, start, end FROM wahlgang WHERE wahl_id = ? ORDER BY serial");
$vorschlagStmt = $db->prepare("SELECT v.id as id, v.name as name, w.result as result FROM vorschlag as v, wahlgang_vorschlag as w WHERE v.id = w.vorschlag_id AND w.wahlgang_id = ? ORDER BY v.name");
$wahlberechtigteStmt = $db->prepare("SELECT code FROM waehler WHERE wahl_id = ? ORDER BY id");

$wahlgangStmt->execute([$wahlId]);
$wahlgaenge = $wahlgangStmt->get_result()->fetch_all(MYSQLI_ASSOC);
for($i = 0; $i < count($wahlgaenge); $i++) {
    $wahlgangId = $wahlgaenge[$i]['id'];
    $vorschlagStmt->execute([$wahlgangId]);
    $vorschlaege = $vorschlagStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $wahlgaenge[$i]['vorschlaege'] = $vorschlaege;
}

$wahlberechtigteStmt->execute([$wahlId]);
$wahlberechtigte = $wahlberechtigteStmt->get_result()->fetch_all(MYSQLI_ASSOC);

try {
    $currentTZ = new DateTimeZone(date_default_timezone_get());
} catch (DateInvalidTimeZoneException $e) {
    exit("Ungültige Zeitzone: " . htmlspecialchars(date_default_timezone_get()));
}
?>
    <script type="text/javascript" src="/js/wahl.js"></script>
<main>
    <h1><?php echo htmlspecialchars($wahlName); ?></h1>
    <p>Hier findest du alle Informationen zu deiner Wahl</p>

    <h2>Wahlberechtigte</h2>
    <table>
        <thead>
        <tr>
            <th>#</th>
            <th>Link</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($wahlberechtigte as $index => $waehler){?>
            <tr>
                <td><?php echo htmlspecialchars($index + 1); ?></td>
                <td><a href="vote.php?code=<?php echo htmlspecialchars($waehler['code']); ?>"><?php echo htmlspecialchars($waehler['code']); ?></a></td>
                <td><button role="button" onclick="copyButtonHandler(event, this, '<?php echo $_SERVER["HTTPS"] ? "https://" : "http://" . htmlspecialchars($_SERVER["HTTP_HOST"]) ?>/vote.php?code=<?php echo htmlspecialchars($waehler['code']); ?>')">Kopieren</button></td>
            </tr>
        <?php } ?>
        </tbody>
    </table>

    <h2>Wahlgänge</h2>
    <div class="wahlgaenge">
        <?php foreach ($wahlgaenge as $wahlgang) {
            try {
                $startStr = (new DateTimeImmutable('@' . $wahlgang['start']))->setTimezone($currentTZ)->format("d.m.Y H:i");
                $endStr = (new DateTimeImmutable('@' . $wahlgang['end']))->setTimezone($currentTZ)->format("d.m.Y H:i");
            } catch (Exception $e) {
                echo "<p class='error'>Ungültige Datumswerte für Wahlgang " . htmlspecialchars($wahlgang['titel']) . ": " . htmlspecialchars($e->getMessage()) . "</p>";
                exit(1);
            }

            ?>
            <div class="wahlgang">
                <h3><?php echo htmlspecialchars($wahlgang['titel']); ?></h3>
                <p><strong>Anzahl Posten:</strong> <?php echo htmlspecialchars($wahlgang['anzahl_posten']); ?></p>
                <p><strong>Start:</strong> <?php echo $startStr; ?></p>
                <p><strong>Ende:</strong> <?php echo $endStr; ?></p>

                <h4>Kandidaten</h4>
                <ul>
                    <?php foreach ($wahlgang['vorschlaege'] as $vorschlag) { ?>
                        <li>
                            <?php echo htmlspecialchars($vorschlag['name']); ?>: <?php echo htmlspecialchars($vorschlag['result'] ?? ''); ?>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        <?php } ?>
    </div>
</main>

<?php

include "../includes/footer.php";