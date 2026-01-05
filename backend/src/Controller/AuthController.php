<?php

namespace App\Controller;

use App\Entity\Entreprise;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator
    ) {}

    #[Route('/register', name: 'api_auth_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $requiredFields = ['email', 'password', 'displayName'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return new JsonResponse(['error' => "Field '$field' is required"], Response::HTTP_BAD_REQUEST);
            }
        }

        // Check if user already exists
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return new JsonResponse(['error' => 'Email already registered'], Response::HTTP_CONFLICT);
        }

        // Extract domain from email
        $emailParts = explode('@', $data['email']);
        $domain = $emailParts[1] ?? null;

        if (!$domain) {
            return new JsonResponse(['error' => 'Invalid email format'], Response::HTTP_BAD_REQUEST);
        }

        // Find or create entreprise by domain
        $entreprise = $this->entityManager->getRepository(Entreprise::class)
            ->findOneBy(['domain' => $domain]);

        if (!$entreprise) {
            // Create a new entreprise based on domain
            $entreprise = new Entreprise();
            $entreprise->setDomain($domain);
            // Use domain name as company name (e.g., "example.com" -> "Example")
            $companyName = $data['companyName'] ?? ucfirst(explode('.', $domain)[0]);
            $entreprise->setName($companyName);
            $this->entityManager->persist($entreprise);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setDisplayName($data['displayName']);
        $user->setRole($data['role'] ?? 'user');
        $user->setEntreprise($entreprise);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'User registered successfully',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'displayName' => $user->getDisplayName(),
                'role' => $user->getRole(),
                'entreprise' => [
                    'id' => $entreprise->getId(),
                    'name' => $entreprise->getName()
                ]
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        // This method is handled by the security firewall (json_login)
        // It will never be called directly
        return new JsonResponse(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
    }

    #[Route('/me', name: 'api_auth_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'displayName' => $user->getDisplayName(),
            'role' => $user->getRole(),
            'entreprise' => $user->getEntreprise() ? [
                'id' => $user->getEntreprise()->getId(),
                'name' => $user->getEntreprise()->getName()
            ] : null,
            'team' => $user->getTeam() ? [
                'id' => $user->getTeam()->getId(),
                'name' => $user->getTeam()->getName()
            ] : null
        ]);
    }

    #[Route('/setup-entreprise', name: 'api_auth_setup_entreprise', methods: ['POST'])]
    public function setupEntreprise(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        // If user already has an entreprise, skip
        if ($user->getEntreprise()) {
            return new JsonResponse([
                'message' => 'Entreprise already configured',
                'entreprise' => [
                    'id' => $user->getEntreprise()->getId(),
                    'name' => $user->getEntreprise()->getName()
                ]
            ]);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        // Extract domain from email
        $emailParts = explode('@', $user->getEmail());
        $domain = $emailParts[1];

        // Find or create entreprise by domain
        $entreprise = $this->entityManager->getRepository(Entreprise::class)
            ->findOneBy(['domain' => $domain]);

        if (!$entreprise) {
            $entreprise = new Entreprise();
            $entreprise->setDomain($domain);
            $companyName = $data['companyName'] ?? ucfirst(explode('.', $domain)[0]);
            $entreprise->setName($companyName);
            $this->entityManager->persist($entreprise);
        }

        $user->setEntreprise($entreprise);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Entreprise configured successfully',
            'entreprise' => [
                'id' => $entreprise->getId(),
                'name' => $entreprise->getName()
            ]
        ]);
    }
}
