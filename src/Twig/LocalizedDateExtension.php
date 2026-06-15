<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\Extra\Intl\IntlExtension;
use Twig\TwigFilter;

/**
 * Alias de compatibilité SF3→SF6 pour le filtre Twig `localizeddate`.
 *
 * L'ancien filtre (twig/extensions, abandonné) avait la signature :
 *   localizeddate(date_format, time_format, locale, timezone, format, calendar)
 *
 * Le remplaçant moderne (twig/intl-extra) fournit format_date / format_datetime.
 * Cette extension fournit `localizeddate` comme alias pour ne pas avoir à modifier
 * tous les templates existants.
 */
class LocalizedDateExtension extends AbstractExtension
{
    private IntlExtension $intlExtension;

    public function __construct()
    {
        $this->intlExtension = new IntlExtension();
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('localizeddate', [$this, 'localizeddate'], ['needs_environment' => true]),
        ];
    }

    /**
     * @param \Twig\Environment $env
     * @param \DateTimeInterface|string|null $date
     * @param string $dateFormat  'none'|'short'|'medium'|'long'|'full'
     * @param string $timeFormat  'none'|'short'|'medium'|'long'|'full'
     * @param string|null $locale
     * @param string|null $timezone
     * @param string|null $format  Pattern ICU (ex: "dd MMMM yyyy")
     * @param string $calendar    'gregorian'|'traditional'
     */
    public function localizeddate(
        \Twig\Environment $env,
        mixed $date,
        string $dateFormat = 'medium',
        string $timeFormat = 'medium',
        ?string $locale = null,
        ?string $timezone = null,
        ?string $format = null,
        string $calendar = 'gregorian',
    ): string {
        if ($date === null) {
            return '';
        }

        $resolvedLocale = $locale ?? \Locale::getDefault();

        if ($timeFormat === 'none' && $dateFormat !== 'none') {
            return $this->intlExtension->formatDate($env, $date, $dateFormat, $format ?? '', $timezone, $calendar, $resolvedLocale);
        }

        if ($dateFormat === 'none' && $timeFormat !== 'none') {
            return $this->intlExtension->formatTime($env, $date, $timeFormat, $format ?? '', $timezone, $calendar, $resolvedLocale);
        }

        return $this->intlExtension->formatDateTime($env, $date, $dateFormat, $timeFormat, $format ?? '', $timezone, $calendar, $resolvedLocale);
    }
}
