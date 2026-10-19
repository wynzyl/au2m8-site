<?php

use App\Support\WebpEncoder;

/**
 * Write a GD image of the given size to a temporary file and return its path.
 *
 * @param  'jpeg'|'png'  $format
 */
function writeTestImage(int $width, int $height, string $format, bool $transparentCorner = false): string
{
    $image = imagecreatetruecolor($width, $height);
    imagefilledrectangle($image, 0, 0, $width - 1, $height - 1, imagecolorallocate($image, 200, 80, 20));

    if ($transparentCorner) {
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagesetpixel($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
    }

    $path = tempnam(sys_get_temp_dir(), 'img');

    if ($format === 'jpeg') {
        imagejpeg($image, $path);
    } else {
        imagepng($image, $path);
    }

    return $path;
}

/**
 * Write a JPEG with an EXIF Orientation of 6 (rotate 90° clockwise) and return its path.
 */
function writeRotatedTestJpeg(int $width, int $height): string
{
    $path = writeTestImage($width, $height, 'jpeg');
    $exif = "Exif\x00\x00MM\x00\x2A\x00\x00\x00\x08\x00\x01\x01\x12\x00\x03\x00\x00\x00\x01\x00\x06\x00\x00\x00\x00\x00\x00";
    $jpeg = file_get_contents($path);

    file_put_contents($path, substr($jpeg, 0, 2)."\xFF\xE1".pack('n', 2 + strlen($exif)).$exif.substr($jpeg, 2));

    return $path;
}

/**
 * @return array{0: int, 1: int}
 */
function webpSize(string $bytes): array
{
    $size = getimagesizefromstring($bytes);

    return [$size[0], $size[1]];
}

test('a JPEG is converted to WebP at the same size', function () {
    $bytes = (new WebpEncoder)->encode(writeTestImage(300, 200, 'jpeg'));

    expect(substr($bytes, 0, 4))->toBe('RIFF')
        ->and(substr($bytes, 8, 4))->toBe('WEBP')
        ->and(webpSize($bytes))->toBe([300, 200]);
});

test('PNG transparency is kept', function () {
    $bytes = (new WebpEncoder)->encode(writeTestImage(300, 200, 'png', transparentCorner: true));
    $image = imagecreatefromstring($bytes);

    expect(imagecolorsforindex($image, imagecolorat($image, 0, 0))['alpha'])->toBe(127);
});

test('a JPEG is turned upright using its EXIF orientation', function () {
    $bytes = (new WebpEncoder)->encode(writeRotatedTestJpeg(300, 200));

    expect(webpSize($bytes))->toBe([200, 300]);
});

test('bytes that are not an image are refused', function () {
    $path = tempnam(sys_get_temp_dir(), 'img');
    file_put_contents($path, 'not an image');

    (new WebpEncoder)->encode($path);
})->throws(RuntimeException::class);

test('large images are scaled down and small ones keep their size', function (int $width, int $height, array $expected) {
    $bytes = (new WebpEncoder)->encode(writeTestImage($width, $height, 'png'));

    expect(webpSize($bytes))->toBe($expected);
})->with([
    'wide' => [3264, 1000, [1632, 500]],
    'tall' => [1000, 3264, [500, 1632]],
    'at the limit' => [1632, 1632, [1632, 1632]],
    'small' => [816, 544, [816, 544]],
]);

test('a 12 megapixel phone photo is scaled down to fit', function () {
    $bytes = (new WebpEncoder)->encode(writeTestImage(4032, 3024, 'jpeg'));

    expect(webpSize($bytes))->toBe([1632, 1224]);
});

test('scaling applies to the image after EXIF rotation', function () {
    $bytes = (new WebpEncoder)->encode(writeRotatedTestJpeg(3264, 1000));

    expect(webpSize($bytes))->toBe([500, 1632]);
});
