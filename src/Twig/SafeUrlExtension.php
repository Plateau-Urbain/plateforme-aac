<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Filtre Twig pour n'autoriser en href que les URL http/https.
 * Évite les XSS via javascript:, data:, vbscript:, etc.
 */
class SafeUrlExtension extends AbstractExtension
{
    private static array $allowedSchemes = ['http', 'https'];

    public function getFilters(): array
    {
        return [
            new TwigFilter('safe_href', [$this, 'safeHref']),
        ];
    }

    public function safeHref(?string $url): string
    {
        if ($url === null || $url === '') {
            return '';
        }
        $url = trim($url);
        $pos = strpos($url, ':');
        if ($pos === false) {
            return '';
        }
        $scheme = strtolower(substr($url, 0, $pos));

        return in_array($scheme, self::$allowedSchemes, true) ? $url : '';
    }
}
