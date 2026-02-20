<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
//$pdo = new PDO('mysql:host=enriqueoropezanet.netfirmsmysql.com;dbname=burgerweek', 'user_bw', 'Burger.20@i4'); 
$pdo = new PDO('mysql:host=localhost;port=3307;dbname=mecapos', 'root', '');       
$pdo->exec("set names utf8");
$pdo->exec("SET SQL_BIG_SELECTS=1");
// Get our server-side secret key from a secure location.
$serverKey = '5f2b5cdbe5194f10b3241568fe4e2b24';
?>