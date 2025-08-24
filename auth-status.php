<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

$isAdmin = isset($_SESSION['isAdmin']) && $_SESSION['isAdmin'] === true;

// 從 JSON 檔案讀取站台設定
$siteConfigFile = __DIR__ . '/data/site-config.json';
$defaultConfig = [
    'site' => ['title' => '美國保健品代購｜正品保證・快速送達台灣'],
    'brand' => ['text' => 'HealthShop 代購'],
    'contact' => [
        'lineId' => '@yourlineid',
        'email' => 'service@yourbrand.tw'
    ]
];

if (file_exists($siteConfigFile)) {
    $jsonContent = file_get_contents($siteConfigFile);
    $siteConfig = json_decode($jsonContent, true);
    if (!is_array($siteConfig)) {
        $siteConfig = $defaultConfig;
    }
} else {
    $siteConfig = $defaultConfig;
}

echo json_encode([
  'ok' => true,
  'isAdmin' => $isAdmin,
  'config' => [
    'siteTitle' => $siteConfig['site']['title'],
    'brandText' => $siteConfig['brand']['text'],
    'lineId' => $siteConfig['contact']['lineId'],
    'email' => $siteConfig['contact']['email'],
  ],
]);
