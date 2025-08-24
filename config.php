<?php
require_once __DIR__ . '/env.php';

return [
  // 管理員認證設定
  'adminTokenPlain' => env('ADMIN_TOKEN', null),
  'adminTokenSha256' => env('ADMIN_TOKEN_SHA256', null),
  
  // 安全/審計設定
  'adminIpWhitelist' => array_values(array_filter(array_map('trim', explode(',', (string)env('ADMIN_IP_WHITELIST', ''))), function($v){ return $v !== ''; })),
  'authRateLimitWindow' => (int)env('AUTH_RATE_LIMIT_WINDOW_SECONDS', '300'),
  'authRateLimitMaxAttempts' => (int)env('AUTH_RATE_LIMIT_MAX_ATTEMPTS', '10'),
  'authLogPath' => env('AUTH_LOG_PATH', __DIR__ . '/logs/auth.log'),
  'authStorageDir' => env('AUTH_STORAGE_DIR', sys_get_temp_dir()),
  
  // 上傳設定
  'uploadDir' => env('UPLOAD_DIR', __DIR__ . '/uploads'),
  'uploadBaseUrl' => env('UPLOAD_BASE_URL', '/uploads'),
  'uploadMaxBytes' => (int)env('UPLOAD_MAX_BYTES', 2 * 1024 * 1024), // 2MB
  'uploadAllowedMime' => array_values(array_filter(array_map('trim', explode(',', (string)env('UPLOAD_ALLOWED_MIME', 'image/jpeg,image/png,image/webp,image/gif'))))),
];
