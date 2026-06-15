<?php

namespace App\Repository;

use App\Entity\Actuality;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ActualityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Actuality::class);
    }

    public function findPublished(): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.published = true')
            ->orderBy('a.date', 'DESC')
            ->getQuery()
            ->execute();
    }
}
