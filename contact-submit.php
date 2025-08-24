<?php
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

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

// 準備回應資料
$response = [
    'ok' => true,
    'message' => '您的詢問已送出，我們會盡快回覆您！',
    'contactOptions' => []
];

// 根據設定提供不同的聯絡選項
$siteConfigFile = __DIR__ . '/data/site-config.json';
if (file_exists($siteConfigFile)) {
    $jsonContent = file_get_contents($siteConfigFile);
    $siteConfig = json_decode($jsonContent, true);
    
    if (is_array($siteConfig)) {
        $contact = $siteConfig['contact'] ?? [];
        
        // LINE 選項
        if (!empty($contact['lineId'])) {
            $response['contactOptions'][] = [
                'type' => 'line',
                'name' => 'LINE',
                'description' => '加入 LINE 好友，即時洽詢',
                'action' => 'https://line.me/ti/p/' . urlencode($contact['lineId']),
                'icon' => '💬'
            ];
        }
        
        // Email 選項
        if (!empty($contact['email'])) {
            $response['contactOptions'][] = [
                'type' => 'email',
                'name' => 'Email',
                'description' => '發送郵件詢問',
                'action' => 'mailto:' . $contact['email'] . '?subject=' . urlencode('保健品代購詢問 - ' . $name),
                'icon' => '✉️'
            ];
        }
        
        // Facebook Messenger 選項（如果設定中有）
        if (!empty($contact['facebook'])) {
            $response['contactOptions'][] = [
                'type' => 'facebook',
                'name' => 'Facebook Messenger',
                'description' => '透過 Messenger 聯絡',
                'action' => 'https://m.me/' . $contact['facebook'],
                'icon' => '📘'
            ];
        }
        
        // Telegram 選項（如果設定中有）
        if (!empty($contact['telegram'])) {
            $response['contactOptions'][] = [
                'type' => 'telegram',
                'name' => 'Telegram',
                'description' => '透過 Telegram 聯絡',
                'action' => 'https://t.me/' . $contact['telegram'],
                'icon' => '📱'
            ];
        }
    }
}

// 如果沒有設定檔，提供預設選項
if (empty($response['contactOptions'])) {
    $response['contactOptions'] = [
        [
            'type' => 'line',
            'name' => 'LINE',
            'description' => '加入 LINE 好友，即時洽詢',
            'action' => 'https://line.me/ti/p/@yourlineid',
            'icon' => '💬'
        ],
        [
            'type' => 'email',
            'name' => 'Email',
            'description' => '發送郵件詢問',
            'action' => 'mailto:service@yourbrand.tw?subject=' . urlencode('保健品代購詢問 - ' . $name),
            'icon' => '✉️'
        ]
    ];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
