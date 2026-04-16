<?php

namespace App\Ai\Provider;

use App\Ai\AiProviderInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MistralProvider implements AiProviderInterface
{
    private const API_URL = 'https://api.mistral.ai/v1/chat/completions';
    private const MODEL = 'mistral-large-latest';

    public function __construct(private HttpClientInterface $httpClient) {}

    public function chat(string $systemPrompt, string $userMessage, string $apiKey): string
    {
        $response = $this->httpClient->request('POST', self::API_URL, [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => self::MODEL,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userMessage],
                ],
                'max_tokens' => 2000,
                'temperature' => 0.7,
            ],
        ]);

        $data = $response->toArray();

        if (!isset($data['choices'][0]['message']['content'])) {
            throw new \RuntimeException('Réponse Mistral invalide ou vide');
        }

        return $data['choices'][0]['message']['content'];
    }

    public function getProviderName(): string
    {
        return 'mistral';
    }
}
