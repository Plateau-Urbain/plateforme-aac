<?php

namespace App\Repository;

use App\Entity\Application;
use App\Entity\Space;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function getByTypeQueryBuilder(int $type): QueryBuilder
    {
        return $this->createQueryBuilder('u')
            ->where('u.typeUser = :typeUser')
            ->orderBy('u.lastname', 'ASC')
            ->addOrderBy('u.firstname', 'ASC')
            ->setParameter('typeUser', $type);
    }

    public function getByType(int $type): array
    {
        return $this->getByTypeQueryBuilder($type)->getQuery()->getResult();
    }

    /**
     * Propriétaires : compat migration (type_user souvent NULL, rôles / espaces en base).
     */
    public function createProprietairesQueryBuilder(string $alias = 'u'): QueryBuilder
    {
        $em = $this->getEntityManager();

        $ownerIdsDql = $em->createQueryBuilder()
            ->select('IDENTITY(sp.owner)')
            ->from(Space::class, 'sp')
            ->where('sp.owner IS NOT NULL')
            ->getDQL();

        return $this->createQueryBuilder($alias)
            ->where(
                $alias.'.typeUser = :typeProprio
                OR '.$alias.'.id IN ('.$ownerIdsDql.')
                OR '.$alias.'.roles LIKE :roleOwnerPattern'
            )
            ->orderBy($alias.'.lastname', 'ASC')
            ->addOrderBy($alias.'.firstname', 'ASC')
            ->setParameter('typeProprio', User::PROPRIO)
            ->setParameter('roleOwnerPattern', '%"ROLE_OWNER"%');
    }

    /**
     * Porteurs de projet : compat migration (type_user NULL + candidatures liées).
     */
    public function createPorteursQueryBuilder(string $alias = 'u'): QueryBuilder
    {
        $em = $this->getEntityManager();

        $ownerIdsDql = $em->createQueryBuilder()
            ->select('IDENTITY(sp.owner)')
            ->from(Space::class, 'sp')
            ->where('sp.owner IS NOT NULL')
            ->getDQL();

        $holderIdsDql = $em->createQueryBuilder()
            ->select('IDENTITY(a.projectHolder)')
            ->from(Application::class, 'a')
            ->where('a.projectHolder IS NOT NULL')
            ->getDQL();

        return $this->createQueryBuilder($alias)
            ->where(
                $alias.'.typeUser = :typePorteur
                OR '.$alias.'.id IN ('.$holderIdsDql.')
                OR (
                    '.$alias.'.typeUser IS NULL
                    AND '.$alias.'.id NOT IN ('.$ownerIdsDql.')
                    AND '.$alias.'.roles NOT LIKE :roleOwnerPattern
                    AND '.$alias.'.roles NOT LIKE :roleAdminPattern
                    AND '.$alias.'.roles NOT LIKE :roleSuperAdminPattern
                )'
            )
            ->orderBy($alias.'.lastname', 'ASC')
            ->addOrderBy($alias.'.firstname', 'ASC')
            ->setParameter('typePorteur', User::PORTEUR)
            ->setParameter('roleOwnerPattern', '%"ROLE_OWNER"%')
            ->setParameter('roleAdminPattern', '%"ROLE_ADMIN"%')
            ->setParameter('roleSuperAdminPattern', '%"ROLE_SUPER_ADMIN"%');
    }

    public function findByEmailOrUsername(string $identifier): ?User
    {
        $canonical = strtolower(trim($identifier));
        if ($canonical === '') {
            return null;
        }

        return $this->createQueryBuilder('u')
            ->where('u.email = :id OR u.emailCanonical = :id OR u.username = :id OR u.usernameCanonical = :id')
            ->setParameter('id', $canonical)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findUserByValidResetToken(string $token, int $ttlSeconds): ?User
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }

        $user = $this->findOneBy(['confirmationToken' => $token]);
        if (!$user instanceof User) {
            return null;
        }

        $requestedAt = $user->getPasswordRequestedAt();
        if (!$requestedAt instanceof \DateTimeInterface) {
            return null;
        }

        if (time() > $requestedAt->getTimestamp() + $ttlSeconds) {
            return null;
        }

        return $user;
    }
}
