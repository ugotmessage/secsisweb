<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

$config = require __DIR__ . '/config.php';

// 檢查管理員權限
if(empty($_SESSION['isAdmin'])){
    http_response_code(401);
    echo json_encode(['ok'=>false,'error'=>'UNAUTHORIZED']);
    exit;
}

// 設定檔案路徑
$configFile = __DIR__ . '/data/site-config.json';
$configDir = dirname($configFile);

// 確保資料目錄存在
if(!is_dir($configDir)){
    @mkdir($configDir, 0775, true);
}

// 預設設定
$defaultConfig = [
    'site' => [
        'title' => '美國保健品代購｜正品保證・快速送達台灣',
        'description' => '美國保健品代購｜正品保證・快速送達台灣。維他命C、魚油、膠原蛋白、益生菌等。',
        'keywords' => '美國保健品代購,正品保證,快速送達台灣,維他命C,魚油,膠原蛋白,益生菌',
        'url' => '',
        'ogImage' => ''
    ],
    'brand' => [
        'text' => 'HealthShop 代購',
        'mark' => 'HS'
    ],
    'contact' => [
        'lineId' => '@yourlineid',
        'email' => 'service@yourbrand.tw'
    ],
    'content' => [
        'hero' => [
            'title' => '美國保健品代購',
            'subtitle' => '正品保證・快速送達台灣',
            'ctaPrimary' => '立即下單',
            'ctaSecondary' => '加入LINE洽詢',
            'note' => '支援多品牌代購：維他命C、魚油、膠原蛋白、益生菌等'
        ],
        'sections' => [
            'products' => [
                'title' => '熱銷推薦',
                'subtitle' => '精選美國熱賣保健品，支援客製代購與組合詢價'
            ],
            'how' => [
                'title' => '代購流程',
                'steps' => [
                    ['icon' => '📝', 'title' => '下單', 'description' => '選擇商品並加入詢問清單，填寫聯絡方式送出。'],
                    ['icon' => '✈️', 'title' => '代購', 'description' => '我們於美國採購正品並安排空運或集運。'],
                    ['icon' => '📦', 'title' => '收貨', 'description' => '完成清關後寄送至台灣地址，提供物流追蹤。']
                ]
            ],
            'faq' => [
                'title' => '常見問題',
                'items' => [
                    ['question' => '運送時間需要多久？', 'answer' => '一般狀況下約 7-14 個工作天（不含假日），旺季與通關查驗可能延長。'],
                    ['question' => '如何付款？', 'answer' => '提供台灣銀行轉帳或行動支付。確認商品與金額後再行付款。'],
                    ['question' => '是否會被課稅？需要提供什麼資料？', 'answer' => '依台灣海關規定可能課徵進口稅。若需報關可能請您提供身分證字號作實名認證。'],
                    ['question' => '是否保證正品？', 'answer' => '所有商品均自美國正規通路採購並保留單據，保障您的權益。']
                ]
            ],
            'contact' => ['title' => '聯絡我們']
        ],
        'footer' => [
            'copyright' => '© 2024 HealthShop. All rights reserved.',
            'disclaimer' => '本網站所述商品為一般營養補充品，非醫療或治療用途。實際效果因人而異，如有身體不適請諮詢專業醫師。'
        ]
    ]
];

// 讀取設定
function loadSiteConfig($file, $defaults){
    if(!file_exists($file)){
        return $defaults;
    }
    
    $content = @file_get_contents($file);
    if($content === false){
        return $defaults;
    }
    
    $data = @json_decode($content, true);
    if(!is_array($data)){
        return $defaults;
    }
    
    return $data;
}

// 儲存設定
function saveSiteConfig($file, $config){
    $json = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if($json === false){
        return false;
    }
    
    return @file_put_contents($file, $json) !== false;
}

// 環境變數函數（如果 env.php 不存在）
if (!function_exists('env')) {
    function env($key, $default = null) {
        return $default;
    }
}

// 根據 HTTP 方法處理請求
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

switch($method){
    case 'GET':
        // 讀取設定
        $siteConfig = loadSiteConfig($configFile, $defaultConfig);
        echo json_encode(['ok'=>true, 'config'=>$siteConfig]);
        break;
        
    case 'POST':
        // 更新設定
        $body = file_get_contents('php://input');
        $data = json_decode($body, true);
        
        if(!$data){
            http_response_code(400);
            echo json_encode(['ok'=>false, 'error'=>'INVALID_JSON']);
            exit;
        }
        
        if(saveSiteConfig($configFile, $data)){
            echo json_encode(['ok'=>true, 'message'=>'設定已儲存']);
        } else {
            http_response_code(500);
            echo json_encode(['ok'=>false, 'error'=>'SAVE_FAILED']);
        }
        break;
        
    case 'PUT':
        // 重置為預設設定
        if(saveSiteConfig($configFile, $defaultConfig)){
            echo json_encode(['ok'=>true, 'config'=>$defaultConfig, 'message'=>'已重置為預設設定']);
        } else {
            http_response_code(500);
            echo json_encode(['ok'=>false, 'error'=>'RESET_FAILED']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['ok'=>false, 'error'=>'METHOD_NOT_ALLOWED']);
        break;
}
?>
