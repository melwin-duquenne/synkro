<?php

namespace App\Ai;

final class AiResponse
{
    public function __construct(
        public readonly string $response,
        public readonly int $tokensUsed
    ) {}
}
