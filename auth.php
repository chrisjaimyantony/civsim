<?php

session_start();
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

if ($action === 'logout') {
  session_destroy();
  echo json_encode(['ok' => true]);
  exit;
}

// ── LOGIN ─────────────────────────────────────────
if ($action === 'login') {
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';

  if (!$username || !$password) {
    echo json_encode(['error' => 'Please fill in all fields.']);
    exit;
  }

  $pdo  = getDB();
  $stmt = $pdo->prepare('SELECT id, username, password_hash FROM users WHERE username = ? LIMIT 1');
  $stmt->execute([$username]);
  $user = $stmt->fetch();

  if (!$user || !password_verify($password, $user['password_hash'])) {
    echo json_encode(['error' => 'Invalid username or password.']);
    exit;
  }

  $_SESSION['user_id']  = $user['id'];
  $_SESSION['username'] = $user['username'];
  echo json_encode(['ok' => true, 'username' => $user['username']]);
  exit;
}

// ── REGISTER ──────────────────────────────────────
if ($action === 'register') {
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';
  $confirm  = $_POST['confirm']  ?? '';

  // Validation
  if (!$username || !$password || !$confirm) {
    echo json_encode(['error' => 'Please fill in all fields.']);
    exit;
  }
  if (!preg_match('/^[a-zA-Z0-9_\-]{3,40}$/', $username)) {
    echo json_encode(['error' => 'Username must be 3–40 characters: letters, numbers, _ or -']);
    exit;
  }
  if (strlen($password) < 6) {
    echo json_encode(['error' => 'Password must be at least 6 characters.']);
    exit;
  }
  if ($password !== $confirm) {
    echo json_encode(['error' => 'Passwords do not match.']);
    exit;
  }

  $pdo = getDB();

  // Check username taken
  $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
  $stmt->execute([$username]);
  if ($stmt->fetch()) {
    echo json_encode(['error' => 'That username is already taken.']);
    exit;
  }

  // Insert
  $hash = password_hash($password, PASSWORD_BCRYPT);
  $stmt = $pdo->prepare('INSERT INTO users (username, password_hash) VALUES (?, ?)');
  $stmt->execute([$username, $hash]);
  $userId = (int) $pdo->lastInsertId();

  $_SESSION['user_id']  = $userId;
  $_SESSION['username'] = $username;
  echo json_encode(['ok' => true, 'username' => $username]);
  exit;
}

// Unknown action
echo json_encode(['error' => 'Unknown action.']);