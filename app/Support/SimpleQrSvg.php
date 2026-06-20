<?php

namespace App\Support;

use InvalidArgumentException;

class SimpleQrSvg
{
    protected const ALPHANUMERIC = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ $%*+-./:';
    protected const SIZE = 21;
    protected const DATA_CODEWORDS = 19;
    protected const EC_CODEWORDS = 7;

    /**
     * Build a self-contained SVG QR code for the fixed customer QR tokens used by this project.
     *
     * This renderer intentionally supports QR Version 1, error correction L, alphanumeric mode.
     * Customer tokens are generated as QR- + 20 uppercase alphanumeric characters, which fits.
     */
    public static function svg(string $text, int $scale = 9, int $quietZone = 4): string
    {
        $matrix = self::matrix($text);
        $moduleCount = self::SIZE + ($quietZone * 2);
        $size = $moduleCount * $scale;

        $rects = [];

        foreach ($matrix as $row => $cols) {
            foreach ($cols as $col => $dark) {
                if (! $dark) {
                    continue;
                }

                $x = ($col + $quietZone) * $scale;
                $y = ($row + $quietZone) * $scale;
                $rects[] = '<rect x="'.$x.'" y="'.$y.'" width="'.$scale.'" height="'.$scale.'" />';
            }
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$size.' '.$size.'" width="'.$size.'" height="'.$size.'" role="img" aria-label="Customer QR Code" shape-rendering="crispEdges" style="shape-rendering:crispEdges;background:#fff" preserveAspectRatio="xMidYMid meet">'
            .'<rect x="0" y="0" width="'.$size.'" height="'.$size.'" fill="#ffffff" />'
            .'<g fill="#000000" shape-rendering="crispEdges">'.implode('', $rects).'</g>'
            .'</svg>';
    }

    /**
     * @return array<int, array<int, int>>
     */
    protected static function matrix(string $text): array
    {
        $text = strtoupper(trim($text));

        if ($text === '') {
            throw new InvalidArgumentException('QR text cannot be empty.');
        }

        if (strlen($text) > 25) {
            throw new InvalidArgumentException('QR text is too long for this renderer.');
        }

        for ($i = 0; $i < strlen($text); $i++) {
            if (strpos(self::ALPHANUMERIC, $text[$i]) === false) {
                throw new InvalidArgumentException('QR text contains unsupported characters.');
            }
        }

        $size = self::SIZE;
        $matrix = array_fill(0, $size, array_fill(0, $size, null));
        $reserved = array_fill(0, $size, array_fill(0, $size, false));

        $set = function (int $row, int $col, int $value, bool $reserve = true) use (&$matrix, &$reserved, $size): void {
            if ($row < 0 || $row >= $size || $col < 0 || $col >= $size) {
                return;
            }

            $matrix[$row][$col] = $value ? 1 : 0;

            if ($reserve) {
                $reserved[$row][$col] = true;
            }
        };

        $finder = function (int $row, int $col) use ($set, $size): void {
            for ($dr = -1; $dr <= 7; $dr++) {
                for ($dc = -1; $dc <= 7; $dc++) {
                    $r = $row + $dr;
                    $c = $col + $dc;

                    if ($r < 0 || $r >= $size || $c < 0 || $c >= $size) {
                        continue;
                    }

                    $inFinder = $dr >= 0 && $dr <= 6 && $dc >= 0 && $dc <= 6;
                    $dark = $inFinder && (
                        $dr === 0 || $dr === 6 || $dc === 0 || $dc === 6 ||
                        ($dr >= 2 && $dr <= 4 && $dc >= 2 && $dc <= 4)
                    );

                    $set($r, $c, $dark ? 1 : 0);
                }
            }
        };

        $finder(0, 0);
        $finder(0, $size - 7);
        $finder($size - 7, 0);

        for ($i = 8; $i < $size - 8; $i++) {
            $set(6, $i, $i % 2 === 0 ? 1 : 0);
            $set($i, 6, $i % 2 === 0 ? 1 : 0);
        }

        $set(13, 8, 1);

        $formatPositions1 = [];
        for ($c = 0; $c <= 5; $c++) {
            $formatPositions1[] = [8, $c];
        }
        $formatPositions1[] = [8, 7];
        $formatPositions1[] = [8, 8];
        $formatPositions1[] = [7, 8];
        for ($r = 5; $r >= 0; $r--) {
            $formatPositions1[] = [$r, 8];
        }

        $formatPositions2 = [];
        for ($r = $size - 1; $r >= $size - 7; $r--) {
            $formatPositions2[] = [$r, 8];
        }
        for ($c = $size - 8; $c < $size; $c++) {
            $formatPositions2[] = [8, $c];
        }

        foreach (array_merge($formatPositions1, $formatPositions2) as [$r, $c]) {
            $reserved[$r][$c] = true;
        }

        $dataCodewords = self::dataCodewords($text);
        $errorCorrection = self::reedSolomon($dataCodewords, self::EC_CODEWORDS);
        $codewords = array_merge($dataCodewords, $errorCorrection);

        $bits = [];
        foreach ($codewords as $byte) {
            for ($i = 7; $i >= 0; $i--) {
                $bits[] = ($byte >> $i) & 1;
            }
        }

        $bitIndex = 0;
        $upwards = true;

        for ($col = $size - 1; $col > 0; $col -= 2) {
            if ($col === 6) {
                $col--;
            }

            $rows = $upwards ? range($size - 1, 0) : range(0, $size - 1);

            foreach ($rows as $row) {
                foreach ([$col, $col - 1] as $c) {
                    if ($reserved[$row][$c]) {
                        continue;
                    }

                    $bit = $bits[$bitIndex] ?? 0;

                    if (($row + $c) % 2 === 0) {
                        $bit ^= 1;
                    }

                    $set($row, $c, $bit, false);
                    $bitIndex++;
                }
            }

            $upwards = ! $upwards;
        }

        $format = self::formatBits();
        $formatBits = [];
        for ($i = 14; $i >= 0; $i--) {
            $formatBits[] = ($format >> $i) & 1;
        }

        foreach ($formatPositions1 as $index => [$r, $c]) {
            $set($r, $c, $formatBits[$index]);
        }

        foreach ($formatPositions2 as $index => [$r, $c]) {
            $set($r, $c, $formatBits[$index]);
        }

        return $matrix;
    }

    /**
     * @return array<int, int>
     */
    protected static function dataCodewords(string $text): array
    {
        $bits = [];
        $addBits = function (int $value, int $length) use (&$bits): void {
            for ($i = $length - 1; $i >= 0; $i--) {
                $bits[] = ($value >> $i) & 1;
            }
        };

        $addBits(0b0010, 4);
        $addBits(strlen($text), 9);

        for ($i = 0; $i + 1 < strlen($text); $i += 2) {
            $value = (45 * strpos(self::ALPHANUMERIC, $text[$i])) + strpos(self::ALPHANUMERIC, $text[$i + 1]);
            $addBits($value, 11);
        }

        if (strlen($text) % 2 === 1) {
            $addBits(strpos(self::ALPHANUMERIC, $text[strlen($text) - 1]), 6);
        }

        $capacity = self::DATA_CODEWORDS * 8;
        $terminator = min(4, $capacity - count($bits));
        $addBits(0, max(0, $terminator));

        while (count($bits) % 8 !== 0) {
            $bits[] = 0;
        }

        $codewords = [];
        foreach (array_chunk($bits, 8) as $chunk) {
            $byte = 0;
            foreach ($chunk as $bit) {
                $byte = ($byte << 1) | $bit;
            }
            $codewords[] = $byte;
        }

        $pads = [0xec, 0x11];
        $padIndex = 0;
        while (count($codewords) < self::DATA_CODEWORDS) {
            $codewords[] = $pads[$padIndex % 2];
            $padIndex++;
        }

        return $codewords;
    }

    /**
     * @param array<int, int> $data
     * @return array<int, int>
     */
    protected static function reedSolomon(array $data, int $count): array
    {
        $generator = self::rsGenerator($count);
        $message = array_merge($data, array_fill(0, $count, 0));

        for ($i = 0; $i < count($data); $i++) {
            $coefficient = $message[$i];

            if ($coefficient === 0) {
                continue;
            }

            foreach ($generator as $j => $value) {
                $message[$i + $j] ^= self::gfMultiply($coefficient, $value);
            }
        }

        return array_slice($message, -$count);
    }

    /**
     * @return array<int, int>
     */
    protected static function rsGenerator(int $degree): array
    {
        $generator = [1];

        for ($i = 0; $i < $degree; $i++) {
            $generator = self::polyMultiply($generator, [1, self::gfExp($i)]);
        }

        return $generator;
    }

    /**
     * @param array<int, int> $left
     * @param array<int, int> $right
     * @return array<int, int>
     */
    protected static function polyMultiply(array $left, array $right): array
    {
        $result = array_fill(0, count($left) + count($right) - 1, 0);

        foreach ($left as $i => $a) {
            foreach ($right as $j => $b) {
                $result[$i + $j] ^= self::gfMultiply($a, $b);
            }
        }

        return $result;
    }

    protected static function gfMultiply(int $a, int $b): int
    {
        if ($a === 0 || $b === 0) {
            return 0;
        }

        [$exp, $log] = self::gfTables();

        return $exp[$log[$a] + $log[$b]];
    }

    protected static function gfExp(int $index): int
    {
        [$exp] = self::gfTables();

        return $exp[$index];
    }

    /**
     * @return array{0: array<int, int>, 1: array<int, int>}
     */
    protected static function gfTables(): array
    {
        static $tables = null;

        if ($tables !== null) {
            return $tables;
        }

        $exp = array_fill(0, 512, 0);
        $log = array_fill(0, 256, 0);
        $x = 1;

        for ($i = 0; $i < 255; $i++) {
            $exp[$i] = $x;
            $log[$x] = $i;
            $x <<= 1;

            if (($x & 0x100) !== 0) {
                $x ^= 0x11d;
            }
        }

        for ($i = 255; $i < 512; $i++) {
            $exp[$i] = $exp[$i - 255];
        }

        return $tables = [$exp, $log];
    }

    protected static function formatBits(): int
    {
        $errorCorrectionLevel = 1; // L
        $mask = 0;
        $data = ($errorCorrectionLevel << 3) | $mask;
        $value = $data << 10;
        $polynomial = 0b10100110111;

        for ($i = 14; $i >= 10; $i--) {
            if ((($value >> $i) & 1) !== 0) {
                $value ^= $polynomial << ($i - 10);
            }
        }

        return ((($data << 10) | $value) ^ 0b101010000010010) & 0x7fff;
    }
}
