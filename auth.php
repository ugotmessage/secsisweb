<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

$config = require __DIR__ . '/config.php';

function sha256_hex($text){ return hash('sha256', $text); }
function client_ip(){
  foreach(['HTTP_CLIENT_IP','HTTP_X_FORWARDED_FOR','HTTP_X_FORWARDED','HTTP_X_CLUSTER_CLIENT_IP','HTTP_FORWARDED_FOR','HTTP_FORWARDED','REMOTE_ADDR'] as $k){
    if(isset($_SERVER[$k])){
      $ips = explode(',', $_SERVER[$k]);
      foreach($ips as $ip){
        $ip = trim($ip);
        if(filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
      }
    }
  }
  return '0.0.0.0';
}
function rate_key($ip, $dir){ return rtrim($dir,'/').'/auth-rate-'.preg_replace('/[^a-zA-Z0-9_.-]/','_', $ip).'.json'; }
function rate_check_and_inc($ip, $window, $max, $dir){
  $file = rate_key($ip,$dir);
  $now = time();
  $data = ['start'=>$now,'count'=>0];
  if(is_file($file)){
    $json = @file_get_contents($file);
    $tmp = json_decode($json,true);
    if(is_array($tmp) && isset($tmp['start'],$tmp['count'])){ $data = $tmp; }
  }
  if(($now - $data['start']) > $window){ $data = ['start'=>$now,'count'=>0]; }
  $data['count']++;
  @file_put_contents($file, json_encode($data));
  return $data['count'] <= $max;
}
function auth_log($path, $msg){
  $dir = dirname($path);
  if(!is_dir($dir)) @mkdir($dir, 0775, true);
  $line = '['.date('c').'] '.$msg."\n";
  @file_put_contents($path, $line, FILE_APPEND);
}

$ip = client_ip();
$logPath = $config['authLogPath'] ?? (__DIR__ . '/logs/auth.log');

// 白名單（若設定，非白名單則拒絕）
$whitelist = $config['adminIpWhitelist'] ?? [];
if($whitelist && !in_array($ip, $whitelist, true)){
  auth_log($logPath, "LOGIN_DENY_IP ip={$ip}");
  http_response_code(403);
  echo json_encode(['ok'=>false,'error'=>'IP_NOT_ALLOWED']);
  exit;
}

// 限速檢查
$window = max(30, (int)($config['authRateLimitWindow'] ?? 300));
$maxAttempts = max(3, (int)($config['authRateLimitMaxAttempts'] ?? 10));
$storageDir = $config['authStorageDir'] ?? sys_get_temp_dir();
if(!rate_check_and_inc($ip, $window, $maxAttempts, $storageDir)){
  auth_log($logPath, "LOGIN_RATE_LIMIT ip={$ip}");
  http_response_code(429);
  echo json_encode(['ok'=>false,'error'=>'RATE_LIMITED']);
  exit;
}

$body = file_get_contents('php://input');
$payload = json_decode($body, true) ?: [];
$token = isset($payload['token']) ? trim($payload['token']) : '';

if($token === ''){
  auth_log($logPath, "LOGIN_NO_TOKEN ip={$ip}");
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'TOKEN_REQUIRED']);
  exit;
}

$ok = false;
if(!empty($config['adminTokenPlain'])){
  if(hash_equals($config['adminTokenPlain'], $token)) $ok = true;
}
if(!$ok && !empty($config['adminTokenSha256'])){
  if(hash_equals($config['adminTokenSha256'], sha256_hex($token))) $ok = true;
}

if($ok){
  $_SESSION['isAdmin'] = true;
  auth_log($logPath, "LOGIN_OK ip={$ip}");
  echo json_encode(['ok'=>true]);
}else{
  http_response_code(401);
  auth_log($logPath, "LOGIN_FAIL ip={$ip}");
  echo json_encode(['ok'=>false,'error'=>'INVALID_TOKEN']);
}
