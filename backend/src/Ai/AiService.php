<?php

namespace App\Ai;

use App\Entity\Entreprise;
use App\Entity\User;
use App\Service\EncryptionService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AiService
{
    /** @param iterable<AiProviderInterface> $providers */
    public function __construct(
        private iterable $providers,
        private AiContextBuilder $contextBuilder,
        private EncryptionService $encryptionService
    ) {}

    public function chat(Entreprise $entreprise, User $user, string $message, string $module): string
    {
        if (!$entreprise->isAiEnabled()) {
            throw new BadRequestHttpException("L'IA n'est pas activée pour cette entreprise.");
        }

        $encryptedKey = $entreprise->getAiApiKey();
        if (!$encryptedKey) {
            throw new BadRequestHttpException("Aucune clé API IA configurée. Configurez-la dans les paramètres de l'entreprise.");
        }

        $providerName = $entreprise->getAiProvider() ?? 'mistral';
        $provider = $this->resolveProvider($providerName);
        $apiKey = $this->encryptionService->decrypt($encryptedKey);
        $systemPrompt = $this->contextBuilder->buildSystemPrompt($entreprise, $user, $module);

        return $provider->chat($systemPrompt, $message, $apiKey);
    }

    private function resolveProvider(string $name): AiProviderInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->getProviderName() === $name) {
                return $provider;
            }
        }
        throw new BadRequestHttpException("Fournisseur IA inconnu : {$name}. Providers supportés : mistral.");
    }
}
