<?php

namespace App\State;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use App\UseCase\Room\CreateRoomUseCase;
use App\UseCase\Room\DeleteRoomUseCase;
use App\UseCase\Room\ReorderRoomModulesUseCase;
use App\Exception\ErrorMessage;
use App\UseCase\Room\UpdateRoomUseCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class RoomProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private CreateRoomUseCase $createRoomUseCase,
        private UpdateRoomUseCase $updateRoomUseCase,
        private DeleteRoomUseCase $deleteRoomUseCase,
        private ReorderRoomModulesUseCase $reorderRoomModulesUseCase
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException(ErrorMessage::AUTH_REQUIRED);
        }

        $request = $context['request'] ?? null;
        if ($request && str_contains($request->getPathInfo(), '/reorder-modules')) {
            return $this->reorderRoomModulesUseCase->execute($uriVariables['id'], $user, $request);
        }

        if ($operation instanceof Post) {
            return $this->createRoomUseCase->execute($data, $user);
        }

        if ($operation instanceof Patch) {
            return $this->updateRoomUseCase->execute($data, $uriVariables['id'], $user);
        }

        if ($operation instanceof Delete) {
            return $this->deleteRoomUseCase->execute($uriVariables['id'], $user);
        }

        return null;
    }
}
