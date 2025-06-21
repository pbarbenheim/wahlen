<header>
    <nav>
        <ul>
            <li><a href="index.php">Wahlen-Tool</a></li>
            <li><a href="create_wahl.php">Wahl erstellen</a></li>
            <?php if (include 'auth.php') { ?>
                <li><a href="my_wahlen.php">Meine Wahlen</a></li>
                <li><a href="logout.php">Abmelden</a></li>
            <?php } else { ?>
                <li><a href="login.php">Anmelden</a></li>
                <li><a href="register.php">Registrieren</a></li>
            <?php } ?>
        </ul>
    </nav>
</header>