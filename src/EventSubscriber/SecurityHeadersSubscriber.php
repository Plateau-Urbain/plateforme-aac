<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Ajoute les en-têtes de sécurité HTTP sur toutes les réponses.
 *
 * Mitigation partielle pendant la migration vers Symfony 5.4/6.4 :
 * - CVE-2026-46633 / CVE-2026-46638 : pas d'impact direct, mais réduit la surface
 *   d'attaque XSS/clickjacking qui pourrait amplifier d'autres vulnérabilités.
 * - Recommandation OWASP A05:2021 Security Misconfiguration.
 */
class SecurityHeadersSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -100],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();
        $headers = $response->headers;

        // Empêche le MIME-sniffing (XSS via upload de fichiers mal typés)
        $headers->set('X-Content-Type-Options', 'nosniff');

        // Protège contre le clickjacking
        $headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Protection XSS navigateur legacy (désactivée sur les navigateurs modernes qui l'ignorent)
        $headers->set('X-XSS-Protection', '1; mode=block');

        // Limite les informations envoyées dans le Referer
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Empêche la détection de la technologie serveur
        $headers->remove('X-Powered-By');
        $headers->remove('Server');

        // Force HTTPS sur les navigateurs qui ont déjà visité le site (1 an)
        if ($event->getRequest()->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
    }
}
