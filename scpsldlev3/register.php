<?php
session_start();
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($password !== $confirm_password) {
        $error = "Hasła nie są zgodne.";
    } else {
        $user_id = register_user($username, $email, $password);
        if ($user_id) {
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            header('Location: index.php');
            exit();
        } else {
            $error = "Nie udało się zarejestrować. Nazwa użytkownika lub email już istnieje.";
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
    <title>Rejestracja - SCP-SL Zgadywanka</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Rejestracja</h1>
    <?php if (isset($error)): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <form action="register.php" method="post">
        <input type="text" name="username" placeholder="Nazwa użytkownika" required>
        <input type="email" name="email" placeholder="E-mail" required>
        <input type="password" name="password" placeholder="Hasło" required>
        <input type="password" name="confirm_password" placeholder="Potwierdź hasło" required>
        <button type="submit">Zarejestruj się</button>
    </form>
    <p><a href="index.php">Powrót do strony głównej</a></p>
</body>
</html>