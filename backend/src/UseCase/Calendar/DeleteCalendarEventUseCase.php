<?php

namespace App\UseCase\Calendar;

use App\Entity\CalendarEvent;
use App\Entity\User;
use App\Exception\ErrorMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DeleteCalendarEventUseCase
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function execute(int $id, User $user): null
    {
        $event = $this->entityManager->getRepository(CalendarEvent::class)->find($id);

        if (!$event) {
            throw new NotFoundHttpException(ErrorMessage::EVENT_NOT_FOUND);
        }

        if ($event->getUser()->getId() !== $user->getId()) {
            throw new AccessDeniedHttpException(ErrorMessage::EVENT_DELETE_DENIED);
        }

        $this->entityManager->remove($event);
        $this->entityManager->flush();

        return null;
    }
}
