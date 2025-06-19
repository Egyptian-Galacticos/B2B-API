<?php

$placeholders = [
    'electronics' => ['bg' => '#1E40AF', 'text' => 'Electronics'],
    'fashion'     => ['bg' => '#DC2626', 'text' => 'Fashion'],
    'home'        => ['bg' => '#059669', 'text' => 'Home & Garden'],
    'sports'      => ['bg' => '#D97706', 'text' => 'Sports'],
    'automotive'  => ['bg' => '#7C2D12', 'text' => 'Automotive'],
    'default'     => ['bg' => '#6B7280', 'text' => 'Product'],
];

$width = 800;
$height = 600;
$outputDir = __DIR__.'/../storage/app/public/placeholders/';

if (! is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
    echo "Created directory: $outputDir\n";
}

echo "Generating placeholder images...\n";

foreach ($placeholders as $name => $config) {
    $image = imagecreate($width, $height);

    $hex = ltrim($config['bg'], '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));

    $bgColor = imagecolorallocate($image, $r, $g, $b);
    $textColor = imagecolorallocate($image, 255, 255, 255);

    imagefill($image, 0, 0, $bgColor);

    $text = $config['text'];
    $fontSize = 5;

    $textWidth = strlen($text) * imagefontwidth($fontSize);
    $textHeight = imagefontheight($fontSize);
    $x = ($width - $textWidth) / 2;
    $y = ($height - $textHeight) / 2;

    imagestring($image, $fontSize, $x, $y, $text, $textColor);

    $filename = $outputDir.$name.'.jpg';
    imagejpeg($image, $filename, 90);
    imagedestroy($image);

    echo "✓ Created: $filename\n";
}

echo "\nAll placeholder images created successfully!\n";
echo "Location: $outputDir\n";
echo "\nTo regenerate images, run: php scripts/generate-placeholders.php\n";
