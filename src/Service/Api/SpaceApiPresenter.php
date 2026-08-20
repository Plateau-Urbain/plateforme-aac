<?php

namespace App\Service\Api;

use App\Entity\Space;
use App\Entity\SpaceImage;
use App\Entity\SpaceLocation;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Vich\UploaderBundle\Storage\StorageInterface;

/**
 * Convertit les entités Space en tableaux prêts à sérialiser pour l'API publique.
 *
 * Volontairement séparé du Serializer Symfony : peu de champs, mapping stable,
 * pas de config XML/YAML supplémentaire à maintenir pour ce besoin.
 */
class SpaceApiPresenter
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly StorageInterface $storage,
    ) {
    }

    public function present(Space $space): array
    {
        $status = 'draft';
        if ($space->isSubmitted() && $space->isEnabled()) {
            $status = 'published';
        } elseif ($space->isSubmitted()) {
            $status = 'submitted';
        }
        if ($space->isClosed()) {
            $status = 'closed';
        }

        $data = [
            'id' => $space->getId(),
            'name' => $space->getName(),
            'status' => $status,
            'workflowType' => $space->getWorkflowType(),
            'url' => $this->router->generate('space_show', ['id' => $space->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            'createdAt' => $this->toIso($space->getCreated()),
            'updatedAt' => $this->toIso($space->getUpdated()),
            'applicationDeadline' => $this->toIso($space->getLimitAvailability()),
            'description' => $space->getDescription(),
            'activityDescription' => $space->getActivityDescription(),
            'locationDescription' => $space->getLocationDescription(),
            'usageRestriction' => $space->getUsageRestriction(),
            'type' => $space->getType()?->getName(),
            'tags' => array_values(array_filter(array_map(
                static fn ($spaceAttribute) => $spaceAttribute->getAttribute()?->getName(),
                $space->getTags()->toArray()
            ))),
            'surface' => $space->getSurface(),
            'size' => $space->getSize(),
            'nbSpaces' => $space->getNbSpaces(),
            'minSpace' => $space->getMinSpace(),
            'maxSpace' => $space->getMaxSpace(),
            'price' => $space->getPrice(),
            'priceText' => $space->getPriceText(),
            'images' => array_map(
                fn (SpaceImage $image) => $this->presentImage($image),
                $space->getPics()
            ),
            'owner' => [
                'company' => $space->getOwner()?->getCompany(),
                'website' => $space->getOwner()?->getWebsite(),
            ],
        ];

        if ($space->isMultiLocation()) {
            $data['locations'] = array_map(
                fn (SpaceLocation $location) => $this->presentLocation($location),
                $space->getOrderedActiveLocations()
            );
        } else {
            $data['address'] = $space->getAddress();
            $data['zipCode'] = $space->getZipCode();
            $data['city'] = $space->getCity();
        }

        return $data;
    }

    public function presentCollection(iterable $spaces): array
    {
        return array_map(fn (Space $space) => $this->present($space), is_array($spaces) ? $spaces : iterator_to_array($spaces));
    }

    private function presentImage(SpaceImage $image): array
    {
        $uri = $this->storage->resolveUri($image, 'file');

        return [
            'url' => $uri ? $this->toAbsoluteUrl($uri) : null,
            'position' => $image->getPosition(),
        ];
    }

    private function toAbsoluteUrl(string $uri): string
    {
        if (str_starts_with($uri, 'http://') || str_starts_with($uri, 'https://')) {
            return $uri;
        }

        $context = $this->router->getContext();
        $port = '';
        if ($context->getScheme() === 'http' && $context->getHttpPort() !== 80) {
            $port = ':' . $context->getHttpPort();
        } elseif ($context->getScheme() === 'https' && $context->getHttpsPort() !== 443) {
            $port = ':' . $context->getHttpsPort();
        }

        return $context->getScheme() . '://' . $context->getHost() . $port . $uri;
    }

    private function presentLocation(SpaceLocation $location): array
    {
        return [
            'id' => $location->getId(),
            'name' => $location->getName(),
            'address' => $location->getAddress(),
            'zipCode' => $location->getZipCode(),
            'city' => $location->getCity(),
            'latitude' => $location->getLatitude(),
            'longitude' => $location->getLongitude(),
            'description' => $location->getDescription(),
            'availability' => $location->getAvailability(),
        ];
    }

    private function toIso(?\DateTimeInterface $date): ?string
    {
        return $date?->format(\DateTimeInterface::ATOM);
    }
}
