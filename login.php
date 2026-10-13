<?php
// filepath: /Users/melikkilic/Documents/Nexed/eindproject/login.php
declare(strict_types=1);

session_start();
require_once 'dbconnectie.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $wachtwoord = trim((string)($_POST['wachtwoord'] ?? ''));

    $stmt = $pdo->prepare("SELECT id, username, wachtwoord FROM gebruikers WHERE username = :u LIMIT 1");
    $stmt->execute([':u' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);


    if ($user && hash_equals((string)$user['wachtwoord'], $wachtwoord)) {
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['username'] = (string)$user['username'];

        header('Location: profile.php');
        exit;
    }

    $error = 'Foute login.';
}
?>
<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inloggen</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="login.css">
</head>

<body>
    <?php include 'navbar.php'; ?>

    <main class="container login-page">
        <section class="login-card">
            <h1>Inloggen</h1>

            <form method="post" class="login-form" autocomplete="off">
                <label class="login-field">
                    <span>Gebruikersnaam</span>
                    <input type="text" name="username" required value="<?= htmlspecialchars((string)($_POST['username'] ?? '')) ?>">
                </label>

                <label class="login-field">
                    <span>Wachtwoord</span>
                    <input type="password" name="wachtwoord" required>
                </label>

                <button class="btn" type="submit">Inloggen</button>

                <?php if (!empty($error)) : ?>
                    <p class="login-error"><?= htmlspecialchars($error) ?></p>
                <?php endif; ?>
            </form>
        </section>
    </main>
</body>

</html>