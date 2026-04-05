<?php
define('DB_HOST', 'sql300.infinityfree.com');
define('DB_NAME', 'if0_41481031_civsim');
define('DB_USER', 'if0_41481031');
define('DB_PASS', 'christ252006');
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
  static $pdo = null;
  if ($pdo === null) {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $options = [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    try {
      $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
      http_response_code(500);
      // Return JSON so the frontend gets a readable error instead of a crash
      header('Content-Type: application/json');
      die(json_encode(['error' => 'DB connection failed: ' . $e->getMessage()]));
    }
  }
  return $pdo;
}