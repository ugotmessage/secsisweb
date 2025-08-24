<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

$config = require __DIR__ . '/config.php';

if(empty($_SESSION['isAdmin'])){
  http_response_code(401);
  echo json_encode(['ok'=>false,'error'=>'UNAUTHORIZED']);
  exit;
}

if(!isset($_FILES['file'])){
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'FILE_REQUIRED']);
  exit;
}

$file = $_FILES['file'];
if($file['error'] !== UPLOAD_ERR_OK){
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'UPLOAD_ERROR','code'=>$file['error']]);
  exit;
}

$max = (int)($config['uploadMaxBytes'] ?? (2*1024*1024));
if($file['size'] > $max){
  http_response_code(413);
  echo json_encode(['ok'=>false,'error'=>'FILE_TOO_LARGE']);
  exit;
}

$allowed = $config['uploadAllowedMime'] ?? ['image/jpeg','image/png','image/webp','image/gif'];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);
if(!in_array($mime, $allowed, true)){
  http_response_code(415);
  echo json_encode(['ok'=>false,'error'=>'UNSUPPORTED_MIME','mime'=>$mime]);
  exit;
}

$extMap = [
  'image/jpeg' => 'jpg',
  'image/png' => 'png',
  'image/webp' => 'webp',
  'image/gif' => 'gif',
];
$ext = $extMap[$mime] ?? 'bin';
$dir = rtrim($config['uploadDir'] ?? (__DIR__.'/uploads'), '/');
if(!is_dir($dir)){
  @mkdir($dir, 0775, true);
}
$basename = bin2hex(random_bytes(8)).'-'.time().'.'.$ext;
$path = $dir.'/'.$basename;
if(!move_uploaded_file($file['tmp_name'], $path)){
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'SAVE_FAILED']);
  exit;
}

$baseUrl = rtrim($config['uploadBaseUrl'] ?? '/uploads', '/');
$url = $baseUrl.'/'.$basename;

echo json_encode(['ok'=>true,'url'=>$url]);
