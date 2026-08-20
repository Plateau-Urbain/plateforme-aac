<?php

namespace App\Controller\Api;

use App\Repository\SpaceRepository;
use App\Service\Api\SpaceApiPresenter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/spaces', name: 'api_v1_spaces_')]
class SpaceApiController extends AbstractController
{
    public function __construct(
        private readonly SpaceRepository $spaceRepository,
        private readonly SpaceApiPresenter $presenter,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $params = ['enabled' => true];

        // Correspond aux filtres déjà supportés par SpaceRepository::filter()
        if ($zipCode = $request->query->get('zipCode')) {
            $params['zipCode'] = $zipCode;
        }
        if ($localType = $request->query->get('type')) {
            $params['localType'] = $localType;
        }

        $spaces = $this->spaceRepository->filter($params);

        $response = new JsonResponse(['data' => $this->presenter->presentCollection($spaces)]);
        $response->setSharedMaxAge(300);
        $response->headers->set('Vary', 'X-API-Key');

        return $response;
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        $space = $this->spaceRepository->find($id);

        if (!$space || !$space->isEnabled() || !$space->isSubmitted()) {
            return new JsonResponse(['error' => 'Espace introuvable.'], 404);
        }

        $response = new JsonResponse(['data' => $this->presenter->present($space)]);
        $response->setSharedMaxAge(300);
        $response->headers->set('Vary', 'X-API-Key');

        return $response;
    }
}
