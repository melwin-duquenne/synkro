<?php

namespace App\State;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Task\CreateTaskInput;
use App\Dto\Task\ReorderTasksInput;
use App\Entity\User;
use App\UseCase\Task\CreateTaskUseCase;
use App\UseCase\Task\DeleteTaskUseCase;
use App\UseCase\Task\ReorderTasksUseCase;
use App\Exception\ErrorMessage;
use App\UseCase\Task\UpdateTaskUseCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class TaskProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private CreateTaskUseCase $createTaskUseCase,
        private UpdateTaskUseCase $updateTaskUseCase,
        private DeleteTaskUseCase $deleteTaskUseCase,
        private ReorderTasksUseCase $reorderTasksUseCase
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException(ErrorMessage::AUTH_REQUIRED);
        }

        $roomId = $uriVariables['roomId'] ?? null;

        if ($operation instanceof Post) {
            if ($data instanceof ReorderTasksInput) {
                return $this->reorderTasksUseCase->execute($data, $roomId, $user);
            }
            return $this->createTaskUseCase->execute($data, $roomId, $user);
        }

        if ($operation instanceof Put || $operation instanceof Patch) {
            return $this->updateTaskUseCase->execute($data, $roomId, $uriVariables['id'], $user);
        }

        if ($operation instanceof Delete) {
            return $this->deleteTaskUseCase->execute($roomId, $uriVariables['id'], $user);
        }

        return null;
    }
}
