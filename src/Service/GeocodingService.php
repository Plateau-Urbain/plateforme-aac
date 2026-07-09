<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeocodingService
{
    private const NOMINATIM_URL = 'https://nominatim.openstreetmap.org/search';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $appName = 'PlateauUrbainAAC',
    ) {
    }

    /**
     * @return array{lat: float, lng: float, displayName: string}|null
     */
    public function geocode(string $address, string $zipCode, string $city): ?array
    {
        $query = trim(sprintf('%s, %s %s, France', $address, $zipCode, $city), ', ');
        if ($query === '' || $query === 'France') {
            return null;
        }

        try {
            $response = $this->httpClient->request('GET', self::NOMINATIM_URL, [
                'query' => [
                    'q' => $query,
                    'format' => 'json',
                    'limit' => 1,
                    'countrycodes' => 'fr',
                ],
                'headers' => [
                    'User-Agent' => $this->appName,
                    'Accept-Language' => 'fr',
                ],
            ]);

            $data = $response->toArray();
            if ($data === []) {
                return null;
            }

            $result = $data[0];

            return [
                'lat' => (float) $result['lat'],
                'lng' => (float) $result['lon'],
                'displayName' => (string) ($result['display_name'] ?? $query),
            ];
        } catch (\Throwable $e) {
            $this->logger->warning('Geocoding failed', [
                'query' => $query,
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
