<?php
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-cache, no-store, must-revalidate'); // 禁止快取
header('Pragma: no-cache');
header('Expires: 0');

// 商品資料檔案路徑
$productsFile = __DIR__ . '/data/products.json';

// 預設商品資料
$defaultProducts = [
    ['id'=>'vitamin-c', 'name'=>'維他命C 1000mg', 'desc'=>'高劑量每日補給，增強體力與精神，常備保健首選。', 'img'=>''],
    ['id'=>'fish-oil', 'name'=>'高濃度魚油', 'desc'=>'Omega-3 含量高，支持心血管健康與日常保養。', 'img'=>''],
    ['id'=>'collagen', 'name'=>'膠原蛋白粉', 'desc'=>'美妍養護，添加維生素C 配方，沖泡方便好入口。', 'img'=>''],
    ['id'=>'probiotics', 'name'=>'益生菌複方', 'desc'=>'多菌株高含量，幫助調整體質，維持消化道機能。', 'img'=>''],
    ['id'=>'multi-vitamin', 'name'=>'綜合維他命', 'desc'=>'全方位補給日常所需營養素，簡單一次到位。', 'img'=>''],
    ['id'=>'vitamin-d', 'name'=>'維他命D3 2000 IU', 'desc'=>'居家必備好朋友，幫助鈣質吸收與免疫防護。', 'img'=>'']
];

// 讀取商品資料
function loadProducts($file, $defaults){
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

// 只允許 GET 請求
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if($method !== 'GET'){
    http_response_code(405);
    echo json_encode(['ok'=>false, 'error'=>'METHOD_NOT_ALLOWED']);
    exit;
}

// 讀取並回傳商品列表
$products = loadProducts($productsFile, $defaultProducts);

// 除錯訊息
error_log("Products loaded from: " . $productsFile);
error_log("Products data: " . json_encode($products));

echo json_encode(['ok'=>true, 'products'=>$products]);
?>
