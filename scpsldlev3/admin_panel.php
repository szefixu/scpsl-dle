<?php
session_start();
require_once 'functions.php';

if (!isset($_SESSION['user_id']) || !is_admin($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$puzzles = get_all_puzzles();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $type = $_POST['type'] ?? '';
                $content = $_POST['content'] ?? '';
                $answer = $_POST['answer'] ?? '';
                $hints = [
                    $_POST['hint1'] ?? '',
                    $_POST['hint2'] ?? '',
                    $_POST['hint3'] ?? '',
                    $_POST['hint4'] ?? ''
                ];
                $file = $_FILES['file'] ?? null;

                try {
                    add_puzzle($type, $content, $answer, $hints, $file);
                    $message = "Zagadka została dodana pomyślnie.";
                    $puzzles = get_all_puzzles(); // Odśwież listę zagadek
                } catch (Exception $e) {
                    $message = "Błąd podczas dodawania zagadki: " . $e->getMessage();
                }
                break;

            case 'delete':
                $puzzle_id = $_POST['puzzle_id'] ?? '';
                if ($puzzle_id) {
                    delete_puzzle($puzzle_id);
                    $message = "Zagadka została usunięta.";
                    $puzzles = get_all_puzzles(); // Odśwież listę zagadek
                }
                break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <title>SCP-SL Zgadywanka - Panel Admina</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-panel {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .admin-section {
            flex: 1;
            min-width: 300px;
        }
        .puzzle-list {
            max-height: 500px;
            overflow-y: auto;
        }
        .puzzle-item {
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        .message {
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
            background-color: #d4edda;
            color: #155724;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Panel Admina - SCP-SL Zgadywanka</h1>
            <nav>
                <ul>
                    <li><a href="index.php">Strona główna</a></li>
                    <li><a href="game.php">Gra</a></li>
                </ul>
            </nav>
        </header>

        <main class="admin-panel">
            <?php if ($message): ?>
                <div class="message"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <section class="admin-section">
                <h2>Dodaj nową zagadkę</h2>
                <form action="admin_panel.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add">
                    <select name="type" required>
                        <option value="text">Tekst</option>
                        <option value="image">Obraz</option>
                        <option value="audio">Dźwięk</option>
                        <option value="video">Wideo</option>
                    </select>
                    <textarea name="content" placeholder="Treść zagadki (lub zostaw puste dla plików)" rows="3"></textarea>
                    <input type="file" name="file" accept="image/*,audio/*,video/*">
                    <input type="text" name="answer" placeholder="Odpowiedź" required>
                    <input type="text" name="hint1" placeholder="Podpowiedź 1" required>
                    <input type="text" name="hint2" placeholder="Podpowiedź 2" required>
                    <input type="text" name="hint3" placeholder="Podpowiedź 3" required>
                    <input type="text" name="hint4" placeholder="Podpowiedź 4" required>
                    <button type="submit">Dodaj zagadkę</button>
                </form>
            </section>

            <section class="admin-section">
                <h2>Lista zagadek</h2>
                <div class="puzzle-list">
                    <?php foreach ($puzzles as $puzzle): ?>
                        <div class="puzzle-item">
                            <h3><?php echo htmlspecialchars($puzzle['type']); ?> - <?php echo htmlspecialchars(substr($puzzle['content'], 0, 30)); ?>...</h3>
                            <p>Odpowiedź: <?php echo htmlspecialchars($puzzle['answer']); ?></p>
                            <form action="admin_panel.php" method="post" style="display: inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="puzzle_id" value="<?php echo $puzzle['id']; ?>">
                                <button type="submit" onclick="return confirm('Czy na pewno chcesz usunąć tę zagadkę?');">Usuń</button>
                            </form>
                            <a href="edit_puzzle.php?id=<?php echo $puzzle['id']; ?>" class="button">Edytuj</a>
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