<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if (!$user->isEnabled()) {
            throw new CustomUserMessageAccountStatusException(
                'Votre compte n\'est pas encore activé. Vérifiez votre boîte mail pour le lien d\'activation.'
            );
        }

        if ($user->isLocked()) {
            throw new CustomUserMessageAccountStatusException(
                'Votre compte est verrouillé. Contactez Plateau Urbain.'
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
    }
}
