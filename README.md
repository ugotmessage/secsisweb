# 保健品代購網站 - 商品管理系統

## 系統架構

這個網站已經從原本的瀏覽器 localStorage 儲存改為伺服器端 JSON 檔案儲存，提供真正的資料持久化與跨裝置同步。

## 檔案結構

```
secsisweb/
├── index.php              # 主要網站頁面
├── script.js              # 前端 JavaScript（已更新為使用 API）
├── style.css              # 樣式表
├── products.php           # 商品管理 API（需要登入）
├── products-public.php    # 公開商品讀取 API
├── auth.php               # 管理員登入驗證
├── auth-status.php        # 登入狀態查詢
├── logout.php             # 登出
├── upload.php             # 圖片上傳
├── config.php             # 設定檔
├── env.php                # 環境變數載入
├── data/                  # 資料目錄
│   └── products.json      # 商品資料檔案
├── uploads/               # 上傳圖片目錄
├── logs/                  # 日誌目錄
│   └── auth.log          # 登入日誌
└── test-api.html          # API 測試頁面
```

## 主要功能

### 1. 商品管理
- **讀取商品**: `GET /products-public.php` - 公開 API，無需登入
- **新增/編輯商品**: `POST /products.php` - 需要管理員權限
- **刪除商品**: `DELETE /products.php` - 需要管理員權限
- **重置商品**: `PUT /products.php` - 需要管理員權限

### 2. 管理員權限
- 使用 Token 驗證（支援明文或 SHA256 雜湊）
- Session 管理
- IP 白名單（可選）
- 登入限速保護

### 3. 圖片上傳
- 支援 JPG、PNG、WebP、GIF 格式
- 檔案大小限制（預設 2MB）
- 隨機檔名防止衝突
- 僅限已登入管理員

## 設定說明

### 環境變數 (.env 檔案)
```bash
# 基本設定
SITE_TITLE=美國保健品代購｜正品保證・快速送達台灣
BRAND_TEXT=HealthShop 代購
BRAND_MARK=HS
LINE_ID=@yourlineid
EMAIL=service@yourbrand.tw

# 管理員權限（擇一設定）
ADMIN_TOKEN=your-secret-token-here
# 或使用 SHA256 雜湊
ADMIN_TOKEN_SHA256=your-sha256-hash-here

# 安全設定
ADMIN_IP_WHITELIST=192.168.1.1,127.0.0.1
AUTH_RATE_LIMIT_WINDOW_SECONDS=300
AUTH_RATE_LIMIT_MAX_ATTEMPTS=10

# 上傳設定
UPLOAD_MAX_BYTES=2097152
UPLOAD_ALLOWED_MIME=image/jpeg,image/png,image/webp,image/gif
```

### 資料目錄權限
確保 `data/` 目錄可寫入：
```bash
chmod 775 data/
chown www-data:www-data data/  # 如果使用 Apache/Nginx
```

## 使用方法

### 1. 管理員登入
1. 在網站上按 `Shift + A` 然後快速按 `Shift + L`
2. 輸入管理員 Token
3. 或點擊「商品管理」按鈕，然後點擊「複製管理連結」

### 2. 商品操作
- **新增商品**: 點擊「新增商品」，填寫資訊後儲存
- **編輯商品**: 點擊商品旁的「編輯」按鈕
- **刪除商品**: 點擊商品旁的「刪除」按鈕
- **重置商品**: 點擊「重置為預設商品」

### 3. 圖片上傳
1. 在商品表單中選擇圖片檔案
2. 系統會自動上傳並填入圖片網址
3. 支援拖放或點擊選擇

## API 端點

### 公開 API
```
GET /products-public.php
回應: {"ok": true, "products": [...]}
```

### 管理員 API
```
GET    /products.php      # 讀取商品列表
POST   /products.php      # 新增/編輯商品
DELETE /products.php      # 刪除商品
PUT    /products.php      # 重置為預設商品
```

所有管理員 API 都需要先通過 `auth.php` 登入。

## 測試

使用 `test-api.html` 頁面來測試各個 API 端點：
1. 測試公開商品讀取
2. 測試管理員登入
3. 測試商品新增/編輯/刪除

## 安全性

- 所有管理員操作都需要驗證
- 圖片上傳有 MIME 類型與大小限制
- 支援 IP 白名單與登入限速
- Session 管理防止未授權存取

## 故障排除

### 常見問題
1. **權限錯誤**: 檢查 `data/` 目錄權限
2. **上傳失敗**: 檢查檔案大小與格式
3. **登入失敗**: 確認 Token 設定正確
4. **API 錯誤**: 檢查 PHP 錯誤日誌

### 日誌檔案
- 登入日誌: `logs/auth.log`
- PHP 錯誤: 檢查伺服器錯誤日誌

## 升級注意事項

從舊版本升級時：
1. 備份現有的 `localStorage` 商品資料
2. 部署新的 PHP 檔案
3. 建立 `data/` 目錄
4. 設定環境變數
5. 測試 API 功能

## 技術細節

- **後端**: PHP 7.4+，無框架
- **前端**: jQuery + 原生 JavaScript
- **資料儲存**: JSON 檔案
- **認證**: Session + Token
- **圖片處理**: GD 或 Imagick 擴展
