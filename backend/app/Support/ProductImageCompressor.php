<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use RuntimeException;

class ProductImageCompressor
{
    private const MAX_DIMENSION = 1600;
    private const WEBP_QUALITY = 92;

    public function compress(UploadedFile $file): string
    {
        $source = @imagecreatefromstring($file->get());

        if ($source === false) {
            throw new RuntimeException('The uploaded product image could not be decoded.');
        }

        try {
            $width = imagesx($source);
            $height = imagesy($source);
            $scale = min(1, self::MAX_DIMENSION / max($width, $height));
            $targetWidth = max(1, (int) round($width * $scale));
            $targetHeight = max(1, (int) round($height * $scale));
            $resized = imagecreatetruecolor($targetWidth, $targetHeight);

            if ($resized === false) {
                throw new RuntimeException('Unable to prepare the product image for compression.');
            }

            try {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
                imagefill($resized, 0, 0, $transparent);

                if (!imagecopyresampled(
                    $resized,
                    $source,
                    0,
                    0,
                    0,
                    0,
                    $targetWidth,
                    $targetHeight,
                    $width,
                    $height
                )) {
                    throw new RuntimeException('Unable to resize the product image.');
                }

                $output = fopen('php://temp', 'w+b');

                if ($output === false) {
                    throw new RuntimeException('Unable to create a temporary product image.');
                }

                try {
                    if (!imagewebp($resized, $output, self::WEBP_QUALITY)) {
                        throw new RuntimeException('Unable to encode the product image as WebP.');
                    }

                    rewind($output);
                    $contents = stream_get_contents($output);

                    if ($contents === false || $contents === '') {
                        throw new RuntimeException('The compressed product image is empty.');
                    }

                    return $contents;
                } finally {
                    fclose($output);
                }
            } finally {
                imagedestroy($resized);
            }
        } finally {
            imagedestroy($source);
        }
    }
}
