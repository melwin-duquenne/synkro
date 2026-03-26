<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Workload;
use App\Entity\User;
use App\Service\WorkloadCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class WorkloadProvider implements ProviderInterface
{
    public function __construct(
        private WorkloadCalculator $workloadCalculator,
        private Security $security,
        private EntityManagerInterface $entityManager
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Vous devez être connecté pour effectuer cette action');
        }

        // Allow checking workload for another user (if userId is provided in query params)
        $targetUser = $user;
        $request = $context['request'] ?? null;
        if ($request && $request->query->has('userId')) {
            $userId = $request->query->get('userId');
            $targetUser = $this->entityManager->getRepository(User::class)->find($userId);
            if (!$targetUser) {
                $targetUser = $user;
            }
        }

        $date = isset($uriVariables['date'])
            ? new \DateTime($uriVariables['date'])
            : new \DateTime();

        $uriTemplate = $operation->getUriTemplate();

        if (str_contains($uriTemplate, '/workload/weekly')) {
            $data = $this->workloadCalculator->calculateWeeklyWorkload($targetUser, $date);
        } else {
            $data = $this->workloadCalculator->calculateDailyWorkload($targetUser, $date);
        }

        $workload = new Workload();
        $workload->status = $data['status'];
        $workload->statusLabel = $data['statusLabel'];
        $workload->color = $data['color'];
        $workload->percentage = $data['percentage'];
        $workload->totalHours = $data['totalHours'];
        $workload->eventCount = $data['eventCount'];
        $workload->meetingCount = $data['meetingCount'];
        $workload->hasShortBreaks = $data['hasShortBreaks'];
        $workload->standardHours = $data['standardHours'];
        $workload->period = $data['period'];
        $workload->events = $data['events'];
        $workload->modifiers = $data['modifiers'];

        return $workload;
    }
}
