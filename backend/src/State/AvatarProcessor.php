<?php

namespace App\State;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Account\ProfileOutput;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AvatarProcessor implements ProcessorInterface
{
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    ];

    private const MAX_FILE_SIZE = 2 * 1024 * 1024; // 2 MB

    public function __construct(
        private Security $security,
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private string $projectDir
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Vous devez être connecté pour effectuer cette action');
        }

        if ($operation instanceof Post) {
            return $this->upload($user);
        }

        if ($operation instanceof Delete) {
            return $this->delete($user);
        }

        return null;
    }

    private function upload(User $user): ProfileOutput
    {
        $request = $this->requestStack->getCurrentRequest();
        $file = $request?->files->get('avatar');

        if (!$file) {
            throw new BadRequestHttpException('No file uploaded. Use "avatar" as the field name.');
        }

        if (!$file->isValid()) {
            throw new BadRequestHttpException('Invalid file upload: ' . $file->getErrorMessage());
        }

        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new BadRequestHttpException('Fichier trop volumineux. Taille maximale : 2 Mo');
        }

        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw new BadRequestHttpException('Type de fichier non autorisé. Types acceptés : JPEG, PNG, WebP, GIF');
        }

        // Delete old avatar if exists
        $this->removeAvatarFile($user);

        // Generate unique filename
        $extension = $file->guessExtension() ?: 'jpg';
        $filename = sprintf('avatar_%d_%s.%s', $user->getId(), bin2hex(random_bytes(8)), $extension);

        $uploadDir = $this->projectDir . '/public/uploads/avatars';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $file->move($uploadDir, $filename);

        $user->setAvatarPath('/uploads/avatars/' . $filename);
        $this->entityManager->flush();

        return ProfileOutput::fromEntity($user);
    }

    private function delete(User $user): ProfileOutput
    {
        $this->removeAvatarFile($user);

        $user->setAvatarPath(null);
        $this->entityManager->flush();

        return ProfileOutput::fromEntity($user);
    }

    private function removeAvatarFile(User $user): void
    {
        $currentPath = $user->getAvatarPath();
        if ($currentPath) {
            $fullPath = $this->projectDir . '/public' . $currentPath;
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }
    }
}
