<?php

namespace App\Support;

use GdImage;
use RuntimeException;

/**
 * Converts JPEG, PNG and WebP images to WebP with PHP's bundled GD.
 */
class WebpEncoder
{
    /**
     * The longest side, in pixels, an encoded image may have: twice the 816px display frame.
     */
    public const MAX_SIDE = 1632;

    /**
     * Encode the image at the given path as WebP.
     *
     * JPEGs are turned upright using their EXIF orientation, transparency is kept and images
     * longer than MAX_SIDE on either side are scaled down to fit. Smaller images are never enlarged.
     * Scaling runs before rotation so that only the smaller copy is rotated, keeping memory low;
     * a quarter turn does not change the longest side, so the result is the same.
     *
     * @throws RuntimeException When the image cannot be read or converted.
     */
    public function encode(string $path): string
    {
        $contents = @file_get_contents($path);
        $image = $contents === false || $contents === '' ? false : @imagecreatefromstring($contents);
        unset($contents);

        if ($image === false) {
            throw new RuntimeException('The image could not be read.');
        }

        imagepalettetotruecolor($image);
        imagealphablending($image, false);
        imagesavealpha($image, true);

        $image = $this->scaleDown($image);

        if (@exif_imagetype($path) === IMAGETYPE_JPEG) {
            $image = $this->applyOrientation($image, (int) (@exif_read_data($path)['Orientation'] ?? 1));
        }

        ob_start();
        $encoded = imagewebp($image, null, 82);
        $bytes = ob_get_clean();

        unset($image);

        if ($encoded === false || $bytes === false || $bytes === '') {
            throw new RuntimeException('The image could not be converted to WebP.');
        }

        return $bytes;
    }

    /**
     * Scale the image down proportionally when either side is longer than MAX_SIDE.
     *
     * Uses resampled copying, which averages the source pixels and needs no buffer beyond the result.
     */
    private function scaleDown(GdImage $image): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);

        if (max($width, $height) <= self::MAX_SIDE) {
            return $image;
        }

        $scale = self::MAX_SIDE / max($width, $height);
        $scaledWidth = max(1, (int) round($width * $scale));
        $scaledHeight = max(1, (int) round($height * $scale));
        $scaled = imagecreatetruecolor($scaledWidth, $scaledHeight);

        if ($scaled === false) {
            throw new RuntimeException('The image could not be scaled down.');
        }

        imagealphablending($scaled, false);
        imagesavealpha($scaled, true);
        imagefilledrectangle($scaled, 0, 0, $scaledWidth - 1, $scaledHeight - 1, imagecolorallocatealpha($scaled, 0, 0, 0, 127));

        if (! imagecopyresampled($scaled, $image, 0, 0, 0, 0, $scaledWidth, $scaledHeight, $width, $height)) {
            throw new RuntimeException('The image could not be scaled down.');
        }

        return $scaled;
    }

    /**
     * Rotate or flip the image so it displays as the camera's EXIF orientation describes.
     */
    private function applyOrientation(GdImage $image, int $orientation): GdImage
    {
        $rotation = match ($orientation) {
            3 => 180,
            5, 6 => -90,
            7, 8 => 90,
            default => 0,
        };

        if ($rotation !== 0) {
            $rotated = imagerotate($image, $rotation, imagecolorallocatealpha($image, 0, 0, 0, 127));

            if ($rotated === false) {
                throw new RuntimeException('The image could not be rotated.');
            }

            $image = $rotated;
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }

        if (in_array($orientation, [2, 5, 7], true)) {
            imageflip($image, IMG_FLIP_HORIZONTAL);
        } elseif ($orientation === 4) {
            imageflip($image, IMG_FLIP_VERTICAL);
        }

        return $image;
    }
}
