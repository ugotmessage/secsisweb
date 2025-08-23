<?php
function __env_load_once(){
  static $loaded = false;
  static $data = [];
  if($loaded){ return $data; }
  $loaded = true;
  $path = __DIR__ . '/.env';
  if(file_exists($path)){
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach($lines as $line){
      if(strpos(ltrim($line), '#') === 0){ continue; }
      $pos = strpos($line, '=');
      if($pos === false){ continue; }
      $key = trim(substr($line, 0, $pos));
      $val = trim(substr($line, $pos + 1));
      $val = trim($val, "'\"");
      if($key !== ''){ $data[$key] = $val; }
    }
  }
  return $data;
}
function env($key, $default = null){
  $val = getenv($key);
  if($val !== false && $val !== ''){ return $val; }
  $data = __env_load_once();
  return array_key_exists($key, $data) ? $data[$key] : $default;
}
