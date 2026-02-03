<?php

namespace App\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProviderInterface;
use App\Dto\File\FileOutput;
use App\Entity\FileResource;
use App\Entity\Room;
use App\Entity\User;
use App\Security\RoomAccessChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FileResourceProvider implements ProviderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private RoomAccessChecker $accessChecker,
        private RequestStack $requestStack,
        private string $projectDir
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Not authenticated');
        }

        $roomId = $uriVariables['roomId'] ?? null;
        if (!$roomId) {
            throw new BadRequestHttpException('roomId is required');
        }

        $room = $this->entityManager->getRepository(Room::class)->find($roomId);
        if (!$room) {
            throw new NotFoundHttpException('Room not found');
        }

        if (!$this->accessChecker->canAccess($user, $room)) {
            throw new AccessDeniedHttpException('Access denied');
        }

        // Post operations: provider is only used for URI resolution, processor handles the work
        if ($operation instanceof Post) {
            return null;
        }

        // Download operation
        if ($operation->getName() === 'download_file') {
            return $this->download($room, $uriVariables['id']);
        }

        // Collection
        if ($operation instanceof CollectionOperationInterface) {
            return $this->getCollection($room);
        }

        // Item
        $fileId = $uriVariables['id'] ?? null;
        if (!$fileId) {
            return null;
        }

        return $this->getItem($room, $fileId);
    }

    private function getCollection(Room $room): array
    {
        $request = $this->requestStack->getCurrentRequest();
        $parentId = $request?->query->get('parent');
        $search = $request?->query->get('search');
        $type = $request?->query->get('type');
        $sort = $request?->query->get('sort', 'fileName');
        $order = strtoupper($request?->query->get('order', 'ASC'));

        if (!in_array($order, ['ASC', 'DESC'])) {
            $order = 'ASC';
        }

        $qb = $this->entityManager->createQueryBuilder()
            ->select('f')
            ->from(FileResource::class, 'f')
            ->where('f.room = :room')
            ->setParameter('room', $room);

        // Search across all files (no parent filter)
        if ($search) {
            $qb->andWhere('f.fileName LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        } else {
            // Filter by parent
            if ($parentId) {
                $qb->andWhere('f.parent = :parent')
                   ->setParameter('parent', $parentId);
            } else {
                $qb->andWhere('f.parent IS NULL');
            }
        }

        // Filter by type
        if ($type) {
            switch ($type) {
                case 'folder':
                    $qb->andWhere('f.isFolder = true');
                    break;
                case 'image':
                    $qb->andWhere('f.mimeType LIKE :mime')
                       ->setParameter('mime', 'image/%');
                    break;
                case 'pdf':
                    $qb->andWhere('f.mimeType = :mime')
                       ->setParameter('mime', 'application/pdf');
                    break;
                case 'office':
                    $qb->andWhere('(f.mimeType LIKE :docx OR f.mimeType LIKE :xlsx OR f.mimeType LIKE :pptx OR f.mimeType LIKE :msword OR f.mimeType LIKE :msexcel OR f.mimeType LIKE :msppt)')
                       ->setParameter('docx', '%wordprocessingml%')
                       ->setParameter('xlsx', '%spreadsheetml%')
                       ->setParameter('pptx', '%presentationml%')
                       ->setParameter('msword', 'application/msword')
                       ->setParameter('msexcel', 'application/vnd.ms-excel')
                       ->setParameter('msppt', 'application/vnd.ms-powerpoint');
                    break;
                case 'archive':
                    $qb->andWhere('(f.mimeType = :zip OR f.mimeType = :rar OR f.mimeType = :sevenzip)')
                       ->setParameter('zip', 'application/zip')
                       ->setParameter('rar', 'application/vnd.rar')
                       ->setParameter('sevenzip', 'application/x-7z-compressed');
                    break;
                case 'text':
                    $qb->andWhere('(f.mimeType LIKE :text OR f.mimeType = :csv)')
                       ->setParameter('text', 'text/%')
                       ->setParameter('csv', 'text/csv');
                    break;
            }
        }

        // Sort: folders first, then by requested field
        $allowedSorts = ['fileName', 'createdAt', 'size', 'mimeType'];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'fileName';
        }

        $qb->orderBy('f.isFolder', 'DESC')
           ->addOrderBy('f.' . $sort, $order);

        $files = $qb->getQuery()->getResult();

        return array_map(fn(FileResource $f) => FileOutput::fromEntity($f), $files);
    }

    private function getItem(Room $room, int $fileId): FileOutput
    {
        $file = $this->entityManager->getRepository(FileResource::class)->find($fileId);

        if (!$file || $file->getRoom()->getId() !== $room->getId()) {
            throw new NotFoundHttpException('File not found');
        }

        return FileOutput::fromEntity($file);
    }

    private function download(Room $room, int $fileId): BinaryFileResponse
    {
        $file = $this->entityManager->getRepository(FileResource::class)->find($fileId);

        if (!$file || $file->getRoom()->getId() !== $room->getId()) {
            throw new NotFoundHttpException('File not found');
        }

        if ($file->isFolder()) {
            throw new BadRequestHttpException('Cannot download a folder');
        }

        $fullPath = $this->projectDir . '/public' . $file->getFilePath();

        if (!file_exists($fullPath)) {
            throw new NotFoundHttpException('File not found on disk');
        }

        $response = new BinaryFileResponse($fullPath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $file->getFileName()
        );

        return $response;
    }
}
