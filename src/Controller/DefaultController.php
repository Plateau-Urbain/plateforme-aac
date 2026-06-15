<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    #[Route("/", name: "homepage")]
    public function indexAction(): Response
    {
        return $this->redirect($this->generateUrl('search_index'));
    }

    #[Route("/cgu", name: "cgu")]
    public function cguAction(): Response
    {
        return $this->render('Default/cgu.html.twig');
    }

    #[Route("/proprietaire", name: "proprietaire")]
    public function ownerAction(): Response
    {
        return $this->render('Default/owner.html.twig');
    }

    #[Route("/upload_action", name: "upload_action")]
    public function uploadAction(): Response
    {
        return $this->render('Default/upload.html.twig');
    }
}
