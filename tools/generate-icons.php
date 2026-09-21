<?php

/**
 * PWA icon generator — Fish Farm ERP
 * ---------------------------------------------------------------------------
 * Generates the PNG icons referenced by public/manifest.webmanifest.
 *
 * Run once (or after changing the brand colours):
 *   C:\xampp\php\php.exe tools\generate-icons.php
 *
 * This is a build/dev utility, not part of the application runtime.
 * The design mirrors the CSS tokens in resources/css/app.css:
 *   brand gradient  #14532d → #15803d → #0f766e
 *   glyph           white "F" wordmark on the gradient
 */

$publicDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'icons';

if (! is_dir($publicDir) && ! mkdir($publicDir, 0775, true)) {
    fwrite(STDERR, "Unable to create {$publicDir}\n");
    exit(1);
}

if (! extension_loaded('gd')) {
    fwrite(STDERR, "The GD extension is required to generate icons.\n");
    exit(1);
}

/**
 * Draw a rounded-rect glyph icon on a diagonal brand gradient.
 *
 * @param  int  $size          Square dimension in pixels.
 * @param  bool  $maskable     If true, add safe-zone padding for maskable icons.
 */
function makeIcon(int $size, bool $maskable = false): GdImage
{
    $img = imagecreatetruecolor($size, $size);
    imagesavealpha($img, true);
    imagealphablending($img, false);
    imagefill($img, 0, 0, imagecolorallocatealpha($img, 0, 0, 0, 127));
    imagealphablending($img, true);

    // Diagonal gradient #14532d -> #15803d -> #0f766e
    $stops = [
        [0.00, [0x14, 0x53, 0x2d]],
        [0.55, [0x15, 0x80, 0x3d]],
        [1.00, [0x0f, 0x76, 0x6e]],
    ];

    for ($y = 0; $y < $size; $y++) {
        for ($x = 0; $x < $size; $x++) {
            // Project each pixel onto the gradient axis (top-left -> bottom-right).
            $t = (($x / max($size - 1, 1)) + ($y / max($size - 1, 1))) / 2;

            $rgb = interpolateStops($stops, $t);
            $color = imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]);
            imagesetpixel($img, $x, $y, $color);
        }
    }

    // Rounded corner mask (skip for maskable icons — the OS applies its own shape).
    if (! $maskable) {
        applyRoundedMask($img, $size, (int) round($size * 0.22));
    }

    // Wordmark. Maskable icons keep extra padding so the glyph survives cropping.
    $fontScale = $maskable ? 0.34 : 0.44;
    drawCenteredGlyph($img, $size, $fontScale);

    return $img;
}

/** @param  array<int, array{0: float, 1: array{0:int,1:int,2:int}}>  $stops */
function interpolateStops(array $stops, float $t): array
{
    for ($i = 0; $i < count($stops) - 1; $i++) {
        [$posA, $colorA] = $stops[$i];
        [$posB, $colorB] = $stops[$i + 1];

        if ($t >= $posA && $t <= $posB) {
            $local = ($t - $posA) / max($posB - $posA, 0.0001);

            return [
                (int) round($colorA[0] + ($colorB[0] - $colorA[0]) * $local),
                (int) round($colorA[1] + ($colorB[1] - $colorA[1]) * $local),
                (int) round($colorA[2] + ($colorB[2] - $colorA[2]) * $local),
            ];
        }
    }

    return $stops[count($stops) - 1][1];
}

/** Round the four corners to transparent. */
function applyRoundedMask(GdImage $img, int $size, int $radius): void
{
    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);

    for ($y = 0; $y < $size; $y++) {
        for ($x = 0; $x < $size; $x++) {
            $inCornerX = 0;
            $inCornerY = 0;
            $dx = 0;
            $dy = 0;

            if ($x < $radius && $y < $radius) {
                $dx = $radius - $x;
                $dy = $radius - $y;
            } elseif ($x >= $size - $radius && $y < $radius) {
                $dx = $x - ($size - $radius - 1);
                $dy = $radius - $y;
            } elseif ($x < $radius && $y >= $size - $radius) {
                $dx = $radius - $x;
                $dy = $y - ($size - $radius - 1);
            } elseif ($x >= $size - $radius && $y >= $size - $radius) {
                $dx = $x - ($size - $radius - 1);
                $dy = $y - ($size - $radius - 1);
            } else {
                continue;
            }

            if (($dx * $dx + $dy * $dy) > ($radius * $radius)) {
                imagesetpixel($img, $x, $y, $transparent);
            }
        }
    }
}

/** Draw a white "F" using the bundled GD bitmap font, scaled up. */
function drawCenteredGlyph(GdImage $img, int $size, float $scale): void
{
    $white = imagecolorallocate($img, 255, 255, 255);

    // GD's built-in font 5 is the largest; render to a small canvas then scale.
    $char = 'F';
    $cw = imagefontwidth(5);
    $ch = imagefontheight(5);

    $tmpW = $cw + 4;
    $tmpH = $ch + 4;
    $tmp = imagecreatetruecolor($tmpW, $tmpH);
    imagealphablending($tmp, false);
    imagefill($tmp, 0, 0, imagecolorallocatealpha($tmp, 0, 0, 0, 127));
    imagealphablending($tmp, true);
    imagestring($tmp, 5, 2, 2, $char, $white);

    $targetH = (int) round($size * $scale);
    $targetW = (int) round($targetH * ($tmpW / $tmpH));

    $dstX = (int) round(($size - $targetW) / 2);
    $dstY = (int) round(($size - $targetH) / 2);

    imagecopyresampled($img, $tmp, $dstX, $dstY, 0, 0, $targetW, $targetH, $tmpW, $tmpH);

    imagedestroy($tmp);
}

/* ----------------------------------------------------------------- generate */
$targets = [
    'icon-192.png' => [192, false],
    'icon-512.png' => [512, false],
    'maskable-512.png' => [512, true],
    'apple-touch-icon.png' => [180, false],
];

foreach ($targets as $filename => [$size, $maskable]) {
    $img = makeIcon($size, $maskable);
    $path = $publicDir . DIRECTORY_SEPARATOR . $filename;

    if (imagepng($img, $path)) {
        echo "  created {$filename} ({$size}x{$size})\n";
    } else {
        fwrite(STDERR, "  FAILED {$filename}\n");
    }

    imagedestroy($img);
}

echo "\nPWA icons generated in public/icons\n";
