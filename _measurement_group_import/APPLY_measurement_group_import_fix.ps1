$ErrorActionPreference = "Stop"

$path = "app\Services\RetailExcelImportService.php"

if (-not (Test-Path $path)) {
    throw "File not found: $path. Run this script from the Laravel project root."
}

$backupDir = "_backup_measurement_group_import"
New-Item -ItemType Directory -Force -Path $backupDir | Out-Null

$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$backupPath = Join-Path $backupDir "RetailExcelImportService.php.$timestamp.bak"
Copy-Item $path $backupPath -Force

$content = [System.IO.File]::ReadAllText((Resolve-Path $path))

$method = @'

    private function productMeasurementGroup(array $rows): ?string
    {
        foreach ($rows as $row) {
            $value = $this->normalizeText($this->value(
                $row,
                'زمر وحدة القياس',
                'زمرة وحدة القياس',
                'زمر القياس',
                'مجموعة القياس',
                'Measurement Group',
                'measurement_group'
            ));

            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

'@

if ($content -notmatch 'function\s+productMeasurementGroup\s*\(') {
    if ($content -match '(?m)^\s*private\s+function\s+productBodyFit\s*\(') {
        $content = [System.Text.RegularExpressions.Regex]::Replace(
            $content,
            '(?m)^(\s*private\s+function\s+productBodyFit\s*\()',
            $method + '$1',
            1
        )
    } elseif ($content -match '(?m)^\s*private\s+function\s+productDropType\s*\(') {
        $content = [System.Text.RegularExpressions.Regex]::Replace(
            $content,
            '(?m)^(\s*private\s+function\s+productDropType\s*\()',
            $method + '$1',
            1
        )
    } else {
        throw "Could not find productBodyFit/productDropType insertion point. Backup created at: $backupPath"
    }
}

$dropLine = "'drop_type' => `$this->productDropType(`$first),"
$measureLine = "'measurement_group' => `$this->productMeasurementGroup(`$rows),"

if (-not $content.Contains($measureLine)) {
    if (-not $content.Contains($dropLine)) {
        throw "Could not find drop_type line. Backup created at: $backupPath"
    }

    $content = $content.Replace(
        $dropLine,
        $dropLine + [Environment]::NewLine + "                " + $measureLine
    )
}

$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllText((Resolve-Path $path), $content, $utf8NoBom)

Write-Host "Done. Backup saved to: $backupPath"
Write-Host "Now run:"
Write-Host "php artisan optimize:clear"
Write-Host "php -l app\Services\RetailExcelImportService.php"
