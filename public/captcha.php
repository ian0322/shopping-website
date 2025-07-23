<?php

// 啟動會話
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
// 生成隨機驗證碼
$allowed_origins = [
    'http://localhost:5174',           // 開發用 vite
    'http://192.168.0.2:8080',         // 若用 vite dev server
    'http://localhost',                // 打包後使用 localhost 開啟
    'http://192.168.0.2',
    'https://chenyiportfolio.great-site.net'
];
  
  $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
  
  if (in_array($origin, $allowed_origins)) {
      header("Access-Control-Allow-Origin: $origin");
      header("Access-Control-Allow-Credentials: true");
  }
  
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');  // 設置允許的 HTTP 方法
header('Access-Control-Allow-Headers: Content-Type, Authorization');  // 允許的請求頭
header('Content-Type: image/png');

$captcha = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 5);
$_SESSION['captcha'] = $captcha;

// 設置圖像類型
header('Content-Type: image/png');

// 創建圖片
$image = imagecreatetruecolor(120, 50);

// 設置顏色
$bg_color = imagecolorallocate($image, 255, 255, 255); // 背景色為白色
$text_color_1 = imagecolorallocate($image, rand(0, 255), rand(0, 255), rand(0, 255)); // 隨機字體顏色1
$text_color_2 = imagecolorallocate($image, rand(0, 255), rand(0, 255), rand(0, 255)); // 隨機字體顏色2
$text_color_3 = imagecolorallocate($image, rand(0, 255), rand(0, 255), rand(0, 255)); // 隨機字體顏色3

// 填充背景s
imagefilledrectangle($image, 0, 0, 120, 50, $bg_color);

// 使用 Arial 字體
$font = 'C:\\Windows\\Fonts\\arial.ttf';  // 注意路徑要正確

// 在圖片上寫文字並添加隨機角度
for ($i = 0; $i < strlen($captcha); $i++) {
    // 隨機角度
    $angle = rand(-30, 30); 
    // 隨機顏色
    $color = ${'text_color_' . rand(1, 3)};
    // 在圖片上寫每個字母
    imagettftext($image, 20, $angle, 10 + $i * 22, 30, $color, $font, $captcha[$i]);
}

// 添加隨機干擾線
for ($i = 0; $i < 5; $i++) {
    $line_color = imagecolorallocate($image, rand(0, 255), rand(0, 255), rand(0, 255));
    imageline($image, rand(0, 120), rand(0, 50), rand(0, 120), rand(0, 50), $line_color);
}

// 添加隨機點干擾
for ($i = 0; $i < 50; $i++) {
    $pixel_color = imagecolorallocate($image, rand(0, 255), rand(0, 255), rand(0, 255));
    imagesetpixel($image, rand(0, 120), rand(0, 50), $pixel_color);
}
if (!imagepng($image)) {
    echo "圖片輸出失敗";
}
// 輸出圖片
imagepng($image);
imagedestroy($image);
session_write_close();

exit;  // 防止後續輸出干擾圖片

?>