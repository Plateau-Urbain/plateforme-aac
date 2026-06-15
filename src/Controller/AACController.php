<?php

namespace App\Controller;

use App\Entity\Space;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/appels-a-candidature")]
class AACController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private PaginatorInterface $paginator,
    ) {}

    #[Route("/list", name: "aac_list")]
    public function listAction(Request $request): Response
    {
        $params = [
            'closed' => false,
            'limitAvailability' => new \DateTime('today'),
        ];

        $query = $this->em->getRepository(Space::class)->filter($params);

        $pagination = $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1)
        );

        return $this->render('AAC/list.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route("/show")]
    public function showAction(): never
    {
        throw $this->createNotFoundException('Cette page n\'est pas encore implémentée.');
    }
}
