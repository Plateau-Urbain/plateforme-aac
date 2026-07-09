<?php

namespace App\Controller;

use App\Service\GeocodingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class GeocodingController extends AbstractController
{
    #[Route('/geocode', name: 'api_geocode', methods: ['POST'])]
    public function geocode(Request $request, GeocodingService $geocodingService): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        if (!\is_array($payload)) {
            return new JsonResponse(['error' => 'Requête invalide.'], 400);
        }

        $address = trim((string) ($payload['address'] ?? ''));
        $zipCode = trim((string) ($payload['zipCode'] ?? ''));
        $city = trim((string) ($payload['city'] ?? ''));

        if ($city === '' && $zipCode === '' && $address === '') {
            return new JsonResponse(['error' => 'Adresse incomplète.'], 400);
        }

        $result = $geocodingService->geocode($address, $zipCode, $city);
        if ($result === null) {
            return new JsonResponse(['error' => 'Adresse introuvable.'], 404);
        }

        return new JsonResponse($result);
    }
}
