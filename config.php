<?php
require_once __DIR__ . '/env.php';

return [
  'siteTitle' => env('SITE_TITLE', '美國保健品代購｜正品保證・快速送達台灣'),
  'brandText' => env('BRAND_TEXT', 'HealthShop 代購'),
  'brandMark' => env('BRAND_MARK', 'HS'),
  'lineId' => env('LINE_ID', '@yourlineid'),
  'email' => env('EMAIL', 'service@yourbrand.tw'),
  // 可用明文或 SHA256 雜湊，兩者擇一設定即可
  'adminTokenPlain' => env('ADMIN_TOKEN', null),
  'adminTokenSha256' => env('ADMIN_TOKEN_SHA256', null),
  // 安全/審計設定
  // 以逗號分隔的 IP 白名單，留空表示不啟用白名單
  'adminIpWhitelist' => array_values(array_filter(array_map('trim', explode(',', (string)env('ADMIN_IP_WHITELIST', ''))), function($v){ return $v !== ''; })),
  // 每 IP 限速視窗秒數與最大嘗試次數（含成功/失敗）
  'authRateLimitWindow' => (int)env('AUTH_RATE_LIMIT_WINDOW_SECONDS', '300'),
  'authRateLimitMaxAttempts' => (int)env('AUTH_RATE_LIMIT_MAX_ATTEMPTS', '10'),
  // 嘗試紀錄檔與暫存目錄（需可寫入）
  'authLogPath' => env('AUTH_LOG_PATH', __DIR__ . '/logs/auth.log'),
  'authStorageDir' => env('AUTH_STORAGE_DIR', sys_get_temp_dir()),
  // SEO 設定
  'seoDescription' => env('SEO_DESCRIPTION', '美國保健品代購｜正品保證・快速送達台灣。維他命C、魚油、膠原蛋白、益生菌等。'),
  'seoKeywords' => env('SEO_KEYWORDS', '美國保健品代購,正品保證,快速送達台灣,維他命C,魚油,膠原蛋白,益生菌'),
  'siteUrl' => env('SITE_URL', ''),
  'ogImage' => env('OG_IMAGE', ''),
  // 上傳設定
  'uploadDir' => env('UPLOAD_DIR', __DIR__ . '/uploads'),
  'uploadBaseUrl' => env('UPLOAD_BASE_URL', '/uploads'),
  'uploadMaxBytes' => (int)env('UPLOAD_MAX_BYTES', 2 * 1024 * 1024), // 2MB
  'uploadAllowedMime' => array_values(array_filter(array_map('trim', explode(',', (string)env('UPLOAD_ALLOWED_MIME', 'image/jpeg,image/png,image/webp,image/gif'))))),
];
