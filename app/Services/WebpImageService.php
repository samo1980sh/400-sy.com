<?php

namespace App\Services;

use GdImage;
use Illuminate\Support\Facades\Storage;

class WebpImageService
{
    public function convertStoredPath(string $path, array $settings = []): string
    {
        return $this->convertStoredPathToDirectory($path, null, $settings, true);
    }

    public function convertStoredPathToDirectory(
        string $path,
        ?string $targetDirectory = null,
        array $settings = [],
        bool $deleteSource = false,
    ): string {
        if (blank($path)) {
            return $path;
        }

        $normalizedPath = ltrim($path, '/');

        if (str_ends_with(strtolower($normalizedPath), '.webp')) {
            return $normalizedPath;
        }

        $disk = Storage::disk('public');
        $sourceFullPath = $disk->path($normalizedPath);

        if (! is_file($sourceFullPath)) {
            return $normalizedPath;
        }

        $imageInfo = @getimagesize($sourceFullPath);

        if (! is_array($imageInfo) || ! isset($imageInfo[0], $imageInfo[1], $imageInfo['mime'])) {
            return $normalizedPath;
        }

        [$width, $height] = [$imageInfo[0], $imageInfo[1]];
        $mime = $imageInfo['mime'];

        $sourceImage = $this->createImageResource($sourceFullPath, $mime);

        if (! $sourceImage) {
            return $normalizedPath;
        }

        [$targetWidth, $targetHeight] = $this->calculateTargetSize(
            $width,
            $height,
            (int) ($settings['max_width'] ?? 0),
            (int) ($settings['max_height'] ?? 0),
        );

        if ($targetWidth !== $width || $targetHeight !== $height) {
            $targetImage = imagecreatetruecolor($targetWidth, $targetHeight);
            $this->preserveTransparency($targetImage, $mime);
            imagecopyresampled(
                $targetImage,
                $sourceImage,
                0,
                0,
                0,
                0,
                $targetWidth,
                $targetHeight,
                $width,
                $height
            );
            imagedestroy($sourceImage);
            $sourceImage = $targetImage;
        }

        $directory = $targetDirectory !== null
            ? trim($targetDirectory, '/')
            : pathinfo($normalizedPath, PATHINFO_DIRNAME);
        $baseName = pathinfo($normalizedPath, PATHINFO_FILENAME);
        $targetRelativePath = ($directory === '.' ? '' : $directory . '/') . $baseName . '.webp';
        $targetFullPath = $disk->path($targetRelativePath);

        if (is_file($targetFullPath)) {
            $sourceModifiedAt = @filemtime($sourceFullPath) ?: null;
            $targetModifiedAt = @filemtime($targetFullPath) ?: null;

            if ($sourceModifiedAt === null || $targetModifiedAt === null || $targetModifiedAt >= $sourceModifiedAt) {
                imagedestroy($sourceImage);

                return $targetRelativePath;
            }
        }

        if (! is_dir(dirname($targetFullPath))) {
            mkdir(dirname($targetFullPath), 0755, true);
        }

        $quality = (int) ($settings['quality'] ?? 82);
        $saved = imagewebp($sourceImage, $targetFullPath, $quality);
        imagedestroy($sourceImage);

        if (! $saved) {
            return $normalizedPath;
        }

        if ($deleteSource && $normalizedPath !== $targetRelativePath) {
            $disk->delete($normalizedPath);
        }

        return $targetRelativePath;
    }

    public function convertStoredPathToVariant(string $path, string $targetDirectory, array $settings = []): string
    {
        return $this->convertStoredPathToDirectory($path, $targetDirectory, $settings, false);
    }

    public function deleteStoredPath(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        Storage::disk('public')->delete(ltrim($path, '/'));
    }

    /**
     * @param  array<int, string>  $paths
     * @return array<int, string>
     */
    public function convertStoredPaths(array $paths, array $settings = []): array
    {
        return array_values(array_filter(array_map(
            fn (string $path): string => $this->convertStoredPath($path, $settings),
            $paths
        )));
    }

    /**
     * @param  array<int, string>  $paths
     */
    public function deleteStoredPaths(array $paths): void
    {
        foreach ($paths as $path) {
            $this->deleteStoredPath($path);
        }
    }

    protected function createImageResource(string $path, string $mime): GdImage|false
    {
        return match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/gif' => imagecreatefromgif($path),
            'image/webp' => imagecreatefromwebp($path),
            default => false,
        };
    }

    /**
     * @return array{0:int,1:int}
     */
    protected function calculateTargetSize(int $width, int $height, int $maxWidth, int $maxHeight): array
    {
        if ($maxWidth <= 0 && $maxHeight <= 0) {
            return [$width, $height];
        }

        $scale = 1.0;

        if ($maxWidth > 0 && $width > $maxWidth) {
            $scale = min($scale, $maxWidth / $width);
        }

        if ($maxHeight > 0 && $height > $maxHeight) {
            $scale = min($scale, $maxHeight / $height);
        }

        if ($scale >= 1) {
            return [$width, $height];
        }

        return [
            max(1, (int) round($width * $scale)),
            max(1, (int) round($height * $scale)),
        ];
    }

    protected function preserveTransparency(GdImage $image, string $mime): void
    {
        if ($mime === 'image/png' || $mime === 'image/gif') {
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }
    }
}
