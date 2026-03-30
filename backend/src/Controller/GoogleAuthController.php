<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class GoogleAuthController extends AbstractController
{
    public function __construct(
        private JWTTokenManagerInterface $jwtManager,
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private LoggerInterface $logger,
        #[Autowire('%env(APP_FRONTEND_URL)%')]
        private string $frontendUrl = 'http://localhost:5173',
    ) {}

    #[Route('/auth/google', name: 'auth_google')]
    public function connect(ClientRegistry $clientRegistry)
    {
        return $clientRegistry
            ->getClient('google')
            ->redirect(['email', 'profile']);
    }

    #[Route('/auth/google/callback', name: 'auth_google_callback')]
    public function connectCheck(Request $request, ClientRegistry $clientRegistry)
    {
        $client = $clientRegistry->getClient('google');

        try {
            // Récupérer l'utilisateur Google
            $googleUser = $client->fetchUser();

            // Récupérer ou créer l'utilisateur en base
            $user = $this->userRepository->findOneBy(['googleId' => $googleUser->getId()]);

            if (!$user) {
                $user = $this->userRepository->findOneBy(['email' => $googleUser->getEmail()]);

                if (!$user) {
                    $user = new User();
                    $user->setEmail($googleUser->getEmail());
                    $user->setRole('user');
                }

                $user->setGoogleId($googleUser->getId());
                $user->setFirstName($googleUser->getFirstName());
                $user->setLastName($googleUser->getLastName());
                $user->setDisplayName($googleUser->getFirstName() . ' ' . $googleUser->getLastName());

                $this->entityManager->persist($user);
                $this->entityManager->flush();
            }

            // Générer le token JWT
            $token = $this->jwtManager->create($user);

            // Rediriger vers le frontend avec le token
            return $this->redirect($this->frontendUrl . '/auth/callback?token=' . $token);

        } catch (\Exception $e) {
            // Log l'erreur pour debug
            $this->logger->error('OAuth error: ' . $e->getMessage());
            return $this->redirect($this->frontendUrl . '/login?error=oauth_failed&message=' . urlencode($e->getMessage()));
        }
    }

    #[Route('/auth/google/success', name: 'auth_google_success')]
    public function success(Request $request)
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirect('/auth/login?error=no_user');
        }

        $token = $this->jwtManager->create($user);

        // Rediriger vers le frontend avec le token
        return $this->redirect($this->frontendUrl . '/auth/callback?token=' . $token);
    }

    #[Route('/auth/google/failure', name: 'auth_google_failure')]
    public function failure()
    {
        return $this->redirect('/login?error=oauth_failed');
    }
}
