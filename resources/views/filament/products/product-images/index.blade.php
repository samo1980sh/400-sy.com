@php
    use Illuminate\Support\Facades\Storage;

    static $imageIndex = null;

    if ($imageIndex === null) {
        $imageIndex = Storage::disk('public')->allFiles('products');
    }

    $productCode = trim((string) ($record?->product?->model_no ?? ''));
    $colorCode = trim((string) ($record?->color_code ?? ''));
    $baseName = $productCode !== '' && $colorCode !== '' ? $productCode . '-' . $colorCode : '';

    $imageUrls = [];

    if ($baseName !== '') {
        $imagePaths = collect($imageIndex)
            ->filter(function (string $path) use ($baseName): bool {
                $fileName = pathinfo($path, PATHINFO_FILENAME);

                return $fileName === $baseName || str_starts_with($fileName, $baseName . '-');
            })
            ->sortBy(function (string $path) use ($baseName): array {
                $fileName = pathinfo($path, PATHINFO_FILENAME);

                if ($fileName === $baseName) {
                    return [0, 0];
                }

                $suffix = (int) preg_replace('/^' . preg_quote($baseName, '/') . '-/', '', $fileName);

                return [1, $suffix];
            })
            ->values()
            ->all();

        $imageUrls = array_map(
            fn (string $path): string => Storage::disk('public')->url($path),
            $imagePaths,
        );
    }
@endphp

<div class="min-w-[14rem] max-w-[26rem]">
    @if ($imageUrls === [])
        <span class="text-sm text-gray-500">-</span>
    @else
        <div class="space-y-2">
            <div class="grid grid-cols-4 gap-2">
                @foreach (array_slice($imageUrls, 0, 8) as $index => $url)
                    <a href="{{ $url }}" target="_blank" class="block">
                        <img
                            src="{{ $url }}"
                            alt=""
                            class="h-16 w-16 rounded-md border border-gray-200 object-cover transition hover:scale-105 {{ $index === 0 ? 'ring-2 ring-amber-500' : '' }}"
                        >
                    </a>
                @endforeach
            </div>

            @if (count($imageUrls) > 8)
                <div class="text-xs text-gray-500">
                    +{{ count($imageUrls) - 8 }} صورة إضافية
                </div>
            @endif
        </div>
    @endif
</div>
