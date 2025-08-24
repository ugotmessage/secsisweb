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

// 商品資料檔案路徑
$productsFile = __DIR__ . '/data/products.json';
$productsDir = dirname($productsFile);

// 確保資料目錄存在
if(!is_dir($productsDir)){
    @mkdir($productsDir, 0775, true);
}

// 預設商品資料
$defaultProducts = [
    ['id'=>'vitamin-c', 'name'=>'維他命C 1000mg', 'desc'=>'高劑量每日補給，增強體力與精神，常備保健首選。', 'img'=>'/images/default/vitamin-c.svg'],
    ['id'=>'fish-oil', 'name'=>'高濃度魚油', 'desc'=>'Omega-3 含量高，支持心血管健康與日常保養。', 'img'=>'/images/default/fish-oil.svg'],
    ['id'=>'collagen', 'name'=>'膠原蛋白粉', 'desc'=>'美妍養護，添加維生素C 配方，沖泡方便好入口。', 'img'=>'/images/default/collagen.svg'],
    ['id'=>'probiotics', 'name'=>'益生菌複方', 'desc'=>'多菌株高含量，幫助調整體質，維持消化道機能。', 'img'=>'/images/default/probiotics.svg'],
    ['id'=>'multi-vitamin', 'name'=>'綜合維他命', 'desc'=>'全方位補給日常所需營養素，簡單一次到位。', 'img'=>'/images/default/multi-vitamin.svg'],
    ['id'=>'vitamin-d', 'name'=>'維他命D3 2000 IU', 'desc'=>'居家必備好朋友，幫助鈣質吸收與免疫防護。', 'img'=>'/images/default/vitamin-d.svg']
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

// 儲存商品資料
function saveProducts($file, $products){
    $json = json_encode($products, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if($json === false){
        return false;
    }
    
    return @file_put_contents($file, $json) !== false;
}

// 根據 HTTP 方法處理請求
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

switch($method){
    case 'GET':
        // 讀取商品列表
        $products = loadProducts($productsFile, $defaultProducts);
        echo json_encode(['ok'=>true, 'products'=>$products]);
        break;
        
    case 'POST':
        // 新增或更新商品
        $body = file_get_contents('php://input');
        $data = json_decode($body, true);
        
        if(!$data){
            http_response_code(400);
            echo json_encode(['ok'=>false, 'error'=>'INVALID_JSON']);
            exit;
        }
        
        // 檢查是否為批量更新（陣列）或單一商品（物件）
        if(isset($data[0])){
            // 批量更新
            $products = $data;
            if(saveProducts($productsFile, $products)){
                echo json_encode(['ok'=>true, 'message'=>'批量更新成功', 'count'=>count($products)]);
            } else {
                http_response_code(500);
                echo json_encode(['ok'=>false, 'error'=>'BATCH_SAVE_FAILED']);
            }
        } else {
            // 單一商品更新
            if(!isset($data['name'])){
                http_response_code(400);
                echo json_encode(['ok'=>false, 'error'=>'NAME_REQUIRED']);
                exit;
            }
            
            $products = loadProducts($productsFile, $defaultProducts);
            
            // 檢查是否為更新現有商品
            $existingIndex = -1;
            if(isset($data['id'])){
                foreach($products as $i => $p){
                    if($p['id'] === $data['id']){
                        $existingIndex = $i;
                        break;
                    }
                }
            }
            
            $product = [
                'id' => $data['id'] ?? createSlug($data['name']),
                'name' => trim($data['name']),
                'desc' => trim($data['desc'] ?? ''),
                'img' => trim($data['img'] ?? '')
            ];
            
            if($existingIndex >= 0){
                // 更新現有商品
                $products[$existingIndex] = $product;
            } else {
                // 新增商品
                $products[] = $product;
            }
            
            if(saveProducts($productsFile, $products)){
                echo json_encode(['ok'=>true, 'product'=>$product]);
            } else {
                http_response_code(500);
                echo json_encode(['ok'=>false, 'error'=>'SAVE_FAILED']);
            }
        }
        break;
        
    case 'DELETE':
        // 刪除商品
        $body = file_get_contents('php://input');
        $data = json_decode($body, true);
        
        if(!$data || !isset($data['id'])){
            http_response_code(400);
            echo json_encode(['ok'=>false, 'error'=>'ID_REQUIRED']);
            exit;
        }
        
        $products = loadProducts($productsFile, $defaultProducts);
        $products = array_filter($products, function($p) use ($data){
            return $p['id'] !== $data['id'];
        });
        
        if(saveProducts($productsFile, $products)){
            echo json_encode(['ok'=>true]);
        } else {
            http_response_code(500);
            echo json_encode(['ok'=>false, 'error'=>'DELETE_FAILED']);
        }
        break;
        
    case 'PUT':
        // 重置為預設商品
        if(saveProducts($productsFile, $defaultProducts)){
            echo json_encode(['ok'=>true, 'products'=>$defaultProducts]);
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

// 建立商品 ID 的輔助函數
function createSlug($text){
    $text = preg_replace('/[^\p{L}\p{N}]+/u', '-', $text);
    $text = trim($text, '-');
    $text = strtolower($text);
    return $text;
}
?>
