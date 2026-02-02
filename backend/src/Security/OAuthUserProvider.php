<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthAwareUserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class OAuthUserProvider implements OAuthAwareUserProviderInterface
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function loadUserByOAuthUserResponse(UserResponseInterface $response): UserInterface
    {
        $googleId = $response->getUsername(); // Google ID
        $email = $response->getEmail();

        // Chercher l'utilisateur existant ou en créer un nouveau
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['googleId' => $googleId]);

        if (!$user) {
            $user = new User();
            $user->setGoogleId($googleId);
            $user->setEmail($email);
            $user->setFirstName($response->getFirstName());
            $user->setLastName($response->getLastName());
            $user->setDisplayName($response->getFirstName() . ' ' . $response->getLastName());

            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        return $user;
    }

    public function loadUserByUsername($username)
    {
        return $this->entityManager->getRepository(User::class)->findOneBy(['email' => $username]);
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        return $this->loadUserByUsername($identifier);
    }

    public function refreshUser(UserInterface $user)
    {
        return $this->loadUserByUsername($user->getUserIdentifier());
    }

    public function supportsClass($class)
    {
        return $class === User::class;
    }
}
