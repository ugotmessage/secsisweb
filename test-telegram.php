<?php
require_once __DIR__ . '/env.php';
require_once __DIR__ . '/api-templates.php';

echo "測試 Telegram 發送功能\n";
echo "====================\n";

// 檢查環境變數
echo "COMMUNICATION_PLATFORM: " . env('COMMUNICATION_PLATFORM', 'NOT_SET') . "\n";
echo "TG_BOT_TOKEN: " . env('TG_BOT_TOKEN', 'NOT_SET') . "\n";
echo "TG_DEFAULT_CHAT_ID: " . env('TG_DEFAULT_CHAT_ID', 'NOT_SET') . "\n";

// 建立測試資料
$testData = [
    'name' => '測試用戶',
    'email' => 'test@example.com',
    'lineId' => '@testline',
    'phone' => '0912345678',
    'message' => '這是一個測試訊息',
    'inquiry' => json_encode([
        ['id' => 'test1', 'name' => '測試商品1'],
        ['id' => 'test2', 'name' => '測試商品2']
    ]),
    'timestamp' => date('Y-m-d H:i:s'),
    'ip' => '127.0.0.1'
];

echo "\n測試資料:\n";
print_r($testData);

// 建立 API 實例
$api = new CommunicationAPI();

// 格式化訊息
$message = $api->formatMessage($testData);
echo "\n格式化後的訊息:\n";
echo $message . "\n";

// 發送訊息
echo "\n發送訊息到 Telegram...\n";
$result = $api->sendMessage('telegram', $message);

echo "\n發送結果:\n";
print_r($result);
?>
