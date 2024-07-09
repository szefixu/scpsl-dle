<?php
session_start();
require_once 'functions.php';

if (!isset($_SESSION['user_id']) || !is_admin($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$puzzle_id = $_GET['id'] ?? '';
$puzzle = get_puzzle_by_id($puzzle_id);

if (!$puzzle) {
    header('Location: admin_panel.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $content = $_POST['content'] ?? '';
    $answer = $_POST['answer'] ?? '';
    $hints = [
        $_POST['hint1'] ?? '',
        $_POST['hint2'] ?? '',
        $_POST['hint3'] ?? '',
        $_POST['hint4'] ?? ''
    ];

    if (empty($type) || empty($content) || empty($answer) || count(array_filter($hints)) !== 4) {
        $error = "Wszystkie pola są wymagane.";
    } else {
        try {
            update_puzzle($puzzle_id, $type, $content, $answer, $hints);
            header('Location: admin_panel.php');
            exit();
        } catch (Exception $e) {
            $error = "Wystąpił błąd: " . $e->getMessage();
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
    <title>Edytuj zagadkę - SCP-SL Zgadywanka</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Edytuj zagadkę</h1>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <form action="edit_puzzle.php?id=<?php echo $puzzle_id; ?>" method="post">
            <select name="type" required>
                <option value="text" <?php echo $puzzle['type'] === 'text' ? 'selected' : ''; ?>>Tekst</option>
                <option value="image" <?php echo $puzzle['type'] === 'image' ? 'selected' : ''; ?>>Obraz</option>
                <option value="audio" <?php echo $puzzle['type'] === 'audio' ? 'selected' : ''; ?>>Dźwięk</option>
                <option value="video" <?php echo $puzzle['type'] === 'video' ? 'selected' : ''; ?>>Wideo</option>
            </select>
            <input type="text" name="content" value="<?php echo htmlspecialchars($puzzle['content']); ?>" required>
            <input type="text" name="answer" value="<?php echo htmlspecialchars($puzzle['answer']); ?>" required>
            <?php for ($i = 0; $i < 4; $i++): ?>
                <input type="text" name="hint<?php echo $i+1; ?>" value="<?php echo htmlspecialchars($puzzle['hints'][$i]); ?>" required>
            <?php endfor; ?>
            <button type="submit">Zapisz zmiany</button>
        </form>
        <p><a href="admin_panel.php">Powrót do panelu admina</a></p>
    </div>
</body>
</html>