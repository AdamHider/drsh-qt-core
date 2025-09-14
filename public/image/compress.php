<?php

$source_dir = __DIR__ . '/../../writable/uploads/media';
$target_dir = __DIR__ . '/../../writable/uploads/media_compressed';
$quality = 80;

// Рекурсивная конвертация
function convertImages($src, $dst, $quality) {
    $items = scandir($src);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;

        $src_path = $src . '/' . $item;
        $dst_path = $dst . '/' . $item;

        if (is_dir($src_path)) {
            if (!is_dir($dst_path)) mkdir($dst_path, 0777, true);
            convertImages($src_path, $dst_path, $quality);
        } else {
            $ext = strtolower(pathinfo($src_path, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                $webp_path = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $dst_path);
                if (!file_exists($webp_path)) {
                    echo "🔄 Конвертируем: $src_path → $webp_path\n";
                    $image = match ($ext) {
                        'png' => imagecreatefrompng($src_path),
                        'jpg', 'jpeg' => imagecreatefromjpeg($src_path),
                        default => null,
                    };
                    if ($image) {
                        imagewebp($image, $webp_path, $quality);
                        imagedestroy($image);
                    } else {
                        echo "⚠️ Не удалось загрузить: $src_path\n";
                    }
                } else {
                    echo "✅ Уже существует: $webp_path\n";
                }
            }
        }
    }
}

// Запуск
if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
convertImages($source_dir, $target_dir, $quality);
echo "🎉 Конвертация завершена.\n";
