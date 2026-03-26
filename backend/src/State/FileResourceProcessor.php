<?php

namespace App\State;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\File\CreateFolderInput;
use App\Dto\File\FileOutput;
use App\Dto\File\UpdateFileInput;
use App\Entity\FileResource;
use App\Entity\Room;
use App\Entity\User;
use App\Security\RoomAccessChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FileResourceProcessor implements ProcessorInterface
{
    private const ALLOWED_MIME_TYPES = [
        // Images
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
        // PDF
        'application/pdf',
        // Office
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/msword',
        'application/vnd.ms-excel',
        'application/vnd.ms-powerpoint',
        // Archives
        'application/zip',
        'application/x-zip-compressed',
        'application/vnd.rar',
        'application/x-rar-compressed',
        'application/x-7z-compressed',
        // Text
        'text/plain',
        'text/csv',
        'text/markdown',
    ];

    private const ALLOWED_EXTENSIONS = [
        'pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg',
        'docx', 'xlsx', 'pptx', 'doc', 'xls', 'ppt',
        'zip', 'rar', '7z',
        'txt', 'csv', 'md',
    ];

    private const MAX_FILE_SIZE = 100 * 1024 * 1024; // 100 MB

    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private RoomAccessChecker $accessChecker,
        private RequestStack $requestStack,
        private string $projectDir
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Vous devez être connecté pour effectuer cette action');
        }

        $roomId = $uriVariables['roomId'] ?? null;

        if ($operation instanceof Post) {
            if ($data instanceof CreateFolderInput) {
                return $this->createFolder($data, $user, $roomId);
            }
            return $this->upload($user, $roomId);
        }

        if ($operation instanceof Patch) {
            return $this->update($data, $user, $roomId, $uriVariables['id']);
        }

        if ($operation instanceof Delete) {
            return $this->delete($user, $roomId, $uriVariables['id']);
        }

        return null;
    }

    private function upload(User $user, int $roomId): FileOutput
    {
        $room = $this->getRoom($roomId, $user);

        $request = $this->requestStack->getCurrentRequest();
        $file = $request?->files->get('file');

        if (!$file) {
            throw new BadRequestHttpException('No file uploaded. Use "file" as the field name.');
        }

        if (!$file->isValid()) {
            throw new BadRequestHttpException('Invalid file upload: ' . $file->getErrorMessage());
        }

        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new BadRequestHttpException('Fichier trop volumineux. Taille maximale : 100 Mo');
        }

        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw new BadRequestHttpException('Type de fichier non autorisé : ' . $mimeType);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new BadRequestHttpException('Extension de fichier non autorisée : ' . $extension);
        }

        // Check for parent folder
        $parentId = $request->request->get('parentId');
        $parent = null;
        if ($parentId) {
            $parent = $this->entityManager->getRepository(FileResource::class)->find($parentId);
            if (!$parent || !$parent->isFolder() || $parent->getRoom()->getId() !== $roomId) {
                throw new BadRequestHttpException('Dossier parent invalide');
            }
        }

        // Capture metadata before move (temp file is deleted after move)
        $originalName = $file->getClientOriginalName();
        $fileSize = $file->getSize();

        // Check for duplicate name in same folder
        $this->checkDuplicateName($room, $originalName, $parent);

        // Generate unique filename and store
        $storedName = sprintf('file_%d_%s.%s', $roomId, bin2hex(random_bytes(8)), $extension);
        $uploadDir = $this->projectDir . '/public/uploads/rooms/' . $roomId . '/files';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $file->move($uploadDir, $storedName);

        $fileResource = new FileResource();
        $fileResource->setRoom($room);
        $fileResource->setUser($user);
        $fileResource->setFileName($originalName);
        $fileResource->setFilePath('/uploads/rooms/' . $roomId . '/files/' . $storedName);
        $fileResource->setMimeType($mimeType);
        $fileResource->setSize($fileSize);
        $fileResource->setIsFolder(false);
        $fileResource->setParent($parent);

        $this->entityManager->persist($fileResource);
        $this->entityManager->flush();

        return FileOutput::fromEntity($fileResource);
    }

    private function createFolder(CreateFolderInput $data, User $user, int $roomId): FileOutput
    {
        $room = $this->getRoom($roomId, $user);

        $parent = null;
        if ($data->parentId) {
            $parent = $this->entityManager->getRepository(FileResource::class)->find($data->parentId);
            if (!$parent || !$parent->isFolder() || $parent->getRoom()->getId() !== $roomId) {
                throw new BadRequestHttpException('Dossier parent invalide');
            }
        }

        // Check for duplicate name in same folder
        $this->checkDuplicateName($room, $data->name, $parent);

        $folder = new FileResource();
        $folder->setRoom($room);
        $folder->setUser($user);
        $folder->setFileName($data->name);
        $folder->setFilePath(null);
        $folder->setMimeType(null);
        $folder->setSize(0);
        $folder->setIsFolder(true);
        $folder->setParent($parent);

        $this->entityManager->persist($folder);
        $this->entityManager->flush();

        return FileOutput::fromEntity($folder);
    }

    private function update(UpdateFileInput $data, User $user, int $roomId, int $fileId): FileOutput
    {
        $room = $this->getRoom($roomId, $user);

        $file = $this->entityManager->getRepository(FileResource::class)->find($fileId);
        if (!$file || $file->getRoom()->getId() !== $roomId) {
            throw new NotFoundHttpException('Fichier introuvable');
        }

        $newName = $data->fileName ?? $file->getFileName();
        $newParent = $file->getParent();

        if ($data->moveToRoot) {
            $newParent = null;
        } elseif ($data->parentId !== null) {
            $newParent = $this->entityManager->getRepository(FileResource::class)->find($data->parentId);
            if (!$newParent || !$newParent->isFolder() || $newParent->getRoom()->getId() !== $roomId) {
                throw new BadRequestHttpException('Invalid parent folder');
            }
            if ($file->isFolder() && $this->isDescendant($newParent, $file)) {
                throw new BadRequestHttpException('Impossible de déplacer un dossier dans lui-même ou dans l\'un de ses sous-dossiers');
            }
        }

        // Check for duplicate at target location (exclude self)
        if ($data->fileName !== null || $data->parentId !== null || $data->moveToRoot) {
            $this->checkDuplicateName($room, $newName, $newParent, $file->getId());
        }

        $file->setFileName($newName);
        $file->setParent($newParent);

        $this->entityManager->flush();

        return FileOutput::fromEntity($file);
    }

    private function delete(User $user, int $roomId, int $fileId): null
    {
        $room = $this->getRoom($roomId, $user);

        $file = $this->entityManager->getRepository(FileResource::class)->find($fileId);
        if (!$file || $file->getRoom()->getId() !== $roomId) {
            throw new NotFoundHttpException('Fichier introuvable');
        }

        // Delete physical files recursively before removing entities
        $this->deletePhysicalFiles($file);

        $this->entityManager->remove($file);
        $this->entityManager->flush();

        return null;
    }

    private function deletePhysicalFiles(FileResource $file): void
    {
        if ($file->isFolder()) {
            foreach ($file->getChildren() as $child) {
                $this->deletePhysicalFiles($child);
            }
        } else {
            $filePath = $file->getFilePath();
            if ($filePath) {
                $fullPath = $this->projectDir . '/public' . $filePath;
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
            }
        }
    }

    private function isDescendant(FileResource $potentialDescendant, FileResource $ancestor): bool
    {
        $current = $potentialDescendant;
        while ($current !== null) {
            if ($current->getId() === $ancestor->getId()) {
                return true;
            }
            $current = $current->getParent();
        }
        return false;
    }

    private function checkDuplicateName(Room $room, string $fileName, ?FileResource $parent, ?int $excludeId = null): void
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('COUNT(f.id)')
            ->from(FileResource::class, 'f')
            ->where('f.room = :room')
            ->andWhere('f.fileName = :fileName')
            ->setParameter('room', $room)
            ->setParameter('fileName', $fileName);

        if ($parent) {
            $qb->andWhere('f.parent = :parent')
               ->setParameter('parent', $parent);
        } else {
            $qb->andWhere('f.parent IS NULL');
        }

        if ($excludeId) {
            $qb->andWhere('f.id != :excludeId')
               ->setParameter('excludeId', $excludeId);
        }

        $count = (int) $qb->getQuery()->getSingleScalarResult();

        if ($count > 0) {
            throw new BadRequestHttpException('Un fichier ou dossier avec le nom "' . $fileName . '" existe deja a cet emplacement.');
        }
    }

    private function getRoom(int $roomId, User $user): Room
    {
        $room = $this->entityManager->getRepository(Room::class)->find($roomId);
        if (!$room) {
            throw new NotFoundHttpException('Salon introuvable');
        }

        if (!$this->accessChecker->canAccess($user, $room)) {
            throw new AccessDeniedHttpException('Accès refusé');
        }

        return $room;
    }
}
