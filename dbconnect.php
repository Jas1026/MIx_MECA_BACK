<?php
error_reporting(0);
ini_set('display_errors', 0);

$system = $_POST['system'] ?? 'mecapos'; // default

if ($system === 'mixtura') {
    $dbname = 'mixtura';
} else {
    $dbname = 'mecapos';
}

$pdo = new PDO("mysql:host=localhost;port=3307;dbname=$dbname", 'root', '');
$pdo->exec("set names utf8");
$pdo->exec("SET SQL_BIG_SELECTS=1");

$serverKey = '5f2b5cdbe5194f10b3241568fe4e2b24';
?>