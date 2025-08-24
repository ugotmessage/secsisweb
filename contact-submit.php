<?php
// 載入時區設定
require_once __DIR__ . '/timezone-config.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

// 載入環境變數函數
require_once __DIR__ . '/env.php';

// 載入設定
$config = require __DIR__ . '/config.php';

// 檢查請求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'METHOD_NOT_ALLOWED']);
    exit;
}

// 讀取 POST 資料
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    // 嘗試傳統 POST 資料
    $input = $_POST;
}

// 驗證必要欄位
$required = ['name', 'email', 'message'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'MISSING_FIELD', 'field' => $field]);
        exit;
    }
}

// 清理和驗證資料
$name = trim($input['name']);
$email = filter_var(trim($input['email']), FILTER_VALIDATE_EMAIL);
$lineId = trim($input['lineId'] ?? '');
$phone = trim($input['phone'] ?? '');
$message = trim($input['message']);
$inquiry = $input['inquiry'] ?? '';

if (!$email) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'INVALID_EMAIL']);
    exit;
}

// 準備聯絡資訊
$contactData = [
    'name' => $name,
    'email' => $email,
    'lineId' => $lineId,
    'phone' => $phone,
    'message' => $message,
    'inquiry' => $inquiry,
    'timestamp' => date('Y-m-d H:i:s'),
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
];

// 記錄到檔案
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
}

$logFile = $logDir . '/contact-submissions.log';
$logEntry = date('Y-m-d H:i:s') . ' | ' . json_encode($contactData, JSON_UNESCAPED_UNICODE) . "\n";
@file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

// 自動發送訊息到啟用的平台
$enabledPlatform = env('COMMUNICATION_PLATFORM', 'telegram');
if ($enabledPlatform !== 'email') {
    require_once __DIR__ . '/api-templates.php';
    $api = new CommunicationAPI();
    $message = $api->formatMessage($contactData);
    
    // 發送訊息
    $sendResult = $api->sendMessage($enabledPlatform, $message);
    
    // 記錄發送結果
    $sendLogFile = $logDir . '/auto-send.log';
    $sendLogEntry = date('Y-m-d H:i:s') . ' | ' . json_encode([
        'platform' => $enabledPlatform,
        'result' => $sendResult,
        'contactData' => $contactData
    ], JSON_UNESCAPED_UNICODE) . "\n";
    @file_put_contents($sendLogFile, $sendLogEntry, FILE_APPEND | LOCK_EX);
}

// 準備回應資料
$response = [
    'ok' => true,
    'message' => '您的詢問已送出，我們會盡快回覆您！'
];

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
