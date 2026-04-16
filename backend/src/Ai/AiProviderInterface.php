<?php

namespace App\Ai;

interface AiProviderInterface
{
    /**
     * Envoie un message à l'IA et retourne la réponse avec le nombre de tokens consommés.
     * Chaque appel est indépendant (pas de streaming, pas d'historique).
     */
    public function chat(string $systemPrompt, string $userMessage, string $apiKey): AiResponse;

    /** Identifiant du provider : 'mistral', 'openai', 'anthropic', etc. */
    public function getProviderName(): string;
}
