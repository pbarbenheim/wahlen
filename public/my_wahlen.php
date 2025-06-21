<?php
$PAGE_TITLE = "Meine Wahlen";
include "../includes/head.php";
include "../includes/header.php";

$user = include "../includes/auth.php";
if (!$user) {
    header("Location: login.php?from=needto");
    exit;
}
$db = include "../includes/db.php";
$stmt = $db->prepare("SELECT id, name FROM wahl WHERE user_id = ?");
$stmt->execute([$user]);
$wahlen = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<main>
    <h1>Deine Wahlen</h1>
    <?php
    if (count($wahlen) === 0) {
    ?>
    <p>Du hast noch keine Wahlen erstellt. <a href="create_wahl.php">Erstelle jetzt eine Wahl!</a></p>
    <?php
    } else {?>
    <table>
        <tr>
            <th>Name</th>
            <th>Link</th>
        </tr>
        <?php foreach ($wahlen as $wahl) { ?>
        <tr>
            <td><?php echo htmlspecialchars($wahl['name']); ?></td>
            <td><a href="wahl.php?id=<?php echo $wahl['id']; ?>">Zur Wahl</a></td>
        </tr>
        <?php }?>
    </table>
    <?php } ?>
</main>

<?php
include "../includes/footer.php";