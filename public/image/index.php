<?php

$uri = $_SERVER['REQUEST_URI'];
$uri_parts = explode('index.php/', $uri);
$relative_path = $uri_parts[1] ?? '';

$compressed_base = '../../writable/uploads/media_compressed/';
$original_base   = '../../writable/uploads/media/';

$ext = strtolower(pathinfo($relative_path, PATHINFO_EXTENSION));
$filename_no_ext = preg_replace('/\.[^.]+$/', '', $relative_path);

// 👉 Если запрашивается .webp
if ($ext === 'webp') {
    $compressed_path = $compressed_base . $relative_path;
    $original_path   = $original_base . $relative_path;

    if (file_exists($compressed_path)) {
        $file_to_send = $compressed_path;
    } elseif (file_exists($original_path)) {
        $file_to_send = $original_path;
    } else {
        http_response_code(404);
        exit('WebP file not found');
    }

    $content_type = 'image/webp';
}
// 👉 Если запрашивается другой формат
else {
    $compressed_webp = $compressed_base . $filename_no_ext . '.webp';
    $original_path   = $original_base . $relative_path;
    if (file_exists($compressed_webp)) {
        $file_to_send = $compressed_webp;
        $content_type = 'image/webp';
    } elseif (file_exists($original_path)) {
        $file_to_send = $original_path;
        $content_type = match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            default       => 'application/octet-stream',
        };
    } else {
        http_response_code(404);
        exit('Original file not found');
    }
}
// 📦 Отправляем файл
header('Content-Type: ' . $content_type);
header('Cache-Control: public, max-age=604800, immutable');
header('Content-Length: ' . filesize($file_to_send));
readfile($file_to_send);
exit;
