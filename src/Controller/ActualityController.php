<?php

namespace App\Controller;

use App\Entity\Actuality;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/slider")]
class ActualityController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em) {}

    #[Route("/show", name: "show_actualities")]
    public function showAction(): Response
    {
        $actualities = $this->em->getRepository(Actuality::class)->findPublished();

        return $this->render('Actuality/show.html.twig', [
            'actualities' => $actualities,
        ]);
    }
}
