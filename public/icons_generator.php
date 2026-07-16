<?php
$sourcePath = 'C:/Users/SUJITO/.gemini/antigravity/brain/97ddddbd-1224-4ef0-abc0-d228cd39038c/app_icon_512_1784213106380.png';
$targetDir = 'd:/new project/public/icons/';

if (!file_exists($targetDir)) {
    mkdir($targetDir, 0755, true);
}

// Copy to 512x512
copy($sourcePath, $targetDir . 'icon-512.png');

// Resize to 192x192
$src = imagecreatefrompng($sourcePath);
$dst = imagecreatetruecolor(192, 192);

// preserve transparency
imagealphablending($dst, false);
imagesavealpha($dst, true);

imagecopyresampled($dst, $src, 0, 0, 0, 0, 192, 192, 512, 512);
imagepng($dst, $targetDir . 'icon-192.png');

imagedestroy($src);
imagedestroy($dst);

echo "Icons generated successfully!";
