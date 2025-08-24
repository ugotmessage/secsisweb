<?php
// 載入時區設定
require_once __DIR__ . '/timezone-config.php';

/**
 * 通訊軟體 API 範本
 * 包含各種平台的 API 設定和發送邏輯
 */

class CommunicationAPI {
    private $config;
    
    public function __construct() {
        $this->config = require __DIR__ . '/config.php';
    }
    
    /**
     * 發送訊息到指定平台
     */
    public function sendMessage($platform, $messageData, $recipient = null) {
        switch ($platform) {
            case 'line':
                return $this->sendToLine($messageData, $recipient);
            case 'facebook':
                return $this->sendToFacebook($messageData, $recipient);
            case 'telegram':
                return $this->sendToTelegram($messageData, $recipient);
            case 'email':
                return $this->sendToEmail($messageData, $recipient);
            default:
                return ['ok' => false, 'error' => 'UNSUPPORTED_PLATFORM'];
        }
    }
    
    /**
     * LINE Bot API
     * 需要設定 LINE_CHANNEL_ACCESS_TOKEN 和 LINE_CHANNEL_SECRET
     */
    private function sendToLine($messageData, $recipient = null) {
        $token = env('LINE_CHANNEL_ACCESS_TOKEN');
        if (!$token) {
            return ['ok' => false, 'error' => 'LINE_TOKEN_NOT_SET'];
        }
        
        $url = 'https://api.line.me/v2/bot/message/push';
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ];
        
        $data = [
            'to' => $recipient ?: env('LINE_DEFAULT_USER_ID'),
            'messages' => [
                [
                    'type' => 'text',
                    'text' => $messageData
                ]
            ]
        ];
        
        return $this->makeRequest($url, $data, $headers);
    }
    
    /**
     * Facebook Messenger API
     * 需要設定 FB_PAGE_ACCESS_TOKEN 和 FB_PAGE_ID
     */
    private function sendToFacebook($messageData, $recipient = null) {
        $token = env('FB_PAGE_ACCESS_TOKEN');
        $pageId = env('FB_PAGE_ID');
        
        if (!$token || !$pageId) {
            return ['ok' => false, 'error' => 'FB_CONFIG_NOT_SET'];
        }
        
        $url = "https://graph.facebook.com/v18.0/{$pageId}/messages";
        $data = [
            'recipient' => [
                'id' => $recipient ?: env('FB_DEFAULT_USER_ID')
            ],
            'message' => [
                'text' => $messageData
            ],
            'access_token' => $token
        ];
        
        return $this->makeRequest($url, $data);
    }
    
    /**
     * Telegram Bot API
     * 需要設定 TG_BOT_TOKEN 和 TG_CHAT_ID
     */
    private function sendToTelegram($messageData, $recipient = null) {
        $token = env('TG_BOT_TOKEN');
        if (!$token) {
            return ['ok' => false, 'error' => 'TG_TOKEN_NOT_SET'];
        }
        
        $url = "https://api.telegram.org/bot{$token}/sendMessage";
        $data = [
            'chat_id' => $recipient ?: env('TG_DEFAULT_CHAT_ID'),
            'text' => $messageData,
            'parse_mode' => 'HTML'
        ];
        
        return $this->makeRequest($url, $data);
    }
    
    /**
     * Email 發送
     * 使用 PHP 內建的 mail() 函數或 SMTP
     */
    private function sendToEmail($messageData, $recipient = null) {
        $to = $recipient ?: env('ADMIN_EMAIL', 'admin@yourdomain.com');
        $subject = '保健品代購詢問通知';
        
        $headers = [
            'From: ' . env('FROM_EMAIL', 'noreply@yourdomain.com'),
            'Reply-To: ' . env('REPLY_TO_EMAIL', 'service@yourdomain.com'),
            'Content-Type: text/html; charset=UTF-8'
        ];
        
        $result = mail($to, $subject, $messageData, implode("\r\n", $headers));
        
        return [
            'ok' => $result,
            'message' => $result ? 'Email 已發送' : 'Email 發送失敗'
        ];
    }
    
    /**
     * 統一的 HTTP 請求處理
     */
    private function makeRequest($url, $data, $headers = []) {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array_merge([
                'Content-Type: application/json'
            ], $headers),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            return ['ok' => false, 'error' => 'CURL_ERROR', 'message' => $error];
        }
        
        if ($httpCode >= 200 && $httpCode < 300) {
            $jsonResponse = json_decode($response, true);
            return ['ok' => true, 'response' => $jsonResponse];
        } else {
            return ['ok' => false, 'error' => 'HTTP_ERROR', 'code' => $httpCode, 'response' => $response];
        }
    }
    
    /**
     * 格式化訊息內容
     */
    public function formatMessage($contactData) {
        $message = "🔔 新的保健品代購詢問\n\n";
        $message .= "👤 姓名：{$contactData['name']}\n";
        $message .= "📧 Email：{$contactData['email']}\n";
        
        if (!empty($contactData['lineId'])) {
            $message .= "💬 LINE ID：{$contactData['lineId']}\n";
        }
        
        if (!empty($contactData['phone'])) {
            $message .= "📱 手機：{$contactData['phone']}\n";
        }
        
        $message .= "\n📝 詢問內容：\n{$contactData['message']}\n";
        
        if (!empty($contactData['inquiry'])) {
            $inquiry = json_decode($contactData['inquiry'], true);
            if (is_array($inquiry) && !empty($inquiry)) {
                $message .= "\n🛒 詢問清單：\n";
                foreach ($inquiry as $item) {
                    $message .= "• {$item['name']}\n";
                }
            }
        }
        
        $message .= "\n⏰ 時間：{$contactData['timestamp']}\n";
        $message .= "🌐 IP：{$contactData['ip']}";
        
        return $message;
    }
}

// 環境變數函數（如果 env.php 不存在）
if (!function_exists('env')) {
    function env($key, $default = null) {
        return $default;
    }
}
?>
