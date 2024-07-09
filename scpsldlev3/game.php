<?php
session_start();
require_once 'functions.php';

// Włącz wyświetlanie błędów (usuń na produkcji)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!is_user_logged_in()) {
    redirect_to('index.php');
}

$message = '';
$error = '';
$puzzle = null;
$show_next_puzzle = false;
$current_hint = 0;
$possible_points = 5;

// Obsługa wyjścia z gry
if (isset($_GET['exit'])) {
    unset($_SESSION['current_puzzle'], $_SESSION['current_hint'], $_SESSION['possible_points']);
    redirect_to('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $game_state = handle_game_actions($_POST, $_SESSION);
        if (isset($game_state['error'])) {
            $error = $game_state['error'];
        } else {
            $puzzle = $game_state['puzzle'];
            $message = $game_state['message'];
            $show_next_puzzle = $game_state['show_next_puzzle'];
            $current_hint = $game_state['current_hint'];
            $possible_points = $game_state['possible_points'];
        }
    } catch (Exception $e) {
        $error = "Wystąpił błąd: " . $e->getMessage();
    }
} else {
    if (!isset($_SESSION['current_puzzle'])) {
        $puzzle = get_random_puzzle();
        if (!$puzzle) {
            $error = "Nie udało się pobrać zagadki. Proszę spróbować później.";
        } else {
            $_SESSION['current_puzzle'] = $puzzle;
            $_SESSION['current_hint'] = 0;
            $_SESSION['possible_points'] = 5;
        }
    } else {
        $puzzle = $_SESSION['current_puzzle'];
        $current_hint = $_SESSION['current_hint'];
        $possible_points = $_SESSION['possible_points'];
    }
}

// Zabezpieczenie przed niezdefiniowanymi zmiennymi
$puzzle = $puzzle ?? ['content' => 'Brak dostępnej zagadki', 'type' => 'text'];
$current_hint = $current_hint ?? 0;
$possible_points = $possible_points ?? 5;
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SCP-SL Zgadywanka - Gra</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>SCP-SL Zgadywanka - Gra</h1>
            <nav>
                <ul>
                    <li><a href="game.php?exit=true" class="btn">Wyjdź z gry</a></li>
                </ul>
            </nav>
        </header>

        <main>
            <section id="game">
                <h2>Zagadka</h2>
                <?php if ($error): ?>
                    <p class="message error"><?php echo htmlspecialchars($error); ?></p>
                <?php endif; ?>

                <?php if ($message): ?>
                    <p class="message <?php echo strpos($message, 'Gratulacje') !== false ? 'success' : 'info'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </p>
                <?php endif; ?>

                <?php if (!$show_next_puzzle && !$error): ?>
                    <div class="game-info">
                        <p>Możliwe punkty do zdobycia: <span class="points"><?php echo $possible_points; ?></span></p>
                    </div>
                    <div id="puzzle-content">
                        <?php echo render_puzzle_content($puzzle); ?>
                    </div>
                    <form id="guess-form" method="post">
                        <input type="text" name="guess" id="guess" placeholder="Twoja odpowiedź" required>
                        <button type="submit">Zgadnij</button>
                    </form>
                    <form id="surrender-form" method="post">
                        <input type="hidden" name="surrender" value="1">
                        <button type="submit" class="btn-secondary">Poddaj się</button>
                    </form>
                    <?php if ($current_hint > 0 && isset($puzzle['hints'])): ?>
                        <div id="hints">
                            <h3>Podpowiedzi:</h3>
                            <ul>
                                <?php for ($i = 0; $i < $current_hint && $i < count($puzzle['hints']); $i++): ?>
                                    <li><?php echo htmlspecialchars($puzzle['hints'][$i]); ?></li>
                                <?php endfor; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                <?php elseif (!$error): ?>
                    <form method="post">
                        <input type="hidden" name="next_puzzle" value="1">
                        <button type="submit">Pokaż kolejną zagadkę</button>
                    </form>
                <?php endif; ?>
            </section>
        </main>

        <footer>
            <p>&copy; 2024 SCP-SL Zgadywanka. Wszelkie prawa zastrzeżone.</p>
        </footer>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const guessForm = document.getElementById('guess-form');
        const guessInput = document.getElementById('guess');

        if (guessForm) {
            guessForm.addEventListener('submit', function(e) {
                if (!guessInput.value.trim()) {
                    e.preventDefault();
                    alert('Proszę wpisać odpowiedź przed zgadywaniem.');
                }
            });
        }
    });
    </script>
</body>
</html>