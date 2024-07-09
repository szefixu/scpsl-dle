<?php
session_start();
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $user = verify_login($username, $password);
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header('Location: index.php');
        exit();
    } else {
        $error = "Nieprawidłowa nazwa użytkownika lub hasło.";
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <title>Logowanie - SCP-SL Zgadywanka</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Logowanie</h1>
    <?php if (isset($error)): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <form action="login.php" method="post">
        <input type="text" name="username" placeholder="Nazwa użytkownika" required>
        <input type="password" name="password" placeholder="Hasło" required>
        <button type="submit">Zaloguj się</button>
    </form>
    <p><a href="index.php">Powrót do strony głównej</a></p>
</body>