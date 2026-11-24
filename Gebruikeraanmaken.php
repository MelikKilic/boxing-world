<?php

require('dbconnectie.php');
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username  = $_POST['username'];
    $wachtwoord = $_POST['wachtwoord'];

    $sql = "INSERT INTO gebruikers (username, wachtwoord) 
    VALUES (?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$username, $wachtwoord]);
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>Document</title>

</head>

<body>
    <form method="POST">
        <h1>Nieuwe gebruikersnaam</h1>
        <a href="index.php" class="container">terug</a>
        <label for="username">Gebruikersnaam</label>
        <input type="text" id="username" name="username"><br>

        <label for="wachtwoord">Wachtwoord</label>
        <input type="password" id="wachtwoord" name="wachtwoord"><br>

        <button type="submit">voeg toe</button>
    </form>
</body>
