<?php

namespace App\State;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use App\UseCase\Calendar\CreateCalendarEventUseCase;
use App\UseCase\Calendar\DeleteCalendarEventUseCase;
use App\Exception\ErrorMessage;
use App\UseCase\Calendar\UpdateCalendarEventUseCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CalendarEventProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private CreateCalendarEventUseCase $createCalendarEventUseCase,
        private UpdateCalendarEventUseCase $updateCalendarEventUseCase,
        private DeleteCalendarEventUseCase $deleteCalendarEventUseCase
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException(ErrorMessage::AUTH_REQUIRED);
        }

        try {
            if ($operation instanceof Post) {
                return $this->createCalendarEventUseCase->execute($data, $user);
            }

            if ($operation instanceof Patch) {
                return $this->updateCalendarEventUseCase->execute($data, $uriVariables['id'], $user);
            }

            if ($operation instanceof Delete) {
                return $this->deleteCalendarEventUseCase->execute($uriVariables['id'], $user);
            }

            return null;
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
            throw new ConflictHttpException(ErrorMessage::EVENT_DUPLICATE);
        } catch (\Exception $e) {
            if ($e instanceof HttpException) {
                throw $e;
            }
            throw new HttpException(500, ErrorMessage::EVENT_SAVE_ERROR);
        }
    }
}
