<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

// Configuracao do banco via variaveis de ambiente.
$DB_HOST = env_value('DB_HOST', 'localhost');
$DB_NAME = env_value('DB_NAME', '');
$DB_USER = env_value('DB_USER', '');
$DB_PASS = env_value('DB_PASS', '');

$dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4";
$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];
try {
  $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
  http_response_code(500);
  exit('Erro ao conectar no banco.');
}
