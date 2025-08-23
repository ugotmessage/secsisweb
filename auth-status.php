<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

$config = require __DIR__ . '/config.php';

$isAdmin = isset($_SESSION['isAdmin']) && $_SESSION['isAdmin'] === true;

echo json_encode([
  'ok' => true,
  'isAdmin' => $isAdmin,
  'config' => [
    'siteTitle' => $config['siteTitle'],
    'brandText' => $config['brandText'],
    'lineId' => $config['lineId'],
    'email' => $config['email'],
  ],
]);
