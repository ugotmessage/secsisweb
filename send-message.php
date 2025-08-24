<?php
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

// 載入通訊 API 類別
require_once __DIR__ . '/api-templates.php';

// 檢查請求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'METHOD_NOT_ALLOWED']);
    exit;
}

// 讀取 POST 資料
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'INVALID_JSON']);
    exit;
}

// 驗證必要欄位
if (empty($input['platform']) || empty($input['contactData'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'MISSING_REQUIRED_FIELDS']);
    exit;
}

$platform = $input['platform'];
$contactData = $input['contactData'];
$recipient = $input['recipient'] ?? null;

// 建立通訊 API 實例
$api = new CommunicationAPI();

// 格式化訊息
$message = $api->formatMessage($contactData);

// 發送訊息
$result = $api->sendMessage($platform, $message, $recipient);

// 記錄發送結果
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
}

$logFile = $logDir . '/message-sends.log';
$logEntry = date('Y-m-d H:i:s') . ' | ' . json_encode([
    'platform' => $platform,
    'recipient' => $recipient,
    'result' => $result,
    'contactData' => $contactData
], JSON_UNESCAPED_UNICODE) . "\n";
@file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

// 回傳結果
echo json_encode($result, JSON_UNESCAPED_UNICODE);
?>
