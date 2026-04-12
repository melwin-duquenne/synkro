<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public const USERS = [
        [
            'email' => 'admin@demo.synkro.ovh',
            'password' => 'Demo1234!',
            'displayName' => 'Admin Demo',
            'firstName' => 'Admin',
            'lastName' => 'Demo',
            'role' => User::ROLE_ADMIN,
        ],
        [
            'email' => 'user@demo.synkro.ovh',
            'password' => 'Demo1234!',
            'displayName' => 'User Demo',
            'firstName' => 'User',
            'lastName' => 'Demo',
            'role' => User::ROLE_USER,
        ],
        [
            'email' => 'guest@demo.synkro.ovh',
            'password' => 'Demo1234!',
            'displayName' => 'Guest Demo',
            'firstName' => 'Guest',
            'lastName' => 'Demo',
            'role' => User::ROLE_USER,
        ],
    ];

    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        foreach (self::USERS as $userData) {
            $user = new User();
            $user->setEmail($userData['email']);
            $user->setDisplayName($userData['displayName']);
            $user->setFirstName($userData['firstName']);
            $user->setLastName($userData['lastName']);
            $user->setRole($userData['role']);
            $user->setPassword($this->passwordHasher->hashPassword($user, $userData['password']));

            $manager->persist($user);
        }

        $manager->flush();
    }
}
