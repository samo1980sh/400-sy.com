<?php

$companyMedia = require __DIR__ . '/company_media.php';

return [
    'branches' => $companyMedia['branches'] ?? [],
    'company' => [
        'pages' => $companyMedia['company_pages'] ?? [],
        'news_main_image' => $companyMedia['news_main_image'] ?? [],
        'news_gallery_image' => $companyMedia['news_gallery_image'] ?? [],
        'header_images' => $companyMedia['header_images'] ?? [],
        'internal_headers' => $companyMedia['internal_headers'] ?? [],
    ],
];
