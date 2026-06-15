<?php

namespace App\Controller;

use App\Entity\Application;
use App\Entity\Space;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/candidatures")]
class ApplicationController extends AbstractController
{
    #[Route("/", name: "application_list")]
    public function indexAction(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        assert($user instanceof User);
        $applications = $em->getRepository(Application::class)->getApplicationPerOwner($user);

        return $this->render('Application/index.html.twig', [
            'applications' => $applications,
        ]);
    }

    #[Route("/voir/{id}", name: "application_show")]
    public function showAction(Application $application, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $space = $application->getSpace();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        if (!($space instanceof Space && $space->isOwner($user)) && !$isAdmin) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à voir cette candidature.');
        }

        $prevApplication = $em->getRepository(Application::class)->getPrevApplication($application);
        $nextApplication = $em->getRepository(Application::class)->getNextApplication($application);

        if ((($space instanceof Space && $space->isOwner($user)) || $isAdmin)
            && $application->getStatus() === Application::UNREAD_STATUS
        ) {
            $application->setStatus(Application::WAIT_STATUS);
            $em->persist($application);
            $em->flush();
        }

        return $this->render('Application/show.html.twig', [
            'prevApplication' => $prevApplication,
            'nextApplication' => $nextApplication,
            'application'     => $application,
        ]);
    }
}
