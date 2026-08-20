<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Authentifie les routes /api/* par clé statique (header X-API-Key).
 *
 * Suffisant pour un usage serveur-à-serveur (ex. WordPress corporate consommant
 * les fiches espaces publiques) : pas de session, pas d'utilisateur, une seule clé
 * partagée avec les consommateurs autorisés.
 */
class ApiKeySubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly string $apiKey)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 8],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $providedKey = $request->headers->get('X-API-Key');

        if (empty($this->apiKey) || !is_string($providedKey) || !hash_equals($this->apiKey, $providedKey)) {
            $event->setResponse(new JsonResponse(['error' => 'Clé API invalide ou manquante.'], 401));
        }
    }
}
