<?php

namespace App\Controller;

use App\Entity\Space;
use App\Form\SearchType;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/recherche")]
class SearchController extends AbstractController
{
    /** @var list<string> */
    private array $availableCodes = ['95', '78', '92', '93', '75', '94', '77', '91'];

     #[Route("/", name:"search_index")]
    public function indexAction(EntityManagerInterface $em): Response
    {
        try {
            $form = $this->createForm(SearchType::class);
            $formView = $form->createView();
        } catch (\Throwable $e) {
            $formView = null;
        }

        $isAdmin = $this->isGranted('ROLE_ADMIN');

        $params = [
            'orderBy'           => 'created',
            'sort'              => 'DESC',
        ];

        $params['limitAvailability'] = new \DateTime('today');
        $params['enabled'] = true;
        $params['closed'] = false;

        try {
            $all = $em->getRepository(Space::class)->filter($params);
        } catch (\Throwable $e) {
            $all = [];
        }

        $unavailableParams = [
            'orderBy' => 'created',
            'sort' => 'DESC',
            'pagination' => 6,
        ];
        $unavailableParams['unavailable'] = true;

        try {
            $unavailableSpaces = $em->getRepository(Space::class)->filter($unavailableParams);
            if ($unavailableSpaces instanceof \Doctrine\ORM\Tools\Pagination\Paginator) {
                $unavailableSpaces = iterator_to_array($unavailableSpaces);
            }
        } catch (\Throwable $e) {
            $unavailableSpaces = [];
        }

        $departements = $this->buildDepartementsFromSpaces($all);

        return $this->render('Search/index.html.twig', [
            'form'              => $formView,
            'latest'            => $all,
            'unavailableSpaces' => $unavailableSpaces,
            'departements'      => $departements,
            'isAdmin'           => $isAdmin,
        ]);
    }

     #[Route("/resultats", name:"search_action", methods:["POST"])]
    public function searchAction(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator): Response
    {
        $search = $this->createForm(SearchType::class);
        $search->handleRequest($request);

        if ($search->isSubmitted() && $search->isValid()) {
            $isAdmin = $this->isGranted('ROLE_ADMIN');

            $params = [
                'zipCode'           => $search->get('zipCode')->getData(),
                'localType'         => $search->get('localType')->getData(),
                'minimumPrice'      => $search->get('minimumPrice')->getData(),
                'maximumPrice'      => $search->get('maximumPrice')->getData(),
                'minimumSurface'    => $search->get('minimumSurface')->getData(),
                'maximumSurface'    => $search->get('maximumSurface')->getData(),
                'orderBy'           => $search->get('orderBy')->getData(),
                'sort'              => $search->get('sort')->getData(),
            ];

            $params['limitAvailability'] = new \DateTime('today');
            $params['enabled'] = true;
            $params['closed'] = false;

            $query = $em->getRepository(Space::class)->filter($params);

            unset($params['zipCode']);
            $departementsSpace = $em->getRepository(Space::class)->filter($params);
            $departements = $this->buildDepartementsFromSpaces($departementsSpace);

            $pagination = $paginator->paginate(
                $query,
                $request->query->getInt('page', 1),
                10
            );

            return $this->render('Search/search.html.twig', [
                'zipCode'       => $search->get('zipCode')->getData(),
                'pagination'    => $pagination,
                'form'          => $search->createView(),
                'departements'  => $departements,
                'isAdmin'       => $isAdmin,
            ]);
        }

        return $this->redirectToRoute('search_index');
    }

    /** @param iterable<\App\Entity\Space> $spaces
     * @return array<string, int> */
    private function buildDepartementsFromSpaces(iterable $spaces): array
    {
        $departements = [];
        foreach ($spaces as $space) {
            if (in_array($space->getDepCode(), $this->availableCodes)) {
                if (!isset($departements[$space->getDepCode()])) {
                    $departements[$space->getDepCode()] = 0;
                }
                $departements[$space->getDepCode()] += 1;
            }
        }
        return $departements;
    }
}
