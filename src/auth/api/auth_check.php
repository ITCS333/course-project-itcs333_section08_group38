<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in'])) {
  echo json_encode(['logged_in' => false]);
  exit();
}

echo json_encode(['logged_in' => true, 'email' => $_SESSION['email'] ?? null]);
