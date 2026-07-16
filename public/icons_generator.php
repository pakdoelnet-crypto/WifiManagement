<?php
$sourcePath = __DIR__ . '/images/logo.jpg';
$targetDir = __DIR__ . '/icons/';

if (!file_exists($targetDir)) {
    mkdir($targetDir, 0755, true);
}

$imgString = file_get_contents($sourcePath);
$src = imagecreatefromstring($imgString);
if ($src === false) {
    die("Failed to load source image");
}

$srcWidth = imagesx($src);
$srcHeight = imagesy($src);

// Generate 512x512
$dst512 = imagecreatetruecolor(512, 512);
imagealphablending($dst512, false);
imagesavealpha($dst512, true);
imagecopyresampled($dst512, $src, 0, 0, 0, 0, 512, 512, $srcWidth, $srcHeight);
imagepng($dst512, $targetDir . 'icon-512.png');
imagedestroy($dst512);

// Generate 192x192
$dst192 = imagecreatetruecolor(192, 192);
imagealphablending($dst192, false);
imagesavealpha($dst192, true);
imagecopyresampled($dst192, $src, 0, 0, 0, 0, 192, 192, $srcWidth, $srcHeight);
imagepng($dst192, $targetDir . 'icon-192.png');
imagedestroy($dst192);

imagedestroy($src);
echo "Icons generated successfully!";
