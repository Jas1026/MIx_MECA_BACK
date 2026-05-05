<?php
error_reporting(0);
ini_set('display_errors', 0);

$headers = array_change_key_case(getallheaders(), CASE_LOWER);

// Si viene por header (requests autenticados)
if (isset($headers['system'])) {
    $system = $headers['system'];
}
// Si viene por POST (login)
elseif (isset($_POST['system'])) {
    $system = $_POST['system'];
}
// Default
else {
    $system = 'mecapos';
}

if ($system === 'mixtura') {
    $dbname = 'mixtura';
} else {
    $dbname = 'mecapos';
}

$pdo = new PDO("mysql:host=localhost;port=3306;dbname=$dbname", 'root', '');
$pdo->exec("set names utf8");
$pdo->exec("SET SQL_BIG_SELECTS=1");
?>