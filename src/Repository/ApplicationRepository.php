<?php

namespace App\Repository;

use App\Entity\Application;
use App\Entity\Space;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class ApplicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Application::class);
    }

    /**
     * @param User $owner
     *
     * @return array
     */
    public function getApplicationPerOwner(User $owner)
    {
        $qb = $this->createQueryBuilder('a')
            ->addSelect('space')
            ->addSelect('user')
            ->innerJoin('a.space','space')
            ->innerJoin('space.owner','user')
            ->where('space.owner = :user_id')
            ->setParameters([
                'user_id' => $owner->getId()
            ]);

        return $qb->getQuery()->getResult();
    }

    public function formFilter($params)
    {
        $qb = $this->createQueryBuilder('a')
          ->innerJoin('a.space','s');

        if (!empty($params['applicant'])) {
          $qb->andWhere('a.projectHolder = :user_id')->setParameter('user_id', $params['applicant']);
        }

        if (!empty($params['status'])) {
          if($params['status'] == 'sent'){
            $qb->andWhere('a.status = :awaiting OR a.status = :unread')
              ->setParameter('awaiting', 'awaiting')
              ->setParameter('unread', 'unread');
          } else {
            $qb->andWhere('a.status = :status')->setParameter('status', $params['status']);
          }
        }

        // Ne pas filtrer par l'état de l'espace pour permettre de voir les candidatures
        // même si l'espace est temporairement dépublié
        // Les candidatures en brouillon doivent être visibles même si l'espace est suspendu

        if (!empty($params['orderBy'])) {
            $qb->orderBy('s.'.$params['orderBy'], $params['sort']);
        } else {
            $qb->orderBy('s.name', 'ASC');
        }

        return $qb;
    }

    /**
     * @param User $owner
     *
     * @return array
     */
    public function getApplicationPerApplicant(User $applicant)
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.projectHolder = :user_id')
            ->setParameters([
                'user_id' => $applicant->getId()
            ]);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array $params
     *
     * @return QueryBuilder
     */
    public function filter($params)
    {
        $qb = $this->createQueryBuilder('a')
            ->select('a');

        $qb->andWhere('a.status != :status_draft');
        $qb->setParameter('status_draft', 'draft');

        if (!empty($params['space'])) {
            $qb->andWhere('a.space = :space')->setParameter('space', $params['space']);
        }

        if (!empty($params['locationId'])) {
            $locationFilterDql = 'SELECT 1 FROM App\Entity\ApplicationLocationPreference lp_filter
                WHERE lp_filter.application = a AND lp_filter.location = :locationFilterId';
            if (!empty($params['locationRank'])) {
                $locationFilterDql .= ' AND lp_filter.rank = :locationFilterRank';
                $qb->setParameter('locationFilterRank', (int) $params['locationRank']);
            }
            $qb->andWhere($qb->expr()->exists($locationFilterDql));
            $qb->setParameter('locationFilterId', (int) $params['locationId']);
        }

        if (!empty($params['orderBy'])) {
            if ($params['orderBy'] === 'locationFirstChoice') {
                $qb->leftJoin('a.locationPreferences', 'lp_sort_first', 'WITH', 'lp_sort_first.rank = 1')
                    ->leftJoin('lp_sort_first.location', 'loc_sort_first')
                    ->orderBy('loc_sort_first.name', $params['sort'] ?? 'ASC')
                    ->addOrderBy('a.created', 'DESC');
            } else {
                $qb->orderBy('a.'.$params['orderBy'], $params['sort']);
                if ($params['orderBy'] === 'lengthOccupation') {
                    $qb->addOrderBy('a.lengthTypeOccupation', $params['sort']);
                }
            }
        }

        if (!empty($params['status']) && $params['status'] != 'selected') {
            $qb->andWhere('a.status = :status');
            $qb->setParameter('status', $params['status']);
        }

        if (!empty($params['status']) && $params['status'] == 'selected') {
            $qb->andWhere('a.selected = :selected');
            $qb->setParameter('selected', 1);
        }

        return $qb;
    }



    /**
     * @param Application $application
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getNextApplication(Application $application) {
        $qb = $this->createQueryBuilder('a')
            ->select('a')
            ->where('a.id > :id')
            ->andWhere('a.space = :space')
            ->andWhere('a.status != :status')
            ->setParameter('status', 'draft')
            ->setParameter('space', $application->getSpace())
            ->setParameter('id', $application->getId())
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param Application $application
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getPrevApplication(Application $application) {
        $qb = $this->createQueryBuilder('a')
            ->select('a')
            ->where('a.id < :id')
            ->andWhere('a.space = :space')
            ->andWhere('a.status != :status')
            ->setParameter('status', 'draft')
            ->setParameter('space', $application->getSpace())
            ->setParameter('id', $application->getId())
            ->orderBy('a.id', 'DESC')
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param Application $application
     * @param User $applicant
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getApplicantNextApplication(Application $application, User $applicant) {
        $qb = $this->createQueryBuilder('a')
            ->select('a')
            ->where('a.id > :id')
            ->setParameter('id', $application->getId())
            ->andWhere('a.projectHolder = :user_id')
            ->setParameter('user_id', $applicant->getId())
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param Application $application
     * @param User $applicant
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getApplicantPrevApplication(Application $application, User $applicant) {
        $qb = $this->createQueryBuilder('a')
            ->select('a')
            ->where('a.id < :id')
            ->setParameter('id', $application->getId())
            ->andWhere('a.projectHolder = :user_id')
            ->setParameter('user_id', $applicant->getId())
            ->orderBy('a.id', 'DESC')
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

}
