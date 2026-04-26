<?php

namespace App\Models\Concerns;

use App\Services\WebpImageService;
use Illuminate\Database\Eloquent\Model;

trait HasWebpMedia
{
    protected static function bootHasWebpMedia(): void
    {
        static::saving(function (Model $model): void {
            $service = app(WebpImageService::class);

            foreach ($model->webpSingleImageFields() as $field) {
                $model->{$field} = $service->convertStoredPath(
                    (string) $model->{$field},
                    $model->webpImageSettings($field)
                );
            }

            foreach ($model->webpMultipleImageFields() as $field) {
                $paths = $model->{$field};
                $paths = is_array($paths) ? $paths : [];

                $model->{$field} = $service->convertStoredPaths(
                    $paths,
                    $model->webpImageSettings($field)
                );
            }
        });

        static::saved(function (Model $model): void {
            $service = app(WebpImageService::class);

            foreach ($model->webpSingleImageFields() as $field) {
                $original = $model->getOriginal($field);
                $current = $model->{$field};

                if (filled($original) && $original !== $current) {
                    $service->deleteStoredPath((string) $original);
                }
            }

            foreach ($model->webpMultipleImageFields() as $field) {
                $original = $model->getOriginal($field);
                $original = is_string($original) ? json_decode($original, true) : $original;
                $original = is_array($original) ? $original : [];
                $current = $model->{$field} ?? [];
                $current = is_array($current) ? $current : [];

                foreach (array_diff($original, $current) as $removedPath) {
                    $service->deleteStoredPath((string) $removedPath);
                }
            }
        });

        static::deleting(function (Model $model): void {
            $service = app(WebpImageService::class);

            foreach ($model->webpSingleImageFields() as $field) {
                $service->deleteStoredPath((string) $model->{$field});
            }

            foreach ($model->webpMultipleImageFields() as $field) {
                $paths = $model->{$field} ?? [];
                $paths = is_array($paths) ? $paths : [];
                $service->deleteStoredPaths($paths);
            }
        });
    }

    /**
     * @return array<int, string>
     */
    protected function webpSingleImageFields(): array
    {
        return [];
    }

    /**
     * @return array<int, string>
     */
    protected function webpMultipleImageFields(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function webpImageSettings(string $field): array
    {
        return [];
    }
}
