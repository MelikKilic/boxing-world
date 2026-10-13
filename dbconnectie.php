<?php

$host = 'localhost';
$dbname = 'BoxingWorld';
$username = 'bit_academy';
$password = 'bit_academy';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
} catch (PDOException $e) {
    echo 'Fout bij verbinden met de database: ' . $e->getMessage();
}
