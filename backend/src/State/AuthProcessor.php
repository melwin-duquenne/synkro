<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Auth\RegisterInput;
use App\Dto\Auth\UserOutput;
use App\Entity\Entreprise;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof RegisterInput) {
            return $this->register($data);
        }

        return null;
    }

    private function register(RegisterInput $data): object
    {
        // Check if user already exists
        $existingUser = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $data->email]);

        if ($existingUser) {
            throw new ConflictHttpException('Email already registered');
        }

        // Extract domain from email
        $emailParts = explode('@', $data->email);
        $domain = $emailParts[1] ?? null;

        if (!$domain) {
            throw new BadRequestHttpException('Invalid email format');
        }

        // Find or create entreprise by domain
        $entreprise = $this->entityManager->getRepository(Entreprise::class)
            ->findOneBy(['domain' => $domain]);

        if (!$entreprise) {
            $entreprise = new Entreprise();
            $entreprise->setDomain($domain);
            $companyName = $data->companyName ?? ucfirst(explode('.', $domain)[0]);
            $entreprise->setName($companyName);
            $this->entityManager->persist($entreprise);
        }

        $user = new User();
        $user->setEmail($data->email);
        $user->setDisplayName($data->displayName);
        $user->setRole($data->role);
        $user->setEntreprise($entreprise);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $data->password);
        $user->setPassword($hashedPassword);

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            throw new BadRequestHttpException(json_encode(['errors' => $errorMessages]));
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return new class($user) {
            public string $message = 'User registered successfully';
            public UserOutput $user;

            public function __construct(User $user)
            {
                $this->user = UserOutput::fromEntity($user);
            }
        };
    }
}
