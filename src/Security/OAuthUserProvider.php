<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * OAuthUserProvider — Fournisseur d'utilisateurs pour l'authentification OAuth.
 *
 * Cette classe est un stub prêt à l'emploi en vue de l'intégration d'un bundle OAuth
 * (ex. knpuniversity/oauth2-client-bundle).
 *
 * Pour l'activer :
 *  1. Installer le bundle OAuth de votre choix.
 *  2. Créer un Authenticator OAuth qui appelle loadUserByOAuthEmail().
 *  3. Déclarer ce service dans config/packages/security.yaml > providers si nécessaire.
 *
 * @see https://github.com/knpuniversity/oauth2-client-bundle
 */
class OAuthUserProvider implements UserProviderInterface
{
    public function __construct(
        private readonly UserRepository         $userRepository,
        private readonly EntityManagerInterface $em,
    ) {}

    /**
     * Charge un utilisateur à partir d'un email reçu du provider OAuth.
     * À appeler depuis votre Authenticator OAuth.
     *
     * @throws UserNotFoundException
     */
    public function loadUserByOAuthEmail(string $email, string $service, string $socialId): User
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if ($user === null) {
            throw new UserNotFoundException(sprintf('Aucun utilisateur trouvé avec l\'email "%s".', $email));
        }

        // Mémoriser l'identifiant OAuth sur l'entité
        match ($service) {
            'google'   => method_exists($user, 'setGoogleId')   ? $user->setGoogleId($socialId)   : null,
            'facebook' => method_exists($user, 'setFacebookId') ? $user->setFacebookId($socialId) : null,
            'linkedin' => method_exists($user, 'setLinkedinId') ? $user->setLinkedinId($socialId) : null,
            default    => null,
        };

        $this->em->flush();

        return $user;
    }

    // -------------------------------------------------------------------------
    // Implémentation de UserProviderInterface
    // -------------------------------------------------------------------------

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->userRepository->findOneBy(['email' => $identifier]);
        if ($user === null) {
            throw new UserNotFoundException(sprintf('Utilisateur "%s" introuvable.', $identifier));
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances de "%s" non supportées.', $user::class));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return $class === User::class || is_subclass_of($class, User::class);
    }
}
