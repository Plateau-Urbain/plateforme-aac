<?php

namespace App\Repository;

use App\Entity\Space;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

class SpaceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Space::class);
    }

    public function findAllEnabled()
    {
        $qb = $this->createQueryBuilder('_s');
        return $qb
            ->andWhere('_s.enabled = true')
            ->andWhere('_s.closed = false')
            ->getQuery()
            ->execute()
        ;
    }

    public function getEnabled()
    {
        return $this->createQueryBuilder('u')
            ->where('u.enabled = :enabled')
            ->orderBy('u.lastname', 'DESC')
            ->setParameters([
                'enabled' => true,
            ]);
    }


    public function filter($params)
    {
        $qb = $this->createQueryBuilder('s')
            ->select('s')
            ->leftjoin('s.parcels', 'p');

        if (!empty($params['user'])) {
            $qb->andWhere('s.owner = :owner')->setParameter('owner', $params['user']);
        }

        if (!empty($params['enabled'])) {
            $qb->andWhere('s.enabled = :enabled')->setParameter('enabled', $params['enabled']);
            $qb->andWhere('s.submitted = :submitted')->setParameter('submitted', true);
            $qb->andWhere('s.closed = :closed')->setParameter('closed', false);
            $qb->andWhere('s.limitAvailability >= :limitAvailability')->setParameter('limitAvailability', new \DateTime('today'));
        }

        if (!empty($params['submitted'])) {
            $qb->andWhere('s.submitted = :submitted')->setParameter('submitted', $params['submitted']);
        }

        if (isset($params['closed'])) {
            $qb->andWhere('s.closed = :closed OR s.limitAvailability < :limitAvailability')->setParameter('closed', $params['closed'])->setParameter('limitAvailability', new \DateTime('today'));
        }

        if (!empty($params['limitAvailability'])) {
            $qb->andWhere('s.limitAvailability >= :limitAvailability')->setParameter('limitAvailability', $params['limitAvailability']);
        }

        if (!empty($params['zipCode'])) {
            $qb->andWhere('s.zipCode LIKE :zipCode')->setParameter('zipCode', $params['zipCode'] . '%' );
        }

        if (!empty($params['localType'])) {
            $qb->andWhere('s.type = :localType')->setParameter('localType', $params['localType'] );
        }

        if (!empty($params['minimumPrice'])) {
            $qb->andWhere('s.price >= :minimumPrice')->setParameter('minimumPrice', $params['minimumPrice'] );
        }

        if (!empty($params['maximumPrice'])) {
            $qb->andWhere('s.price <= :maximumPrice')->setParameter('maximumPrice', $params['maximumPrice'] );
        }

        if (!empty($params['minimumSurface'])) {
            $qb->andWhere('p.surface >= :minimumSurface')->setParameter('minimumSurface',$params['minimumSurface'] );
        }

        if (!empty($params['maximumSurface'])) {
            $qb->andWhere('p.surface <= :maximumSurface')->setParameter('maximumSurface',$params['maximumSurface'] );
        }

        if (!empty($params['unavailable'])) {
            $qb->andWhere('s.enabled = :enabled')->setParameter('enabled', true);
            $qb->andWhere('s.submitted = :submitted')->setParameter('submitted', true);
            $qb->andWhere('s.closed = :closed OR s.limitAvailability < :limitAvailability')->setParameter('closed', true)->setParameter('limitAvailability', new \DateTime('today'));
        }

        if (!empty($params['orderBy'])) {
            $qb->orderBy('s.'.$params['orderBy'], $params['sort']);
        } else {
            $qb->orderBy('s.name', 'ASC');
        }


        if (isset($params['pagination'])) {
            $qb = $qb->getQuery();
            $qb->setFirstResult(0)
               ->setMaxResults($params['pagination']);

            return new Paginator($qb, $fetchJoinCollection = true);
        }

        return $qb->getQuery()->getResult();
    }
}
