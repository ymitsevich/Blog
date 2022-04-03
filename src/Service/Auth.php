<?php

namespace App\Service;

use App\Entity\BlameableInterface;
use App\Entity\User;
use App\Exception\UnauthorizedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;

class Auth
{
    public function __construct(private Security $security)
    {
    }

    public function getCurrentUser(): User
    {
        /** @var User $user */
        $user = $this->security->getUser();

        if (!$user) {
            throw new AuthenticationException();
        }

        return $user;
    }

    public function validateAuthor(BlameableInterface $entity): void
    {
        if ($entity->getCreatedBy() !== $this->getCurrentUser()) {
            throw new UnauthorizedException();
        }
    }
}
