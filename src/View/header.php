<?php

if (!function_exists('escapeHtml')) {
    function escapeHtml(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('shortText')) {
    function shortText(mixed $value, int $limit = 240): string
    {
        $text = trim((string) $value);

        if ($text === '') {
            return '';
        }

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($text) <= $limit) {
                return $text;
            }

            return mb_substr($text, 0, $limit) . '...';
        }

        if (strlen($text) <= $limit) {
            return $text;
        }

        return substr($text, 0, $limit) . '...';
    }
}

if (!function_exists('getFirstImageUrl')) {
    function getFirstImageUrl(array $item): ?string
    {
        if (!empty($item['image']) && is_string($item['image'])) {
            return $item['image'];
        }

        if (!empty($item['images']) && is_array($item['images'])) {
            foreach ($item['images'] as $image_url) {
                if (is_string($image_url) && trim($image_url) !== '') {
                    return $image_url;
                }
            }
        }

        return null;
    }
}

$page_title = $page_title ?? 'Data parser';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escapeHtml($page_title) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container site-header__inner">
        <a href="/" class="site-logo">Data Parser</a>

        <nav class="site-nav">
            <a href="/">Home</a>
        </nav>
    </div>
</header>