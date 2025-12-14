<?php
session_start();
header('Content-Type: application/json');

echo json_encode([
  'success' => true,
  'logged_in' => !empty($_SESSION['user_id']),
  'user' => [
    'id' => $_SESSION['user_id'] ?? null,
    'name' => $_SESSION['user_name'] ?? null,
    'email' => $_SESSION['user_email'] ?? null,
  ]
]);
exit;
?>
