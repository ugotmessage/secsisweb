<?php
/**
 * 全域時區設定
 * 確保所有 PHP 檔案都使用台北時間
 */

// 設定時區為台北時間
date_default_timezone_set('Asia/Taipei');

// 設定環境變數
putenv('TZ=Asia/Taipei');

// 驗證時區設定
if (date_default_timezone_get() !== 'Asia/Taipei') {
    error_log('Warning: Timezone setting failed, current timezone: ' . date_default_timezone_get());
}
?>
