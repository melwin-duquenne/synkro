<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Ai\AiService;
use App\Dto\Ai\AiChatInput;
use App\Dto\Ai\AiChatOutput;
use App\Entity\User;
use App\Service\EntrepriseContext;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AiChatProcessor implements ProcessorInterface
{
    public function __construct(
        private AiService $aiService,
        private EntrepriseContext $entrepriseContext,
        private Security $security
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): AiChatOutput
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Authentification requise.');
        }

        /** @var User $user */
        /** @var AiChatInput $data */
        $entreprise = $this->entrepriseContext->getEntreprise();
        $response = $this->aiService->chat($entreprise, $user, $data->message, $data->module);

        return new AiChatOutput($response);
    }
}
