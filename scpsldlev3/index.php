<?php
session_start();
require_once 'functions.php';

$message = '';
$error = '';

// Obsługa wylogowania
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}

$ranking = get_ranking();
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SCP-SL Zgadywanka</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>SCP-SL Zgadywanka</h1>
            <nav>
                <ul>
                    <li><a href="#home">Strona główna</a></li>
                    <li><a href="#ranking">Ranking</a></li>
                    <?php if (is_user_logged_in()): ?>
                        <li><a href="game.php">Graj</a></li>
                        <?php if (is_admin($_SESSION['user_id'])): ?>
                            <li><a href="admin_panel.php">Panel Admina</a></li>
                        <?php endif; ?>
                        <li><a href="index.php?logout=1">Wyloguj się</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Logowanie</a></li>
                        <li><a href="register.php">Rejestracja</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </header>

        <main>
            <?php if ($message): ?>
                <div class="message success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="message error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <section id="home">
                <h2>Witaj w SCP-SL Zgadywance!</h2>
                <?php if (is_user_logged_in()): ?>
                    <p>Witaj, <?php echo htmlspecialchars($_SESSION['username']); ?>! Gotowy na nowe wyzwania?</p>
                    <a href="game.php" class="btn">Rozpocznij grę</a>
                <?php else: ?>
                    <p>Zaloguj się lub zarejestruj, aby rozpocząć grę.</p>
                    <div class="action-buttons">
                        <a href="login.php" class="btn">Logowanie</a>
                        <a href="register.php" class="btn">Rejestracja</a>
                    </div>
                <?php endif; ?>
            </section>

            <section id="ranking">
                <h2>Ranking graczy</h2>
                <div class="ranking-container">
                    <?php foreach ($ranking as $index => $player): ?>
                        <div class="ranking-item <?php echo $index < 3 ? 'top-' . ($index + 1) : ''; ?>">
                            <span class="rank"><?php echo $index + 1; ?></span>
                            <span class="username"><?php echo htmlspecialchars($player['username']); ?></span>
                            <span class="score"><?php echo $player['score']; ?> pkt</span>
                            <span class="accuracy"><?php echo $player['accuracy']; ?>%</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </main>

        <footer>
            <p>&copy; 2024 SCP-SL Zgadywanka. Wszelkie prawa zastrzeżone.</p>
        </footer>
    </div>
</body>
</html>