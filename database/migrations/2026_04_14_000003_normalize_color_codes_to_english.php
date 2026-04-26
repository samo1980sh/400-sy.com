<?php

use App\Models\Color;
use App\Models\ProductColor;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $usedCodes = Color::query()
            ->pluck('code')
            ->filter(fn ($code) => ! preg_match('/[^\x20-\x7E]/', (string) $code))
            ->flip()
            ->all();

        $updates = [];

        Color::query()
            ->orderBy('id')
            ->get(['id', 'code', 'name_ar', 'name_en'])
            ->each(function (Color $color) use (&$updates, &$usedCodes): void {
                if (! preg_match('/[^\x20-\x7E]/', (string) $color->code)) {
                    $usedCodes[$color->code] = true;
                    return;
                }

                $base = Str::slug((string) ($color->name_en ?: $color->name_ar), '-');
                if ($base === '') {
                    $base = 'color-' . $color->id;
                }

                $code = $base;
                $counter = 1;
                while (isset($usedCodes[$code])) {
                    $counter++;
                    $code = $base . '-' . $counter;
                }

                $usedCodes[$code] = true;
                $updates[$color->id] = $code;
            });

        foreach ($updates as $colorId => $newCode) {
            Color::query()->whereKey($colorId)->update(['code' => $newCode]);
            ProductColor::query()
                ->where('color_id', $colorId)
                ->update(['color_code' => $newCode]);
        }
    }

    public function down(): void
    {
        // Intentionally left blank. This migration normalizes dictionary codes.
    }
};
