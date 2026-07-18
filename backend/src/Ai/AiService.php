<?php

namespace App\Ai;

use App\Entity\Entreprise;
use App\Entity\User;
use App\Service\EncryptionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AiService
{
    /** @param iterable<AiProviderInterface> $providers */
    public function __construct(
        private iterable $providers,
        private AiContextBuilder $contextBuilder,
        private EncryptionService $encryptionService,
        private EntityManagerInterface $entityManager,
        // Nullable : le processeur d'env `default::` renvoie null (jamais '') quand
        // SYNKRO_MISTRAL_API_KEY est absente ou vide. Un type `string` non-nullable
        // ferait planter la construction du service (TypeError) — ce qui bloquait
        // même le mode BYOK, qui n'utilise pourtant pas cette clé plateforme.
        #[Autowire('%env(default::SYNKRO_MISTRAL_API_KEY)%')]
        private ?string $platformApiKey = null
    ) {}

    public function chat(Entreprise $entreprise, User $user, string $message, string $module): string
    {
        if (!$entreprise->isAiEnabled()) {
            throw new BadRequestHttpException("L'IA n'est pas activée pour cette entreprise.");
        }

        $mode = $entreprise->getAiMode();
        $providerName = $entreprise->getAiProvider() ?? 'mistral';
        $provider = $this->resolveProvider($providerName);
        $systemPrompt = $this->contextBuilder->buildSystemPrompt($entreprise, $user, $module);

        if ($mode === 'platform') {
            $apiKey = $this->resolvePlatformKey($entreprise);
            $aiResponse = $provider->chat($systemPrompt, $message, $apiKey);
            $this->incrementTokens($entreprise, $aiResponse->tokensUsed);
            return $aiResponse->response;
        }

        // Mode BYOK
        $encryptedKey = $entreprise->getAiApiKey();
        if (!$encryptedKey) {
            throw new BadRequestHttpException("Aucune clé API IA configurée. Configurez-la dans les paramètres de l'entreprise.");
        }

        $apiKey = $this->encryptionService->decrypt($encryptedKey);
        $aiResponse = $provider->chat($systemPrompt, $message, $apiKey);
        return $aiResponse->response;
    }

    private function resolvePlatformKey(Entreprise $entreprise): string
    {
        if (!$this->platformApiKey) {
            throw new BadRequestHttpException("Le mode plateforme n'est pas configuré sur ce serveur.");
        }

        if ($entreprise->getAiTokensLimit() === null) {
            throw new BadRequestHttpException("Le plan IA plateforme n'est pas activé pour cette entreprise. Contactez l'équipe Synkro.");
        }

        if ($entreprise->hasReachedTokenLimit()) {
            $limit = number_format($entreprise->getAiTokensLimit(), 0, ',', ' ');
            throw new BadRequestHttpException("Quota mensuel atteint ({$limit} tokens). Il sera réinitialisé le 1er du mois prochain.");
        }

        return $this->platformApiKey;
    }

    private function incrementTokens(Entreprise $entreprise, int $tokensUsed): void
    {
        $entreprise->setAiTokensUsed($entreprise->getAiTokensUsed() + $tokensUsed);
        if ($entreprise->getAiTokensResetAt() === null) {
            $entreprise->setAiTokensResetAt(new \DateTimeImmutable());
        }
        $this->entityManager->flush();
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
