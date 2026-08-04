<?php

namespace App\Support;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Throwable;

class CustomerQrSvg
{
    public static function render(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            throw new \InvalidArgumentException('Customer account number cannot be empty.');
        }

        if (class_exists(QRCode::class) && class_exists(QROptions::class)) {
            try {
                $options = new QROptions([
                    'outputBase64' => false,
                    'svgAddXmlHeader' => false,
                    'drawLightModules' => true,
                    'connectPaths' => true,
                ]);

                return (new QRCode($options))->render($value);
            } catch (Throwable) {
                // Fall through to the small built-in renderer for standard account numbers.
            }
        }

        return SimpleQrSvg::svg($value, 14, 6);
    }
}
