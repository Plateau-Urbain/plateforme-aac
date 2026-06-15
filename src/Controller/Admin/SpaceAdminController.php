<?php

namespace App\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Sonata\AdminBundle\Controller\CRUDController;
use App\Entity\Space;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Symfony\Component\HttpFoundation\Response;

/** @extends CRUDController<Space> */
class SpaceAdminController extends CRUDController
{
    public function __construct(private EntityManagerInterface $em) {}



    /**
     * Action de suppression en lot personnalisée pour gérer les applications associées
     */
    public function batchActionDelete(ProxyQueryInterface $query): Response
    {
        $this->admin->checkAccess('batchDelete');
        
        $em = $this->em;
        
        // Récupérer les Spaces sélectionnés
        $spaces = $query->execute();
        $spaceIds = [];
        
        foreach ($spaces as $space) {
            $spaceIds[] = $space->getId();
        }
        
        if (empty($spaceIds)) {
            $this->addFlash('sonata_flash_info', 'Aucun espace sélectionné.');
            return new RedirectResponse($this->admin->generateUrl('list'));
        }
        
        // Récupérer toutes les applications associées aux Spaces
        $applications = $em->getRepository(\App\Entity\Application::class)->createQueryBuilder('a')
            ->where('a.space IN (:spaceIds)')
            ->setParameter('spaceIds', $spaceIds)
            ->getQuery()
            ->getResult();
        
        // Récupérer les IDs des applications
        $applicationIds = [];
        foreach ($applications as $application) {
            $applicationIds[] = $application->getId();
        }
        
        $nbApplications = count($applicationIds);
        $conn = $em->getConnection();

        $conn->beginTransaction();
        try {
            if (!empty($applicationIds)) {
                // Supprimer tous les fichiers d'application associés
                $em->createQueryBuilder()
                   ->delete(\App\Entity\ApplicationFile::class, 'af')
                   ->where('af.application IN (:applicationIds)')
                   ->setParameter('applicationIds', $applicationIds)
                   ->getQuery()
                   ->execute();

                // Décommenter la ligne ci-dessous pour tester le rollback (puis la remettre en commentaire)
                // throw new \Exception('Test rollback transaction');

                // Supprimer toutes les applications
                $em->createQueryBuilder()
                   ->delete(\App\Entity\Application::class, 'a')
                   ->where('a.id IN (:applicationIds)')
                   ->setParameter('applicationIds', $applicationIds)
                   ->getQuery()
                   ->execute();
            }

            // Supprimer les Spaces
            $nbDeleted = 0;
            foreach ($spaces as $space) {
                $em->remove($space);
                $nbDeleted++;
            }

            $em->flush();
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollBack();
            $em->clear();
            $this->addFlash('sonata_flash_error', 'Erreur lors de la suppression en lot : ' . $e->getMessage());
            return new RedirectResponse($this->admin->generateUrl('list'));
        }
        
        if ($nbApplications > 0) {
            $this->addFlash(
                'sonata_flash_success',
                sprintf('%d espace(s) supprimé(s) avec %d candidature(s) associée(s).', $nbDeleted, $nbApplications)
            );
        } else {
            $this->addFlash(
                'sonata_flash_success',
                sprintf('%d espace(s) supprimé(s).', $nbDeleted)
            );
        }
        
        return new RedirectResponse($this->admin->generateUrl('list'));
    }
}
